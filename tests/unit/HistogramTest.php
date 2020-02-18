<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest;

use Enalean\Prometheus\Histogram;
use Enalean\Prometheus\Storage\HistogramStorage;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Enalean\Prometheus\Histogram
 * @covers Enalean\Prometheus\Metric
 */
final class HistogramTest extends TestCase
{
    public function testStorageIsUpdatedWithObservedValues() : void
    {
        $storage = new class implements HistogramStorage {
            /** @var float[] */
            public $observedValues = [];

            /**
             * @inheritdoc
             */
            public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, string ...$labelValues) : void
            {
                $this->observedValues[] = $value;
            }
        };

        $histogram = new Histogram($storage, MetricName::fromName('name'), 'help');
        $histogram->observe(0);
        $histogram->observe(-4.2);
        $histogram->observe(3);
        $histogram->observe(0);

        self::assertEquals([0, -4.2, 3, 0], $storage->observedValues);
    }

    public function testHistogramMustHaveAtLeastOneBucket() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new Histogram($this->getEmptyStorage(), MetricName::fromName('name'), 'help', null, []);
    }

    public function testHistogramNeedsToBeOrderedInIncreasingOrder() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram buckets must be in increasing order: 2 >= 1');
        new Histogram($this->getEmptyStorage(), MetricName::fromName('name'), 'help', null, [2, 1]);
    }

    public function testHistogramCannotHaveIdenticalBuckets() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram buckets must be in increasing order: 1 >= 1');
        new Histogram($this->getEmptyStorage(), MetricName::fromName('name'), 'help', null, [1, 1]);
    }

    public function testObservationIsRejectedWhenLabelValuesAreNotDefinedCorrectly() : void
    {
        $counter = new Histogram($this->getEmptyStorage(), MetricName::fromName('name'), 'help', HistogramLabelNames::fromNames('labelA', 'labelB'));

        $this->expectException(InvalidArgumentException::class);
        $counter->observe(0, 'valueA');
    }

    public function testMetricInformationCanBeRetrieved() : void
    {
        $name       = MetricName::fromName('name');
        $help       = 'help';
        $labelNames = HistogramLabelNames::fromNames('labelA', 'labelB');

        $histogram = new Histogram($this->getEmptyStorage(), $name, $help, $labelNames);

        self::assertSame($name, $histogram->getName());
        self::assertSame($help, $histogram->getHelp());
        self::assertSame($labelNames, $histogram->getLabelNames());
    }

    private function getEmptyStorage() : HistogramStorage
    {
        return new class implements HistogramStorage {
            /**
             * @inheritdoc
             */
            public function updateHistogram(MetricName $name, float $value, array $buckets, string $help, HistogramLabelNames $labelNames, string ...$labelValues) : void
            {
            }
        };
    }
}
