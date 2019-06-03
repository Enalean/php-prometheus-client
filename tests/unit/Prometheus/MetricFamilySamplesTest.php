<?php

declare(strict_types=1);

namespace Test\Prometheus;

use PHPUnit\Framework\TestCase;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;

/**
 * @covers Prometheus\MetricFamilySamples
 */
final class MetricFamilySamplesTest extends TestCase
{
    public function testMetricFamilySamplesHoldValuesWithoutModification() : void
    {
        $name       = 'name';
        $type       = 'type';
        $help       = 'help';
        $labelNames = ['labelA', 'labelB'];
        $samples    = [new Sample('nameA', 1, [], []), new Sample('nameB', 2, [], [])];

        $metricFamilySamples = new MetricFamilySamples($name, $type, $help, $labelNames, $samples);
        $this->assertSame($name, $metricFamilySamples->getName());
        $this->assertSame($type, $metricFamilySamples->getType());
        $this->assertSame($help, $metricFamilySamples->getHelp());
        $this->assertSame($samples, $metricFamilySamples->getSamples());
        $this->assertSame($labelNames, $metricFamilySamples->getLabelNames());
        $this->assertTrue($metricFamilySamples->hasLabelNames());
    }

    public function testMetricFamilySamplesDetectWhenNoLabelNamesAreGiven() : void
    {
        $metricFamilySamples = new MetricFamilySamples('name', 'type', 'help', [], []);
        $this->assertFalse($metricFamilySamples->hasLabelNames());
    }
}
