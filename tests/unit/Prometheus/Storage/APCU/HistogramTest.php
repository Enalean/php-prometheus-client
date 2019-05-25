<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Test\Prometheus\Storage\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 * @covers Prometheus\Storage\APCUStore
 */
final class HistogramTest extends HistogramBaseTest
{
    use ConfigureAPCUStorage;
}
