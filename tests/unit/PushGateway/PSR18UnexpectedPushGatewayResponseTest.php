<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\PushGateway;

use Enalean\Prometheus\PushGateway\PSR18UnexpectedPushGatewayResponse;
use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @covers Enalean\Prometheus\PushGateway\PSR18UnexpectedPushGatewayResponse
 */
final class PSR18UnexpectedPushGatewayResponseTest extends TestCase
{
    public function testInvalidResponse() : void
    {
        $request  = Psr17FactoryDiscovery::findRequestFactory()->createRequest('PUT', '/');
        $response = Psr17FactoryDiscovery::findResponseFactory()->createResponse(500);

        $exception = PSR18UnexpectedPushGatewayResponse::invalidResponse($request, $response);

        self::assertSame($request, $exception->getRequest());
        self::assertSame($response, $exception->getResponse());
        self::assertNull($exception->getPrevious());
        self::assertEquals(0, $exception->getCode());
    }

    public function testRequestFailure() : void
    {
        $request         = Psr17FactoryDiscovery::findRequestFactory()->createRequest('PUT', '/');
        $clientException = new class extends Exception implements ClientExceptionInterface {
        };

        $exception = PSR18UnexpectedPushGatewayResponse::requestFailure($request, $clientException);

        self::assertSame($request, $exception->getRequest());
        self::assertNull($exception->getResponse());
        self::assertSame($clientException, $exception->getPrevious());
        self::assertEquals($clientException->getCode(), $exception->getCode());
    }
}
