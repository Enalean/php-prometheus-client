<?php

declare(strict_types=1);

namespace Prometheus\Storage;

interface StorageCommand
{
    public const COMMAND_INCREMENT_INTEGER = 1;
    public const COMMAND_INCREMENT_FLOAT   = 2;
    public const COMMAND_SET               = 3;
}
