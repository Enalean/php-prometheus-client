<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Registry;

use Enalean\Prometheus\Counter;
use Enalean\Prometheus\Exception\MetricNotFoundException;
use Enalean\Prometheus\Exception\MetricsRegistrationException;
use Enalean\Prometheus\Gauge;
use Enalean\Prometheus\Histogram;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;

interface Registry
{
    /**
     * @param string                $help       e.g. The duration something took in seconds.
     * @param MetricLabelNames|null $labelNames e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerGauge(MetricName $name, string $help, ?MetricLabelNames $labelNames = null) : Gauge;

    /**
     * @throws MetricNotFoundException
     */
    public function getGauge(MetricName $name) : Gauge;

    /**
     * @param string                $help       e.g. The duration something took in seconds.
     * @param MetricLabelNames|null $labelNames e.g. ['controller', 'action']
     */
    public function getOrRegisterGauge(MetricName $name, string $help, ?MetricLabelNames $labelNames = null) : Gauge;

    /**
     * @param string                $help       e.g. The number of requests made.
     * @param MetricLabelNames|null $labelNames e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerCounter(MetricName $name, string $help, ?MetricLabelNames $labelNames = null) : Counter;

    /**
     * @throws MetricNotFoundException
     */
    public function getCounter(MetricName $name) : Counter;

    /**
     * @param string                $help       e.g. The number of requests made.
     * @param MetricLabelNames|null $labelNames e.g. ['controller', 'action']
     */
    public function getOrRegisterCounter(MetricName $name, string $help, ?MetricLabelNames $labelNames = null) : Counter;

    /**
     * @param string                   $help    e.g. A histogram of the duration in seconds.
     * @param HistogramLabelNames|null $labels  e.g. ['controller', 'action']
     * @param float[]                  $buckets e.g. [100, 200, 300]
     *
     * @throws MetricsRegistrationException
     */
    public function registerHistogram(MetricName $name, string $help, ?HistogramLabelNames $labelNames = null, ?array $buckets = null) : Histogram;

    /**
     * @throws MetricNotFoundException
     */
    public function getHistogram(MetricName $name) : Histogram;

    /**
     * @param string                   $help    e.g. A histogram of the duration in seconds.
     * @param HistogramLabelNames|null $labels  e.g. ['controller', 'action']
     * @param float[]                  $buckets e.g. [100, 200, 300]
     */
    public function getOrRegisterHistogram(MetricName $name, string $help, ?HistogramLabelNames $labelNames = null, ?array $buckets = null) : Histogram;
}
