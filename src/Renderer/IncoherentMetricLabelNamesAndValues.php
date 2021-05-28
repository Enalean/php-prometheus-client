<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Renderer;

use Enalean\Prometheus\MetricFamilySamples;
use LogicException;
use RuntimeException;

use function sprintf;

final class IncoherentMetricLabelNamesAndValues extends RuntimeException
{
    private MetricFamilySamples $metric;

    public function __construct(MetricFamilySamples $metric, int $nbLabelNames, int $nbLabelValues)
    {
        if ($nbLabelNames === $nbLabelValues) {
            throw new LogicException(
                sprintf('Label names and values seems to be coherent, both have %d elements', $nbLabelNames),
            );
        }

        parent::__construct(
            sprintf(
                'Cannot render a sample of the metric %s, got %d names for %d values. Try to flush your store?',
                $metric->getName(),
                $nbLabelNames,
                $nbLabelValues
            )
        );
        $this->metric = $metric;
    }

    public function getMetric(): MetricFamilySamples
    {
        return $this->metric;
    }
}
