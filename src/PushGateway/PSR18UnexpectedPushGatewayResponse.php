<?php

declare(strict_types=1);

namespace Enalean\Prometheus\PushGateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PSR18UnexpectedPushGatewayResponse extends UnexpectedPushGatewayResponse
{
    private function __construct(private RequestInterface $request, private ?ResponseInterface $response, ?ClientExceptionInterface $clientException)
    {
        $exceptionCode = 0;

        $message = 'Cannot connect PushGateway server to ' . $request->getUri()->__toString() . ':';
        if ($response !== null) {
            $message = ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        }

        if ($clientException !== null) {
            $message = ' ' . $clientException->getMessage();
            /** @psalm-var int $exceptionCode */
            $exceptionCode = $clientException->getCode();
        }

        parent::__construct($message, $exceptionCode, $clientException);
    }

    public static function invalidResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self($request, $response, null);
    }

    public static function requestFailure(RequestInterface $request, ClientExceptionInterface $clientException): self
    {
        return new self($request, null, $clientException);
    }

    /**
     * @psalm-mutation-free
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @psalm-mutation-free
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
