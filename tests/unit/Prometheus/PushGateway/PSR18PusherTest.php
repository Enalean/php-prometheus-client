<?php

declare(strict_types=1);

namespace Test\Prometheus\PushGateway;

use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Prometheus\MetricFamilySamples;
use Prometheus\PushGateway\PSR18Pusher;
use Prometheus\PushGateway\UnexpectedPushGatewayResponse;
use Prometheus\Registry\Registry;
use Psr\Http\Client\ClientExceptionInterface;

final class PSR18PusherTest extends TestCase
{
    /**
     * @testWith ["http://example.com", "example.com"]
     *           ["http://example.com", "http://example.com"]
     *           ["http://example.com", "http://example.com/"]
     *           ["https://example.com", "https://example.com"]
     *           ["https://example.com", "https://example.com/"]
     */
    public function testServerURIIsCorrectlyConstructed(string $expectedServerURIStart, string $givenAddress) : void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

        $client->setDefaultResponse($responseFactory->createResponse(202));

        $pusher   = new PSR18Pusher(
            $givenAddress,
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
        $registry = $this->createMock(Registry::class);

        $pusher->push($registry, 'myjob');
        $this->assertEquals('PUT', $client->getLastRequest()->getMethod());
        $pusher->pushAdd($registry, 'myjob');
        $this->assertEquals('POST', $client->getLastRequest()->getMethod());
        $pusher->delete('myjob');
        $this->assertEquals('DELETE', $client->getLastRequest()->getMethod());

        $sentRequests = $client->getRequests();
        $this->assertCount(3, $sentRequests);
        foreach ($sentRequests as $request) {
            $this->assertStringStartsWith($expectedServerURIStart . '/metrics/job/myjob', (string) $request->getUri());
        }
    }

    public function testDataIsPushedToTheGateway() : void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

        $client->setDefaultResponse($responseFactory->createResponse(202));

        $pusher   = new PSR18Pusher(
            'https://example.com',
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
        $registry = $this->createMock(Registry::class);
        /** @psalm-suppress InternalMethod */
        $registry->method('getMetricFamilySamples')->willReturn([new MetricFamilySamples('name', 'type', 'help', [], [])]);

        $pusher->push($registry, 'myjob');
        $this->assertNotEmpty($client->getLastRequest()->getBody()->getContents());
        $pusher->pushAdd($registry, 'myjob');
        $this->assertNotEmpty($client->getLastRequest()->getBody()->getContents());
        $pusher->delete('myjob');
        $this->assertEmpty($client->getLastRequest()->getBody()->getContents());
    }

    public function testPushedInformationCanBeGrouped() : void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

        $client->setDefaultResponse($responseFactory->createResponse(202));

        $pusher   = new PSR18Pusher(
            'https://example.com',
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
        $registry = $this->createMock(Registry::class);

        $pusher->push($registry, 'myjob', ['job' => 'some_job', 'instance' => 'some_instance']);
        $pusher->pushAdd($registry, 'myjob', ['job' => 'some_job', 'instance' => 'some_instance']);
        $pusher->delete('myjob', ['job' => 'some_job', 'instance' => 'some_instance']);

        $sentRequests = $client->getRequests();
        $this->assertCount(3, $sentRequests);
        foreach ($sentRequests as $request) {
            $this->assertStringEndsWith('/job/some_job/instance/some_instance', (string) $request->getUri());
        }
    }

    /**
     * @testWith [400]
     *           [500]
     *           [301]
     *           [200]
     */
    public function testExceptionIsThrownWhenPushGatewayResponseIsNotExpected(int $responseStatusCode) : void
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

    public function testExceptionIsThrownWhenRequestToPushGatewayCanNotBeSent() : void
    {
        $client = new Client();

        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();

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
