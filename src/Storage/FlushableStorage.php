<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Storage;

interface FlushableStorage
{
    public function flush(): void;
}
