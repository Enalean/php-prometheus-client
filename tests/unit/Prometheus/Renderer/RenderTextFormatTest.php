<?php

declare(strict_types=1);

namespace Test\Prometheus\Renderer;

use PHPUnit\Framework\TestCase;
use Prometheus\MetricFamilySamples;
use Prometheus\Renderer\RenderTextFormat;
use Prometheus\Sample;

final class RenderTextFormatTest extends TestCase
{
    public function testRendering() : void
    {
        $renderer = new RenderTextFormat();

        $metrics = [
            new MetricFamilySamples(
                'B',
                'counter',
                'test B',
                ['label1', 'label2'],
                [new Sample('test_some_metric_counter', 1, ['label3'], ['value1', 'value\\2', "value\n3"])]
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
        ];

        $this->assertEquals(
            '# HELP A test A
# TYPE A histogram
test_some_metric_bucket{le="0.005"} 0
test_some_metric_bucket{le="0.01"} 0
# HELP B test B
# TYPE B counter
test_some_metric_counter{label1="value1",label2="value\\\\2",label3="value\n3"} 1
',
            $renderer->render($metrics)
        );
    }

    public function testType() : void
    {
        $renderer = new RenderTextFormat();
        $this->assertStringContainsString('text/plain', $renderer->getMimeType());
    }
}
