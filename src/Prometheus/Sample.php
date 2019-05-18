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
     * @param array<string, int|double|array> $data
     */
    public function __construct(array $data)
    {
        $this->name        = $data['name'];
        $this->labelNames  = (array) $data['labelNames'];
        $this->labelValues = (array) $data['labelValues'];
        $this->value       = $data['value'];
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
