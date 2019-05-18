<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\MetricFamilySamples;

interface Adapter
{
    public const COMMAND_INCREMENT_INTEGER = 1;
    public const COMMAND_INCREMENT_FLOAT   = 2;
    public const COMMAND_SET               = 3;

    /**
     * @return MetricFamilySamples[]
     */
    public function collect() : array;

    /**
     * @param array<string,mixed> $data
     */
    public function updateHistogram(array $data) : void;

    /**
     * @param array<string,mixed> $data
     */
    public function updateGauge(array $data) : void;

    /**
     * @param array<string,mixed> $data
     */
    public function updateCounter(array $data) : void;
}
