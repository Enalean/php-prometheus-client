<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Registry;

use Enalean\Prometheus\MetricFamilySamples;

interface Collector
{
    /** @return MetricFamilySamples[] */
    public function getMetricFamilySamples(): array;
}
