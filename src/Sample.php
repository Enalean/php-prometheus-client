<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

use function count;

/** @psalm-immutable */
final class Sample
{
    /**
     * @param string[] $labelNames
     * @param string[] $labelValues
     */
    public function __construct(private string $name, private float $value, private array $labelNames, private array $labelValues)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return string[] */
    public function getLabelNames(): array
    {
        return $this->labelNames;
    }

    /** @return string[] */
    public function getLabelValues(): array
    {
        return $this->labelValues;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function hasLabelNames(): bool
    {
        return count($this->labelNames) !== 0;
    }
}
