<?php

declare(strict_types=1);

namespace Prometheus\PushGateway;

use Prometheus\Registry\Collector;

interface Pusher
{
    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     *
     * @param array<string,string> $groupingKey
     *
     * @throws UnexpectedPushGatewayResponse
     */
    public function push(Collector $collector, string $job, array $groupingKey = []) : void;

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     *
     * @param array<string,string> $groupingKey
     *
     * @throws UnexpectedPushGatewayResponse
     */
    public function pushAdd(Collector $collector, string $job, array $groupingKey = []) : void;

    /**
     * Deletes metrics from the Pushgateway.
     * Uses HTTP DELETE.
     *
     * @param array<string,string> $groupingKey
     *
     * @throws UnexpectedPushGatewayResponse
     */
    public function delete(string $job, array $groupingKey = []) : void;
}
