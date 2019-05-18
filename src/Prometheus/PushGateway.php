<?php

declare(strict_types=1);

namespace Prometheus;

use GuzzleHttp\Client;
use RuntimeException;

class PushGateway
{
    /** @var string */
    private $address;

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     *
     * @param array<string,string>|null $groupingKey
     */
    public function push(CollectorRegistry $collectorRegistry, string $job, ?array $groupingKey = null) : void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, 'put');
    }

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     *
     * @param array<string,string>|null $groupingKey
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, ?array $groupingKey = null) : void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, 'post');
    }

    /**
     * Deletes metrics from the Pushgateway.
     * Uses HTTP POST.
     *
     * @param array<string,string>|null $groupingKey
     */
    public function delete(string $job, ?array $groupingKey = null) : void
    {
        $this->doRequest(null, $job, $groupingKey, 'delete');
    }

    /**
     * @param array<string,string>|null $groupingKey
     */
    private function doRequest(?CollectorRegistry $collectorRegistry = null, string $job = '', ?array $groupingKey = null, string $method = '') : void
    {
        $url = 'http://' . $this->address . '/metrics/job/' . $job;
        if (! empty($groupingKey)) {
            foreach ($groupingKey as $label => $value) {
                $url .= '/' . $label . '/' . $value;
            }
        }
        $client         = new Client();
        $requestOptions = [
            'headers' => [
                'Content-Type' => RenderTextFormat::MIME_TYPE,
            ],
            'connect_timeout' => 10,
            'timeout' => 20,
        ];
        if ($method !== 'delete' && $collectorRegistry !== null) {
            $renderer               = new RenderTextFormat();
            $requestOptions['body'] = $renderer->render($collectorRegistry->getMetricFamilySamples());
        }
        $response   = $client->request($method, $url, $requestOptions);
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 202) {
            $msg = 'Unexpected status code ' . $statusCode . ' received from pushgateway ' . $this->address . ': ' . $response->getBody();
            throw new RuntimeException($msg);
        }
    }
}
