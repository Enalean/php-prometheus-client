<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\MetricFamilySamples;
use Prometheus\Sample;
use Prometheus\Value\HistogramLabelNames;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;
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

final class RedisStore implements Store, CounterStorage, GaugeStorage, HistogramStorage, FlushableStorage
{
    public const PROMETHEUS_METRIC_KEYS_SUFFIX = '_METRIC_KEYS';

    /** @var string */
    private $prefix;

    /** @var Redis */
    private $redis;

    public function __construct(Redis $redis_client, string $key_prefix = 'PROMETHEUS_')
    {
        $this->redis  = $redis_client;
        $this->prefix = $key_prefix;
    }

    public function flush() : void
    {
        $this->redis->eval(
            <<<LUA
for keyIndex,key in ipairs(KEYS) do
    local members = redis.call('smembers', key)
    for memberIndex,member in ipairs(members) do
       redis.call('del', member)
    end
    redis.call('del', key)
end
LUA
            ,
            [
                $this->prefix . 'counter' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                $this->prefix . 'gauge' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                $this->prefix . 'histogram' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
            ],
            3
        );
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
    public function updateHistogram(MetricName $name, float $value, string $help, HistogramLabelNames $labelNames, array $labelValues, array $data) : void
    {
        $bucketToIncrease = '+Inf';
        foreach ($data['buckets'] as $bucket) {
            if ($value <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }
        $metaData = [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
            'buckets' => $data['buckets'],
        ];
        $this->redis->eval(
            <<<LUA
local increment = redis.call('hIncrByFloat', KEYS[1], KEYS[2], ARGV[1])
redis.call('hIncrBy', KEYS[1], KEYS[3], 1)
if increment == ARGV[1] then
    redis.call('hSet', KEYS[1], '__meta', ARGV[2])
    redis.call('sAdd', KEYS[4], KEYS[1])
end
LUA
            ,
            [
                $this->toMetricKey($name, 'histogram'),
                json_encode(['b' => 'sum', 'labelValues' => $labelValues]),
                json_encode(['b' => $bucketToIncrease, 'labelValues' => $labelValues]),
                $this->prefix . 'histogram' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                $value,
                json_encode($metaData),
            ],
            4
        );
    }

    /**
     * @inheritdoc
     */
    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void
    {
        $this->updateGauge($name, $value, $help, $labelNames, $labelValues, 'hSet');
    }

    /**
     * @inheritdoc
     */
    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void
    {
        $this->updateGauge($name, $value, $help, $labelNames, $labelValues, 'hIncrByFloat');
    }

    /**
     * @param string[] $labelValues
     */
    private function updateGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues, string $command) : void
    {
        $metaData = [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
        ];

        $this->redis->eval(
            <<<LUA
local result = redis.call(KEYS[2], KEYS[1], KEYS[4], ARGV[1])

if KEYS[2] == 'hSet' then
    if result == 1 then
        redis.call('hSet', KEYS[1], '__meta', ARGV[2])
        redis.call('sAdd', KEYS[3], KEYS[1])
    end
else
    if result == ARGV[1] then
        redis.call('hSet', KEYS[1], '__meta', ARGV[2])
        redis.call('sAdd', KEYS[3], KEYS[1])
    end
end
LUA
            ,
            [
                $this->toMetricKey($name, 'gauge'),
                $command,
                $this->prefix . 'gauge' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                json_encode($labelValues),
                $value,
                json_encode($metaData),
            ],
            4
        );
    }

    /**
     * @inheritdoc
     */
    public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void
    {
        $metaData = [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
        ];

        $this->redis->eval(
            <<<LUA
local result = redis.call('hIncrBy', KEYS[1], KEYS[3], ARGV[1])
if result == tonumber(ARGV[1]) then
    redis.call('hMSet', KEYS[1], '__meta', ARGV[2])
    redis.call('sAdd', KEYS[2], KEYS[1])
end
return result
LUA
            ,
            [
                $this->toMetricKey($name, 'counter'),
                $this->prefix . 'counter' . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                json_encode($labelValues),
                $value,
                json_encode($metaData),
            ],
            3
        );
    }

    /**
     * @return array<int,mixed>
     */
    private function collectHistograms() : array
    {
        $keys = $this->redis->sMembers($this->prefix . 'histogram' . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        sort($keys);
        $histograms = [];
        foreach ($keys as $key) {
            $raw               = $this->redis->hGetAll($key);
            $histogram         = json_decode($raw['__meta'], true);
            $histogram['type'] = 'histogram';
            unset($raw['__meta']);
            $histogram['samples'] = [];

            // Add the Inf bucket so we can compute it later on
            $histogram['buckets'][] = '+Inf';

            $allLabelValues = [];
            foreach (array_keys($raw) as $k) {
                $d = json_decode($k, true);
                if ($d['b'] === 'sum') {
                    continue;
                }
                $allLabelValues[] = $d['labelValues'];
            }

            // We need set semantics.
            // This is the equivalent of array_unique but for arrays of arrays.
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
                            'labelValues' => array_merge($labelValues, [$bucket]),
                            'value' => $acc,
                        ];
                    } else {
                        $acc                   += $raw[$bucketKey];
                        $histogram['samples'][] = [
                            'name' => $histogram['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($labelValues, [$bucket]),
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
     * @return array<int,mixed>
     */
    private function collectGauges() : array
    {
        $keys = $this->redis->sMembers($this->prefix . 'gauge' . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        sort($keys);
        $gauges = [];
        foreach ($keys as $key) {
            $raw           = $this->redis->hGetAll($key);
            $gauge         = json_decode($raw['__meta'], true);
            $gauge['type'] = 'gauge';
            unset($raw['__meta']);
            $gauge['samples'] = [];
            foreach ($raw as $k => $value) {
                $gauge['samples'][] = [
                    'name' => $gauge['name'],
                    'labelNames' => [],
                    'labelValues' => json_decode($k, true),
                    'value' => $value,
                ];
            }
            usort($gauge['samples'], static function (array $a, array $b) : int {
                return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
            });
            $gauges[] = $gauge;
        }

        return $gauges;
    }

    /**
     * @return array<int,mixed>
     */
    private function collectCounters() : array
    {
        $keys = $this->redis->sMembers($this->prefix . 'counter' . self::PROMETHEUS_METRIC_KEYS_SUFFIX);

        sort($keys);
        $counters = [];
        foreach ($keys as $key) {
            $raw             = $this->redis->hGetAll($key);
            $counter         = json_decode($raw['__meta'], true);
            $counter['type'] = 'counter';
            unset($raw['__meta']);
            $counter['samples'] = [];
            foreach ($raw as $k => $value) {
                $counter['samples'][] = [
                    'name' => $counter['name'],
                    'labelNames' => [],
                    'labelValues' => json_decode($k, true),
                    'value' => $value,
                ];
            }
            usort($counter['samples'], static function (array $a, array $b) : int {
                return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
            });
            $counters[] = $counter;
        }

        return $counters;
    }

    private function toMetricKey(MetricName $name, string $type) : string
    {
        return implode(':', [$this->prefix, $type, $name->toString()]);
    }
}
