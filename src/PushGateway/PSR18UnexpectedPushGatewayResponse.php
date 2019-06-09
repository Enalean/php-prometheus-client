<?php

declare(strict_types=1);

namespace Enalean\Prometheus\PushGateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PSR18UnexpectedPushGatewayResponse extends UnexpectedPushGatewayResponse
{
    /** @var RequestInterface */
    private $request;
    /** @var ResponseInterface|null */
    private $response;

    private function __construct(RequestInterface $request, ?ResponseInterface $response, ?ClientExceptionInterface $clientException)
    {
        $this->request  = $request;
        $this->response = $response;

        $exceptionCode = 0;

        $message = 'Cannot connect PushGateway server to ' . $request->getUri()->__toString() . ':';
        if ($response !== null) {
            $message = ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        }
        if ($clientException !== null) {
            $message       = ' ' . $clientException->getMessage();
            $exceptionCode = (int) $clientException->getCode();
        }
        parent::__construct($message, $exceptionCode, $clientException);
    }

    public static function invalidResponse(RequestInterface $request, ResponseInterface $response) : self
    {
        return new self($request, $response, null);
    }

    public static function requestFailure(RequestInterface $request, ClientExceptionInterface $clientException) : self
    {
        return new self($request, null, $clientException);
    }

    public function getRequest() : RequestInterface
    {
        return $this->request;
    }

    public function getResponse() : ?ResponseInterface
    {
        return $this->response;
    }
}
