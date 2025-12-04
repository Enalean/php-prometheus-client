<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Sample;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetricFamilySamples::class)]
final class MetricFamilySamplesTest extends TestCase
{
    public function testMetricFamilySamplesHoldValuesWithoutModification(): void
    {
        $name       = 'name';
        $type       = 'type';
        $help       = 'help';
        $labelNames = ['labelA', 'labelB'];
        $samples    = [new Sample('nameA', 1, [], []), new Sample('nameB', 2, [], [])];

        $metricFamilySamples = new MetricFamilySamples($name, $type, $help, $labelNames, $samples);
        self::assertSame($name, $metricFamilySamples->getName());
        self::assertSame($type, $metricFamilySamples->getType());
        self::assertSame($help, $metricFamilySamples->getHelp());
        self::assertSame($samples, $metricFamilySamples->getSamples());
        self::assertSame($labelNames, $metricFamilySamples->getLabelNames());
        self::assertTrue($metricFamilySamples->hasLabelNames());
    }

    public function testMetricFamilySamplesDetectWhenNoLabelNamesAreGiven(): void
    {
        $metricFamilySamples = new MetricFamilySamples('name', 'type', 'help', [], []);
        self::assertFalse($metricFamilySamples->hasLabelNames());
    }
}
