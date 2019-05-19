<?php

declare(strict_types=1);

namespace Test\Prometheus\Redis;

use Prometheus\Storage\Redis;
use Test\Prometheus\CounterBaseTest;
use function getenv;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 */
class CounterTest extends CounterBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new Redis(['host' => getenv('REDIS_HOST')]);
        $this->adapter->flushRedis();
    }
}
