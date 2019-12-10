<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Renderer\RenderTextFormat;

$adapter = (string) ($_GET['adapter'] ?? '');

if ($adapter === 'redis') {
    $redisClient = new Redis();
    $redisClient->connect((string) ($_SERVER['REDIS_HOST'] ?? '127.0.0.1'));
    $adapter = new Enalean\Prometheus\Storage\RedisStore($redisClient);
} elseif ($adapter === 'apcu') {
    $adapter = new Enalean\Prometheus\Storage\APCUStore();
} elseif ($adapter === 'in-memory') {
    $adapter = new Enalean\Prometheus\Storage\InMemoryStore();
} else {
    $adapter = new Enalean\Prometheus\Storage\NullStore();
}

$registry = new CollectorRegistry($adapter);
$renderer = new RenderTextFormat();
$result   = $renderer->render($registry->getMetricFamilySamples());

header('Content-type: ' . $renderer->getMimeType());
echo $result;
