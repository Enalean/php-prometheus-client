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
    public function updateHistogram(MetricName $name, string $help, HistogramLabelNames $labelNames, array $data) : void
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function setGaugeTo(MetricName $name, string $help, MetricLabelNames $labelNames, array $data) : void
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function addToGauge(MetricName $name, string $help, MetricLabelNames $labelNames, array $data) : void
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function incrementCounter(MetricName $name, string $help, MetricLabelNames $labelNames, array $data) : void
    {
        return;
    }
}
