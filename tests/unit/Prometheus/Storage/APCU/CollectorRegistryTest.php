<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Prometheus\Storage\APCUStore;
use Test\Prometheus\CollectorRegistryBaseTest;

/**
 * @requires extension apcu
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCUStore();
        $this->adapter->flush();
    }
}
