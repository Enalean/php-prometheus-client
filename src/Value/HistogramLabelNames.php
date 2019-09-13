<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Value;

use InvalidArgumentException;
use function count;
use function sprintf;

/**
 * @psalm-immutable
 */
final class HistogramLabelNames implements LabelNames
{
    private const RESERVED_LABEL_HISTOGRAM = 'le';

    /** @var MetricLabelNames */
    private $names;

    private function __construct(MetricLabelNames $names)
    {
        $this->names = $names;
    }

    public static function fromNames(string ...$names) : self
    {
        foreach ($names as $name) {
            if ($name === self::RESERVED_LABEL_HISTOGRAM) {
                throw new InvalidArgumentException(
                    sprintf('Histogram label name cannot be "%s".', self::RESERVED_LABEL_HISTOGRAM)
                );
            }
        }

        return new self(MetricLabelNames::fromNames(...$names));
    }

    /**
     * @inheritdoc
     */
    public function toStrings() : array
    {
        return $this->names->toStrings();
    }

    public function count() : int
    {
        return count($this->names);
    }
}
