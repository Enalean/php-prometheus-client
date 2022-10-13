<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Value\HistogramLabelNames;
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

$histogram = $registry->registerHistogram(
    MetricName::fromNamespacedName('test', 'some_histogram'),
    'it observes',
    HistogramLabelNames::fromNames('type'),
    [0.1, 1, 2, 3.5, 4, 5, 6, 7, 8, 9],
);
$histogram->observe(is_numeric($_GET['c']) ? (float) $_GET['c'] : 0, 'blue');

echo "OK\n";
