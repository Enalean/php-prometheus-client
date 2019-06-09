<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\Redis;

use Enalean\PrometheusTest\Storage\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 * @covers Enalean\Prometheus\Storage\RedisStore
 */
final class HistogramTest extends HistogramBaseTest
{
    use ConfigureRedisStorage;
}
