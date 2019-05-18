<?php

declare(strict_types=1);

namespace Test\Prometheus\InMemory;

use Prometheus\Storage\InMemory;
use Test\Prometheus\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
class CounterTest extends CounterBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new InMemory();
        $this->adapter->flushMemory();
    }
}
