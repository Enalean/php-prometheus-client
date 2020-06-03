<?php

declare(strict_types=1);

namespace Enalean\PrometheusTestE2E\PushGateway;

use Enalean\Prometheus\PushGateway\Pusher;
use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Storage\APCUStore;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;

abstract class BlackBoxPushGatewayTest extends TestCase
{
    abstract public function getPusher(string $address): Pusher;

    /**
     * @test
     */
    public function pushGatewayShouldWork(): void
    {
        $adapter  = new APCUStore();
        $registry = new CollectorRegistry($adapter);

        $counter = $registry->registerCounter(
            MetricName::fromNamespacedName('test', 'some_counter'),
            'it increases',
            MetricLabelNames::fromNames('type')
        );
        $counter->incBy(6, 'blue');

        $pusher = $this->getPusher('pushgateway:9091');
        $pusher->push($registry, 'my_job', ['instance' => 'foo']);

        $httpClient     = Psr18ClientDiscovery::find();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $metricsRequest = $requestFactory->createRequest('GET', 'http://pushgateway:9091/metrics');
        $metrics        = $httpClient->sendRequest($metricsRequest)->getBody()->getContents();
        self::assertStringContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );

        $pusher->delete('my_job', ['instance' => 'foo']);

        $metrics = $httpClient->sendRequest($metricsRequest)->getBody()->getContents();
        self::assertStringNotContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );
    }
}
