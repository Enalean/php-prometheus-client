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

/** @psalm-immutable */
final class RenderTextFormat implements MetricsRenderer
{
    private const MIME_TYPE = 'text/plain; version=0.0.4';

    /**
     * @param MetricFamilySamples[] $metrics
     *
     * @throws IncoherentMetricLabelNamesAndValues
     *
     * @psalm-pure
     */
    public function render(array $metrics): string
    {
        usort($metrics, static function (MetricFamilySamples $a, MetricFamilySamples $b): int {
            return strcmp($a->getName(), $b->getName());
        });

        $lines = [];

        foreach ($metrics as $metric) {
            $lines[] = sprintf('# HELP %s %s', $metric->getName(), $metric->getHelp());
            $lines[] = sprintf('# TYPE %s %s', $metric->getName(), $metric->getType());
            foreach ($metric->getSamples() as $sample) {
                $lines[] = self::renderSample($metric, $sample);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @throws IncoherentMetricLabelNamesAndValues
     *
     * @psalm-pure
     */
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

            return $sample->getName() . '{' . implode(',', $escapedLabels) . '} ' . $sample->getValue();
        }

        return $sample->getName() . ' ' . $sample->getValue();
    }

    /** @psalm-pure */
    private static function escapeLabelValue(string $v): string
    {
        return str_replace(['\\', "\n", '"'], ['\\\\', "\\n", '\\"'], $v);
    }

    public function getMimeType(): string
    {
        return self::MIME_TYPE;
    }
}
