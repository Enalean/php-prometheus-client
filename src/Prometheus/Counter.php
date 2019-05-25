<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Storage\CounterStorage;
use Prometheus\Storage\StorageCommand;

class Counter extends Metric
{
    private const TYPE = 'counter';

    /** @var CounterStorage */
    private $storage;

    /**
     * @inheritdoc
     */
    public function __construct(CounterStorage $storage, string $namespace, string $name, string $help, array $labels = [])
    {
        parent::__construct($namespace, $name, $help, $labels);
        $this->storage = $storage;
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
     * @param int      $count  e.g. 2
     * @param string[] $labels e.g. ['status', 'opcode']
     */
    public function incBy(int $count, array $labels = []) : void
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storage->updateCounter(
            [
                'name' => $this->getName(),
                'help' => $this->getHelp(),
                'type' => $this->getType(),
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $count,
                'command' => StorageCommand::COMMAND_INCREMENT_INTEGER,
            ]
        );
    }
}
