<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Value\HistogramLabelNames;
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

$histogram = $registry->registerHistogram(
    MetricName::fromNamespacedName('test', 'some_histogram'),
    'it observes',
    HistogramLabelNames::fromNames('type'),
    [0.1, 1, 2, 3.5, 4, 5, 6, 7, 8, 9]
);
$histogram->observe((float) $_GET['c'], 'blue');

echo "OK\n";
