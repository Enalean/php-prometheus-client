<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Value;

use Countable;

interface LabelNames extends Countable
{
    /**
     * @return string[]
     */
    public function toStrings(): array;
}
