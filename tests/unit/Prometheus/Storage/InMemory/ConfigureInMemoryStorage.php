<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\InMemory;

use Prometheus\Storage\InMemoryStore;

trait ConfigureInMemoryStorage
{
    protected function getStorage() : InMemoryStore
    {
        return new InMemoryStore();
    }
}
