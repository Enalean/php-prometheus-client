<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Test\Prometheus\Storage\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 * @covers Prometheus\Storage\APCUStore
 */
final class CounterTest extends CounterBaseTest
{
    use ConfigureAPCUStorage;
}
