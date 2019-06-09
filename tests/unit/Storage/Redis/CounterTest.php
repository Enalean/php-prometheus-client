<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\Redis;

use Enalean\PrometheusTest\Storage\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 * @covers Enalean\Prometheus\Storage\RedisStore
 */
final class CounterTest extends CounterBaseTest
{
    use ConfigureRedisStorage;
}
