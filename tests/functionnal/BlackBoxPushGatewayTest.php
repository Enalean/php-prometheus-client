<?php

declare(strict_types=1);

namespace Test;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\PushGateway;
use Prometheus\Storage\APCU;

final class BlackBoxPushGatewayTest extends TestCase
{
    /**
     * @test
     */
    public function pushGatewayShouldWork() : void
    {
        $adapter  = new APCU();
        $registry = new CollectorRegistry($adapter);

        $counter = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
        $counter->incBy(6, ['blue']);

        $pushGateway = new PushGateway('pushgateway:9091');
        $pushGateway->push($registry, 'my_job', ['instance' => 'foo']);

        $httpClient = new Client();
        $metrics    = $httpClient->get('http://pushgateway:9091/metrics')->getBody()->getContents();
        $this->assertStringContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );

        $pushGateway->delete('my_job', ['instance' => 'foo']);

        $httpClient = new Client();
        $metrics    = $httpClient->get('http://pushgateway:9091/metrics')->getBody()->getContents();
        $this->assertStringNotContainsString(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );
    }
}
