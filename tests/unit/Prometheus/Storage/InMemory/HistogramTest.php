<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\InMemory;

use Test\Prometheus\Storage\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @covers Prometheus\Storage\InMemoryStore
 */
final class HistogramTest extends HistogramBaseTest
{
    use ConfigureInMemoryStorage;
}
