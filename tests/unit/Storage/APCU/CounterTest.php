<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\APCU;

use Enalean\Prometheus\Storage\APCUStore;
use Enalean\PrometheusTest\Storage\CounterTestBase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 */
#[CoversClass(APCUStore::class)]
final class CounterTest extends CounterTestBase
{
    use ConfigureAPCUStorage;
}
