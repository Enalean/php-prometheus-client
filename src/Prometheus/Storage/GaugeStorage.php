<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricName;

interface GaugeStorage
{
    /**
     * @param array<string,string|int|float|string[]> $data
     *
     * @psalm-param array{
     *      labelNames:string[],
     *      value:float,
     *      command:int,
     *      labelValues:string[]
     * } $data
     */
    public function updateGauge(MetricName $name, string $help, array $data) : void;
}
