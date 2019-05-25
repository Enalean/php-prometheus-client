<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\InMemory;

use Test\Prometheus\Storage\CollectorRegistryBaseTest;

/**
 * @covers Prometheus\Registry\CollectorRegistry
 * @covers Prometheus\Storage\InMemoryStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    use ConfigureInMemoryStorage;
}
