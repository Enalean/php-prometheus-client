<?php

declare(strict_types=1);

namespace Prometheus;

use InvalidArgumentException;
use Prometheus\Storage\Storage;
use function count;
use function preg_match;
use function print_r;
use function sprintf;

abstract class Collector
{
    public const RE_METRIC_LABEL_NAME = '/^[a-zA-Z_:][a-zA-Z0-9_:]*$/';

    /** @var Storage */
    protected $storageAdapter;
    /** @var string */
    protected $name;
    /** @var string */
    protected $help;
    /** @var string[] */
    protected $labels;

    /**
     * @param string[] $labels
     */
    public function __construct(Storage $storageAdapter, string $namespace, string $name, string $help, array $labels = [])
    {
        $this->storageAdapter = $storageAdapter;
        $metricName           = ($namespace ? $namespace . '_' : '') . $name;
        if (! preg_match(self::RE_METRIC_LABEL_NAME, $metricName)) {
            throw new InvalidArgumentException("Invalid metric name: '" . $metricName . "'");
        }
        $this->name = $metricName;
        $this->help = $help;
        foreach ($labels as $label) {
            if (! preg_match(self::RE_METRIC_LABEL_NAME, $label)) {
                throw new InvalidArgumentException("Invalid label name: '" . $label . "'");
            }
        }
        $this->labels = $labels;
    }

    abstract public function getType() : string;

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getLabelNames() : array
    {
        return $this->labels;
    }

    public function getHelp() : string
    {
        return $this->help;
    }

    /**
     * @param string[] $labels
     */
    protected function assertLabelsAreDefinedCorrectly(array $labels) : void
    {
        if (count($labels) !== count($this->labels)) {
            throw new InvalidArgumentException(sprintf('Labels are not defined correctly: ', print_r($labels, true)));
        }
    }
}
