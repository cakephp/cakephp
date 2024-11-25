<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use InvalidArgumentException;
use function is_array;

class ArrayCast
{
    /**
     * @param mixed $value
     * @param bool $convertEmptyArrayToNull
     * @return array|null
     */
    public static function tryFrom(mixed $value, bool $convertEmptyArrayToNull = true): ?array
    {
        try {
            return self::from($value, $convertEmptyArrayToNull);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param bool $failOnEmptyArray
     * @return array
     */
    public static function from(mixed $value, bool $failOnEmptyArray = true): array
    {
        if (is_array($value)) {
            if ($failOnEmptyArray && !$value) {
                throw new InvalidArgumentException('Only non-empty arrays are allowed');
            }

            return $value;
        }

        throw new InvalidArgumentException('Value cannot be converted to array');
    }
}
