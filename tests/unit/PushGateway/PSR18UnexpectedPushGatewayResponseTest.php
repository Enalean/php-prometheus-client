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

        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
        $this->assertNull($exception->getPrevious());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testRequestFailure() : void
    {
        $request         = Psr17FactoryDiscovery::findRequestFactory()->createRequest('PUT', '/');
        $clientException = new class extends Exception implements ClientExceptionInterface {
        };

        $exception = PSR18UnexpectedPushGatewayResponse::requestFailure($request, $clientException);

        $this->assertSame($request, $exception->getRequest());
        $this->assertNull($exception->getResponse());
        $this->assertSame($clientException, $exception->getPrevious());
        $this->assertEquals($clientException->getCode(), $exception->getCode());
    }
}
