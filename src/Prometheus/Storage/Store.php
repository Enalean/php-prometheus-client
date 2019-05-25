<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use Prometheus\MetricFamilySamples;

interface Store
{
    /**
     * @return MetricFamilySamples[]
     */
    public function collect() : array;
}
