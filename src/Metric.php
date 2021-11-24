<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

use Enalean\Prometheus\Value\LabelNames;
use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;

use function count;
use function print_r;
use function sprintf;

/**
 * @template TLabelNames of LabelNames
 */
abstract class Metric
{
    /**
     * @psalm-param TLabelNames $labelNames
     */
    public function __construct(private MetricName $name, private string $help, private LabelNames $labelNames)
    {
    }

    /**
     * @psalm-mutation-free
     */
    public function getName(): MetricName
    {
        return $this->name;
    }

    /**
     * @psalm-return TLabelNames
     *
     * @psalm-mutation-free
     */
    public function getLabelNames(): LabelNames
    {
        return $this->labelNames;
    }

    /**
     * @psalm-mutation-free
     */
    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * @psalm-mutation-free
     */
    final protected function assertLabelsAreDefinedCorrectly(string ...$labelValues): void
    {
        if (count($labelValues) !== count($this->labelNames)) {
            throw new InvalidArgumentException(sprintf('Labels are not defined correctly: %s', print_r($labelValues, true)));
        }
    }
}
