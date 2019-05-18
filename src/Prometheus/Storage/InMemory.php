<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\MetricFamilySamples;
use RuntimeException;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function base64_decode;
use function base64_encode;
use function explode;
use function implode;
use function json_decode;
use function json_encode;
use function json_last_error_msg;
use function sort;
use function strcmp;
use function usort;

class InMemory implements Adapter
{
    /** @var array<string,mixed> */
    private $counters = [];
    /** @var array<string,mixed> */
    private $gauges = [];
    /**
     * @var array<string,string[]>
     * @psalm-var array<string, array{
     *      meta: array{name:string, help:string, type:string, labelNames:string[], buckets:array<int|float>},
     *      samples: array<string, int|float>
     * }>
     */
    private $histograms = [];

    /**
     * @return MetricFamilySamples[]
     */
    public function collect() : array
    {
        $metrics = $this->internalCollect($this->counters);
        $metrics = array_merge($metrics, $this->internalCollect($this->gauges));
        $metrics = array_merge($metrics, $this->collectHistograms());

        return $metrics;
    }

    public function flushMemory() : void
    {
        $this->counters   = [];
        $this->gauges     = [];
        $this->histograms = [];
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectHistograms() : array
    {
        $histograms = [];
        foreach ($this->histograms as $histogram) {
            $metaData = $histogram['meta'];
            $data     = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
                'buckets' => $metaData['buckets'],
                'samples' => [],
            ];

            // Add the Inf bucket so we can compute it later on
            $data['buckets'][] = '+Inf';

            $histogramBuckets = [];
            foreach ($histogram['samples'] as $key => $value) {
                $parts       = explode(':', $key);
                $labelValues = $parts[2];
                $bucket      = $parts[3];
                // Key by labelValues
                $histogramBuckets[$labelValues][$bucket] = $value;
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
                        $acc              += $histogramBuckets[$labelValues][$bucket];
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
                    'value' => $histogramBuckets[$labelValues]['sum'],
                ];
            }
            $histograms[] = new MetricFamilySamples($data);
        }

        return $histograms;
    }

    /**
     * @param array<string,mixed> $metrics
     *
     * @return MetricFamilySamples[]
     *
     * @psalm-param array<
     *      string,
     *      array{
     *          meta:array{name:string, help:string, type:string, labelNames:string[]},
     *          samples:array<string, int|float>
     *      }
     *  > $metrics
     */
    private function internalCollect(array $metrics) : array
    {
        $result = [];
        foreach ($metrics as $metric) {
            $metaData = $metric['meta'];
            $data     = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
                'samples' => [],
            ];
            foreach ($metric['samples'] as $key => $value) {
                $parts             = explode(':', $key);
                $labelValues       = $parts[2];
                $data['samples'][] = [
                    'name' => $metaData['name'],
                    'labelNames' => [],
                    'labelValues' => $this->decodeLabelValues($labelValues),
                    'value' => $value,
                ];
            }
            usort($data['samples'], static function (array $a, array $b) : int {
                return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
            });
            $result[] = new MetricFamilySamples($data);
        }

        return $result;
    }

    /**
     * @param array<string,string|int|float|array> $data
     *
     * @psalm-param array{
     *      name:string,
     *      help:string,
     *      type:string,
     *      labelNames:string[],
     *      buckets:array<int|float>,
     *      value:int|float,
     *      labelValues:string[]
     * } $data
     */
    public function updateHistogram(array $data) : void
    {
        // Initialize the sum
        $metaKey = $this->metaKey($data);
        if (array_key_exists($metaKey, $this->histograms) === false) {
            $this->histograms[$metaKey] = [
                'meta' => $this->metaData($data),
                'samples' => [],
            ];
        }
        $sumKey = $this->histogramBucketValueKey($data, 'sum');
        if (array_key_exists($sumKey, $this->histograms[$metaKey]['samples']) === false) {
            $this->histograms[$metaKey]['samples'][$sumKey] = 0;
        }

        $this->histograms[$metaKey]['samples'][$sumKey] += $data['value'];

        $bucketToIncrease = '+Inf';
        foreach ($data['buckets'] as $bucket) {
            if ($data['value'] <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        $bucketKey = $this->histogramBucketValueKey($data, (string) $bucketToIncrease);
        if (array_key_exists($bucketKey, $this->histograms[$metaKey]['samples']) === false) {
            $this->histograms[$metaKey]['samples'][$bucketKey] = 0;
        }
        $this->histograms[$metaKey]['samples'][$bucketKey] += 1;
    }

    /**
     * @param array<string,string|int|float|string[]> $data
     *
     * @psalm-param array{
     *      name:string,
     *      help:string,
     *      type:string,
     *      labelNames:string[],
     *      value:int|float,
     *      command:int,
     *      labelValues:string[]
     * } $data
     */
    public function updateGauge(array $data) : void
    {
        $metaKey  = $this->metaKey($data);
        $valueKey = $this->valueKey($data);
        if (array_key_exists($metaKey, $this->gauges) === false) {
            /** @psalm-suppress InvalidArgument */
            $meta                   = $this->metaData($data);
            $this->gauges[$metaKey] = [
                'meta' => $meta,
                'samples' => [],
            ];
        }
        if (array_key_exists($valueKey, $this->gauges[$metaKey]['samples']) === false) {
            $this->gauges[$metaKey]['samples'][$valueKey] = 0;
        }
        if ($data['command'] === Adapter::COMMAND_SET) {
            $this->gauges[$metaKey]['samples'][$valueKey] = $data['value'];
        } else {
            $this->gauges[$metaKey]['samples'][$valueKey] += $data['value'];
        }
    }

    /**
     * @param array<string,string|int|float|string[]> $data
     *
     * @psalm-param array{
     *      name:string,
     *      help:string,
     *      type:string,
     *      labelNames:string[],
     *      value:int|float,
     *      command:int,
     *      labelValues:string[]
     * } $data
     */
    public function updateCounter(array $data) : void
    {
        $metaKey  = $this->metaKey($data);
        $valueKey = $this->valueKey($data);
        if (array_key_exists($metaKey, $this->counters) === false) {
            /** @psalm-suppress InvalidArgument */
            $meta                     = $this->metaData($data);
            $this->counters[$metaKey] = [
                'meta' => $data,
                'samples' => [],
            ];
        }
        if (array_key_exists($valueKey, $this->counters[$metaKey]['samples']) === false) {
            $this->counters[$metaKey]['samples'][$valueKey] = 0;
        }
        if ($data['command'] === Adapter::COMMAND_SET) {
            $this->counters[$metaKey]['samples'][$valueKey] = 0;
        } else {
            $this->counters[$metaKey]['samples'][$valueKey] += $data['value'];
        }
    }

    /**
     * @param array<string,string|string[]> $data
     *
     * @psalm-param array{type:string, name:string, labelValues: string[]} $data
     */
    private function histogramBucketValueKey(array $data, string $bucket) : string
    {
        return implode(':', [
            $data['type'],
            $data['name'],
            $this->encodeLabelValues($data['labelValues']),
            $bucket,
        ]);
    }

    /**
     * @param array<string,string> $data
     *
     * @psalm-param array{type:string, name:string} $data
     */
    private function metaKey(array $data) : string
    {
        return implode(':', [$data['type'], $data['name'], 'meta']);
    }

    /**
     * @param array<string,string|string[]> $data
     *
     * @psalm-param array{
     *      type:string,
     *      name:string,
     *      labelValues:string[]
     * } $data
     */
    private function valueKey(array $data) : string
    {
        return implode(
            ':',
            [$data['type'], $data['name'], $this->encodeLabelValues($data['labelValues']), 'value']
        );
    }

    /**
     * @param array<string,mixed> $data
     *
     * @return array<string,string|array>
     *
     * @psalm-param array{
     *      name:string,
     *      help:string,
     *      type:string,
     *      labelNames:string[],
     *      buckets:array<int|float>,
     *      value?:mixed,
     *      command?:mixed,
     *      labelValues?:mixed
     *  } $data
     * @psalm-return array{name:string, help:string, type:string, labelNames:string[], buckets:array<int|float>}
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
