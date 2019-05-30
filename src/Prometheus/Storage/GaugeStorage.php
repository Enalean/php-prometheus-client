<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

interface GaugeStorage
{
    /**
     * @param array<string,float|string[]> $data
     *
     * @psalm-param array{
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function setGaugeTo(MetricName $name, string $help, MetricLabelNames $labelNames, array $data) : void;

    /**
     * @param array<string,float|string[]> $data
     *
     * @psalm-param array{
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function addToGauge(MetricName $name, string $help, MetricLabelNames $labelNames, array $data) : void;
}
