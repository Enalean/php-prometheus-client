<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

use function count;

/** @psalm-immutable */
final class MetricFamilySamples
{
    /**
     * @param string[] $labelNames
     * @param Sample[] $samples
     */
    public function __construct(private string $name, private string $type, private string $help, private array $labelNames, private array $samples)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    /** @return Sample[] */
    public function getSamples(): array
    {
        return $this->samples;
    }

    /** @return string[] */
    public function getLabelNames(): array
    {
        return $this->labelNames;
    }

    public function hasLabelNames(): bool
    {
        return count($this->labelNames) !== 0;
    }
}
