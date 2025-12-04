<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\InMemory;

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Storage\InMemoryStore;
use Enalean\PrometheusTest\Storage\CollectorRegistryTestBase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryStore::class)]
#[CoversClass(CollectorRegistry::class)]
final class CollectorRegistryTest extends CollectorRegistryTestBase
{
    use ConfigureInMemoryStorage;
}
