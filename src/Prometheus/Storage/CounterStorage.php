<?php

declare(strict_types=1);

namespace Prometheus\Storage;

interface CounterStorage
{
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
