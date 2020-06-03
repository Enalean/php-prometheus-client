<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage\APCU;

use Enalean\Prometheus\Storage\APCUStore;

trait ConfigureAPCUStorage
{
    protected function getStorage(): APCUStore
    {
        return new APCUStore();
    }
}
