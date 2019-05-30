<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

interface CounterStorage
{
    /**
     * @param string[] $labelValues
     */
    public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, array $labelValues) : void;
}
