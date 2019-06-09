<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

use Enalean\Prometheus\Storage\CounterStorage;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;
use function sprintf;

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
     * @param string[] $labelValues e.g. ['status', 'opcode']
     */
    public function inc(string ...$labelValues) : void
    {
        $this->incBy(1, ...$labelValues);
    }

    /**
     * @param float    $count       e.g. 2
     * @param string[] $labelValues e.g. ['status', 'opcode']
     */
    public function incBy(float $count, string ...$labelValues) : void
    {
        if ($count <= 0) {
            throw new InvalidArgumentException(sprintf('Counter can only be incremented, %d is not positive', $count));
        }

        $this->assertLabelsAreDefinedCorrectly(...$labelValues);

        $this->storage->incrementCounter(
            $this->getName(),
            $count,
            $this->getHelp(),
            $this->getLabelNames(),
            ...$labelValues
        );
    }
}
