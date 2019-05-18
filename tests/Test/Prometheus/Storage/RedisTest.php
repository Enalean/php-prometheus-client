<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use PHPUnit\Framework\TestCase;
use Prometheus\Exception\StorageException;

/**
 * @requires extension redis
 */
class RedisTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldThrowAnExceptionOnConnectionFailure() : void
    {
        $redis = new Redis(['host' => 'doesntexist.test']);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage("Can't connect to Redis server");
        $redis->flushRedis();
    }
}
