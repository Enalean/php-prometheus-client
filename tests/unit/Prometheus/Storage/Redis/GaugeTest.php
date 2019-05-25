<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\Redis;

use Test\Prometheus\GaugeBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 * @covers Prometheus\Storage\RedisStore
 */
final class GaugeTest extends GaugeBaseTest
{
    use ConfigureRedisStorage;
}
