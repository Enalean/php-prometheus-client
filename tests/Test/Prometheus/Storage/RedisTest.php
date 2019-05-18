<?php


namespace Prometheus\Storage;

/**
 * @requires extension redis
 */
class RedisTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function itShouldThrowAnExceptionOnConnectionFailure()
    {
        $redis = new Redis(array('host' => 'doesntexist.test'));

        $this->expectException(\Prometheus\Exception\StorageException::class);
        $this->expectExceptionMessage("Can't connect to Redis server");
        $redis->flushRedis();
    }

}
