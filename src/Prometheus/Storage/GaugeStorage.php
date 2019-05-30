<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

interface GaugeStorage
{
    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void;

    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void;
}
