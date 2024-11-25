<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use InvalidArgumentException;
use function is_array;

class ArrayCast
{
    /**
     * @param mixed $value
     * @param bool $strict
     */
    public static function tryFrom(mixed $value, bool $strict = false): ?array
    {
        try {
            return self::from($value, $strict);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param bool $strict
     */
    public static function from(mixed $value, bool $strict = false): array
    {
        if ($value === null) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (!$strict && is_scalar($value)) {
            return strlen((string)$value) === 0 ? [] : [$value];
        }

        throw new InvalidArgumentException('Value cannot be converted to array');
    }
}
