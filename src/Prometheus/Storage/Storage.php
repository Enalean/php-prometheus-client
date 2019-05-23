<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\MetricFamilySamples;

interface Storage
{
    public const COMMAND_INCREMENT_INTEGER = 1;
    public const COMMAND_INCREMENT_FLOAT   = 2;
    public const COMMAND_SET               = 3;

    /**
     * @return MetricFamilySamples[]
     */
    public function collect() : array;

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

    /**
     * @param array<string,string|int|float|string[]> $data
     *
     * @psalm-param array{
     *      name:string,
     *      help:string,
     *      type:string,
     *      labelNames:string[],
     *      value:float,
     *      command:int,
     *      labelValues:string[]
     * } $data
     */
    public function updateGauge(array $data) : void;

    /**
     * @param array<string,string|int|float|string[]> $data
     *
     * @psalm-param array{
     *      name:string,
     *      help:string,
     *      type:string,
     *      labelNames:string[],
     *      value:float,
     *      command:int,
     *      labelValues:string[]
     * } $data
     */
    public function updateCounter(array $data) : void;
}
