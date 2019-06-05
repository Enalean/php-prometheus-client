<?php

declare(strict_types=1);

namespace Test\Prometheus;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Histogram;
use Prometheus\Storage\HistogramStorage;
use Prometheus\Value\HistogramLabelNames;
use Prometheus\Value\MetricName;

/**
 * @covers Prometheus\Histogram
 * @covers Prometheus\Metric
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

        $this->assertEquals([0, -4.2, 3, 0], $storage->observedValues);
    }

    public function testHistogramMustHaveAtLeastOneBucket() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new Histogram($this->getEmptyStorage(), MetricName::fromName('name'), 'help', null, []);
    }

    public function testHistogramNeedsToBeOrderedInIncreasingOrder() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new Histogram($this->getEmptyStorage(), MetricName::fromName('name'), 'help', null, [2, 1]);
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
