<?php

declare(strict_types=1);

namespace Test\Prometheus\Redis;

use Test\Prometheus\GaugeBaseTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 */
final class GaugeTest extends GaugeBaseTest
{
    use ConfigureRedisStorage;
}
