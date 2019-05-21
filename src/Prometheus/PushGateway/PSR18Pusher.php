<?php

declare(strict_types=1);

namespace Prometheus\PushGateway;

use Prometheus\MetricFamilySamples;
use Prometheus\Registry\Registry;
use Prometheus\RenderTextFormat;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use function rawurlencode;
use function strpos;
use function substr;

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
        if ($address[-1] === '/') {
            $address = substr($address, 0, -1);
        }
        $this->address         = $address . '/metrics/job/';
        $this->client          = $client;
        $this->request_factory = $request_factory;
        $this->stream_factory  = $stream_factory;
    }

    /**
     * @param array<string,string> $groupingKey
     */
    public function push(Registry $collectorRegistry, string $job, array $groupingKey = []) : void
    {
        $this->send('PUT', $job, $groupingKey, $collectorRegistry->getMetricFamilySamples());
    }

    /**
     * @param array<string,string> $groupingKey
     */
    public function pushAdd(Registry $collectorRegistry, string $job, array $groupingKey = []) : void
    {
        $this->send('POST', $job, $groupingKey, $collectorRegistry->getMetricFamilySamples());
    }

    /**
     * @param array<string,string> $groupingKey
     */
    public function delete(string $job, array $groupingKey = []) : void
    {
        $this->send('DELETE', $job, $groupingKey, []);
    }

    /**
     * @param array<string,string>  $groupingKey
     * @param MetricFamilySamples[] $metricFamilySamples
     */
    private function send(string $method, string $job, array $groupingKey, array $metricFamilySamples) : void
    {
        $uri = $this->address . rawurlencode($job);
        foreach ($groupingKey as $label => $value) {
            $uri .= '/' . rawurlencode($label) . '/' . rawurlencode($value);
        }

        $request = $this->request_factory->createRequest($method, $uri)
                        ->withHeader('Content-Type', RenderTextFormat::MIME_TYPE);

        if ($request->getMethod() !== 'DELETE') {
            $renderer = new RenderTextFormat();
            $request  = $request->withBody($this->stream_factory->createStream($renderer->render($metricFamilySamples)));
        }

        $response = $this->client->sendRequest($request);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 202) {
            $msg = 'Unexpected status code ' . $statusCode . ' received from pushgateway ' . $this->address . ': ' . $response->getBody();
            throw new RuntimeException($msg);
        }
    }
}
