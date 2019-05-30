<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

interface GaugeStorage
{
    /**
     * @param string[]            $labelValues
     * @param array<string,float> $data
     *
     * @psalm-param array{
     *      value:float
     * } $data
     */
    public function setGaugeTo(MetricName $name, string $help, MetricLabelNames $labelNames, array $labelValues, array $data) : void;

    /**
     * @param string[]            $labelValues
     * @param array<string,float> $data
     *
     * @psalm-param array{
     *      value:float
     * } $data
     */
    public function addToGauge(MetricName $name, string $help, MetricLabelNames $labelNames, array $labelValues, array $data) : void;
}
