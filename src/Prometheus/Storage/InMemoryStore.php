<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\MetricFamilySamples;
use Prometheus\Sample;
use Prometheus\Value\HistogramLabelNames;
use Prometheus\Value\LabelNames;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;
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

final class InMemoryStore implements Store, CounterStorage, GaugeStorage, HistogramStorage, FlushableStorage
{
    /** @var array<string,mixed> */
    private $counters = [];
    /** @var array<string,mixed> */
    private $gauges = [];
    /**
     * @var array<string,string[]>
     * @psalm-var array<string, array{
     *      meta: array{name:string, help:string, labelNames:string[], buckets:array<int|float>},
     *      samples: array<string, float>
     * }>
     */
    private $histograms = [];

    /**
     * @inheritdoc
     */
    public function collect() : array
    {
        $metrics = $this->internalCollect('counter', $this->counters);
        $metrics = array_merge($metrics, $this->internalCollect('gauge', $this->gauges));
        $metrics = array_merge($metrics, $this->collectHistograms());

        return $metrics;
    }

    public function flush() : void
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
                'type' => 'histogram',
                'labelNames' => $metaData['labelNames'],
                'buckets' => $metaData['buckets'],
                'samples' => [],
            ];

            // Add the Inf bucket so we can compute it later on
            $data['buckets'][] = '+Inf';

            $histogramBuckets = [];
            foreach ($histogram['samples'] as $key => $value) {
                $parts       = explode(':', $key);
                $labelValues = $parts[1];
                $bucket      = $parts[2];
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
            $samples = [];
            foreach ($data['samples'] as $sampleData) {
                $samples[] = new Sample($sampleData['name'], $sampleData['value'], $sampleData['labelNames'], $sampleData['labelValues']);
            }
            $histograms[] = new MetricFamilySamples($data['name'], $data['type'], $data['help'], $data['labelNames'], $samples);
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
     *          samples:array<string, float>
     *      }
     *  > $metrics
     */
    private function internalCollect(string $type, array $metrics) : array
    {
        $result = [];
        foreach ($metrics as $metric) {
            $metaData = $metric['meta'];
            $data     = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'labelNames' => $metaData['labelNames'],
                'samples' => [],
            ];
            foreach ($metric['samples'] as $key => $value) {
                $parts             = explode(':', $key);
                $labelValues       = $parts[1];
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
            $samples = [];
            foreach ($data['samples'] as $sampleData) {
                $samples[] = new Sample($sampleData['name'], $sampleData['value'], $sampleData['labelNames'], $sampleData['labelValues']);
            }
            $result[] = new MetricFamilySamples($data['name'], $type, $data['help'], $data['labelNames'], $samples);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, array $labelValues) : void
    {
        // Initialize the sum
        $metaKey = $this->metaKey($name);
        if (array_key_exists($metaKey, $this->histograms) === false) {
            $metaData                   = $this->metaData($name, $help, $labelNames);
            $metaData['buckets']        = $buckets;
            $this->histograms[$metaKey] = [
                'meta' => $metaData,
                'samples' => [],
            ];
        }
        $sumKey = $this->histogramBucketValueKey($name, $labelValues, 'sum');
        if (array_key_exists($sumKey, $this->histograms[$metaKey]['samples']) === false) {
            $this->histograms[$metaKey]['samples'][$sumKey] = 0;
        }

        $this->histograms[$metaKey]['samples'][$sumKey] += $value;

        $bucketToIncrease = '+Inf';
        foreach ($buckets as $bucket) {
            if ($value <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        $bucketKey = $this->histogramBucketValueKey($name, $labelValues, (string) $bucketToIncrease);
        if (array_key_exists($bucketKey, $this->histograms[$metaKey]['samples']) === false) {
            $this->histograms[$metaKey]['samples'][$bucketKey] = 0;
        }
        $this->histograms[$metaKey]['samples'][$bucketKey] += 1;
    }

    /**
     * @inheritdoc
     */
    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void
    {
        $metaKey  = $this->initializeGaugeIfNecessary($name, $help, $labelNames);
        $valueKey = $this->valueKey($name, $labelValues);

        $this->gauges[$metaKey]['samples'][$valueKey] = $value;
    }

    /**
     * @inheritdoc
     */
    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void
    {
        $metaKey  = $this->initializeGaugeIfNecessary($name, $help, $labelNames);
        $valueKey = $this->valueKey($name, $labelValues);

        $oldValue                                     = $this->gauges[$metaKey]['samples'][$valueKey] ?? 0;
        $this->gauges[$metaKey]['samples'][$valueKey] = $oldValue + $value;
    }

    private function initializeGaugeIfNecessary(MetricName $name, string $help, LabelNames $labelNames) : string
    {
        $metaKey = $this->metaKey($name);
        if (array_key_exists($metaKey, $this->gauges) === false) {
            /** @psalm-suppress InvalidArgument */
            $meta                   = $this->metaData($name, $help, $labelNames);
            $this->gauges[$metaKey] = [
                'meta' => $meta,
                'samples' => [],
            ];
        }

        return $metaKey;
    }

    /**
     * @inheritdoc
     */
    public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void
    {
        $metaKey  = $this->metaKey($name);
        $valueKey = $this->valueKey($name, $labelValues);
        if (array_key_exists($metaKey, $this->counters) === false) {
            /** @psalm-suppress InvalidArgument */
            $meta                     = $this->metaData($name, $help, $labelNames);
            $this->counters[$metaKey] = [
                'meta' => $meta,
                'samples' => [],
            ];
        }
        if (array_key_exists($valueKey, $this->counters[$metaKey]['samples']) === false) {
            $this->counters[$metaKey]['samples'][$valueKey] = 0;
        }
        $this->counters[$metaKey]['samples'][$valueKey] += $value;
    }

    /**
     * @param string[] $labelValues
     */
    private function histogramBucketValueKey(MetricName $name, array $labelValues, string $bucket) : string
    {
        return implode(':', [
            $name->toString(),
            $this->encodeLabelValues($labelValues),
            $bucket,
        ]);
    }

    private function metaKey(MetricName $name) : string
    {
        return $name->toString() . ':meta';
    }

    /**
     * @param string[] $labelValues
     */
    private function valueKey(MetricName $name, array $labelValues) : string
    {
        return implode(
            ':',
            [$name->toString(), $this->encodeLabelValues($labelValues), 'value']
        );
    }

    /**
     * @param array<int|float> $buckets
     *
     * @return array<string,string|array>
     *
     * @psalm-return array{name:string, help:string, labelNames:string[]}
     */
    private function metaData(MetricName $name, string $help, LabelNames $labelNames) : array
    {
        return [
            'name' => $name->toString(),
            'help' => $help,
            'labelNames' => $labelNames->toStrings(),
        ];
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
