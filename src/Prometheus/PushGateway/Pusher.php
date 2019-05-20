<?php

declare(strict_types=1);

namespace Prometheus\PushGateway;

use Prometheus\RegistryInterface;

interface Pusher
{
    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     *
     * @param array<string,string> $groupingKey
     */
    public function push(RegistryInterface $collectorRegistry, string $job, array $groupingKey = []) : void;

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     *
     * @param array<string,string> $groupingKey
     */
    public function pushAdd(RegistryInterface $collectorRegistry, string $job, array $groupingKey = []) : void;

    /**
     * Deletes metrics from the Pushgateway.
     * Uses HTTP DELETE.
     *
     * @param array<string,string> $groupingKey
     */
    public function delete(string $job, array $groupingKey = []) : void;
}
