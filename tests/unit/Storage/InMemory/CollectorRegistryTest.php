<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\InMemory;

use Enalean\PrometheusTest\Storage\CollectorRegistryBaseTest;

/**
 * @covers Enalean\Prometheus\Registry\CollectorRegistry
 * @covers Enalean\Prometheus\Storage\InMemoryStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    use ConfigureInMemoryStorage;
}
