<?php

declare(strict_types=1);

namespace Enalean\Prometheus;

use Enalean\Prometheus\Storage\HistogramStorage;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;

use function count;

/**
 * @extends Metric<HistogramLabelNames>
 */
final class Histogram extends Metric
{
    /**
     * List of default buckets suitable for typical web application latency metrics
     */
    public const DEFAULT_BUCKETS = [
        0.005,
        0.01,
        0.025,
        0.05,
        0.075,
        0.1,
        0.25,
        0.5,
        0.75,
        1.0,
        2.5,
        5.0,
        7.5,
        10.0,
    ];

    /** @var float[] */
    private array $buckets;

    /**
     * @param float[] $buckets
     */
    public function __construct(private HistogramStorage $storage, MetricName $name, string $help, ?HistogramLabelNames $labelNames = null, ?array $buckets = null)
    {
        parent::__construct($name, $help, $labelNames ?? HistogramLabelNames::fromNames());

        if ($buckets === null) {
            $buckets = self::DEFAULT_BUCKETS;
        }

        if (count($buckets) === 0) {
            throw new InvalidArgumentException('Histogram must have at least one bucket.');
        }

        for ($i = 0; $i < count($buckets) - 1; $i++) {
            if ($buckets[$i] >= $buckets[$i + 1]) {
                throw new InvalidArgumentException(
                    'Histogram buckets must be in increasing order: ' .
                    $buckets[$i] . ' >= ' . $buckets[$i + 1]
                );
            }
        }

        $this->buckets = $buckets;
    }

    /**
     * @param float  $value          e.g. 123
     * @param string ...$labelValues e.g. ['status', 'opcode']
     */
    public function observe(float $value, string ...$labelValues): void
    {
        /** @psalm-suppress UnusedMethodCall */
        $this->assertLabelsAreDefinedCorrectly(...$labelValues);

        $this->storage->updateHistogram(
            $this->getName(),
            $value,
            $this->buckets,
            $this->getHelp(),
            $this->getLabelNames(),
            ...$labelValues
        );
    }
}
