<?php

declare(strict_types=1);

namespace Prometheus\Value;

use Countable;

interface LabelNames extends Countable
{
    /**
     * @return string[]
     */
    public function toStrings() : array;
}
