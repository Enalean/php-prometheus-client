<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Storage;

use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricName;

interface HistogramStorage
{
    /**
     * @param float[] $buckets
     */
    public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, string ...$labelValues): void;
}
