<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Prometheus\Registry\CollectorRegistry;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

$adapter = $_GET['adapter'];

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

$counter = $registry->registerCounter(
    MetricName::fromNamespacedName('test', 'some_counter'),
    'it increases',
    MetricLabelNames::fromNames('type')
);
$counter->incBy((int) $_GET['c'], 'blue');

echo "OK\n";
