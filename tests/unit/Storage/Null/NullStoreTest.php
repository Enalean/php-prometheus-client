<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\Null;

use Enalean\Prometheus\Counter;
use Enalean\Prometheus\Gauge;
use Enalean\Prometheus\Histogram;
use Enalean\Prometheus\Storage\NullStore;
use Enalean\Prometheus\Value\MetricName;
use PHPUnit\Framework\TestCase;

/** @covers Enalean\Prometheus\Storage\NullStore */
final class NullStoreTest extends TestCase
{
    public function testNothingIsStored(): void
    {
        $nullStore = new NullStore();

        $counter = new Counter($nullStore, MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing');
        $counter->inc();
        $gauge = new Gauge($nullStore, MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing');
        $gauge->set(12.1);
        $gauge->incBy(2);
        $histogram = new Histogram(
            $nullStore,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
        );
        $histogram->observe(123);

        self::assertEmpty($nullStore->collect());
        $nullStore->flush();
        self::assertEmpty($nullStore->collect());
    }
}
