<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\HistogramLabelNames;
use Prometheus\Value\MetricName;

interface HistogramStorage
{
    /**
     * @param string[] $labelValues
     * @param float[]  $buckets
     */
    public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, array $labelValues) : void;
}
