<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$adapter = $_GET['adapter'] ?? '';

$storage = null;

if ($adapter === 'redis') {
    $redis_client = new Redis();
    $redis_client->connect($_SERVER['REDIS_HOST'] ?? '127.0.0.1');

    $storage = new Prometheus\Storage\RedisStore($redis_client);
}

if ($adapter === 'apcu') {
    $storage = new Prometheus\Storage\APCUStore();
}

if ($adapter === 'in-memory') {
    $storage = new Prometheus\Storage\InMemoryStore();
}

if ($storage !== null) {
    $storage->flush();
}
