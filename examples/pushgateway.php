<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Prometheus\CollectorRegistry;
use Prometheus\PushGateway\PSR18Pusher;
use Prometheus\Storage\Redis;

$adapter = $_GET['adapter'] ?? '';

if ($adapter === 'redis') {
    $redis_client = new \Redis();
    $redis_client->connect($_SERVER['REDIS_HOST'] ?? '127.0.0.1');
    $adapter = new Redis($redis_client);
} elseif ($adapter === 'apcu') {
    $adapter = new Prometheus\Storage\APCU();
} elseif ($adapter === 'in-memory') {
    $adapter = new Prometheus\Storage\InMemory();
}

$registry = new CollectorRegistry($adapter);

$counter = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
$counter->incBy(6, ['blue']);

$pusher = new PSR18Pusher('192.168.59.100:9091', Psr18ClientDiscovery::find(), Psr17FactoryDiscovery::findRequestFactory(), Psr17FactoryDiscovery::findStreamFactory());
$pusher->push($registry, 'my_job', ['instance' => 'foo']);
