<?php

declare(strict_types=1);

namespace Enalean\Prometheus\Value;

use InvalidArgumentException;
use function count;
use function preg_match;
use function sprintf;
use function strpos;

final class MetricLabelNames implements LabelNames
{
    private const LABEL_NAME_REGEX      = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    private const RESERVED_LABEL_PREFIX = '__';

    /** @var string[] */
    private $names;

    private function __construct(string ...$names)
    {
        $this->names = $names;
    }

    public static function fromNames(string ...$names) : self
    {
        foreach ($names as $name) {
            if (! preg_match(self::LABEL_NAME_REGEX, $name)) {
                throw new InvalidArgumentException(sprintf(
                    'The label name %s does not match the expected pattern.',
                    $name
                ));
            }
            if (strpos($name, self::RESERVED_LABEL_PREFIX) === 0) {
                throw new InvalidArgumentException(sprintf(
                    'Label starting with %s are reserved for internal use, %s is not acceptable.',
                    self::RESERVED_LABEL_PREFIX,
                    $name
                ));
            }
        }

        return new self(...$names);
    }

    /**
     * @inheritdoc
     */
    public function toStrings() : array
    {
        return $this->names;
    }

    public function count() : int
    {
        return count($this->names);
    }
}
