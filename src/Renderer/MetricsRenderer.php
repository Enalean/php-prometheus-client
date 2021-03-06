<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Renderer;

use Enalean\Prometheus\MetricFamilySamples;

interface MetricsRenderer
{
    /**
     * @param MetricFamilySamples[] $metrics
     *
     * @throws IncoherentMetricLabelNamesAndValues
     *
     * @psalm-pure
     */
    public function render(array $metrics): string;

    public function getMimeType(): string;
}
