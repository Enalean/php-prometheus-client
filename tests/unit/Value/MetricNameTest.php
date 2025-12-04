<?php

declare(strict_types=1);

namespace Enalean\PrometheusTest\Value;

use Enalean\Prometheus\Value\MetricName;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetricName::class)]
final class MetricNameTest extends TestCase
{
    #[TestWith(['foo', 'bar', 'foo_bar', '', 'bar', 'bar'])]
    public function testValidNamespacedMetricName(string $namespace, string $name, string $expectedName): void
    {
        $name = MetricName::fromNamespacedName($namespace, $name);

        self::assertEquals($expectedName, $name->toString());
    }

    #[TestWith(['foo_bar', 'foo_bar', 'bar', 'bar'])]
    public function testValidMetricName(string $name, string $expectedName): void
    {
        $name = MetricName::fromName($name);

        self::assertEquals($expectedName, $name->toString());
    }

    #[TestWith(['invalid namespace', 'bar', 'foo', 'invalid name', 'invalid namespace', 'invalid name'])]
    public function testInvalidMetricName(string $namespace, string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        MetricName::fromNamespacedName($namespace, $name);
    }
}
