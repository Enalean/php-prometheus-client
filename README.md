# A prometheus client library written in PHP

[![Build Status](https://github.com/Enalean/php-prometheus-client/workflows/CI/badge.svg)](https://github.com/Enalean/php-prometheus-client/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Enalean/php-prometheus-client/branch/master/graph/badge.svg)](https://codecov.io/gh/Enalean/php-prometheus-client)
[![Type Coverage](https://shepherd.dev/github/enalean/php-prometheus-client/coverage.svg)](https://shepherd.dev/github/enalean/php-prometheus-client)

This library uses Redis or APCu to do the client side aggregation.
If using Redis, we recommend to run a local Redis instance next to your PHP workers.

## How does it work?

Usually PHP worker processes don't share any state.
You can pick from three adapters.
Redis, APC or an in memory adapter.
While the first needs a separate binary running, the second just needs the [APC](https://pecl.php.net/package/APCU) extension to be installed. If you don't need persistent metrics between requests (e.g. a long running cron job or script) the in memory adapter might be suitable to use.

## Usage

A simple counter:
```php
$storage = new \Enalean\Prometheus\Storage\InMemoryStore();
(new \Enalean\Prometheus\Registry\CollectorRegistry($storage))
    ->getOrRegisterCounter(\Enalean\Prometheus\Value\MetricName::fromName('some_quick_counter'), 'just a quick measurement')
    ->inc();
```

Write some enhanced metrics:
```php
$storage = new \Enalean\Prometheus\Storage\InMemoryStore();
$registry = new \Enalean\Prometheus\Registry\CollectorRegistry($storage);

$counter = $registry->getOrRegisterCounter(
    \Enalean\Prometheus\Value\MetricName::fromNamespacedName('test', 'some_counter'),
    'it increases',
    \Enalean\Prometheus\Value\MetricLabelNames::fromNames('type')
);
$counter->incBy(3, 'blue');

$gauge = $registry->getOrRegisterGauge(
    \Enalean\Prometheus\Value\MetricName::fromNamespacedName('test', 'some_gauge'),
    'it sets',
    \Enalean\Prometheus\Value\MetricLabelNames::fromNames('type')
);
$gauge->set(2.5, 'blue');

$histogram = $registry->getOrRegisterHistogram(
    \Enalean\Prometheus\Value\MetricName::fromNamespacedName('test', 'some_histogram'),
    'it observes',
    \Enalean\Prometheus\Value\HistogramLabelNames::fromNames('type'),
    [0.1, 1, 2, 3.5, 4, 5, 6, 7, 8, 9]
);
$histogram->observe(3.5, 'blue');
```

Manually register and retrieve metrics (these steps are combined in the `getOrRegister...` methods):
```php
$storage = new \Enalean\Prometheus\Storage\InMemoryStore();
$registry = new \Enalean\Prometheus\Registry\CollectorRegistry($storage);

$counterA = $registry->registerCounter(
    \Enalean\Prometheus\Value\MetricName::fromNamespacedName('test', 'some_counter'),
    'it increases',
    \Enalean\Prometheus\Value\MetricLabelNames::fromNames('type')
);
$counterA->incBy(3, 'blue');

// once a metric is registered, it can be retrieved using e.g. getCounter:
$counterB = $registry->getCounter(\Enalean\Prometheus\Value\MetricName::fromNamespacedName('test', 'some_counter'));
$counterB->incBy(2, 'red');
```

Expose the metrics:
```php
$storage = new \Enalean\Prometheus\Storage\InMemoryStore();
$registry = new \Enalean\Prometheus\Registry\CollectorRegistry($storage);

$renderer = new \Enalean\Prometheus\Renderer\RenderTextFormat();

header('Content-type: ' . $renderer->getMimeType());
echo $renderer->render($registry->getMetricFamilySamples());
```

Using the Redis storage:
```php
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$storage = new \Enalean\Prometheus\Storage\RedisStore();
$registry = new CollectorRegistry($storage);

$counter = $registry->registerCounter(
    \Enalean\Prometheus\Value\MetricName::fromNamespacedName('test', 'some_counter'),
    'it increases',
    \Enalean\Prometheus\Value\MetricLabelNames::fromNames('type')
);
$counter->incBy(3, 'blue');

$renderer = new \Enalean\Prometheus\Renderer\RenderTextFormat();
$result = $renderer->render($registry->getMetricFamilySamples());
```

Using the APCu storage:
```php
$storage = new \Enalean\Prometheus\Storage\APCUStore();
$registry = new CollectorRegistry($storage);

$counter = $registry->registerCounter(
    \Enalean\Prometheus\Value\MetricName::fromNamespacedName('test', 'some_counter'),
    'it increases',
    \Enalean\Prometheus\Value\MetricLabelNames::fromNames('type')
);
$counter->incBy(3, 'blue');

$renderer = new \Enalean\Prometheus\Renderer\RenderTextFormat();
$result = $renderer->render($registry->getMetricFamilySamples());
```

Also look at the [examples](examples).

## Development

### Dependencies

* PHP 7.2+
* PHP Redis extension
* PHP APCu extension
* [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
* Redis

Start a Redis instance:
```
docker-compose up redis
```

### Tests
Run the tests:
```
composer install

# when Redis is not listening on localhost:
# export REDIS_HOST=192.168.59.100
./vendor/bin/phpunit
# You might need to enable APCu on the CLI
php -d apc.enable_cli=1 vendor/bin/phpunit
```

Run the tests with mutation testing:
```
# when Redis is not listening on localhost:
# export REDIS_HOST=192.168.59.100
./vendor/bin/infection --initial-tests-php-options="-d apc.enable_cli=1"
```

Run the static analysis:
```
vendor/bin/psalm
```

Check conformance with the coding standards:
```
vendor/bin/phpcs
```

### Black box testing

Just start the nginx, fpm & Redis setup with docker-compose:
```
docker-compose up
```
Pick the adapter you want to test.

```
docker-compose exec phpunit env ADAPTER=apcu vendor/bin/phpunit --testsuite=functionnal
docker-compose exec phpunit env ADAPTER=redis vendor/bin/phpunit --testsuite=functionnal
```

## Acknowledgment

This library is based on the work done on [Jimdo/prometheus_client_php](https://github.com/Jimdo/prometheus_client_php).