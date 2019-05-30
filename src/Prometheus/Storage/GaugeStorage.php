<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

interface GaugeStorage
{
    /**
     * @param string[] $labelValues
     */
    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void;

    /**
     * @param string[] $labelValues
     */
    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void;
}
