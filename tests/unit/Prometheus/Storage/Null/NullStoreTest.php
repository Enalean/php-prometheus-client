<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\Null;

use PHPUnit\Framework\TestCase;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\Storage\NullStore;
use Prometheus\Value\MetricName;

/**
 * @covers Prometheus\Storage\NullStore
 */
final class NullStoreTest extends TestCase
{
    public function testNothingIsStored() : void
    {
        $null_store = new NullStore();

        $counter = new Counter($null_store, MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing');
        $counter->inc();
        $gauge = new Gauge($null_store, MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing');
        $gauge->set(12.1);
        $gauge->incBy(2);
        $histogram = new Histogram(
            $null_store,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing'
        );
        $histogram->observe(123);

        $this->assertEmpty($null_store->collect());
        $null_store->flush();
        $this->assertEmpty($null_store->collect());
    }
}
