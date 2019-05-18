<?php

declare(strict_types=1);

namespace Test\Prometheus\Redis;

use Prometheus\Storage\Redis;
use Test\Prometheus\HistogramBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 */
class HistogramTest extends HistogramBaseTest
{
    public function configureAdapter() : void
    {
        $this->adapter = new Redis(['host' => REDIS_HOST]);
        $this->adapter->flushRedis();
    }
}
