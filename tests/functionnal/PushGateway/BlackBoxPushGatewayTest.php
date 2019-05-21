<?php

declare(strict_types=1);

namespace Test\Prometheus\PushGateway;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;
use Prometheus\PushGateway\Pusher;
use Prometheus\Registry\CollectorRegistry;
use Prometheus\Storage\APCUStore;

abstract class BlackBoxPushGatewayTest extends TestCase
{
    abstract public function getPusher(string $address) : Pusher;

    /**
     * @test
     */
    public function pushGatewayShouldWork() : void
    {
        $adapter  = new APCUStore();
        $registry = new CollectorRegistry($adapter);

        $counter = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
        $counter->incBy(6, ['blue']);

        $pusher = $this->getPusher('pushgateway:9091');
        $pusher->push($registry, 'my_job', ['instance' => 'foo']);

        $httpClient     = Psr18ClientDiscovery::find();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $metricsRequest = $requestFactory->createRequest('GET', 'http://pushgateway:9091/metrics');
        $metrics        = $httpClient->sendRequest($metricsRequest)->getBody()->getContents();
        $this->assertStringContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );

        $pusher->delete('my_job', ['instance' => 'foo']);

        $metrics = $httpClient->sendRequest($metricsRequest)->getBody()->getContents();
        $this->assertStringNotContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );
    }
}
