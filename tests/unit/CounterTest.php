<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest;

use Enalean\Prometheus\Counter;
use Enalean\Prometheus\Storage\CounterStorage;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Enalean\Prometheus\Counter
 * @covers Enalean\Prometheus\Metric
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
        $counter->incBy(0.123);
        $this->assertEquals($storage->value, 5.123);
    }

    /**
     * @testWith [-1]
     *           [0]
     */
    public function testCounterCanOnlyBeIncremented(int $value) : void
    {
        $counter = new Counter($this->getEmptyStorage(), MetricName::fromName('name'), 'help');

        $this->expectException(InvalidArgumentException::class);
        $counter->incBy($value);
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

    public function testMetricInformationCanBeRetrieved() : void
    {
        $name       = MetricName::fromName('name');
        $help       = 'help';
        $labelNames = MetricLabelNames::fromNames('labelA', 'labelB');

        $counter = new Counter($this->getEmptyStorage(), $name, $help, $labelNames);

        $this->assertSame($name, $counter->getName());
        $this->assertSame($help, $counter->getHelp());
        $this->assertSame($labelNames, $counter->getLabelNames());
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
