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
    public function testSampleHoldValuesWithoutModification(): void
    {
        $name        = 'name';
        $value       = 0.1;
        $labelNames  = ['labelA', 'labelB'];
        $labelValues = ['valueA', 'valueB'];

        $sample = new Sample($name, $value, $labelNames, $labelValues);
        self::assertSame($name, $sample->getName());
        self::assertSame($value, $sample->getValue());
        self::assertSame($labelNames, $sample->getLabelNames());
        self::assertSame($labelValues, $sample->getLabelValues());
        self::assertTrue($sample->hasLabelNames());
    }

    public function testSampleDetectWhenNoLabelNamesAreGiven(): void
    {
        $sample = new Sample('name', 0.1, [], []);
        self::assertFalse($sample->hasLabelNames());
    }
}
