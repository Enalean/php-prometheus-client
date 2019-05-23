<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\Redis;

use Test\Prometheus\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 */
final class CounterTest extends CounterBaseTest
{
    use ConfigureRedisStorage;
}
