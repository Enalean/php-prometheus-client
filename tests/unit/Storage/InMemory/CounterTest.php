<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\InMemory;

use Enalean\PrometheusTest\Storage\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @covers Enalean\Prometheus\Storage\InMemoryStore
 */
final class CounterTest extends CounterBaseTest
{
    use ConfigureInMemoryStorage;
}
