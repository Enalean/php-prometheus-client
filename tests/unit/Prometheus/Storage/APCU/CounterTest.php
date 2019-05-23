<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Prometheus\Storage\APCUStore;
use Test\Prometheus\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 */
final class CounterTest extends CounterBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCUStore();
        $this->adapter->flush();
    }
}
