<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Value;

use Enalean\Prometheus\Value\MetricLabelNames;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use function count;

/**
 * @covers Enalean\Prometheus\Value\MetricLabelNames
 */
final class MetricLabelNamesTest extends TestCase
{
    /**
     * @testWith ["label"]
     *           ["label01"]
     *           ["label_1"]
     *           ["le"]
     */
    public function testValidLabelNames(string $name) : void
    {
        $labelNames = MetricLabelNames::fromNames($name);

        self::assertEquals([$name], $labelNames->toStrings());
        self::assertCount(1, $labelNames);
    }

    /**
     * @testWith ["__label"]
     *           ["label test"]
     */
    public function testInvalidLabelNames(string $name) : void
    {
        $this->expectException(InvalidArgumentException::class);
        MetricLabelNames::fromNames($name);
    }

    public function testCollectionOfNames() : void
    {
        $labels     = ['label1', 'label2', 'label3'];
        $labelNames = MetricLabelNames::fromNames(...$labels);

        self::assertEquals($labels, $labelNames->toStrings());
        self::assertEquals(count($labels), count($labelNames));
    }
}
