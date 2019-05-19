<?php

declare(strict_types=1);

namespace Test\Prometheus\APCU;

use Prometheus\Storage\APCU;
use Test\Prometheus\CollectorRegistryBaseTest;

/**
 * @requires extension apcu
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCU();
        $this->adapter->flushAPC();
    }
}
