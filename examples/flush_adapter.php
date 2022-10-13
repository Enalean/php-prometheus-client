<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$adapter = $_GET['adapter'] ?? '';

$storage = null;

if ($adapter === 'redis') {
    $redisClient = new Redis();
    $redisClient->connect($_SERVER['REDIS_HOST'] ?? '127.0.0.1');

    $storage = new Enalean\Prometheus\Storage\RedisStore($redisClient);
}

if ($adapter === 'apcu') {
    $storage = new Enalean\Prometheus\Storage\APCUStore();
}

if ($adapter === 'in-memory') {
    $storage = new Enalean\Prometheus\Storage\InMemoryStore();
}

if ($storage !== null) {
    $storage->flush();
}
