<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

final class MetricFamilySamples
{
    /** @var string */
    private $name;
    /** @var string */
    private $type;
    /** @var string */
    private $help;
    /** @var string[] */
    private $labelNames;
    /** @var Sample[] */
    private $samples;

    /**
     * @param string[] $labelNames
     * @param Sample[] $samples
     */
    public function __construct(string $name, string $type, string $help, array $labelNames, array $samples)
    {
        $this->name       = $name;
        $this->type       = $type;
        $this->help       = $help;
        $this->labelNames = $labelNames;
        $this->samples    = $samples;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getHelp() : string
    {
        return $this->help;
    }

    /**
     * @return Sample[]
     */
    public function getSamples() : array
    {
        return $this->samples;
    }

    /**
     * @return string[]
     */
    public function getLabelNames() : array
    {
        return $this->labelNames;
    }

    public function hasLabelNames() : bool
    {
        return ! empty($this->labelNames);
    }
}
