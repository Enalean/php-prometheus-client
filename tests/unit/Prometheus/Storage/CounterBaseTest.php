<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage;

use PHPUnit\Framework\TestCase;
use Prometheus\Counter;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;
use Prometheus\Storage\CounterStorage;
use Prometheus\Storage\FlushableStorage;
use Prometheus\Storage\Store;
use Prometheus\Value\MetricLabelNames;
use Prometheus\Value\MetricName;
use function array_combine;
use function array_merge;
use function chr;
use function reset;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
abstract class CounterBaseTest extends TestCase
{
    /**
     * @return CounterStorage&Store
     */
    abstract protected function getStorage();

    /**
     * @before
     */
    protected function flushStorage() : void
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
    public function itShouldIncreaseWithLabels() : void
    {
        $storage = $this->getStorage();
        $counter = new Counter(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $counter->inc('lalal', 'lululu');
        $counter->inc('lalal', 'lululu');
        $counter->inc('lalal', 'lululu');
        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'counter',
                    'this is for testing',
                    ['foo', 'bar'],
                    [new Sample('test_some_metric', 3, [], ['lalal', 'lululu'])]
                ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldIncreaseWithoutLabelWhenNoLabelsAreDefined() : void
    {
        $storage = $this->getStorage();
        $counter = new Counter($storage, MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing');
        $counter->inc();
        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'counter',
                    'this is for testing',
                    [],
                    [new Sample('test_some_metric', 1, [], [])]
                ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldIncreaseTheCounterByAnArbitraryInteger() : void
    {
        $storage = $this->getStorage();
        $counter = new Counter(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $counter->inc('lalal', 'lululu');
        $counter->incBy(123, 'lalal', 'lululu');
        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'counter',
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
    public function itShouldIncreaseTheCounterByAnArbitraryFloat() : void
    {
        $storage = $this->getStorage();
        $counter = new Counter(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar')
        );
        $counter->inc('lalal', 'lululu');
        $counter->incBy(123.5, 'lalal', 'lululu');
        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'counter',
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
     * @dataProvider labelValuesDataProvider
     */
    public function isShouldAcceptAnySequenceOfBasicLatinCharactersForLabelValues(string $value) : void
    {
        $storage = $this->getStorage();
        $label   = 'foo';
        $counter = new Counter($storage, MetricName::fromNamespacedName('test', 'some_metric'), 'help', MetricLabelNames::fromNames($label));
        $counter->inc($value);

        $metrics = $storage->collect();
        self::assertCount(1, $metrics);
        self::assertContainsOnlyInstancesOf(MetricFamilySamples::class, $metrics);

        $metric  = reset($metrics);
        $samples = $metric->getSamples();
        self::assertContainsOnlyInstancesOf(Sample::class, $samples);

        foreach ($samples as $sample) {
            $labels = array_combine(
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
    public function labelValuesDataProvider() : array
    {
        $cases = [];
        // Basic Latin
        // See https://en.wikipedia.org/wiki/List_of_Unicode_characters#Basic_Latin
        for ($i = 32; $i <= 121; $i++) {
            $cases['ASCII code ' . $i] = [chr($i)];
        }

        return $cases;
    }
}
