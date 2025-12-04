<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Value;

use Enalean\Prometheus\Value\HistogramLabelNames;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function count;

#[CoversClass(HistogramLabelNames::class)]
final class HistogramLabelNamesTest extends TestCase
{
    #[TestWith(['label', 'label01', 'label_1'])]
    public function testValidLabelNames(string $name): void
    {
        $labelNames = HistogramLabelNames::fromNames($name);

        self::assertEquals([$name], $labelNames->toStrings());
        self::assertCount(1, $labelNames);
    }

    #[TestWith(['__label', 'label test', 'le'])]
    public function testInvalidLabelNames(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        HistogramLabelNames::fromNames($name);
    }

    public function testCollectionOfNames(): void
    {
        $labels     = ['label1', 'label2', 'label3'];
        $labelNames = HistogramLabelNames::fromNames(...$labels);

        self::assertEquals($labels, $labelNames->toStrings());
        self::assertEquals(count($labels), count($labelNames));
    }
}
