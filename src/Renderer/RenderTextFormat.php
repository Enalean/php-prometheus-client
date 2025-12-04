<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Renderer;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Sample;

use function array_combine;
use function array_merge;
use function count;
use function implode;
use function sprintf;
use function str_replace;
use function strcmp;
use function usort;

final class RenderTextFormat implements MetricsRenderer
{
    private const MIME_TYPE = 'text/plain; version=0.0.4';

    /**
     * @param MetricFamilySamples[] $metrics
     *
     * @throws IncoherentMetricLabelNamesAndValues
     */
    public function render(array $metrics): string
    {
        $sortableMetrics = [...$metrics];
        usort($sortableMetrics, static function (MetricFamilySamples $a, MetricFamilySamples $b): int {
            return strcmp($a->getName(), $b->getName());
        });

        $lines = [];

        foreach ($sortableMetrics as $metric) {
            $lines[] = sprintf('# HELP %s %s', $metric->getName(), $metric->getHelp());
            $lines[] = sprintf('# TYPE %s %s', $metric->getName(), $metric->getType());
            foreach ($metric->getSamples() as $sample) {
                $lines[] = self::renderSample($metric, $sample);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /** @throws IncoherentMetricLabelNamesAndValues */
    private static function renderSample(MetricFamilySamples $metric, Sample $sample): string
    {
        $escapedLabels = [];

        $labelNames = $metric->getLabelNames();
        if ($metric->hasLabelNames() || $sample->hasLabelNames()) {
            $allLabelNames = array_merge($labelNames, $sample->getLabelNames());
            $labelValues   = $sample->getLabelValues();
            if (count($allLabelNames) !== count($labelValues)) {
                throw new IncoherentMetricLabelNamesAndValues($metric, count($allLabelNames), count($labelValues));
            }

            $labels = array_combine($allLabelNames, $labelValues);
            foreach ($labels as $labelName => $labelValue) {
                $escapedLabels[] = $labelName . '="' . self::escapeLabelValue($labelValue) . '"';
            }

            return $sample->getName() . '{' . implode(',', $escapedLabels) . '} ' . (string) $sample->getValue();
        }

        return $sample->getName() . ' ' . (string) $sample->getValue();
    }

    private static function escapeLabelValue(string $v): string
    {
        return str_replace(['\\', "\n", '"'], ['\\\\', "\\n", '\\"'], $v);
    }

    public function getMimeType(): string
    {
        return self::MIME_TYPE;
    }
}
