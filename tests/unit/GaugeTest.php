<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest;

use Enalean\Prometheus\Gauge;
use Enalean\Prometheus\Storage\GaugeStorage;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Enalean\Prometheus\Gauge
 * @covers Enalean\Prometheus\Metric
 */
final class GaugeTest extends TestCase
{
    public function testStorageIsUpdatedByGaugeModifications(): void
    {
        $storage = new class implements GaugeStorage {
            /** @var float */
            public $value = 0;

            public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void
            {
                $this->value = $value;
            }

            public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void
            {
                $this->value += $value;
            }
        };

        $gauge = new Gauge($storage, MetricName::fromName('name'), 'help');
        $gauge->inc();
        self::assertEquals($storage->value, 1);
        $gauge->incBy(2.2);
        self::assertEquals($storage->value, 3.2);
        $gauge->dec();
        self::assertEquals($storage->value, 2.2);
        $gauge->decBy(2.1);
        self::assertEquals($storage->value, 0.1);
        $gauge->set(-10);
        self::assertEquals($storage->value, -10);
    }

    public function testIncrementIsRejectedWhenLabelValuesAreNotDefinedCorrectly(): void
    {
        $gauge = new Gauge($this->getEmptyStorage(), MetricName::fromName('name'), 'help', MetricLabelNames::fromNames('labelA', 'labelB'));

        $this->expectException(InvalidArgumentException::class);
        $gauge->inc('valueA');
    }

    public function testSettingTheGaugeToAnArbitraryValueIsRejectedWhenLabelValuesAreNotDefinedCorrectly(): void
    {
        $gauge = new Gauge($this->getEmptyStorage(), MetricName::fromName('name'), 'help', MetricLabelNames::fromNames('labelA', 'labelB'));

        $this->expectException(InvalidArgumentException::class);
        $gauge->set(10, 'valueA');
    }

    public function testMetricInformationCanBeRetrieved(): void
    {
        $name       = MetricName::fromName('name');
        $help       = 'help';
        $labelNames = MetricLabelNames::fromNames('labelA', 'labelB');

        $gauge = new Gauge($this->getEmptyStorage(), $name, $help, $labelNames);

        self::assertSame($name, $gauge->getName());
        self::assertSame($help, $gauge->getHelp());
        self::assertSame($labelNames, $gauge->getLabelNames());
    }

    private function getEmptyStorage(): GaugeStorage
    {
        return new class implements GaugeStorage {
            public function setGaugeTo(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void
            {
            }

            public function addToGauge(MetricName $name, float $value, string $help, MetricLabelNames $labelNames, string ...$labelValues): void
            {
            }
        };
    }
}
