<?php

declare(strict_types=1);

namespace Test\Prometheus\APCU;

use Prometheus\Storage\APCUStore;
use Test\Prometheus\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 */
final class HistogramTest extends HistogramBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCUStore();
        $this->adapter->flush();
    }
}
