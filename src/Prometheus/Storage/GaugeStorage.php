<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricName;

interface GaugeStorage
{
    /**
     * @param array<string,float|string[]> $data
     *
     * @psalm-param array{
     *      labelNames:string[],
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function setGaugeTo(MetricName $name, string $help, array $data) : void;

    /**
     * @param array<string,float|string[]> $data
     *
     * @psalm-param array{
     *      labelNames:string[],
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function addToGauge(MetricName $name, string $help, array $data) : void;
}
