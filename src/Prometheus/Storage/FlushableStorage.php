<?php

declare(strict_types=1);

namespace Prometheus\Storage;

interface FlushableStorage
{
    public function flush() : void;
}
