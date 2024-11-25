<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use InvalidArgumentException;
use Stringable;
use function is_string;
use function trim;

class StringCast
{
    /**
     * @param mixed $value
     * @param bool $strict
     */
    public static function tryFrom(mixed $value, bool $strict = false): ?string
    {
        try {
            return self::from($value, $strict);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param mixed $value
     */
    public static function from(mixed $value, bool $strict = false): string
    {
        if (!$strict && $value === null) {
            return '';
        }
        if (!$strict && is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_string($value) || is_numeric($value) || $value instanceof Stringable) {
            $value = (string)$value;
            if ($strict && trim($value) === '') {
                throw new InvalidArgumentException('Only non-empty strings are allowed');
            }

            return $value;
        }

        throw new InvalidArgumentException('Value cannot be converted to string');
    }
}
