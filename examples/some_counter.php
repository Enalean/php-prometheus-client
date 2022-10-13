<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;

$adapter = $_GET['adapter'] ?? '';

if ($adapter === 'redis') {
    $redisClient = new Redis();
    $redisClient->connect($_SERVER['REDIS_HOST'] ?? '127.0.0.1');
    $adapter = new Enalean\Prometheus\Storage\RedisStore($redisClient);
} elseif ($adapter === 'apcu') {
    $adapter = new Enalean\Prometheus\Storage\APCUStore();
} elseif ($adapter === 'in-memory') {
    $adapter = new Enalean\Prometheus\Storage\InMemoryStore();
} else {
    $adapter = new Enalean\Prometheus\Storage\NullStore();
}

$registry = new CollectorRegistry($adapter);

$counter = $registry->registerCounter(
    MetricName::fromNamespacedName('test', 'some_counter'),
    'it increases',
    MetricLabelNames::fromNames('type'),
);
$counter->incBy(is_numeric($_GET['c']) ? (float) $_GET['c'] : 0, 'blue');

echo "OK\n";
