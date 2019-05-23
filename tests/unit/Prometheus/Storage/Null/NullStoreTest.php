<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\Null;

use PHPUnit\Framework\TestCase;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\Storage\NullStore;

final class NullStoreTest extends TestCase
{
    public function testNothingIsStored() : void
    {
        $null_store = new NullStore();

        $gauge = new Counter($null_store, 'test', 'some_metric', 'this is for testing');
        $gauge->inc();
        $gauge = new Gauge($null_store, 'test', 'some_metric', 'this is for testing');
        $gauge->set(12.1);
        $histogram = new Histogram(
            $null_store,
            'test',
            'some_metric',
            'this is for testing'
        );
        $histogram->observe(123);

        $this->assertEmpty($null_store->collect());
    }
}
