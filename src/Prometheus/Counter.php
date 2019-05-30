<?php

declare(strict_types=1);

namespace Prometheus;

use Prometheus\Storage\CounterStorage;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

/**
 * @extends Metric<MetricLabelNames>
 */
final class Counter extends Metric
{
    /** @var CounterStorage */
    private $storage;

    public function __construct(CounterStorage $storage, MetricName $name, string $help, ?MetricLabelNames $labelNames = null)
    {
        parent::__construct($name, $help, $labelNames ?? MetricLabelNames::fromNames());
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
            $this->getLabelNames(),
            [
                'labelValues' => $labels,
                'value' => $count,
            ]
        );
    }
}
