<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

/**
 * @psalm-immutable
 */
final class Sample
{
    /** @var string */
    private $name;
    /** @var string[] */
    private $labelNames;
    /** @var string[] */
    private $labelValues;
    /** @var float */
    private $value;

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

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getLabelNames() : array
    {
        return $this->labelNames;
    }

    /**
     * @return string[]
     */
    public function getLabelValues() : array
    {
        return $this->labelValues;
    }

    public function getValue() : float
    {
        return $this->value;
    }

    public function hasLabelNames() : bool
    {
        return ! empty($this->labelNames);
    }
}
