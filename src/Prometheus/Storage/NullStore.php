<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\HistogramLabelNames;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

final class NullStore implements Store, CounterStorage, GaugeStorage, HistogramStorage
{
    /**
     * @inheritdoc
     */
    public function collect() : array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, string ...$labelValues) : void
    {
        return;
    }

    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
    {
        return;
    }

    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
    {
        return;
    }

    public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
    {
        return;
    }
}
