<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\InMemory;

use Enalean\Prometheus\Storage\InMemoryStore;

trait ConfigureInMemoryStorage
{
    protected function getStorage() : InMemoryStore
    {
        return new InMemoryStore();
    }
}
