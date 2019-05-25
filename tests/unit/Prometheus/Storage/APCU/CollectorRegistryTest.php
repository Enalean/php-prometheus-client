<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Test\Prometheus\Storage\CollectorRegistryBaseTest;

/**
 * @requires extension apcu
 * @covers Prometheus\Registry\CollectorRegistry
 * @covers Prometheus\Storage\APCUStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    use ConfigureAPCUStorage;
}
