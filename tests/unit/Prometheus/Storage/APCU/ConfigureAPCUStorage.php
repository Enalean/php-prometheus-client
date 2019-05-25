<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage\APCU;

use Prometheus\Storage\APCUStore;

trait ConfigureAPCUStorage
{
    protected function getStorage() : APCUStore
    {
        return new APCUStore();
    }
}
