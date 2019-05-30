<?php

declare(strict_types=1);

namespace Prometheus;

use InvalidArgumentException;
use Prometheus\Value\LabelNames;
use Prometheus\Value\MetricName;
use function count;
use function print_r;
use function sprintf;

/**
 * @template TLabelNames of LabelNames
 */
abstract class Metric
{
    public const RE_METRIC_LABEL_NAME = '/^[a-zA-Z_:][a-zA-Z0-9_:]*$/';

    /** @var MetricName */
    private $name;
    /** @var string */
    private $help;
    /**
     * @var LabelNames
     * @psalm-var TLabelNames
     * */
    private $labelNames;

    /**
     * @psalm-param TLabelNames $labelNames
     */
    public function __construct(MetricName $name, string $help, LabelNames $labelNames)
    {
        $this->name       = $name;
        $this->help       = $help;
        $this->labelNames = $labelNames;
    }

    public function getName() : MetricName
    {
        return $this->name;
    }

    /**
     * @psalm-return TLabelNames
     */
    public function getLabelNames() : LabelNames
    {
        return $this->labelNames;
    }

    public function getHelp() : string
    {
        return $this->help;
    }

    final protected function assertLabelsAreDefinedCorrectly(string ...$labelValues) : void
    {
        if (count($labelValues) !== count($this->labelNames)) {
            throw new InvalidArgumentException(sprintf('Labels are not defined correctly: ', print_r($labelValues, true)));
        }
    }
}
