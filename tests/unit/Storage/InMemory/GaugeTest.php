<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\InMemory;

use Enalean\PrometheusTest\Storage\GaugeBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @covers Enalean\Prometheus\Storage\InMemoryStore
 */
final class GaugeTest extends GaugeBaseTest
{
    use ConfigureInMemoryStorage;
}
