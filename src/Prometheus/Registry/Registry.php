<?php

declare(strict_types=1);

namespace Prometheus\Registry;

use Prometheus\Counter;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;
use Prometheus\Value\MetricName;

interface Registry
{
    /**
     * @return MetricFamilySamples[]
     */
    public function getMetricFamilySamples() : array;

    /**
     * @param string   $help   e.g. The duration something took in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerGauge(MetricName $name, string $help, array $labels = []) : Gauge;

    /**
     * @throws MetricNotFoundException
     */
    public function getGauge(MetricName $name) : Gauge;

    /**
     * @param string   $help   e.g. The duration something took in seconds.
     * @param string[] $labels e.g. ['controller', 'action']
     */
    public function getOrRegisterGauge(MetricName $name, string $help, array $labels = []) : Gauge;

    /**
     * @param string   $help   e.g. The number of requests made.
     * @param string[] $labels e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerCounter(MetricName $name, string $help, array $labels = []) : Counter;

    /**
     * @throws MetricNotFoundException
     */
    public function getCounter(MetricName $name) : Counter;

    /**
     * @param string   $help   e.g. The number of requests made.
     * @param string[] $labels e.g. ['controller', 'action']
     */
    public function getOrRegisterCounter(MetricName $name, string $help, array $labels = []) : Counter;

    /**
     * @param string        $help    e.g. A histogram of the duration in seconds.
     * @param string[]      $labels  e.g. ['controller', 'action']
     * @param int[]|float[] $buckets e.g. [100, 200, 300]
     *
     * @throws MetricsRegistrationException
     */
    public function registerHistogram(MetricName $name, string $help, array $labels = [], ?array $buckets = null) : Histogram;

    /**
     * @throws MetricNotFoundException
     */
    public function getHistogram(MetricName $name) : Histogram;

    /**
     * @param string        $help    e.g. A histogram of the duration in seconds.
     * @param string[]      $labels  e.g. ['controller', 'action']
     * @param int[]|float[] $buckets e.g. [100, 200, 300]
     */
    public function getOrRegisterHistogram(MetricName $name, string $help, array $labels = [], ?array $buckets = null) : Histogram;
}
