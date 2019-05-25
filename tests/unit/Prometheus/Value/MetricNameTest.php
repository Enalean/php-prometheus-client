<?php

declare(strict_types=1);

namespace Test\Prometheus\Value;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Value\MetricName;

/**
 * @covers Prometheus\Value\MetricName
 */
final class MetricNameTest extends TestCase
{
    /**
     * @testWith ["foo", "bar", "foo_bar"]
     *           ["", "bar", "bar"]
     */
    public function testValidNamespacedMetricName(string $namespace, string $name, string $expectedName) : void
    {
        $name = MetricName::fromNamespacedName($namespace, $name);

        $this->assertEquals($expectedName, $name->toString());
    }

    /**
     * @testWith ["foo_bar", "foo_bar"]
     *           ["bar", "bar"]
     */
    public function testValidMetricName(string $name, string $expectedName) : void
    {
        $name = MetricName::fromName($name);

        $this->assertEquals($expectedName, $name->toString());
    }

    /**
     * @testWith ["invalid namespace", "bar"]
     *           ["foo", "invalid name"]
     *           ["invalid namespace", "invalid name"]
     */
    public function testInvalidMetricName(string $namespace, string $name) : void
    {
        $this->expectException(InvalidArgumentException::class);
        MetricName::fromNamespacedName($namespace, $name);
    }
}
