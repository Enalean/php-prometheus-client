<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Storage\CounterStorage;
use Prometheus\Value\MetricName;

class Counter extends Metric
{
    /** @var CounterStorage */
    private $storage;

    /**
     * @inheritdoc
     */
    public function __construct(CounterStorage $storage, MetricName $name, string $help, array $labels = [])
    {
        parent::__construct($name, $help, $labels);
        $this->storage = $storage;
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

        $this->storage->incrementCounter(
            $this->getName(),
            $this->getHelp(),
            [
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $count,
            ]
        );
    }
}
