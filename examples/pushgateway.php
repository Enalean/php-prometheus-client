<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Enalean\Prometheus\PushGateway\PSR18Pusher;
use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Storage\RedisStore;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

$adapter = (string) ($_GET['adapter'] ?? '');

if ($adapter === 'redis') {
    $redis_client = new Redis();
    $redis_client->connect((string) ($_SERVER['REDIS_HOST'] ?? '127.0.0.1'));
    $adapter = new RedisStore($redis_client);
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
    MetricLabelNames::fromNames('type')
);
$counter->incBy(6, 'blue');

$pusher = new PSR18Pusher('192.168.59.100:9091', Psr18ClientDiscovery::find(), Psr17FactoryDiscovery::findRequestFactory(), Psr17FactoryDiscovery::findStreamFactory());
$pusher->push($registry, 'my_job', ['instance' => 'foo']);
