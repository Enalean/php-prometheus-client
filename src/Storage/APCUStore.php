<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Storage;

use APCuIterator;
use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Sample;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\LabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;

use function apcu_add;
use function apcu_cas;
use function apcu_delete;
use function apcu_fetch;
use function apcu_inc;
use function apcu_store;
use function array_keys;
use function array_merge;
use function base64_decode;
use function base64_encode;
use function explode;
use function implode;
use function json_decode;
use function json_encode;
use function pack;
use function sort;
use function strcmp;
use function unpack;
use function usort;

use const JSON_THROW_ON_ERROR;

final class APCUStore implements Store, CounterStorage, GaugeStorage, HistogramStorage, FlushableStorage
{
    public const PROMETHEUS_PREFIX = 'prom';

    /**
     * @inheritdoc
     */
    public function collect(): array
    {
        $metrics = $this->collectHistograms();
        $metrics = array_merge($metrics, $this->collectGauges());
        $metrics = array_merge($metrics, $this->collectCounters());

        return $metrics;
    }

    /**
     * @inheritdoc
     */
    public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, string ...$labelValues): void
    {
        // Initialize the sum
        $sumKey = $this->histogramBucketValueKey($name, $labelValues, 'sum');
        $new    = apcu_add($sumKey, $this->toInteger(0));

        // If sum does not exist, assume a new histogram and store the metadata
        if ($new) {
            $metaData            = $this->metaData($name, $help, $labelNames);
            $metaData['buckets'] = $buckets;
            apcu_store($this->metaKey($name, 'histogram'), json_encode($metaData));
        }

        // Atomically increment the sum
        // Taken from https://github.com/prometheus/client_golang/blob/66058aac3a83021948e5fb12f1f408ff556b9037/prometheus/value.go#L91
        $done = false;
        while (! $done) {
            $old  = (int) apcu_fetch($sumKey);
            $done = apcu_cas($sumKey, $old, $this->toInteger($this->fromInteger($old) + $value));
        }

        // Figure out in which bucket the observation belongs
        $bucketToIncrease = '+Inf';
        foreach ($buckets as $bucket) {
            if ($value <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        // Initialize and increment the bucket
        apcu_add($this->histogramBucketValueKey($name, $labelValues, (string) $bucketToIncrease), 0);
        apcu_inc($this->histogramBucketValueKey($name, $labelValues, (string) $bucketToIncrease));
    }

    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void
    {
        $valueKey = $this->valueKey($name, 'gauge', $labelValues);
        apcu_store($valueKey, $this->toInteger($value));
        apcu_store($this->metaKey($name, 'gauge'), json_encode($this->metaData($name, $help, $labelNames)));
    }

    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void
    {
        $valueKey = $this->valueKey($name, 'gauge', $labelValues);
        $new      = apcu_add($valueKey, $this->toInteger(0));
        if ($new) {
            apcu_store($this->metaKey($name, 'gauge'), json_encode($this->metaData($name, $help, $labelNames)));
        }

            // Taken from https://github.com/prometheus/client_golang/blob/66058aac3a83021948e5fb12f1f408ff556b9037/prometheus/value.go#L91
            $done = false;
        while (! $done) {
            $old  = (int) apcu_fetch($valueKey);
            $done = apcu_cas($valueKey, $old, $this->toInteger($this->fromInteger($old) + $value));
        }
    }

    public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void
    {
        $valueKey = $this->valueKey($name, 'counter', $labelValues);
        $new      = apcu_add($valueKey, $this->toInteger(0));
        if ($new) {
            apcu_store($this->metaKey($name, 'counter'), json_encode($this->metaData($name, $help, $labelNames)));
        }

        // Taken from https://github.com/prometheus/client_golang/blob/66058aac3a83021948e5fb12f1f408ff556b9037/prometheus/value.go#L91
        $done = false;
        while (! $done) {
            $old  = (int) apcu_fetch($valueKey);
            $done = apcu_cas($valueKey, $old, $this->toInteger($this->fromInteger($old) + $value));
        }
    }

    public function flush(): void
    {
        apcu_delete(new APCuIterator('/^' . self::PROMETHEUS_PREFIX . ':.*/'));
    }

    private function metaKey(MetricName $name, string $type): string
    {
        return implode(':', [self::PROMETHEUS_PREFIX, $type, $name->toString(), 'meta']);
    }

    /**
     * @param string[] $labelValues
     */
    private function valueKey(MetricName $name, string $type, array $labelValues): string
    {
        return implode(':', [
            self::PROMETHEUS_PREFIX,
            $type,
            $name->toString(),
            $this->encodeLabelValues($labelValues),
            'value',
        ]);
    }

    /**
     * @param string[] $labelValues
     */
    private function histogramBucketValueKey(MetricName $name, array $labelValues, string $bucket): string
    {
        return implode(':', [
            self::PROMETHEUS_PREFIX,
            'histogram',
            $name->toString(),
            $this->encodeLabelValues($labelValues),
            $bucket,
            'value',
        ]);
    }

    /**
     * @return array<string,string|string[]>
     *
     * @psalm-return array{name:string, help:string, labelNames:string[]}
     */
    private function metaData(MetricName $name, string $help, LabelNames $labelNames): array
    {
        return [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
        ];
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectCounters(): array
    {
        $counters = [];
        /** @psalm-var array{value:string} $counter */
        foreach (new APCuIterator('/^prom:counter:.*:meta/') as $counter) {
            /**
             * @psalm-var array{name:string, help:string, labelNames:string[]} $metaData
             */
            $metaData   = json_decode($counter['value'], true);
            $labelNames = $metaData['labelNames'];

            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'labelNames' => $labelNames,
                'samples' => [],
            ];

            /** @psalm-var array{key:string,value:int} $value */
            foreach (new APCuIterator('/^prom:counter:' . $metaData['name'] . ':.*:value/') as $value) {
                $parts             = explode(':', $value['key']);
                $labelValues       = $parts[3];
                $data['samples'][] = [
                    'name' => $metaData['name'],
                    'labelNames' => [],
                    'labelValues' => $this->decodeLabelValues($labelValues),
                    'value' => $this->fromInteger($value['value']),
                ];
            }

            self::sortSamples($data['samples']);
            $samples = [];
            foreach ($data['samples'] as $sampleData) {
                $samples[] = new Sample($sampleData['name'], $sampleData['value'], $sampleData['labelNames'], $sampleData['labelValues']);
            }

            $counters[] = new MetricFamilySamples($data['name'], 'counter', $data['help'], $data['labelNames'], $samples);
        }

        return $counters;
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectGauges(): array
    {
        $gauges = [];
        /** @psalm-var array{value:string} $gauge */
        foreach (new APCuIterator('/^prom:gauge:.*:meta/') as $gauge) {
            /**
             * @psalm-var array{name:string, help:string, labelNames:string[]} $metaData
             */
            $metaData   = json_decode($gauge['value'], true);
            $labelNames = $metaData['labelNames'];

            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'labelNames' => $labelNames,
                'samples' => [],
            ];
            /** @psalm-var array{key:string,value:int} $value */
            foreach (new APCuIterator('/^prom:gauge:' . $metaData['name'] . ':.*:value/') as $value) {
                $parts             = explode(':', $value['key']);
                $labelValues       = $parts[3];
                $data['samples'][] = [
                    'name' => $metaData['name'],
                    'labelNames' => [],
                    'labelValues' => $this->decodeLabelValues($labelValues),
                    'value' => $this->fromInteger($value['value']),
                ];
            }

            self::sortSamples($data['samples']);
            $samples = [];
            foreach ($data['samples'] as $sampleData) {
                $samples[] = new Sample($sampleData['name'], $sampleData['value'], $sampleData['labelNames'], $sampleData['labelValues']);
            }

            $gauges[] = new MetricFamilySamples($data['name'], 'gauge', $data['help'], $data['labelNames'], $samples);
        }

        return $gauges;
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectHistograms(): array
    {
        $histograms = [];
        /** @psalm-var array{value:string} $histogram */
        foreach (new APCuIterator('/^prom:histogram:.*:meta/') as $histogram) {
            /**
             * @psalm-var array{name:string, help:string, labelNames:string[], buckets:array<string|float>} $metaData
             */
            $metaData   = json_decode($histogram['value'], true);
            $labelNames = $metaData['labelNames'];

            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'labelNames' => $labelNames,
                'buckets' => $metaData['buckets'],
                'samples' => [],
            ];

            // Add the Inf bucket so we can compute it later on
            $data['buckets'][] = '+Inf';

            $histogramBuckets = [];
            /** @psalm-var array{key:string,value:int} $value */
            foreach (new APCuIterator('/^prom:histogram:' . $metaData['name'] . ':.*:value/') as $value) {
                $parts       = explode(':', $value['key']);
                $labelValues = $parts[3];
                $bucket      = $parts[4];
                // Key by labelValues
                $histogramBuckets[$labelValues][$bucket] = $value['value'];
            }

            // Compute all buckets
            $labels = array_keys($histogramBuckets);
            sort($labels);
            foreach ($labels as $labelValues) {
                $acc                = 0;
                $decodedLabelValues = $this->decodeLabelValues($labelValues);
                foreach ($data['buckets'] as $bucket) {
                    $bucket = (string) $bucket;
                    if (! isset($histogramBuckets[$labelValues][$bucket])) {
                        $data['samples'][] = [
                            'name' => $metaData['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($decodedLabelValues, [$bucket]),
                            'value' => $acc,
                        ];
                    } else {
                        $acc              += (float) $histogramBuckets[$labelValues][$bucket];
                        $data['samples'][] = [
                            'name' => $metaData['name'] . '_bucket',
                            'labelNames' => ['le'],
                            'labelValues' => array_merge($decodedLabelValues, [$bucket]),
                            'value' => $acc,
                        ];
                    }
                }

                // Add the count
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_count',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => $acc,
                ];

                // Add the sum
                $data['samples'][] = [
                    'name' => $metaData['name'] . '_sum',
                    'labelNames' => [],
                    'labelValues' => $decodedLabelValues,
                    'value' => $this->fromInteger($histogramBuckets[$labelValues]['sum']),
                ];
            }

            unset($data['buckets']);
            $samples = [];
            foreach ($data['samples'] as $sampleData) {
                $samples[] = new Sample($sampleData['name'], $sampleData['value'], $sampleData['labelNames'], $sampleData['labelValues']);
            }

            $histograms[] = new MetricFamilySamples($data['name'], 'histogram', $data['help'], $data['labelNames'], $samples);
        }

        return $histograms;
    }

    private function toInteger(float $val): int
    {
        return (int) unpack('Q', pack('d', $val))[1];
    }

    private function fromInteger(int $val): float
    {
        return (float) unpack('d', pack('Q', $val))[1];
    }

    /**
     * @param string[][]|float[][]|string[][][] $samples
     *
     * @psalm-param array<array{labelValues: string[]}> $samples
     */
    private static function sortSamples(array &$samples): void
    {
        usort(
            $samples,
            /**
             * @psalm-param array{labelValues: string[]} $a
             * @psalm-param array{labelValues: string[]} $b
             */
            static function (array $a, array $b): int {
                return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
            }
        );
    }

    /**
     * @param string[] $values
     */
    private function encodeLabelValues(array $values): string
    {
        $json = json_encode($values, JSON_THROW_ON_ERROR);

        return base64_encode($json);
    }

    /**
     * @return string[]
     */
    private function decodeLabelValues(string $values): array
    {
        /** @psalm-var string[] */
        return json_decode((string) base64_decode($values, true), true, JSON_THROW_ON_ERROR);
    }
}
