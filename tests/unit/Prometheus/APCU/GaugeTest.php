<?php

declare(strict_types=1);

namespace Test\Prometheus\APCU;

use Prometheus\Storage\APCUStore;
use Test\Prometheus\GaugeBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension apcu
 */
final class GaugeTest extends GaugeBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new APCUStore();
        $this->adapter->flush();
    }
}
