<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use APCUIterator;
use Prometheus\MetricFamilySamples;
use RuntimeException;
use function apcu_add;
use function apcu_cas;
use function apcu_clear_cache;
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
use function json_last_error_msg;
use function pack;
use function sort;
use function strcmp;
use function unpack;
use function usort;

final class APCUStore implements Storage
{
    public const PROMETHEUS_PREFIX = 'prom';

    /**
     * @return MetricFamilySamples[]
     */
    public function collect() : array
    {
        $metrics = $this->collectHistograms();
        $metrics = array_merge($metrics, $this->collectGauges());
        $metrics = array_merge($metrics, $this->collectCounters());

        return $metrics;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateHistogram(array $data) : void
    {
        // Initialize the sum
        $sumKey = $this->histogramBucketValueKey($data, 'sum');
        $new    = apcu_add($sumKey, $this->toInteger(0));

        // If sum does not exist, assume a new histogram and store the metadata
        if ($new) {
            apcu_store($this->metaKey($data), json_encode($this->metaData($data)));
        }

        // Atomically increment the sum
        // Taken from https://github.com/prometheus/client_golang/blob/66058aac3a83021948e5fb12f1f408ff556b9037/prometheus/value.go#L91
        $done = false;
        while (! $done) {
            $old  = apcu_fetch($sumKey);
            $done = apcu_cas($sumKey, $old, $this->toInteger($this->fromInteger($old) + $data['value']));
        }

        // Figure out in which bucket the observation belongs
        $bucketToIncrease = '+Inf';
        foreach ($data['buckets'] as $bucket) {
            if ($data['value'] <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        // Initialize and increment the bucket
        apcu_add($this->histogramBucketValueKey($data, (string) $bucketToIncrease), 0);
        apcu_inc($this->histogramBucketValueKey($data, (string) $bucketToIncrease));
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateGauge(array $data) : void
    {
        $valueKey = $this->valueKey($data);
        if ($data['command'] === self::COMMAND_SET) {
            apcu_store($valueKey, $this->toInteger($data['value']));
            apcu_store($this->metaKey($data), json_encode($this->metaData($data)));
        } else {
            $new = apcu_add($valueKey, $this->toInteger(0));
            if ($new) {
                apcu_store($this->metaKey($data), json_encode($this->metaData($data)));
            }
            // Taken from https://github.com/prometheus/client_golang/blob/66058aac3a83021948e5fb12f1f408ff556b9037/prometheus/value.go#L91
            $done = false;
            while (! $done) {
                $old  = apcu_fetch($valueKey);
                $done = apcu_cas($valueKey, $old, $this->toInteger($this->fromInteger($old) + $data['value']));
            }
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateCounter(array $data) : void
    {
        $new = apcu_add($this->valueKey($data), 0);
        if ($new) {
            apcu_store($this->metaKey($data), json_encode($this->metaData($data)));
        }
        apcu_inc($this->valueKey($data), $data['value']);
    }

    public function flushAPC() : void
    {
        apcu_clear_cache();
    }

    /**
     * @param array<string,string> $data
     */
    private function metaKey(array $data) : string
    {
        return implode(':', [self::PROMETHEUS_PREFIX, $data['type'], $data['name'], 'meta']);
    }

    /**
     * @param array<string,string|string[]> $data
     *
     * @psalm-param array{type:string, name:string, labelValues: string[]} $data
     */
    private function valueKey(array $data) : string
    {
        return implode(':', [
            self::PROMETHEUS_PREFIX,
            $data['type'],
            $data['name'],
            $this->encodeLabelValues($data['labelValues']),
            'value',
        ]);
    }

    /**
     * @param array<string,string|string[]> $data
     *
     * @psalm-param array{type:string, name:string, labelValues: string[]} $data
     */
    private function histogramBucketValueKey(array $data, string $bucket) : string
    {
        return implode(':', [
            self::PROMETHEUS_PREFIX,
            $data['type'],
            $data['name'],
            $this->encodeLabelValues($data['labelValues']),
            $bucket,
            'value',
        ]);
    }

    /**
     * @param array<string,string> $data
     *
     * @return array<string,string>
     */
    private function metaData(array $data) : array
    {
        $metricsMetaData = $data;
        unset($metricsMetaData['value']);
        unset($metricsMetaData['command']);
        unset($metricsMetaData['labelValues']);

        return $metricsMetaData;
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectCounters() : array
    {
        $counters = [];
        foreach (new APCUIterator('/^prom:counter:.*:meta/') as $counter) {
            $metaData   = json_decode($counter['value'], true);
            $labelNames = [];
            foreach ($metaData['labelNames'] as $labelName) {
                $labelNames[] = (string) $labelName;
            }
            $data = [
                'name' => (string) $metaData['name'],
                'help' => (string) $metaData['help'],
                'type' => (string) $metaData['type'],
                'labelNames' => $labelNames,
                'samples' => [],
            ];

            foreach (new APCUIterator('/^prom:counter:' . $metaData['name'] . ':.*:value/') as $value) {
                $parts             = explode(':', $value['key']);
                $labelValues       = $parts[3];
                $data['samples'][] = [
                    'name' => (string) $metaData['name'],
                    'labelNames' => [],
                    'labelValues' => $this->decodeLabelValues($labelValues),
                    'value' => (float) $value['value'],
                ];
            }
            $this->sortSamples($data['samples']);
            $counters[] = new MetricFamilySamples($data);
        }

        return $counters;
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectGauges() : array
    {
        $gauges = [];
        foreach (new APCUIterator('/^prom:gauge:.*:meta/') as $gauge) {
            $metaData   = json_decode($gauge['value'], true);
            $labelNames = [];
            foreach ($metaData['labelNames'] as $labelName) {
                $labelNames[] = (string) $labelName;
            }
            $data = [
                'name' => (string) $metaData['name'],
                'help' => (string) $metaData['help'],
                'type' => (string) $metaData['type'],
                'labelNames' => $labelNames,
                'samples' => [],
            ];
            foreach (new APCUIterator('/^prom:gauge:' . $metaData['name'] . ':.*:value/') as $value) {
                $parts             = explode(':', $value['key']);
                $labelValues       = $parts[3];
                $data['samples'][] = [
                    'name' => (string) $metaData['name'],
                    'labelNames' => [],
                    'labelValues' => $this->decodeLabelValues($labelValues),
                    'value' => $this->fromInteger($value['value']),
                ];
            }

            $this->sortSamples($data['samples']);
            $gauges[] = new MetricFamilySamples($data);
        }

        return $gauges;
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectHistograms() : array
    {
        $histograms = [];
        foreach (new APCUIterator('/^prom:histogram:.*:meta/') as $histogram) {
            $metaData   = json_decode($histogram['value'], true);
            $labelNames = [];
            foreach ($metaData['labelNames'] as $labelName) {
                $labelNames[] = (string) $labelName;
            }
            $data = [
                'name' => (string) $metaData['name'],
                'help' => (string) $metaData['help'],
                'type' => (string) $metaData['type'],
                'labelNames' => $labelNames,
                'buckets' => $metaData['buckets'],
                'samples' => [],
            ];

            // Add the Inf bucket so we can compute it later on
            $data['buckets'][] = '+Inf';

            $histogramBuckets = [];
            foreach (new APCUIterator('/^prom:histogram:' . $metaData['name'] . ':.*:value/') as $value) {
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
            $histograms[] = new MetricFamilySamples($data);
        }

        return $histograms;
    }

    /**
     * @param mixed $val
     */
    private function toInteger($val) : int
    {
        return unpack('Q', pack('d', $val))[1];
    }

    /**
     * @param mixed $val
     */
    private function fromInteger($val) : float
    {
        return unpack('d', pack('Q', $val))[1];
    }

    /**
     * @param string[][] $samples
     */
    private static function sortSamples(array &$samples) : void
    {
        usort($samples, static function (array $a, array $b) : int {
            return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
        });
    }

    /**
     * @param string[] $values
     *
     * @throws RuntimeException
     */
    private function encodeLabelValues(array $values) : string
    {
        $json = json_encode($values);
        if ($json === false) {
            throw new RuntimeException(json_last_error_msg());
        }

        return base64_encode($json);
    }

    /**
     * @return string[]
     *
     * @throws RuntimeException
     */
    private function decodeLabelValues(string $values) : array
    {
        $json = base64_decode($values, true);
        if ($json === false) {
            throw new RuntimeException('Cannot base64 decode label values');
        }
        $decodedValues = json_decode($json, true);
        if ($decodedValues === false) {
            throw new RuntimeException(json_last_error_msg());
        }

        return $decodedValues;
    }
}
