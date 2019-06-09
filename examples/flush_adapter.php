<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$adapter = (string) $_GET['adapter'] ?? '';

$storage = null;

if ($adapter === 'redis') {
    $redis_client = new Redis();
    $redis_client->connect((string) $_SERVER['REDIS_HOST'] ?? '127.0.0.1');

    $storage = new Enalean\Prometheus\Storage\RedisStore($redis_client);
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
