<?php

declare(strict_types=1);

namespace Test\Prometheus;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Gauge;
use Prometheus\Storage\GaugeStorage;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;

/**
 * @covers Prometheus\Gauge
 * @covers Prometheus\Metric
 */
final class GaugeTest extends TestCase
{
    public function testStorageIsUpdatedByGaugeModifications() : void
    {
        $storage = new class implements GaugeStorage {
            /** @var float */
            public $value = 0;

            public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
            {
                $this->value = $value;
            }

            public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
            {
                $this->value += $value;
            }
        };

        $gauge = new Gauge($storage, MetricName::fromName('name'), 'help');
        $gauge->inc();
        $this->assertEquals($storage->value, 1);
        $gauge->incBy(2.2);
        $this->assertEquals($storage->value, 3.2);
        $gauge->dec();
        $this->assertEquals($storage->value, 2.2);
        $gauge->decBy(2.1);
        $this->assertEquals($storage->value, 0.1);
        $gauge->set(-10);
        $this->assertEquals($storage->value, -10);
    }

    public function testIncrementIsRejectedWhenLabelValuesAreNotDefinedCorrectly() : void
    {
        $gauge = new Gauge($this->getEmptyStorage(), MetricName::fromName('name'), 'help', MetricLabelNames::fromNames('labelA', 'labelB'));

        $this->expectException(InvalidArgumentException::class);
        $gauge->inc('valueA');
    }

    public function testSettingTheGaugeToAnArbitraryValueIsRejectedWhenLabelValuesAreNotDefinedCorrectly() : void
    {
        $gauge = new Gauge($this->getEmptyStorage(), MetricName::fromName('name'), 'help', MetricLabelNames::fromNames('labelA', 'labelB'));

        $this->expectException(InvalidArgumentException::class);
        $gauge->set(10, 'valueA');
    }

    public function testMetricInformationCanBeRetrieved() : void
    {
        $name       = MetricName::fromName('name');
        $help       = 'help';
        $labelNames = MetricLabelNames::fromNames('labelA', 'labelB');

        $gauge = new Gauge($this->getEmptyStorage(), $name, $help, $labelNames);

        $this->assertSame($name, $gauge->getName());
        $this->assertSame($help, $gauge->getHelp());
        $this->assertSame($labelNames, $gauge->getLabelNames());
    }

    private function getEmptyStorage() : GaugeStorage
    {
        return new class implements GaugeStorage {
            public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
            {
            }

            public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues) : void
            {
            }
        };
    }
}
