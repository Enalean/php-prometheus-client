<?php

declare(strict_types=1);

namespace Test\Prometheus\InMemory;

use Prometheus\Storage\InMemoryStore;
use Test\Prometheus\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
final class HistogramTest extends HistogramBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new InMemoryStore();
        $this->adapter->flushMemory();
    }
}
