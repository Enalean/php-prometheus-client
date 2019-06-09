<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\APCU;

use Enalean\PrometheusTest\Storage\CollectorRegistryBaseTest;

/**
 * @requires extension apcu
 * @covers Enalean\Prometheus\Registry\CollectorRegistry
 * @covers Enalean\Prometheus\Storage\APCUStore
 */
final class CollectorRegistryTest extends CollectorRegistryBaseTest
{
    use ConfigureAPCUStorage;
}
