<?php

declare(strict_types=1);

namespace Test\Prometheus\Redis;

use Test\Prometheus\CounterBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 */
class CounterTest extends CounterBaseTest
{
    use ConfigureRedisStorage;
}
