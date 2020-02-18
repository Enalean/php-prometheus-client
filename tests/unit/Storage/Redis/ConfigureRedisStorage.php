<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\Redis;

use Enalean\Prometheus\Storage\RedisStore;
use Redis;
use function getenv;

trait ConfigureRedisStorage
{
    /** @var RedisStore */
    public $adapter;

    private function getRedisClient() : Redis
    {
        $redisClient = new Redis();
        $redisClient->connect(
            (string) getenv('REDIS_HOST'),
            (int) getenv('REDIS_PORT')
        );

        return $redisClient;
    }

    public function configureAdapter() : void
    {
        $this->adapter = new RedisStore($this->getRedisClient());
        $this->adapter->flush();
    }

    protected function getStorage() : RedisStore
    {
        return new RedisStore($this->getRedisClient());
    }
}
