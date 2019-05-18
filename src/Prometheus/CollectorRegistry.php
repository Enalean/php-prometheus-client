<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\Redis;

class CollectorRegistry implements RegistryInterface
{
    /** @var CollectorRegistry */
    private static $defaultRegistry;

    /** @var Adapter */
    private $storageAdapter;
    /** @var Gauge[] */
    private $gauges = [];
    /** @var Counter[] */
    private $counters = [];
    /** @var Histogram[] */
    private $histograms = [];

    public function __construct(Adapter $redisAdapter)
    {
        $this->storageAdapter = $redisAdapter;
    }

    public static function getDefault() : CollectorRegistry
    {
        if (! self::$defaultRegistry) {
            return self::$defaultRegistry = new static(new Redis());
        }

        return self::$defaultRegistry;
    }

    /**
     * @return MetricFamilySamples[]
     */
    public function getMetricFamilySamples() : array
    {
        return $this->storageAdapter->collect();
    }

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. duration_seconds
     * @param string   $help      e.g. The duration something took in seconds.
     * @param string[] $labels    e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerGauge(string $namespace, string $name, string $help, array $labels = []) : Gauge
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->gauges[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }
        $this->gauges[$metricIdentifier] = new Gauge(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels
        );

        return $this->gauges[$metricIdentifier];
    }

    /**
     * @throws MetricNotFoundException
     */
    public function getGauge(string $namespace, string $name) : Gauge
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (! isset($this->gauges[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->gauges[$metricIdentifier];
    }

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. duration_seconds
     * @param string   $help      e.g. The duration something took in seconds.
     * @param string[] $labels    e.g. ['controller', 'action']
     */
    public function getOrRegisterGauge(string $namespace, string $name, string $help, array $labels = []) : Gauge
    {
        try {
            $gauge = $this->getGauge($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $gauge = $this->registerGauge($namespace, $name, $help, $labels);
        }

        return $gauge;
    }

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. requests
     * @param string   $help      e.g. The number of requests made.
     * @param string[] $labels    e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerCounter(string $namespace, string $name, string $help, array $labels = []) : Counter
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->counters[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }
        $this->counters[$metricIdentifier] = new Counter(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels
        );

        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @throws MetricNotFoundException
     */
    public function getCounter(string $namespace, string $name) : Counter
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (! isset($this->counters[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. requests
     * @param string   $help      e.g. The number of requests made.
     * @param string[] $labels    e.g. ['controller', 'action']
     */
    public function getOrRegisterCounter(string $namespace, string $name, string $help, array $labels = []) : Counter
    {
        try {
            $counter = $this->getCounter($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $counter = $this->registerCounter($namespace, $name, $help, $labels);
        }

        return $counter;
    }

    /**
     * @param string        $namespace e.g. cms
     * @param string        $name      e.g. duration_seconds
     * @param string        $help      e.g. A histogram of the duration in seconds.
     * @param string[]      $labels    e.g. ['controller', 'action']
     * @param int[]|float[] $buckets   e.g. [100, 200, 300]
     *
     * @throws MetricsRegistrationException
     */
    public function registerHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null) : Histogram
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->histograms[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }
        $this->histograms[$metricIdentifier] = new Histogram(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels,
            $buckets
        );

        return $this->histograms[$metricIdentifier];
    }

    /**
     * @throws MetricNotFoundException
     */
    public function getHistogram(string $namespace, string $name) : Histogram
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (! isset($this->histograms[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->histograms[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string        $namespace e.g. cms
     * @param string        $name      e.g. duration_seconds
     * @param string        $help      e.g. A histogram of the duration in seconds.
     * @param string[]      $labels    e.g. ['controller', 'action']
     * @param int[]|float[] $buckets   e.g. [100, 200, 300]
     */
    public function getOrRegisterHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null) : Histogram
    {
        try {
            $histogram = $this->getHistogram($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $histogram = $this->registerHistogram($namespace, $name, $help, $labels, $buckets);
        }

        return $histogram;
    }

    private static function metricIdentifier(string $namespace, string $name) : string
    {
        return $namespace . ':' . $name;
    }
}
