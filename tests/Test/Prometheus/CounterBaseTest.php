<?php

declare(strict_types=1);

namespace Test\Prometheus;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Counter;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;
use Prometheus\Storage\Adapter;
use function array_combine;
use function array_merge;
use function chr;
use function reset;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
abstract class CounterBaseTest extends TestCase
{
    /** @var Adapter */
    public $adapter;

    protected function setUp() : void
    {
        $this->configureAdapter();
    }

    /**
     * @test
     */
    public function itShouldIncreaseWithLabels() : void
    {
        $gauge = new Counter($this->adapter, 'test', 'some_metric', 'this is for testing', ['foo', 'bar']);
        $gauge->inc(['lalal', 'lululu']);
        $gauge->inc(['lalal', 'lululu']);
        $gauge->inc(['lalal', 'lululu']);
        $this->assertThat(
            $this->adapter->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    [
                        'type' => Counter::TYPE,
                        'help' => 'this is for testing',
                        'name' => 'test_some_metric',
                        'labelNames' => ['foo', 'bar'],
                        'samples' => [
                            [
                                'labelValues' => ['lalal', 'lululu'],
                                'value' => 3,
                                'name' => 'test_some_metric',
                                'labelNames' => [],
                            ],
                        ],
                    ]
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
        $gauge = new Counter($this->adapter, 'test', 'some_metric', 'this is for testing');
        $gauge->inc();
        $this->assertThat(
            $this->adapter->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    [
                        'type' => Counter::TYPE,
                        'help' => 'this is for testing',
                        'name' => 'test_some_metric',
                        'labelNames' => [],
                        'samples' => [
                            [
                                'labelValues' => [],
                                'value' => 1,
                                'name' => 'test_some_metric',
                                'labelNames' => [],
                            ],
                        ],
                    ]
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
        $gauge = new Counter($this->adapter, 'test', 'some_metric', 'this is for testing', ['foo', 'bar']);
        $gauge->inc(['lalal', 'lululu']);
        $gauge->incBy(123, ['lalal', 'lululu']);
        $this->assertThat(
            $this->adapter->collect(),
            $this->equalTo(
                [new MetricFamilySamples(
                    [
                        'type' => Counter::TYPE,
                        'help' => 'this is for testing',
                        'name' => 'test_some_metric',
                        'labelNames' => ['foo', 'bar'],
                        'samples' => [
                            [
                                'labelValues' => ['lalal', 'lululu'],
                                'value' => 124,
                                'name' => 'test_some_metric',
                                'labelNames' => [],
                            ],
                        ],
                    ]
                ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function itShouldRejectInvalidMetricsNames() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new Counter($this->adapter, 'test', 'some metric invalid metric', 'help');
    }

    /**
     * @test
     */
    public function itShouldRejectInvalidLabelNames() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new Counter($this->adapter, 'test', 'some_metric', 'help', ['invalid label']);
    }

    /**
     * @param mixed $value The label value
     *
     * @test
     * @dataProvider labelValuesDataProvider
     */
    public function isShouldAcceptAnySequenceOfBasicLatinCharactersForLabelValues($value) : void
    {
        $label     = 'foo';
        $histogram = new Counter($this->adapter, 'test', 'some_metric', 'help', [$label]);
        $histogram->inc([$value]);

        $metrics = $this->adapter->collect();
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

    abstract public function configureAdapter() : void;
}
