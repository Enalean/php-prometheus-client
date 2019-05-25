<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\InMemory;

use Prometheus\Storage\InMemoryStore;
use Test\Prometheus\Storage\CollectorRegistryBaseTest;

/**
 * @covers Prometheus\Registry\CollectorRegistry
 * @covers Prometheus\Storage\InMemoryStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new InMemoryStore();
        $this->adapter->flush();
    }
}
