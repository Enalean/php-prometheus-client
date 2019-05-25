<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Prometheus\Storage\APCUStore;
use Test\Prometheus\Storage\CollectorRegistryBaseTest;

/**
 * @requires extension apcu
 * @covers Prometheus\Registry\CollectorRegistry
 * @covers Prometheus\Storage\APCUStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCUStore();
        $this->adapter->flush();
    }
}
