<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Exception;

use Exception;

/**
 * Exception thrown if a metric can't be found in the CollectorRegistry.
 */
final class MetricNotFoundException extends Exception
{
}
