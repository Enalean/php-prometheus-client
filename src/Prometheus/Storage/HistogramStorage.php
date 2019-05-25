<?php

declare(strict_types=1);

namespace Prometheus\Storage;

interface HistogramStorage
{
    /**
     * @param array<string,string|float|array> $data
     *
     * @psalm-param array{
     *      name:string,
     *      help:string,
     *      type:string,
     *      labelNames:string[],
     *      buckets:array<int|float>,
     *      value:float,
     *      labelValues:string[]
     * } $data
     */
    public function updateHistogram(array $data) : void;
}
