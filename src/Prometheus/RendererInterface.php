<?php

declare(strict_types=1);

namespace Prometheus;

interface RendererInterface
{
    /**
     * @param MetricFamilySamples[] $metrics
     */
    public function render(array $metrics) : string;
}
