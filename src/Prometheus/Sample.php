<?php

declare(strict_types=1);

namespace Prometheus;

class Sample
{
    /** @var string */
    private $name;
    /** @var string[] */
    private $labelNames;
    /** @var string[] */
    private $labelValues;
    /** @var int|double */
    private $value;

    /**
     * @param int|float $value
     * @param string[] $labelNames
     * @param string[] $labelValues
     */
    public function __construct(string $name, $value, array $labelNames, array $labelValues)
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

    /**
     * @return int|double
     */
    public function getValue()
    {
        return $this->value;
    }

    public function hasLabelNames() : bool
    {
        return ! empty($this->labelNames);
    }
}
