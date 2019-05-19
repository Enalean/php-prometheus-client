<?php

declare(strict_types=1);

namespace Test\Prometheus\Redis;

use Prometheus\Storage\Redis;
use function getenv;

trait ConfigureRedisStorage
{
    /** @var Redis */
    public $adapter;

    private function getRedisClient() : \Redis
    {
        $redisClient = new \Redis();
        $redisClient->connect(getenv('REDIS_HOST') ?: '');

        return $redisClient;
    }

    public function configureAdapter() : void
    {
        $this->adapter = new Redis($this->getRedisClient());
        $this->adapter->flushRedis();
    }
}
