<?php

declare(strict_types=1);

namespace Prometheus\Registry;

use Prometheus\MetricFamilySamples;

interface Collector
{
    /**
     * @return MetricFamilySamples[]
     */
    public function getMetricFamilySamples() : array;
}
