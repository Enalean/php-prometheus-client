<?php

declare(strict_types=1);

namespace Prometheus;

class MetricFamilySamples
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
    private $samples = [];

    /**
     * @param string[]                      $labelNames
     * @param array<string|int|float|array> $samples
     *
     * @psalm-param array<array{name:string, value:int|double, labelValues:string[], labelNames:string[]}> $samples
     */
    public function __construct(string $name, string $type, string $help, array $labelNames, array $samples)
    {
        $this->name       = $name;
        $this->type       = $type;
        $this->help       = $help;
        $this->labelNames = $labelNames;
        foreach ($samples as $sampleData) {
            $this->samples[] = new Sample($sampleData['name'], $sampleData['value'], $sampleData['labelNames'], $sampleData['labelValues']);
        }
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
