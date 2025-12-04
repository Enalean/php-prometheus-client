<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Storage;

use Enalean\Prometheus\Exception\MetricNotFoundException;
use Enalean\Prometheus\Exception\MetricsRegistrationException;
use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Renderer\RenderTextFormat;
use Enalean\Prometheus\Storage\CounterStorage;
use Enalean\Prometheus\Storage\FlushableStorage;
use Enalean\Prometheus\Storage\GaugeStorage;
use Enalean\Prometheus\Storage\HistogramStorage;
use Enalean\Prometheus\Storage\Store;
use Enalean\Prometheus\Value\HistogramLabelNames;
use Enalean\Prometheus\Value\MetricLabelNames;
use Enalean\Prometheus\Value\MetricName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

abstract class CollectorRegistryTestBase extends TestCase
{
    private RenderTextFormat $renderer;

    /** @return CounterStorage&GaugeStorage&HistogramStorage&Store */
    abstract protected function getStorage();

    protected function setUp(): void
    {
        $this->renderer = new RenderTextFormat();
        $storage        = $this->getStorage();
        if (! ($storage instanceof FlushableStorage)) {
            return;
        }

        $storage->flush();
    }

    #[Test]
    public function itShouldSaveGauges(): void
    {
        $storage  = $this->getStorage();
        $registry = new CollectorRegistry($storage);

        $g = $registry->registerGauge(
            MetricName::fromNamespacedName('test', 'some_metric'),
            'this is for testing',
            MetricLabelNames::fromNames('foo'),
        );
        $g->set(35, 'bbb');
        $g->set(35, 'ddd');
        $g->set(35, 'aaa');
        $g->set(35, 'ccc');

        $registry = new CollectorRegistry($storage);
        self::assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            self::equalTo(<<<'EOF'
# HELP test_some_metric this is for testing
# TYPE test_some_metric gauge
test_some_metric{foo="aaa"} 35
test_some_metric{foo="bbb"} 35
test_some_metric{foo="ccc"} 35
test_some_metric{foo="ddd"} 35

EOF),
        );
    }

    #[Test]
    public function itShouldSaveCounters(): void
    {
        $storage    = $this->getStorage();
        $registry   = new CollectorRegistry($storage);
        $metricName = MetricName::fromNamespacedName('test', 'some_metric');
        $metric     = $registry->registerCounter(
            $metricName,
            'this is for testing',
            MetricLabelNames::fromNames('foo', 'bar'),
        );
        $metric->incBy(2, 'lalal', 'lululu');
        $registry->getCounter($metricName)->inc('lalal', 'lululu');
        $registry->getCounter($metricName)->inc('lalal', 'lvlvlv');

        $registry = new CollectorRegistry($storage);
        self::assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            self::equalTo(<<<'EOF'
# HELP test_some_metric this is for testing
# TYPE test_some_metric counter
test_some_metric{foo="lalal",bar="lululu"} 3
test_some_metric{foo="lalal",bar="lvlvlv"} 1

EOF),
        );
    }

    #[Test]
    public function itShouldSaveHistograms(): void
    {
        $storage    = $this->getStorage();
        $registry   = new CollectorRegistry($storage);
        $metricName = MetricName::fromNamespacedName('test', 'some_metric');
        $metric     = $registry->registerHistogram(
            $metricName,
            'this is for testing',
            HistogramLabelNames::fromNames('foo', 'bar'),
            [0.1, 1, 5, 10],
        );
        $metric->observe(2, 'lalal', 'lululu');
        $registry->getHistogram($metricName)->observe(7.1, 'lalal', 'lvlvlv');
        $registry->getHistogram($metricName)->observe(13, 'lalal', 'lululu');
        $registry->getHistogram($metricName)->observe(7.1, 'lalal', 'lululu');
        $registry->getHistogram($metricName)->observe(7.1, 'gnaaha', 'hihihi');

        $registry = new CollectorRegistry($storage);
        self::assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            self::equalTo(<<<'EOF'
# HELP test_some_metric this is for testing
# TYPE test_some_metric histogram
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="0.1"} 0
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="1"} 0
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="5"} 0
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="10"} 1
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="+Inf"} 1
test_some_metric_count{foo="gnaaha",bar="hihihi"} 1
test_some_metric_sum{foo="gnaaha",bar="hihihi"} 7.1
test_some_metric_bucket{foo="lalal",bar="lululu",le="0.1"} 0
test_some_metric_bucket{foo="lalal",bar="lululu",le="1"} 0
test_some_metric_bucket{foo="lalal",bar="lululu",le="5"} 1
test_some_metric_bucket{foo="lalal",bar="lululu",le="10"} 2
test_some_metric_bucket{foo="lalal",bar="lululu",le="+Inf"} 3
test_some_metric_count{foo="lalal",bar="lululu"} 3
test_some_metric_sum{foo="lalal",bar="lululu"} 22.1
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="0.1"} 0
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="1"} 0
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="5"} 0
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="10"} 1
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="+Inf"} 1
test_some_metric_count{foo="lalal",bar="lvlvlv"} 1
test_some_metric_sum{foo="lalal",bar="lvlvlv"} 7.1

EOF),
        );
    }

    #[Test]
    public function itShouldSaveHistogramsWithoutLabels(): void
    {
        $storage    = $this->getStorage();
        $registry   = new CollectorRegistry($storage);
        $metricName = MetricName::fromNamespacedName('test', 'some_metric');
        $metric     = $registry->registerHistogram($metricName, 'this is for testing');
        $metric->observe(2);
        $registry->getHistogram($metricName)->observe(13);
        $registry->getHistogram($metricName)->observe(7.1);

        $registry = new CollectorRegistry($storage);
        self::assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            self::equalTo(<<<'EOF'
# HELP test_some_metric this is for testing
# TYPE test_some_metric histogram
test_some_metric_bucket{le="0.005"} 0
test_some_metric_bucket{le="0.01"} 0
test_some_metric_bucket{le="0.025"} 0
test_some_metric_bucket{le="0.05"} 0
test_some_metric_bucket{le="0.075"} 0
test_some_metric_bucket{le="0.1"} 0
test_some_metric_bucket{le="0.25"} 0
test_some_metric_bucket{le="0.5"} 0
test_some_metric_bucket{le="0.75"} 0
test_some_metric_bucket{le="1"} 0
test_some_metric_bucket{le="2.5"} 1
test_some_metric_bucket{le="5"} 1
test_some_metric_bucket{le="7.5"} 2
test_some_metric_bucket{le="10"} 2
test_some_metric_bucket{le="+Inf"} 3
test_some_metric_count 3
test_some_metric_sum 22.1

EOF),
        );
    }

    #[Test]
    public function itShouldIncreaseACounterWithoutNamespace(): void
    {
        $registry = new CollectorRegistry($this->getStorage());
        $registry
            ->registerCounter(MetricName::fromName('some_quick_counter'), 'just a quick measurement')
            ->inc();

        self::assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            self::equalTo(<<<'EOF'
# HELP some_quick_counter just a quick measurement
# TYPE some_quick_counter counter
some_quick_counter 1

EOF),
        );
    }

    #[Test]
    public function itShouldForbidRegisteringTheSameCounterTwice(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'metric');
        $registry->registerCounter($metricName, 'help');
        $this->expectException(MetricsRegistrationException::class);
        $registry->registerCounter($metricName, 'help');
    }

    #[Test]
    public function itShouldForbidRegisteringTheSameCounterWithDifferentLabels(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'metric');
        $registry->registerCounter($metricName, 'help', MetricLabelNames::fromNames('foo', 'bar'));
        $this->expectException(MetricsRegistrationException::class);
        $registry->registerCounter($metricName, 'help', MetricLabelNames::fromNames('spam', 'eggs'));
    }

    #[Test]
    public function itShouldForbidRegisteringTheSameHistogramTwice(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'metric');
        $registry->registerHistogram($metricName, 'help');
        $this->expectException(MetricsRegistrationException::class);
        $registry->registerHistogram($metricName, 'help');
    }

    #[Test]
    public function itShouldForbidRegisteringTheSameHistogramWithDifferentLabels(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'metric');
        $registry->registerHistogram($metricName, 'help', HistogramLabelNames::fromNames('foo', 'bar'));
        $this->expectException(MetricsRegistrationException::class);
        $registry->registerHistogram($metricName, 'help', HistogramLabelNames::fromNames('spam', 'eggs'));
    }

    #[Test]
    public function itShouldForbidRegisteringTheSameGaugeTwice(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'metric');
        $registry->registerGauge($metricName, 'help');
        $this->expectException(MetricsRegistrationException::class);
        $registry->registerGauge($metricName, 'help');
    }

    #[Test]
    public function itShouldForbidRegisteringTheSameGaugeWithDifferentLabels(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'metric');
        $registry->registerGauge($metricName, 'help', MetricLabelNames::fromNames('foo', 'bar'));
        $this->expectException(MetricsRegistrationException::class);
        $registry->registerGauge($metricName, 'help', MetricLabelNames::fromNames('spam', 'eggs'));
    }

    #[Test]
    public function itShouldThrowAnExceptionWhenGettingANonExistentMetric(): void
    {
        $registry = new CollectorRegistry($this->getStorage());
        $this->expectException(MetricNotFoundException::class);
        $registry->getGauge(MetricName::fromNamespacedName('not_here', 'go_away'));
    }

    #[Test]
    public function itShouldNotRegisterACounterTwice(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'bar');
        $counterA   = $registry->getOrRegisterCounter($metricName, 'Help text');
        $counterB   = $registry->getOrRegisterCounter($metricName, 'Help text');

        self::assertSame($counterA, $counterB);
    }

    #[Test]
    public function itShouldNotRegisterAGaugeTwice(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'bar');
        $gaugeA     = $registry->getOrRegisterGauge($metricName, 'Help text');
        $gaugeB     = $registry->getOrRegisterGauge($metricName, 'Help text');

        self::assertSame($gaugeA, $gaugeB);
    }

    #[Test]
    public function itShouldNotRegisterAHistogramTwice(): void
    {
        $registry   = new CollectorRegistry($this->getStorage());
        $metricName = MetricName::fromNamespacedName('foo', 'bar');
        $histogramA = $registry->getOrRegisterHistogram($metricName, 'Help text');
        $histogramB = $registry->getOrRegisterHistogram($metricName, 'Help text');

        self::assertSame($histogramA, $histogramB);
    }
}
