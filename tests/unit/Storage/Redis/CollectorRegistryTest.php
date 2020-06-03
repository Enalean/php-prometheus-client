<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\Redis;

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Renderer\RenderTextFormat;
use Enalean\Prometheus\Storage\RedisStore;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use Enalean\PrometheusTest\Storage\CollectorRegistryBaseTest;

/**
 * @requires extension redis
 * @covers \Enalean\Prometheus\Registry\CollectorRegistry
 * @covers \Enalean\Prometheus\Storage\RedisStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    use ConfigureRedisStorage;

    /**
     * @before
     * @after
     */
    public function flushRedis(): void
    {
        $this->getRedisClient()->flushDB();
    }

    /**
     * @test
     */
    public function itShouldOnlyFlushMetricData(): void
    {
        $redis = $this->getRedisClient();
        $redis->set('foo', 'bar');

        $storage  = $this->getStorage();
        $registry = new CollectorRegistry($storage);

        $counter = $registry->registerCounter(MetricName::fromNamespacedName('test', 'some_counter'), 'it increases', MetricLabelNames::fromNames('type'));
        $counter->incBy(6, 'blue');
        $counterRedisKey = 'PROMETHEUS_counter' . RedisStore::PROMETHEUS_METRIC_KEYS_SUFFIX;
        self::assertEquals(['PROMETHEUS_:counter:test_some_counter'], $redis->sMembers($counterRedisKey));

        $gauge = $registry->registerGauge(MetricName::fromNamespacedName('test', 'some_gauge'), 'this is for testing', MetricLabelNames::fromNames('foo'));
        $gauge->set(35, 'bar');
        $gaugeRedisKey = 'PROMETHEUS_gauge' . RedisStore::PROMETHEUS_METRIC_KEYS_SUFFIX;
        self::assertEquals(['PROMETHEUS_:gauge:test_some_gauge'], $redis->sMembers($gaugeRedisKey));

        $histogram = $registry->registerHistogram(
            MetricName::fromNamespacedName('test', 'some_histogram'),
            'this is for testing',
            HistogramLabelNames::fromNames('foo', 'bar'),
            [0.1, 1, 5, 10]
        );
        $histogram->observe(2, 'cat', 'meow');
        $histogramRedisKey = 'PROMETHEUS_histogram' . RedisStore::PROMETHEUS_METRIC_KEYS_SUFFIX;
        self::assertEquals(['PROMETHEUS_:histogram:test_some_histogram'], $redis->sMembers($histogramRedisKey));

        $storage->flush();

        self::assertEquals('bar', $redis->get('foo'));

        self::assertEquals([], $redis->sMembers($counterRedisKey));
        self::assertFalse($redis->get('PROMETHEUS_:counter:test_some_counter'));
        self::assertEquals([], $redis->sMembers($gaugeRedisKey));
        self::assertFalse($redis->get('PROMETHEUS_:gauge:test_some_gauge'));
        self::assertEquals([], $redis->sMembers($histogramRedisKey));
        self::assertFalse($redis->get('PROMETHEUS_:histogram:test_some_histogram'));

        self::assertEquals("\n", (new RenderTextFormat())->render($registry->getMetricFamilySamples()));

        $redis->del('foo');
    }
}
