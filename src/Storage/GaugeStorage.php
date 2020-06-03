<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Storage;

use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;

interface GaugeStorage
{
    public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void;

    public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void;
}
