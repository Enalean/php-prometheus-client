<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricName;

interface CounterStorage
{
    /**
     * @param array<string,string|int|float|string[]> $data
     *
     * @psalm-param array{
     *      labelNames:string[],
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function incrementCounter(MetricName $name, string $help, array $data) : void;
}
