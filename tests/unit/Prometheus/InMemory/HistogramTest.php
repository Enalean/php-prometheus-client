<?php

declare(strict_types=1);

namespace Test\Prometheus\InMemory;

use Prometheus\Storage\InMemory;
use Test\Prometheus\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
final class HistogramTest extends HistogramBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new InMemory();
        $this->adapter->flushMemory();
    }
}
