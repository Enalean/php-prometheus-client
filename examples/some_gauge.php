<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Prometheus\Registry\CollectorRegistry;

error_log('c=' . $_GET['c']);

$adapter = $_GET['adapter'] ?? '';

if ($adapter === 'redis') {
    $redis_client = new Redis();
    $redis_client->connect($_SERVER['REDIS_HOST'] ?? '127.0.0.1');
    $adapter = new Prometheus\Storage\RedisStore($redis_client);
} elseif ($adapter === 'apcu') {
    $adapter = new Prometheus\Storage\APCUStore();
} elseif ($adapter === 'in-memory') {
    $adapter = new Prometheus\Storage\InMemoryStore();
}
$registry = new CollectorRegistry($adapter);

$gauge = $registry->registerGauge('test', 'some_gauge', 'it sets', ['type']);
$gauge->set((float) $_GET['c'], ['blue']);

echo "OK\n";
