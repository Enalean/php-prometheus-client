<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\HistogramLabelNames;
use Prometheus\Value\MetricName;

interface HistogramStorage
{
    /**
     * @param string[]                  $labelValues
     * @param array<string,float|array> $data
     *
     * @psalm-param array{
     *      buckets:array<int|float>,
     *      value:float
     * } $data
     */
    public function updateHistogram(MetricName $name, string $help, HistogramLabelNames $labelNames, array $labelValues, array $data) : void;
}
