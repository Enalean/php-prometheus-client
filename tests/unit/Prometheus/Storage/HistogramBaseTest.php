<?php

declare(strict_types=1);

namespace Test\Prometheus\Storage;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;
use Prometheus\Storage\FlushableStorage;
use Prometheus\Storage\HistogramStorage;
use Prometheus\Storage\Store;
use Prometheus\Value\MetricName;
use function array_combine;
use function array_merge;
use function chr;
use function reset;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
abstract class HistogramBaseTest extends TestCase
{
    /**
     * @return HistogramStorage&Store
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
    public function itShouldObserveWithLabels() : void
    {
        $storage   = $this->getStorage();
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            ['foo', 'bar'],
            [100, 200, 300]
        );
        $histogram->observe(123, ['lalal', 'lululu']);
        $histogram->observe(245, ['lalal', 'lululu']);

        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'histogram',
                    'this is for testing',
                    ['foo', 'bar'],
                    [
                        new Sample('test_some_metric_bucket', 0, ['le'], ['lalal', 'lululu', '100']),
                        new Sample('test_some_metric_bucket', 1, ['le'], ['lalal', 'lululu', '200']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['lalal', 'lululu', '300']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['lalal', 'lululu', '+Inf']),
                        new Sample('test_some_metric_count', 2, [], ['lalal', 'lululu']),
                        new Sample('test_some_metric_sum', 368, [], ['lalal', 'lululu']),
                    ]
                ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldObserveWithoutLabelWhenNoLabelsAreDefined() : void
    {
        $storage   = $this->getStorage();
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            [],
            [100, 200, 300]
        );
        $histogram->observe(245);
        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'histogram',
                    'this is for testing',
                    [],
                    [
                        new Sample('test_some_metric_bucket', 0, ['le'], ['100']),
                        new Sample('test_some_metric_bucket', 0, ['le'], ['200']),
                        new Sample('test_some_metric_bucket', 1, ['le'], ['300']),
                        new Sample('test_some_metric_bucket', 1, ['le'], ['+Inf']),
                        new Sample('test_some_metric_count', 1, [], []),
                        new Sample('test_some_metric_sum', 245, [], []),
                    ]
                ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldObserveValuesOfTypeFloat() : void
    {
        $storage   = $this->getStorage();
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            [],
            [0.1, 0.2, 0.3]
        );
        $histogram->observe(0.11);
        $histogram->observe(0.3);
        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'histogram',
                    'this is for testing',
                    [],
                    [
                        new Sample('test_some_metric_bucket', 0, ['le'], ['0.1']),
                        new Sample('test_some_metric_bucket', 1, ['le'], ['0.2']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['0.3']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['+Inf']),
                        new Sample('test_some_metric_count', 2, [], []),
                        new Sample('test_some_metric_sum', 0.41, [], []),
                    ]
                ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldProvideDefaultBuckets() : void
    {
        $storage = $this->getStorage();
        // .005, .01, .025, .05, .075, .1, .25, .5, .75, 1.0, 2.5, 5.0, 7.5, 10.0
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            []
        );
        $histogram->observe(0.11);
        $histogram->observe(0.03);
        $this->assertThat(
            $storage->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    'test_some_metric',
                    'histogram',
                    'this is for testing',
                    [],
                    [
                        new Sample('test_some_metric_bucket', 0, ['le'], ['0.005']),
                        new Sample('test_some_metric_bucket', 0, ['le'], ['0.01']),
                        new Sample('test_some_metric_bucket', 0, ['le'], ['0.025']),
                        new Sample('test_some_metric_bucket', 1, ['le'], ['0.05']),
                        new Sample('test_some_metric_bucket', 1, ['le'], ['0.075']),
                        new Sample('test_some_metric_bucket', 1, ['le'], ['0.1']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['0.25']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['0.5']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['0.75']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['1']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['2.5']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['5']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['7.5']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['10']),
                        new Sample('test_some_metric_bucket', 2, ['le'], ['+Inf']),
                        new Sample('test_some_metric_count', 2, [], []),
                        new Sample('test_some_metric_sum', 0.14, [], []),
                    ]
                ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldThrowAnExceptionWhenTheBucketSizesAreNotIncreasing() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram buckets must be in increasing order');
        new Histogram($this->getStorage(), MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing', [], [1, 1]);
    }

    /**
     * @test
     */
    public function itShouldThrowAnExceptionWhenThereIsLessThanOneBucket() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram must have at least one bucket');
        new Histogram($this->getStorage(), MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing', [], []);
    }

    /**
     * @test
     */
    public function itShouldThrowAnExceptionWhenThereIsALabelNamedLe() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram cannot have a label named');
        new Histogram($this->getStorage(), MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing', ['le'], [1]);
    }

    /**
     * @test
     */
    public function itShouldRejectInvalidLabelNames() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid label name');
        new Histogram($this->getStorage(), MetricName::fromNamespacedName('test', 'some_metric'), 'help', ['invalid label'], [1]);
    }

    /**
     * @param mixed $value The label value
     *
     * @tes
     * @dataProvider labelValuesDataProvider
     */
    public function isShouldAcceptAnySequenceOfBasicLatinCharactersForLabelValues($value) : void
    {
        $storage   = $this->getStorage();
        $label     = 'foo';
        $histogram = new Histogram($storage, MetricName::fromNamespacedName('test', 'some_metric'), 'help', [$label], [1]);
        $histogram->observe(1, [$value]);

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
