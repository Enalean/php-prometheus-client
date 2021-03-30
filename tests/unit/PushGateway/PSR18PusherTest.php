<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\PushGateway;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\PushGateway\PSR18Pusher;
use Enalean\Prometheus\PushGateway\UnexpectedPushGatewayResponse;
use Enalean\Prometheus\Registry\Collector;
use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @covers Enalean\Prometheus\PushGateway\PSR18Pusher
 */
final class PSR18PusherTest extends TestCase
{
    /**
     * @testWith ["http://example.com", "example.com"]
     *           ["http://example.com", "http://example.com"]
     *           ["https://example.com", "https://example.com"]
     */
    public function testServerURIIsCorrectlyConstructed(string $expectedServerURIStart, string $givenAddress): void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

        $client->setDefaultResponse($responseFactory->createResponse());

        $pusher    = new PSR18Pusher(
            $givenAddress,
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
        $collector = $this->createMock(Collector::class);

        $pusher->push($collector, 'myjob');
        self::assertEquals('PUT', $client->getLastRequest()->getMethod());
        $pusher->pushAdd($collector, 'myjob');
        self::assertEquals('POST', $client->getLastRequest()->getMethod());
        $pusher->delete('myjob');
        self::assertEquals('DELETE', $client->getLastRequest()->getMethod());

        $sentRequests = $client->getRequests();
        self::assertCount(3, $sentRequests);
        foreach ($sentRequests as $request) {
            self::assertStringStartsWith($expectedServerURIStart . '/metrics/job/myjob', (string) $request->getUri());
        }
    }

    /**
     * PushGateway before v0.10.0 was returning a 202 in case of success
     *
     * @testWith [200]
     *           [202]
     */
    public function testDataIsPushedToTheGateway(int $responseStatusCode): void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

        $client->setDefaultResponse($responseFactory->createResponse($responseStatusCode));

        $pusher    = new PSR18Pusher(
            'https://example.com',
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
        $collector = $this->createMock(Collector::class);
        $collector->expects(self::atLeastOnce())->method('getMetricFamilySamples')
            ->willReturn([new MetricFamilySamples('name', 'type', 'help', [], [])]);

        $pusher->push($collector, 'myjob');
        self::assertNotEmpty($client->getLastRequest()->getBody()->getContents());
        $pusher->pushAdd($collector, 'myjob');
        self::assertNotEmpty($client->getLastRequest()->getBody()->getContents());
        $pusher->delete('myjob');
        self::assertEmpty($client->getLastRequest()->getBody()->getContents());
    }

    public function testPushedInformationCanBeGrouped(): void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

        $client->setDefaultResponse($responseFactory->createResponse());

        $pusher    = new PSR18Pusher(
            'https://example.com',
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
        $collector = $this->createMock(Collector::class);

        $pusher->push($collector, 'myjob', ['job' => 'some_job', 'instance' => 'some_instance']);
        $pusher->pushAdd($collector, 'myjob', ['job' => 'some_job', 'instance' => 'some_instance']);
        $pusher->delete('myjob', ['job' => 'some_job', 'instance' => 'some_instance']);

        $sentRequests = $client->getRequests();
        self::assertCount(3, $sentRequests);
        foreach ($sentRequests as $request) {
            self::assertStringEndsWith('/job/some_job/instance/some_instance', (string) $request->getUri());
        }
    }

    /**
     * @testWith [400]
     *           [500]
     *           [301]
     */
    public function testExceptionIsThrownWhenPushGatewayResponseIsNotExpected(int $responseStatusCode): void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

        $client->setDefaultResponse($responseFactory->createResponse($responseStatusCode));

        $pusher = new PSR18Pusher(
            'https://example.com',
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );

        $this->expectException(UnexpectedPushGatewayResponse::class);
        $pusher->delete('myjob');
    }

    public function testExceptionIsThrownWhenRequestToPushGatewayCanNotBeSent(): void
    {
        $client = new Client();

        $client->addException(new class extends Exception implements ClientExceptionInterface {
        });

        $pusher = new PSR18Pusher(
            'https://example.com',
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );

        $this->expectException(UnexpectedPushGatewayResponse::class);
        $pusher->delete('myjob');
    }
}
