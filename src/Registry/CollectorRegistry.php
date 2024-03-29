<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Registry;

use Enalean\Prometheus\Counter;
use Enalean\Prometheus\Exception\MetricNotFoundException;
use Enalean\Prometheus\Exception\MetricsRegistrationException;
use Enalean\Prometheus\Gauge;
use Enalean\Prometheus\Histogram;
use Enalean\Prometheus\Storage\CounterStorage;
use Enalean\Prometheus\Storage\GaugeStorage;
use Enalean\Prometheus\Storage\HistogramStorage;
use Enalean\Prometheus\Storage\Store;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;

final class CollectorRegistry implements Registry, Collector
{
    /** @var CounterStorage&GaugeStorage&HistogramStorage&Store */
    private $storageAdapter;
    /** @var Gauge[] */
    private array $gauges = [];
    /** @var Counter[] */
    private array $counters = [];
    /** @var Histogram[] */
    private array $histograms = [];

    /**
     * @param CounterStorage|GaugeStorage|HistogramStorage|Store $storage
     * @psalm-param CounterStorage&GaugeStorage&HistogramStorage&Store $storage
     */
    public function __construct(Store $storage)
    {
        $this->storageAdapter = $storage;
    }

    /** @inheritdoc */
    public function getMetricFamilySamples(): array
    {
        return $this->storageAdapter->collect();
    }

    public function registerGauge(MetricName $name, string $help, MetricLabelNames|null $labelNames = null): Gauge
    {
        $metricIdentifier = $name->toString();
        if (isset($this->gauges[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }

        $this->gauges[$metricIdentifier] = new Gauge(
            $this->storageAdapter,
            $name,
            $help,
            $labelNames,
        );

        return $this->gauges[$metricIdentifier];
    }

    public function getGauge(MetricName $name): Gauge
    {
        $metricIdentifier = $name->toString();
        if (! isset($this->gauges[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->gauges[$metricIdentifier];
    }

    public function getOrRegisterGauge(MetricName $name, string $help, MetricLabelNames|null $labelNames = null): Gauge
    {
        try {
            $gauge = $this->getGauge($name);
        } catch (MetricNotFoundException) {
            $gauge = $this->registerGauge($name, $help, $labelNames);
        }

        return $gauge;
    }

    public function registerCounter(MetricName $name, string $help, MetricLabelNames|null $labelNames = null): Counter
    {
        $metricIdentifier = $name->toString();
        if (isset($this->counters[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }

        $this->counters[$metricIdentifier] = new Counter(
            $this->storageAdapter,
            $name,
            $help,
            $labelNames,
        );

        return $this->counters[$metricIdentifier];
    }

    public function getCounter(MetricName $name): Counter
    {
        $metricIdentifier = $name->toString();
        if (! isset($this->counters[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->counters[$metricIdentifier];
    }

    public function getOrRegisterCounter(MetricName $name, string $help, MetricLabelNames|null $labelNames = null): Counter
    {
        try {
            $counter = $this->getCounter($name);
        } catch (MetricNotFoundException) {
            $counter = $this->registerCounter($name, $help, $labelNames);
        }

        return $counter;
    }

    public function registerHistogram(MetricName $name, string $help, HistogramLabelNames|null $labelNames = null, array|null $buckets = null): Histogram
    {
        $metricIdentifier = $name->toString();
        if (isset($this->histograms[$metricIdentifier])) {
            throw new MetricsRegistrationException('Metric already registered');
        }

        $this->histograms[$metricIdentifier] = new Histogram(
            $this->storageAdapter,
            $name,
            $help,
            $labelNames,
            $buckets,
        );

        return $this->histograms[$metricIdentifier];
    }

    public function getHistogram(MetricName $name): Histogram
    {
        $metricIdentifier = $name->toString();
        if (! isset($this->histograms[$metricIdentifier])) {
            throw new MetricNotFoundException('Metric not found:' . $metricIdentifier);
        }

        return $this->histograms[$metricIdentifier];
    }

    public function getOrRegisterHistogram(MetricName $name, string $help, HistogramLabelNames|null $labelNames = null, array|null $buckets = null): Histogram
    {
        try {
            $histogram = $this->getHistogram($name);
        } catch (MetricNotFoundException) {
            $histogram = $this->registerHistogram($name, $help, $labelNames, $buckets);
        }

        return $histogram;
    }
}
