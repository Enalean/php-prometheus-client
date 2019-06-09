<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Storage;

use Enalean\Prometheus\MetricFamilySamples;

interface Store
{
    /**
     * @return MetricFamilySamples[]
     */
    public function collect() : array;
}
