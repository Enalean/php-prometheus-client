<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Storage\GaugeStorage;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

/**
 * @extends Metric<MetricLabelNames>
 */
final class Gauge extends Metric
{
    /** @var GaugeStorage */
    private $storage;

    public function __construct(GaugeStorage $storage, MetricName $name, string $help, ?MetricLabelNames $labelNames = null)
    {
        parent::__construct($name, $help, $labelNames ?? MetricLabelNames::fromNames());
        $this->storage = $storage;
    }

    /**
     * @param float    $value  e.g. 123
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function set(float $value, array $labels = []) : void
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storage->setGaugeTo(
            $this->getName(),
            $this->getHelp(),
            $this->getLabelNames(),
            [
                'labelValues' => $labels,
                'value' => $value,
            ]
        );
    }

    /**
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function inc(array $labels = []) : void
    {
        $this->incBy(1, $labels);
    }

    /**
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function incBy(float $value, array $labels = []) : void
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storage->addToGauge(
            $this->getName(),
            $this->getHelp(),
            $this->getLabelNames(),
            [
                'labelValues' => $labels,
                'value' => $value,
            ]
        );
    }

    /**
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function dec(array $labels = []) : void
    {
        $this->decBy(1, $labels);
    }

    /**
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function decBy(float $value, array $labels = []) : void
    {
        $this->incBy(-$value, $labels);
    }
}
