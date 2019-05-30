<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

interface CounterStorage
{
    /**
     * @param string[]            $labelValues
     * @param array<string,float> $data
     *
     * @psalm-param array{
     *      value:float
     * } $data
     */
    public function incrementCounter(MetricName $name, string $help, MetricLabelNames $labelNames, array $labelValues, array $data) : void;
}
