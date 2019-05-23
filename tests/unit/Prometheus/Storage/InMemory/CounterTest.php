<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\InMemory;

use Prometheus\Storage\InMemoryStore;
use Test\Prometheus\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
final class CounterTest extends CounterBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new InMemoryStore();
        $this->adapter->flush();
    }
}
