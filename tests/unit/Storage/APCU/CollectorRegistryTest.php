<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\APCU;

use APCUIterator;
use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Renderer\RenderTextFormat;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use Enalean\PrometheusTest\Storage\CollectorRegistryBaseTest;

use function apcu_clear_cache;
use function apcu_delete;
use function apcu_fetch;
use function apcu_store;

/**
 * @requires extension apcu
 * @covers \Enalean\Prometheus\Registry\CollectorRegistry
 * @covers \Enalean\Prometheus\Storage\APCUStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    use ConfigureAPCUStorage;

    /**
     * @before
     * @after
     */
    public function flushAPCu(): void
    {
        apcu_clear_cache();
    }

    public function testShouldOnlyFlushMetricData(): void
    {
        apcu_store('foo', 'bar');

        $storage  = $this->getStorage();
        $registry = new CollectorRegistry($storage);

        $counter = $registry->registerCounter(MetricName::fromNamespacedName('test', 'some_counter'), 'it increases', MetricLabelNames::fromNames('type'));
        $counter->incBy(6, 'blue');

        $gauge = $registry->registerGauge(MetricName::fromNamespacedName('test', 'some_gauge'), 'this is for testing', MetricLabelNames::fromNames('foo'));
        $gauge->set(35, 'bar');

        $histogram = $registry->registerHistogram(
            MetricName::fromNamespacedName('test', 'some_histogram'),
            'this is for testing',
            HistogramLabelNames::fromNames('foo', 'bar'),
            [0.1, 1, 5, 10],
        );
        $histogram->observe(2, 'cat', 'meow');

        self::assertGreaterThan(1, (new APCUIterator('/.*/'))->getTotalCount());

        $storage->flush();

        self::assertEquals(1, (new APCUIterator('/.*/'))->getTotalCount());
        self::assertEquals('bar', apcu_fetch('foo'));
        self::assertEquals("\n", (new RenderTextFormat())->render($registry->getMetricFamilySamples()));

        apcu_delete('foo');
    }
}
