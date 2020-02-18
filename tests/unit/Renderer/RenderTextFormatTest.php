<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Renderer;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Renderer\RenderTextFormat;
use Enalean\Prometheus\Sample;
use PHPUnit\Framework\TestCase;

final class RenderTextFormatTest extends TestCase
{
    /**
     * @covers Enalean\Prometheus\Renderer\RenderTextFormat
     */
    public function testRendering() : void
    {
        $renderer = new RenderTextFormat();

        $metrics = [
            new MetricFamilySamples(
                'B',
                'counter',
                'test B',
                ['label1', 'label2'],
                [new Sample('test_some_metric_counter', 1, ['label3', 'label4'], ['value1', 'value\\2', "value\n3", 'value"4'])]
            ),
            new MetricFamilySamples(
                'A',
                'histogram',
                'test A',
                [],
                [
                    new Sample('test_some_metric_bucket', 0, ['le'], ['0.005']),
                    new Sample('test_some_metric_bucket', 0, ['le'], ['0.01']),
                ]
            ),
            new MetricFamilySamples(
                'C',
                'gauge',
                'test C',
                [],
                [new Sample('test_some_metric_gauge', 0, [], [])]
            ),
        ];

        self::assertEquals(
            '# HELP A test A
# TYPE A histogram
test_some_metric_bucket{le="0.005"} 0
test_some_metric_bucket{le="0.01"} 0
# HELP B test B
# TYPE B counter
test_some_metric_counter{label1="value1",label2="value\\\\2",label3="value\n3",label4="value\"4"} 1
# HELP C test C
# TYPE C gauge
test_some_metric_gauge 0
',
            $renderer->render($metrics)
        );
    }

    /**
     * @covers Enalean\Prometheus\Renderer\RenderTextFormat::getMimeType
     */
    public function testType() : void
    {
        $renderer = new RenderTextFormat();
        self::assertStringContainsString('text/plain', $renderer->getMimeType());
    }
}
