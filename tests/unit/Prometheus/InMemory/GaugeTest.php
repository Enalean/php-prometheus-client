<?php

declare(strict_types=1);

namespace Test\Prometheus\InMemory;

use Prometheus\Storage\InMemoryStore;
use Test\Prometheus\GaugeBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
final class GaugeTest extends GaugeBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new InMemoryStore();
        $this->adapter->flush();
    }
}
