<?php

declare(strict_types=1);

namespace Enalean\Prometheus\PushGateway;

use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Registry\Collector;
use Enalean\Prometheus\Renderer\RenderTextFormat;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use function rawurlencode;
use function strpos;

final class PSR18Pusher implements Pusher
{
    /** @var string */
    private $address;
    /** @var ClientInterface */
    private $client;
    /** @var RequestFactoryInterface */
    private $request_factory;
    /** @var StreamFactoryInterface */
    private $stream_factory;

    public function __construct(string $address, ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory)
    {
        if (strpos($address, '://') === false) {
            $address = 'http://' . $address;
        }
        $this->address         = $address . '/metrics/job/';
        $this->client          = $client;
        $this->request_factory = $request_factory;
        $this->stream_factory  = $stream_factory;
    }

    /**
     * @throws PSR18UnexpectedPushGatewayResponse
     *
     * @inheritdoc
     */
    public function push(Collector $collector, string $job, array $groupingKey = []) : void
    {
        $this->send('PUT', $job, $groupingKey, $collector->getMetricFamilySamples());
    }

    /**
     * @throws PSR18UnexpectedPushGatewayResponse
     *
     * @inheritdoc
     */
    public function pushAdd(Collector $collector, string $job, array $groupingKey = []) : void
    {
        $this->send('POST', $job, $groupingKey, $collector->getMetricFamilySamples());
    }

    /**
     * @throws PSR18UnexpectedPushGatewayResponse
     *
     * @inheritdoc
     */
    public function delete(string $job, array $groupingKey = []) : void
    {
        $this->send('DELETE', $job, $groupingKey, []);
    }

    /**
     * @param array<string,string>  $groupingKey
     * @param MetricFamilySamples[] $metricFamilySamples
     *
     * @throws UnexpectedPushGatewayResponse
     */
    private function send(string $method, string $job, array $groupingKey, array $metricFamilySamples) : void
    {
        $uri = $this->address . rawurlencode($job);
        foreach ($groupingKey as $label => $value) {
            $uri .= '/' . rawurlencode($label) . '/' . rawurlencode($value);
        }

        $renderer = new RenderTextFormat();
        $request  = $this->request_factory->createRequest($method, $uri)
                        ->withHeader('Content-Type', $renderer->getMimeType());

        if ($request->getMethod() !== 'DELETE') {
            $request = $request->withBody($this->stream_factory->createStream($renderer->render($metricFamilySamples)));
        }

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $ex) {
            throw PSR18UnexpectedPushGatewayResponse::requestFailure($request, $ex);
        }

        $response_status_code = $response->getStatusCode();
        if ($response_status_code !== 200 && $response_status_code !== 202) {
            throw PSR18UnexpectedPushGatewayResponse::invalidResponse($request, $response);
        }
    }
}
