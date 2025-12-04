<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\Redis;

use Enalean\Prometheus\Storage\RedisStore;
use Enalean\PrometheusTest\Storage\HistogramTestBase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 *
 * @requires extension redis
 */
#[CoversClass(RedisStore::class)]
final class HistogramTest extends HistogramTestBase
{
    use ConfigureRedisStorage;
}
