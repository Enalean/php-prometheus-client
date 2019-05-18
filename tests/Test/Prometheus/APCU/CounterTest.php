<?php

declare(strict_types=1);

namespace Test\Prometheus\APCU;

use Prometheus\Storage\APCU;
use Test\Prometheus\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 */
class CounterTest extends CounterBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCU();
        $this->adapter->flushAPC();
    }
}
