<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

interface CounterStorage
{
    /**
     * @param array<string,float|string[]> $data
     *
     * @psalm-param array{
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function incrementCounter(MetricName $name, string $help, MetricLabelNames $labelNames, array $data) : void;
}
