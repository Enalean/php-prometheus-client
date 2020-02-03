<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Renderer;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Sample;
use function array_combine;
use function array_merge;
use function assert;
use function implode;
use function is_array;
use function sprintf;
use function str_replace;
use function strcmp;
use function usort;

/**
 * @psalm-immutable
 */
final class RenderTextFormat implements MetricsRenderer
{
    private const MIME_TYPE = 'text/plain; version=0.0.4';

    /**
     * @param MetricFamilySamples[] $metrics
     */
    public function render(array $metrics) : string
    {
        /** @psalm-suppress ImpureFunctionCall https://github.com/vimeo/psalm/issues/2631 */
        usort($metrics, static function (MetricFamilySamples $a, MetricFamilySamples $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $lines = [];

        foreach ($metrics as $metric) {
            $lines[] = sprintf('# HELP %s %s', $metric->getName(), $metric->getHelp());
            $lines[] = sprintf('# TYPE %s %s', $metric->getName(), $metric->getType());
            foreach ($metric->getSamples() as $sample) {
                $lines[] = $this->renderSample($metric, $sample);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function renderSample(MetricFamilySamples $metric, Sample $sample) : string
    {
        $escapedLabels = [];

        $labelNames = $metric->getLabelNames();
        if ($metric->hasLabelNames() || $sample->hasLabelNames()) {
            $labels = array_combine(array_merge($labelNames, $sample->getLabelNames()), $sample->getLabelValues());
            assert(is_array($labels));
            foreach ($labels as $labelName => $labelValue) {
                $escapedLabels[] = $labelName . '="' . $this->escapeLabelValue($labelValue) . '"';
            }

            return $sample->getName() . '{' . implode(',', $escapedLabels) . '} ' . $sample->getValue();
        }

        return $sample->getName() . ' ' . $sample->getValue();
    }

    private function escapeLabelValue(string $v) : string
    {
        return str_replace(['\\', "\n", '"'], ['\\\\', "\\n", '\\"'], $v);
    }

    public function getMimeType() : string
    {
        return self::MIME_TYPE;
    }
}
