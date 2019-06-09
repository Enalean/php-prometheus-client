<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\APCU;

use Enalean\PrometheusTest\Storage\GaugeBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 * @covers Enalean\Prometheus\Storage\APCUStore
 */
final class GaugeTest extends GaugeBaseTest
{
    use ConfigureAPCUStorage;
}
