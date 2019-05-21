<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Prometheus\Registry\CollectorRegistry;
use Prometheus\Renderer\RenderTextFormat;

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
$renderer = new RenderTextFormat();
$result   = $renderer->render($registry->getMetricFamilySamples());

header('Content-type: ' . $renderer->getMimeType());
echo $result;
