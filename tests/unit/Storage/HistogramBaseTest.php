<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage;

use Enalean\Prometheus\Histogram;
use Enalean\Prometheus\MetricFamilySamples;
use Enalean\Prometheus\Sample;
use Enalean\Prometheus\Storage\FlushableStorage;
use Enalean\Prometheus\Storage\HistogramStorage;
use Enalean\Prometheus\Storage\Store;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function array_combine;
use function array_merge;
use function assert;
use function chr;
use function reset;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
abstract class HistogramBaseTest extends TestCase
{
    /** @return HistogramStorage&Store */
    abstract protected function getStorage();

    /** @before */
    protected function flushStorage(): void
    {
        $storage = $this->getStorage();
        if (! ($storage instanceof FlushableStorage)) {
            return;
        }

        $storage->flush();
    }

    /** @test */
    public function itShouldObserveWithLabels(): void
    {
        $storage   = $this->getStorage();
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            HistogramLabelNames::fromNames('foo', 'bar'),
            [100, 200, 300],
        );
        $histogram->observe(123, 'lalal', 'lululu');
        $histogram->observe(245, 'lalal', 'lululu');
        $histogram->observe(254, 'lalal', 'lululu');

        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'histogram',
                        'this is for testing',
                        ['foo', 'bar'],
                        [
                            new Sample('test_some_metric_bucket', 0, ['le'], ['lalal', 'lululu', '100']),
                            new Sample('test_some_metric_bucket', 1, ['le'], ['lalal', 'lululu', '200']),
                            new Sample('test_some_metric_bucket', 3, ['le'], ['lalal', 'lululu', '300']),
                            new Sample('test_some_metric_bucket', 3, ['le'], ['lalal', 'lululu', '+Inf']),
                            new Sample('test_some_metric_count', 3, [], ['lalal', 'lululu']),
                            new Sample('test_some_metric_sum', 622, [], ['lalal', 'lululu']),
                        ],
                    ),
                ],
            ),
        );
    }

    /** @test */
    public function itShouldObserveWithoutLabelWhenNoLabelsAreDefined(): void
    {
        $storage   = $this->getStorage();
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            null,
            [100, 200, 300],
        );
        $histogram->observe(245);
        $histogram->observe(254);
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
                        'test_some_metric',
                        'histogram',
                        'this is for testing',
                        [],
                        [
                            new Sample('test_some_metric_bucket', 0, ['le'], ['100']),
                            new Sample('test_some_metric_bucket', 0, ['le'], ['200']),
                            new Sample('test_some_metric_bucket', 2, ['le'], ['300']),
                            new Sample('test_some_metric_bucket', 2, ['le'], ['+Inf']),
                            new Sample('test_some_metric_count', 2, [], []),
                            new Sample('test_some_metric_sum', 499, [], []),
                        ],
                    ),
                ],
            ),
        );
    }

    /** @test */
    public function itShouldObserveValuesOfTypeFloat(): void
    {
        $storage   = $this->getStorage();
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            null,
            [0.1, 0.2, 0.3],
        );
        $histogram->observe(0.11);
        $histogram->observe(0.3);
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
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
                        ],
                    ),
                ],
            ),
        );
    }

    /** @test */
    public function itShouldProvideDefaultBuckets(): void
    {
        $storage = $this->getStorage();
        // .005, .01, .025, .05, .075, .1, .25, .5, .75, 1.0, 2.5, 5.0, 7.5, 10.0
        $histogram = new Histogram(
            $storage,
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            null,
        );
        $histogram->observe(0.11);
        $histogram->observe(0.03);
        self::assertThat(
            $storage->collect(),
            self::equalTo(
                [
                    new MetricFamilySamples(
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
                        ],
                    ),
                ],
            ),
        );
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenTheBucketSizesAreNotIncreasing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram buckets must be in increasing order');
        new Histogram($this->getStorage(), MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing', null, [1, 1]);
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenThereIsLessThanOneBucket(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram must have at least one bucket');
        new Histogram($this->getStorage(), MetricName::fromNamespacedName('test', 'some_metric'), 'this is for testing', null, []);
    }

    /**
     * @test
     * @dataProvider labelValuesDataProvider
     */
    public function isShouldAcceptAnySequenceOfBasicLatinCharactersForLabelValues(string $value): void
    {
        $storage   = $this->getStorage();
        $label     = 'foo';
        $histogram = new Histogram($storage, MetricName::fromNamespacedName('test', 'some_metric'), 'help', HistogramLabelNames::fromNames($label), [1]);
        $histogram->observe(1, $value);

        $metrics = $storage->collect();
        self::assertCount(1, $metrics);
        self::assertContainsOnlyInstancesOf(MetricFamilySamples::class, $metrics);

        $metric = reset($metrics);
        assert($metric !== false);
        $samples = $metric->getSamples();
        self::assertContainsOnlyInstancesOf(Sample::class, $samples);

        foreach ($samples as $sample) {
            $labels = array_combine(
                array_merge($metric->getLabelNames(), $sample->getLabelNames()),
                $sample->getLabelValues(),
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
        $storage             = $this->getStorage();
        $expectedHistogramNb = 3;
        for ($i = 0; $i < $expectedHistogramNb; $i++) {
            $histogram = new Histogram(
                $storage,
                MetricName::fromNamespacedName('test', 'some_metric_' . $i),
                'Some test ' . $i,
            );
            $histogram->observe(1.0);
        }

        $samples = $storage->collect();
        self::assertCount($expectedHistogramNb, $samples);
    }
}
