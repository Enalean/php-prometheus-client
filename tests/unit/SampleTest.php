<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest;

use Enalean\Prometheus\Sample;
use PHPUnit\Framework\TestCase;

/**
 * @covers Enalean\Prometheus\Sample
 */
final class SampleTest extends TestCase
{
    public function testSampleHoldValuesWithoutModification() : void
    {
        $name        = 'name';
        $value       = 0.1;
        $labelNames  = ['labelA', 'labelB'];
        $labelValues = ['valueA', 'valueB'];

        $sample = new Sample($name, $value, $labelNames, $labelValues);
        $this->assertSame($name, $sample->getName());
        $this->assertSame($value, $sample->getValue());
        $this->assertSame($labelNames, $sample->getLabelNames());
        $this->assertSame($labelValues, $sample->getLabelValues());
        $this->assertTrue($sample->hasLabelNames());
    }

    public function testSampleDetectWhenNoLabelNamesAreGiven() : void
    {
        $sample = new Sample('name', 0.1, [], []);
        $this->assertFalse($sample->hasLabelNames());
    }
}
