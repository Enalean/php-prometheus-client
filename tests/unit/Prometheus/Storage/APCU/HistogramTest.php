<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Prometheus\Storage\APCUStore;
use Test\Prometheus\Storage\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 * @covers Prometheus\Storage\APCUStore
 */
final class HistogramTest extends HistogramBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCUStore();
        $this->adapter->flush();
    }
}
