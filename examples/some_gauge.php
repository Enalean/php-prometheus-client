<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;

$adapter = (string) ($_GET['adapter'] ?? '');

if ($adapter === 'redis') {
    $redis_client = new Redis();
    $redis_client->connect((string) ($_SERVER['REDIS_HOST'] ?? '127.0.0.1'));
    $adapter = new Enalean\Prometheus\Storage\RedisStore($redis_client);
} elseif ($adapter === 'apcu') {
    $adapter = new Enalean\Prometheus\Storage\APCUStore();
} elseif ($adapter === 'in-memory') {
    $adapter = new Enalean\Prometheus\Storage\InMemoryStore();
} else {
    $adapter = new Enalean\Prometheus\Storage\NullStore();
}
$registry = new CollectorRegistry($adapter);

$gauge = $registry->registerGauge(
    MetricName::fromNamespacedName('test', 'some_gauge'),
    'it sets',
    MetricLabelNames::fromNames('type')
);
$gauge->set((float) $_GET['c'], 'blue');

echo "OK\n";
