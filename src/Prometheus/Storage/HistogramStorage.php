<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\HistogramLabelNames;
use Prometheus\Value\MetricName;

interface HistogramStorage
{
    /**
     * @param string[]                       $labelValues
     * @param array<string,array<int|float>> $data
     *
     * @psalm-param array{
     *      buckets:array<int|float>
     * } $data
     */
    public function updateHistogram(MetricName $name, float $value, string $help, HistogramLabelNames $labelNames, array $labelValues, array $data) : void;
}
