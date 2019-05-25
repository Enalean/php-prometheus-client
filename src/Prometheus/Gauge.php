<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Storage\GaugeStorage;
use Prometheus\Storage\StorageCommand;
use Prometheus\Value\MetricName;

class Gauge extends Metric
{
    private const TYPE = 'gauge';

    /** @var GaugeStorage */
    private $storage;

    /**
     * @inheritdoc
     */
    public function __construct(GaugeStorage $storage, MetricName $name, string $help, array $labels = [])
    {
        parent::__construct($name, $help, $labels);
        $this->storage = $storage;
    }

    /**
     * @param float    $value  e.g. 123
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function set(float $value, array $labels = []) : void
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storage->updateGauge(
            $this->getName(),
            $this->getHelp(),
            [
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $value,
                'command' => StorageCommand::COMMAND_SET,
            ]
        );
    }

    public function getType() : string
    {
        return self::TYPE;
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

        $this->storage->updateGauge(
            $this->getName(),
            $this->getHelp(),
            [
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $value,
                'command' => StorageCommand::COMMAND_INCREMENT_FLOAT,
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
