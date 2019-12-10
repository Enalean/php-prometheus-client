<?php

declare(strict_types=1);

namespace Enalean\PrometheusTestE2E;

use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use function getenv;

final class BlackBoxTest extends TestCase
{
    private const BASE_URI = 'http://nginx:80/';

    /** @var HttpAsyncClient */
    private $client;
    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var string */
    private $adapter;

    protected function setUp() : void
    {
        $this->adapter        = getenv('ADAPTER') ?: '';
        $this->client         = HttpAsyncClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->client->sendAsyncRequest(
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/flush_adapter.php?adapter=' . $this->adapter)
        )->wait();
    }

    /**
     * @test
     */
    public function gaugesShouldBeOverwritten() : void
    {
        $requests = [
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_gauge.php?c=0&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_gauge.php?c=1&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_gauge.php?c=2&adapter=' . $this->adapter),
        ];
        $promises = [];
        foreach ($requests as $request) {
            $promises[] = $this->client->sendAsyncRequest($request);
        }

        foreach ($promises as $promise) {
            $promise->wait();
        }

        $metricsResult = $this->client->sendAsyncRequest(
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/metrics.php?adapter=' . $this->adapter)
        )->wait();
        $this->assertThat(
            $metricsResult->getBody()->getContents(),
            $this->logicalOr(
                $this->stringContains('test_some_gauge{type="blue"} 0'),
                $this->stringContains('test_some_gauge{type="blue"} 1'),
                $this->stringContains('test_some_gauge{type="blue"} 2')
            )
        );
    }

    /**
     * @test
     */
    public function countersShouldIncrementAtomically() : void
    {
        $promises = [];
        $sum      = 0;
        for ($i = 0; $i < 275; $i++) {
            $request    = $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_counter.php?c=' . $i . '&adapter=' . $this->adapter);
            $promises[] =  $this->client->sendAsyncRequest($request);
            $sum       += $i;
        }

        foreach ($promises as $promise) {
            $promise->wait();
        }

        $metricsResult = $this->client->sendAsyncRequest(
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/metrics.php?adapter=' . $this->adapter)
        )->wait();

        $this->assertThat($metricsResult->getBody()->getContents(), $this->stringContains('test_some_counter{type="blue"} ' . $sum));
    }

    /**
     * @test
     */
    public function histogramsShouldIncrementAtomically() : void
    {
        $requests = [
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=0&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=1&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=2&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=3&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=4&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=5&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=6&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=7&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=8&adapter=' . $this->adapter),
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/some_histogram.php?c=9&adapter=' . $this->adapter),
        ];
        $promises = [];
        foreach ($requests as $request) {
            $promises[] = $this->client->sendAsyncRequest($request);
        }

        foreach ($promises as $promise) {
            $promise->wait();
        }

        $metricsResult = $this->client->sendAsyncRequest(
            $this->requestFactory->createRequest('GET', self::BASE_URI . '/examples/metrics.php?adapter=' . $this->adapter)
        )->wait();

        $this->assertThat($metricsResult->getBody()->getContents(), $this->stringContains(<<<EOF
test_some_histogram_bucket{type="blue",le="0.1"} 1
test_some_histogram_bucket{type="blue",le="1"} 2
test_some_histogram_bucket{type="blue",le="2"} 3
test_some_histogram_bucket{type="blue",le="3.5"} 4
test_some_histogram_bucket{type="blue",le="4"} 5
test_some_histogram_bucket{type="blue",le="5"} 6
test_some_histogram_bucket{type="blue",le="6"} 7
test_some_histogram_bucket{type="blue",le="7"} 8
test_some_histogram_bucket{type="blue",le="8"} 9
test_some_histogram_bucket{type="blue",le="9"} 10
test_some_histogram_bucket{type="blue",le="+Inf"} 10
test_some_histogram_count{type="blue"} 10
test_some_histogram_sum{type="blue"} 45
EOF
        ));
    }
}
