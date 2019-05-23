<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Storage\Storage;

class Gauge extends Collector
{
    private const TYPE = 'gauge';

    /**
     * @param float    $value  e.g. 123
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function set(float $value, array $labels = []) : void
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storageAdapter->updateGauge(
            [
                'name' => $this->getName(),
                'help' => $this->getHelp(),
                'type' => $this->getType(),
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $value,
                'command' => Storage::COMMAND_SET,
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

        $this->storageAdapter->updateGauge(
            [
                'name' => $this->getName(),
                'help' => $this->getHelp(),
                'type' => $this->getType(),
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $value,
                'command' => Storage::COMMAND_INCREMENT_FLOAT,
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
