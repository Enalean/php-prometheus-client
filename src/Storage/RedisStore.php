<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Storage;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Sample;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use Redis;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function implode;
use function json_decode;
use function json_encode;
use function sort;
use function strcmp;
use function usort;
use const JSON_THROW_ON_ERROR;

final class RedisStore implements Store, CounterStorage, GaugeStorage, HistogramStorage, FlushableStorage
{
    public const PROMETHEUS_METRIC_KEYS_SUFFIX = '_METRIC_KEYS';

    /** @var string */
    private $prefix;

    /** @var Redis */
    private $redis;

    public function __construct(Redis $redisClient, string $keyPrefix = 'PROMETHEUS_')
    {
        $this->redis  = $redisClient;
        $this->prefix = $keyPrefix;
    }

    public function flush() : void
    {
        $storageMainKeys = [
            $this->prefix . 'counter' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
            $this->prefix . 'gauge' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
            $this->prefix . 'histogram' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
        ];
        $membersToRemove = [];
        foreach ($storageMainKeys as $storageMainKey) {
            $this->redis->watch($storageMainKey);
            $membersToRemove[] = $this->redis->sMembers($storageMainKey);
        }

        /** @psalm-var string[] $membersToRemove */
        $membersToRemove = array_merge([], ...$membersToRemove);
        $redis           = $this->redis->multi();
        $redis->del($membersToRemove);
        $redis->del($storageMainKeys);
        $redis->exec();
    }

    /**
     * @inheritdoc
     */
    public function collect() : array
    {
        $metrics = $this->collectHistograms();
        $metrics = array_merge($metrics, $this->collectGauges());
        $metrics = array_merge($metrics, $this->collectCounters());

        $familySamples = [];
        foreach ($metrics as $metric) {
            $samples = [];
            foreach ($metric['samples'] as $sampleData) {
                $samples[] = new Sample($sampleData['name'], (float) $sampleData['value'], $sampleData['labelNames'], $sampleData['labelValues']);
            }

            $familySamples[] = new MetricFamilySamples($metric['name'], $metric['type'], $metric['help'], $metric['labelNames'], $samples);
        }

        return $familySamples;
    }

    /**
     * @inheritdoc
     */
    public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, string ...$labelValues) : void
    {
        $bucketToIncrease = '+Inf';
        foreach ($buckets as $bucket) {
            if ($value <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        $metaData  = [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
            'buckets' => $buckets,
        ];
        $metricKey = $this->toMetricKey($name, 'histogram');
        $redis     = $this->redis->multi();
        $redis->hIncrByFloat($metricKey, json_encode(['b' => 'sum', 'labelValues' => $labelValues], JSON_THROW_ON_ERROR), $value);
        $redis->hIncrBy($metricKey, json_encode(['b' => $bucketToIncrease, 'labelValues' => $labelValues], JSON_THROW_ON_ERROR), 1);
        $redis->hSetNx($metricKey, '__meta', json_encode($metaData, JSON_THROW_ON_ERROR));
        $redis->sAdd($this->prefix . 'histogram' . self::PROMETHEUS_METRIC_KEYS_SUFFIX, $metricKey);
        $redis->exec();
    }

    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
    {
        $this->updateGauge($name, $value, $help, $labelNames, $labelValues, false);
    }

    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
    {
        $this->updateGauge($name, $value, $help, $labelNames, $labelValues, true);
    }

    /**
     * @param string[] $labelValues
     */
    private function updateGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues, bool $isIncrement) : void
    {
        $metaData = [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
        ];

        $metricKey = $this->toMetricKey($name, 'gauge');
        $redis     = $this->redis->multi();
        if ($isIncrement) {
            $redis->hIncrByFloat($metricKey, json_encode($labelValues, JSON_THROW_ON_ERROR), $value);
        } else {
            $redis->hSet($metricKey, json_encode($labelValues, JSON_THROW_ON_ERROR), (string) $value);
        }

        $redis->hSetNx($metricKey, '__meta', json_encode($metaData, JSON_THROW_ON_ERROR));
        $redis->sAdd($this->prefix . 'gauge' . self::PROMETHEUS_METRIC_KEYS_SUFFIX, $metricKey);
        $redis->exec();
    }

    public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
    {
        $metaData = [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
        ];

        $metricKey = $this->toMetricKey($name, 'counter');
        $redis     = $this->redis->multi();
        $redis->hIncrByFloat($metricKey, json_encode($labelValues, JSON_THROW_ON_ERROR), $value);
        $redis->hSetNx($metricKey, '__meta', json_encode($metaData, JSON_THROW_ON_ERROR));
        $redis->sAdd($this->prefix . 'counter' . self::PROMETHEUS_METRIC_KEYS_SUFFIX, $metricKey);
        $redis->exec();
    }

    /**
     * @return array<int,array<string,mixed>>
     *
     * @psalm-return array<array{name:string, help:string, labelNames: string[], type:'histogram', samples: list<array{name:string, labelNames:list<string>, labelValues: string[], value: int}>}>
     */
    private function collectHistograms() : array
    {
        /** @var string[] $keys */
        $keys = $this->redis->sMembers($this->prefix . 'histogram' . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        $histograms = [];
        foreach ($keys as $key) {
            /** @psalm-var array{__meta:string, string:int} $raw */
            $raw = $this->redis->hGetAll($key);
            /** @psalm-var array{name:string, help:string, labelNames: string[], buckets:float[]} $histogram */
            $histogram         = json_decode($raw['__meta'], true);
            $histogram['type'] = 'histogram';
            unset($raw['__meta']);
            $histogram['samples'] = [];

            // Add the Inf bucket so we can compute it later on
            $histogram['buckets'][] = '+Inf';

            $allLabelValues = [];
            foreach (array_keys($raw) as $k) {
                /** @psalm-var array{b:string, labelValues:string[]} $d */
                $d = json_decode($k, true);
                if ($d['b'] === 'sum') {
                    continue;
                }

                $allLabelValues[] = $d['labelValues'];
            }

            // We need set semantics.
            // This is the equivalent of array_unique but for arrays of arrays.
            /** @var array<string[]> $allLabelValues */
            $allLabelValues = array_map('unserialize', array_unique(array_map('serialize', $allLabelValues)));
            sort($allLabelValues);

            foreach ($allLabelValues as $labelValues) {
                // Fill up all buckets.
                // If the bucket doesn't exist fill in values from
                // the previous one.
                $acc = 0;
                foreach ($histogram['buckets'] as $bucket) {
                    $bucketKey = json_encode(['b' => $bucket, 'labelValues' => $labelValues]);
                    if (! isset($raw[$bucketKey])) {
                        $histogram['samples'][] = [
                            'name' => $histogram['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($labelValues, [(string) $bucket]),
                            'value' => $acc,
                        ];
                    } else {
                        $acc                   += $raw[$bucketKey];
                        $histogram['samples'][] = [
                            'name' => $histogram['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($labelValues, [(string) $bucket]),
                            'value' => $acc,
                        ];
                    }
                }

                // Add the count
                $histogram['samples'][] = [
                    'name' => $histogram['name'] . '_count',
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => $acc,
                ];

                // Add the sum
                $histogram['samples'][] = [
                    'name' => $histogram['name'] . '_sum',
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => $raw[json_encode(['b' => 'sum', 'labelValues' => $labelValues])],
                ];
            }

            $histograms[] = $histogram;
        }

        return $histograms;
    }

    /**
     * @return array<int,array<string,mixed>>
     *
     * @phpstan-return array<array{name:string, help:string, labelNames: string[], type:'gauge', samples: list<array{name:string, labelNames:array<mixed>, labelValues: string[], value: float}>}>
     * @psalm-return array<array{name:string, help:string, labelNames: string[], type:'gauge', samples: list<array{name:string, labelNames:array<empty,empty>, labelValues: string[], value: float}>}>
     */
    private function collectGauges() : array
    {
        /** @var string[] $keys */
        $keys = $this->redis->sMembers($this->prefix . 'gauge' . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        $gauges = [];
        foreach ($keys as $key) {
            /** @psalm-var array{__meta:string,string:string} $raw */
            $raw = $this->redis->hGetAll($key);
            /** @psalm-var array{name:string, help:string, labelNames: string[]} $gauge */
            $gauge         = json_decode($raw['__meta'], true);
            $gauge['type'] = 'gauge';
            unset($raw['__meta']);
            $gauge['samples'] = [];
            foreach ($raw as $k => $value) {
                /** @var string[] $labelValues */
                $labelValues        = json_decode($k, true);
                $gauge['samples'][] = [
                    'name' => $gauge['name'],
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => (float) $value,
                ];
            }

            usort(
                $gauge['samples'],
                /**
                 * @psalm-param array{labelValues: string[]} $a
                 * @psalm-param array{labelValues: string[]} $b
                 */
                static function (array $a, array $b) : int {
                    return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
                }
            );
            $gauges[] = $gauge;
        }

        return $gauges;
    }

    /**
     * @return array<int,array<string,mixed>>
     *
     * @phpstan-return array<array{name:string, help:string, labelNames: string[], type:'counter', samples: list<array{name:string, labelNames:array<mixed>, labelValues: string[], value: float}>}>
     * @psalm-return array<array{name:string, help:string, labelNames: string[], type:'counter', samples: list<array{name:string, labelNames:array<empty,empty>, labelValues: string[], value: float}>}>
     */
    private function collectCounters() : array
    {
        /** @var string[] $keys */
        $keys = $this->redis->sMembers($this->prefix . 'counter' . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        $counters = [];
        foreach ($keys as $key) {
            /** @psalm-var array{__meta:string,string:string} $raw */
            $raw = $this->redis->hGetAll($key);
            /** @psalm-var array{name:string, help:string, labelNames: string[]} $counter */
            $counter         = json_decode($raw['__meta'], true);
            $counter['type'] = 'counter';
            unset($raw['__meta']);
            $counter['samples'] = [];
            foreach ($raw as $k => $value) {
                /** @var string[] $labelValues */
                $labelValues          = json_decode($k, true);
                $counter['samples'][] = [
                    'name' => $counter['name'],
                    'labelNames' => [],
                    'labelValues' => $labelValues,
                    'value' => (float) $value,
                ];
            }

            usort(
                $counter['samples'],
                /**
                 * @psalm-param array{labelValues: string[]} $a
                 * @psalm-param array{labelValues: string[]} $b
                 */
                static function (array $a, array $b) : int {
                    return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
                }
            );
            $counters[] = $counter;
        }

        return $counters;
    }

    private function toMetricKey(MetricName $name, string $type) : string
    {
        return implode(':', [$this->prefix, $type, $name->toString()]);
    }
}
