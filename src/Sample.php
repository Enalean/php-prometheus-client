<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

use function count;

/**
 * @psalm-immutable
 */
final class Sample
{
    private string $name;
    /** @var string[] */
    private array $labelNames;
    /** @var string[] */
    private array $labelValues;
    private float $value;

    /**
     * @param string[] $labelNames
     * @param string[] $labelValues
     */
    public function __construct(string $name, float $value, array $labelNames, array $labelValues)
    {
        $this->name        = $name;
        $this->labelNames  = $labelNames;
        $this->labelValues = $labelValues;
        $this->value       = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getLabelNames(): array
    {
        return $this->labelNames;
    }

    /**
     * @return string[]
     */
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
