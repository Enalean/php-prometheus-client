<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\Value\MetricName;

interface HistogramStorage
{
    /**
     * @param array<string,string|float|array> $data
     *
     * @psalm-param array{
     *      type:string,
     *      labelNames:string[],
     *      buckets:array<int|float>,
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function updateHistogram(MetricName $name, string $help, array $data) : void;
}
