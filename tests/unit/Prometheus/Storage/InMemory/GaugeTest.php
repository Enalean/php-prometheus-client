<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\InMemory;

use Prometheus\Storage\InMemoryStore;
use Test\Prometheus\GaugeBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @covers Prometheus\Storage\InMemoryStore
 */
final class GaugeTest extends GaugeBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new InMemoryStore();
        $this->adapter->flush();
    }
}
