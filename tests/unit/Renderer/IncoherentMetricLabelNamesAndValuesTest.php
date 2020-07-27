<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Renderer;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Renderer\IncoherentMetricLabelNamesAndValues;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Enalean\Prometheus\Renderer\IncoherentMetricLabelNamesAndValues
 */
final class IncoherentMetricLabelNamesAndValuesTest extends TestCase
{
    public function testHasACoherentErrorMessage(): void
    {
        $metrics = new MetricFamilySamples('some_name', 'counter', 'Help phrase', [], []);

        $exception = new IncoherentMetricLabelNamesAndValues($metrics, 2, 3);

        self::assertStringStartsWith('Cannot render a sample of the metric some_name, got 2 names for 3 values', $exception->getMessage());
        self::assertSame($metrics, $exception->getMetric());
    }

    public function testCannotInstantiateExceptionIfLabelNamesAndValuesAppearToBeCoherent(): void
    {
        $metrics = new MetricFamilySamples('some_name', 'counter', 'Help phrase', [], []);

        $this->expectException(LogicException::class);
        new IncoherentMetricLabelNamesAndValues($metrics, 2, 2);
    }
}
