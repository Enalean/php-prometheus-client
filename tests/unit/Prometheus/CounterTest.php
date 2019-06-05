<?php

declare(strict_types=1);

namespace Test\Prometheus;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Counter;
use Prometheus\Storage\CounterStorage;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

/**
 * @covers Prometheus\Counter
 * @covers Prometheus\Metric
 */
final class CounterTest extends TestCase
{
    public function testStorageIsIncremented() : void
    {
        $storage = new class implements CounterStorage {
            /** @var float */
            public $value = 0;

            public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
            {
                $this->value += $value;
            }
        };

        $counter = new Counter($storage, MetricName::fromName('name'), 'help');
        $counter->inc();
        $this->assertEquals($storage->value, 1);
        $counter->inc();
        $this->assertEquals($storage->value, 2);
        $counter->incBy(3);
        $this->assertEquals($storage->value, 5);
    }

    public function testIncrementIsRejectedWhenLabelValuesAreNotDefinedCorrectly() : void
    {
        $counter = new Counter($this->getEmptyStorage(), MetricName::fromName('name'), 'help', MetricLabelNames::fromNames('labelA', 'labelB'));

        $this->expectException(InvalidArgumentException::class);
        $counter->inc('valueA');
    }

    public function testIncrementByACustomValueIsRejectedWhenLabelValuesAreNotDefinedCorrectly() : void
    {
        $counter = new Counter($this->getEmptyStorage(), MetricName::fromName('name'), 'help', MetricLabelNames::fromNames('labelA', 'labelB'));

        $this->expectException(InvalidArgumentException::class);
        $counter->inc('valueA');
    }

    private function getEmptyStorage() : CounterStorage
    {
        return new class implements CounterStorage {
            public function incrementCounter(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
            {
            }
        };
    }
}
