<?php

declare(strict_types=1);

namespace Prometheus\PushGateway;

use Prometheus\Registry\Registry;

interface Pusher
{
    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     *
     * @param array<string,string> $groupingKey
     */
    public function push(Registry $collectorRegistry, string $job, array $groupingKey = []) : void;

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     *
     * @param array<string,string> $groupingKey
     */
    public function pushAdd(Registry $collectorRegistry, string $job, array $groupingKey = []) : void;

    /**
     * Deletes metrics from the Pushgateway.
     * Uses HTTP DELETE.
     *
     * @param array<string,string> $groupingKey
     */
    public function delete(string $job, array $groupingKey = []) : void;
}
