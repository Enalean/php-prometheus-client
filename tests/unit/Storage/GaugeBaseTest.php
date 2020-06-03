<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage;

use Enalean\Prometheus\Gauge;
use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Sample;
use Enalean\Prometheus\Storage\FlushableStorage;
use Enalean\Prometheus\Storage\GaugeStorage;
use Enalean\Prometheus\Storage\Store;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use PHPUnit\Framework\TestCase;

use function array_combine;
use function array_merge;
use function assert;
use function chr;
use function reset;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
abstract class GaugeBaseTest extends TestCase
{
    /**
     * @return GaugeStorage&Store
     */
    abstract protected function getStorage();

    /**
     * @before
     */
    protected function flushStorage(): void
    {
        $storage = $this->getStorage();
        if (! ($storage instanceof FlushableStorage)) {
            return;
        }

        $storage->flush();
    }

    /**
     * @test
     */
    public function itShouldAllowSetWithLabels(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $gauge->set(123, 'lalal', 'lululu');
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        ['foo', 'bar'],
                        [new Sample('test_some_metric', 123, [], ['lalal', 'lululu'])]
                    ),
                ]
            )
        );
        self::assertThat($gauge->getHelp(), self::equalTo('this is for testing'));
    }

    /**
     * @test
     */
    public function itShouldAllowSetWithoutLabelWhenNoLabelsAreDefined(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge($storage, MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing');
        $gauge->set(123);
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        [],
                        [new Sample('test_some_metric', 123, [], [])]
                    ),
                ]
            )
        );
        self::assertThat($gauge->getHelp(), self::equalTo('this is for testing'));
    }

    /**
     * @test
     */
    public function itShouldAllowSetWithAFloatValue(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge($storage, MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing');
        $gauge->set(123.5);
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        [],
                        [new Sample('test_some_metric', 123.5, [], [])]
                    ),
                ]
            )
        );
        self::assertThat($gauge->getHelp(), self::equalTo('this is for testing'));
    }

    /**
     * @test
     */
    public function itShouldIncrementAValue(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $gauge->inc('lalal', 'lululu');
        $gauge->incBy(123, 'lalal', 'lululu');
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        ['foo', 'bar'],
                        [new Sample('test_some_metric', 124, [], ['lalal', 'lululu'])]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldIncrementWithFloatValue(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $gauge->inc('lalal', 'lululu');
        $gauge->incBy(123.5, 'lalal', 'lululu');
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        ['foo', 'bar'],
                        [new Sample('test_some_metric', 124.5, [], ['lalal', 'lululu'])]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldDecrementAValue(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $gauge->dec('lalal', 'lululu');
        $gauge->decBy(123, 'lalal', 'lululu');
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        ['foo', 'bar'],
                        [new Sample('test_some_metric', -124, [], ['lalal', 'lululu'])]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldDecrementWithFloatValue(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $gauge->dec('lalal', 'lululu');
        $gauge->decBy(122.5, 'lalal', 'lululu');
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        ['foo', 'bar'],
                        [new Sample('test_some_metric', -123.5, [], ['lalal', 'lululu'])]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldOverwriteWhenSettingTwice(): void
    {
        $storage = $this->getStorage();
        $gauge   = new Gauge(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $gauge->set(123, 'lalal', 'lululu');
        $gauge->set(321, 'lalal', 'lululu');
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'gauge',
                        'this is for testing',
                        ['foo', 'bar'],
                        [new Sample('test_some_metric', 321, [], ['lalal', 'lululu'])]
                    ),
                ]
            )
        );
    }

    /**
     * @test
     * @dataProvider labelValuesDataProvider
     */
    public function isShouldAcceptAnySequenceOfBasicLatinCharactersForLabelValues(string $value): void
    {
        $storage   = $this->getStorage();
        $label     = 'foo';
        $histogram = new Gauge($storage, MetricName::fromNamespacedName('test', 'some_metric'), 'help', MetricLabelNames::fromNames($label));
        $histogram->inc($value);

        $metrics = $storage->collect();
        self::assertCount(1, $metrics);
        self::assertContainsOnlyInstancesOf(MetricFamilySamples::class, $metrics);

        $metric = reset($metrics);
        assert($metric !== false);
        $samples = $metric->getSamples();
        self::assertContainsOnlyInstancesOf(Sample::class, $samples);

        foreach ($samples as $sample) {
            $labels = (array) array_combine(
                array_merge($metric->getLabelNames(), $sample->getLabelNames()),
                $sample->getLabelValues()
            );
            self::assertEquals($value, $labels[$label]);
        }
    }

    /**
     * @see isShouldAcceptArbitraryLabelValues
     *
     * @return array<string,string[]>
     */
    public function labelValuesDataProvider(): array
    {
        $cases = [];
        // Basic Latin
        // See https://en.wikipedia.org/wiki/List_of_Unicode_characters#Basic_Latin
        for ($i = 32; $i <= 121; $i++) {
            $cases['ASCII code ' . $i] = [chr($i)];
        }

        return $cases;
    }

    public function testMultipleGaugesCanBeStored(): void
    {
        $storage         = $this->getStorage();
        $expectedGaugeNb = 3;
        for ($i = 0; $i < $expectedGaugeNb; $i++) {
            $gauge = new Gauge(
                $storage,
                MetricName::fromNamespacedName('test', 'some_metric_' . $i),
                'Some test ' . $i
            );
            $gauge->inc();
        }

        $samples = $storage->collect();
        self::assertCount($expectedGaugeNb, $samples);
    }
}
