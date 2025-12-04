<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\InMemory;

use Enalean\Prometheus\Storage\InMemoryStore;
use Enalean\PrometheusTest\Storage\GaugeTestBase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
#[CoversClass(InMemoryStore::class)]
final class GaugeTest extends GaugeTestBase
{
    use ConfigureInMemoryStorage;
}
