<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\PushGateway;

use Enalean\Prometheus\PushGateway\PSR18UnexpectedPushGatewayResponse;
use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

#[CoversClass(PSR18UnexpectedPushGatewayResponse::class)]
final class PSR18UnexpectedPushGatewayResponseTest extends TestCase
{
    public function testInvalidResponse(): void
    {
        $request  = Psr17FactoryDiscovery::findRequestFactory()->createRequest('PUT', '/');
        $response = Psr17FactoryDiscovery::findResponseFactory()->createResponse(500);

        $exception = PSR18UnexpectedPushGatewayResponse::invalidResponse($request, $response);

        self::assertStringContainsString('Cannot connect PushGateway server to /: 500 Internal', $exception->getMessage());
        self::assertSame($request, $exception->getRequest());
        self::assertSame($response, $exception->getResponse());
        self::assertNull($exception->getPrevious());
        self::assertEquals(0, $exception->getCode());
    }

    public function testRequestFailure(): void
    {
        $request         = Psr17FactoryDiscovery::findRequestFactory()->createRequest('PUT', '/');
        $clientException = new class ('some reason') extends Exception implements ClientExceptionInterface {
        };

        $exception = PSR18UnexpectedPushGatewayResponse::requestFailure($request, $clientException);

        self::assertStringContainsString('Cannot connect PushGateway server to /: some reason', $exception->getMessage());
        self::assertSame($request, $exception->getRequest());
        self::assertNull($exception->getResponse());
        self::assertSame($clientException, $exception->getPrevious());
        self::assertEquals($clientException->getCode(), $exception->getCode());
    }
}
