<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Storage\Storage;

class Counter extends Collector
{
    private const TYPE = 'counter';

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
     * @param int      $count  e.g. 2
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function incBy(int $count, array $labels = []) : void
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storageAdapter->updateCounter(
            [
                'name' => $this->getName(),
                'help' => $this->getHelp(),
                'type' => $this->getType(),
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $count,
                'command' => Storage::COMMAND_INCREMENT_INTEGER,
            ]
        );
    }
}
