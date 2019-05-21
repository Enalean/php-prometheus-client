<?php

declare(strict_types=1);

namespace Prometheus\Registry;

use Prometheus\Counter;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;

interface Registry
{
    /**
     * @return MetricFamilySamples[]
     */
    public function getMetricFamilySamples() : array;

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. duration_seconds
     * @param string   $help      e.g. The duration something took in seconds.
     * @param string[] $labels    e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerGauge(string $namespace, string $name, string $help, array $labels = []) : Gauge;

    /**
     * @throws MetricNotFoundException
     */
    public function getGauge(string $namespace, string $name) : Gauge;

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. duration_seconds
     * @param string   $help      e.g. The duration something took in seconds.
     * @param string[] $labels    e.g. ['controller', 'action']
     */
    public function getOrRegisterGauge(string $namespace, string $name, string $help, array $labels = []) : Gauge;

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. requests
     * @param string   $help      e.g. The number of requests made.
     * @param string[] $labels    e.g. ['controller', 'action']
     *
     * @throws MetricsRegistrationException
     */
    public function registerCounter(string $namespace, string $name, string $help, array $labels = []) : Counter;

    /**
     * @throws MetricNotFoundException
     */
    public function getCounter(string $namespace, string $name) : Counter;

    /**
     * @param string   $namespace e.g. cms
     * @param string   $name      e.g. requests
     * @param string   $help      e.g. The number of requests made.
     * @param string[] $labels    e.g. ['controller', 'action']
     */
    public function getOrRegisterCounter(string $namespace, string $name, string $help, array $labels = []) : Counter;

    /**
     * @param string        $namespace e.g. cms
     * @param string        $name      e.g. duration_seconds
     * @param string        $help      e.g. A histogram of the duration in seconds.
     * @param string[]      $labels    e.g. ['controller', 'action']
     * @param int[]|float[] $buckets   e.g. [100, 200, 300]
     *
     * @throws MetricsRegistrationException
     */
    public function registerHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null) : Histogram;

    /**
     * @throws MetricNotFoundException
     */
    public function getHistogram(string $namespace, string $name) : Histogram;

    /**
     * @param string        $namespace e.g. cms
     * @param string        $name      e.g. duration_seconds
     * @param string        $help      e.g. A histogram of the duration in seconds.
     * @param string[]      $labels    e.g. ['controller', 'action']
     * @param int[]|float[] $buckets   e.g. [100, 200, 300]
     */
    public function getOrRegisterHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null) : Histogram;
}
