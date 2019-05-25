<?php

declare(strict_types=1);

namespace Prometheus\Registry;

use Prometheus\Counter;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;
use Prometheus\Storage\CounterStorage;
use Prometheus\Storage\GaugeStorage;
use Prometheus\Storage\HistogramStorage;
use Prometheus\Storage\Store;
use Prometheus\Value\MetricName;

final class CollectorRegistry implements Registry, Collector
{
    /** @var CounterStorage&GaugeStorage&HistogramStorage&Store */
    private $storageAdapter;
    /** @var Gauge[] */
    private $gauges = [];
    /** @var Counter[] */
    private $counters = [];
    /** @var Histogram[] */
    private $histograms = [];

    /**
     * @param CounterStorage|GaugeStorage|HistogramStorage|Store $storage
     *
     * @psalm-param CounterStorage&GaugeStorage&HistogramStorage&Store $storage
     */
    public function __construct(Store $storage)
    {
        $this->storageAdapter = $storage;
    }

    /**
     * @return MetricFamilySamples[]
     */
    public function getMetricFamilySamples() : array
    {
        return $this->storageAdapter->collect();
    }

    /**
     * @param string   $help   e.g. The duration something took in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerGauge(MetricName $name, string $help, array $labels = []) : Gauge
    {
        $metricIdentifier = $name->toString();
        if (isset($this->gauges[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }
        $this->gauges[$metricIdentifier] = new Gauge(
            $this->storageAdapter,
            $name,
            $help,
            $labels
        );

        return $this->gauges[$metricIdentifier];
    }

    /**
     * @throws MetricNotFoundException
     */
    public function getGauge(MetricName $name) : Gauge
    {
        $metricIdentifier = $name->toString();
        if (! isset($this->gauges[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->gauges[$metricIdentifier];
    }

    /**
     * @param MetricName $name   e.g. MetricName::fromNamespacedName('test', 'duration_seconds')
     * @param string     $help   e.g. The duration something took in seconds.
     * @param string[]   $labels e.g. ['controller', 'action']
     */
    public function getOrRegisterGauge(MetricName $name, string $help, array $labels = []) : Gauge
    {
        try {
            $gauge = $this->getGauge($name);
        } catch (MetricNotFoundException $e) {
            $gauge = $this->registerGauge($name, $help, $labels);
        }

        return $gauge;
    }

    /**
     * @param MetricName $name   e.g.   MetricName::fromNamespacedName('test', 'requests')
     * @param string     $help   e.g. The number of requests made.
     * @param string[]   $labels e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerCounter(MetricName $name, string $help, array $labels = []) : Counter
    {
        $metricIdentifier = $name->toString();
        if (isset($this->counters[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }
        $this->counters[$metricIdentifier] = new Counter(
            $this->storageAdapter,
            $name,
            $help,
            $labels
        );

        return $this->counters[$metricIdentifier];
    }

    /**
     * @throws MetricNotFoundException
     */
    public function getCounter(MetricName $name) : Counter
    {
        $metricIdentifier = $name->toString();
        if (! isset($this->counters[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->counters[$metricIdentifier];
    }

    /**
     * @param string   $help   e.g. The number of requests made.
     * @param string[] $labels e.g. ['controller', 'action']
     */
    public function getOrRegisterCounter(MetricName $name, string $help, array $labels = []) : Counter
    {
        try {
            $counter = $this->getCounter($name);
        } catch (MetricNotFoundException $e) {
            $counter = $this->registerCounter($name, $help, $labels);
        }

        return $counter;
    }

    /**
     * @param string        $help    e.g. A histogram of the duration in seconds.
     * @param string[]      $labels  e.g. ['controller', 'action']
     * @param int[]|float[] $buckets e.g. [100, 200, 300]
     *
     * @throws MetricsRegistrationException
     */
    public function registerHistogram(MetricName $name, string $help, array $labels = [], ?array $buckets = null) : Histogram
    {
        $metricIdentifier = $name->toString();
        if (isset($this->histograms[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }
        $this->histograms[$metricIdentifier] = new Histogram(
            $this->storageAdapter,
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
    public function getHistogram(MetricName $name) : Histogram
    {
        $metricIdentifier = $name->toString();
        if (! isset($this->histograms[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->histograms[$metricIdentifier];
    }

    /**
     * @param string        $help    e.g. A histogram of the duration in seconds.
     * @param string[]      $labels  e.g. ['controller', 'action']
     * @param int[]|float[] $buckets e.g. [100, 200, 300]
     */
    public function getOrRegisterHistogram(MetricName $name, string $help, array $labels = [], ?array $buckets = null) : Histogram
    {
        try {
            $histogram = $this->getHistogram($name);
        } catch (MetricNotFoundException $e) {
            $histogram = $this->registerHistogram($name, $help, $labels, $buckets);
        }

        return $histogram;
    }
}
