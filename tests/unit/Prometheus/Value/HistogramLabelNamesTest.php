<?php

declare(strict_types=1);

namespace Test\Prometheus\Value;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Value\HistogramLabelNames;
use function count;

/**
 * @covers Prometheus\Value\HistogramLabelNames
 */
final class HistogramLabelNamesTest extends TestCase
{
        /**
         * @testWith ["label"]
         *           ["label01"]
         *           ["label_1"]
         */
    public function testValidLabelNames(string $name) : void
    {
        $labelNames = HistogramLabelNames::fromNames($name);

        $this->assertEquals([$name], $labelNames->toStrings());
        $this->assertEquals(1, count($labelNames));
    }

    /**
     * @testWith ["__label"]
     *           ["label test"]
     *           ["le"]
     */
    public function testInvalidLabelNames(string $name) : void
    {
        $this->expectException(InvalidArgumentException::class);
        HistogramLabelNames::fromNames($name);
    }

    public function testCollectionOfNames() : void
    {
        $labels     = ['label1', 'label2', 'label3'];
        $labelNames = HistogramLabelNames::fromNames(...$labels);

        $this->assertEquals($labels, $labelNames->toStrings());
        $this->assertEquals(count($labels), count($labelNames));
    }
}
