<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use InvalidArgumentException;
use Stringable;
use function filter_var;
use function is_float;
use function is_int;
use function is_object;
use function method_exists;
use function round;

class IntCast
{
    /**
     * @param mixed $value
     * @param bool $strict
     * @param int|null $min
     * @param int|null $max
     */
    public static function tryFrom(mixed $value, bool $strict = false, ?int $min = null, ?int $max = null): ?int
    {
        if (is_object($value) && method_exists($value, 'toInt')) {
            $value = $value->toInt();
        }
        if ($value instanceof Stringable) {
            $value = (string)$value;
        }
        if (is_float($value) && $value === round($value)) {
            $value = (int)$value;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if (is_int($value) && $min !== null && $value < $min) {
            $value = null;
        }
        if (is_int($value) && $max !== null && $value > $max) {
            $value = null;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param bool $strict
     * @param int|null $min
     * @param int|null $max
     */
    public static function from(mixed $value, bool $strict = false, ?int $min = null, ?int $max = null): int
    {
        $result = self::tryFrom($value, $strict, $min, $max);
        if ($result === null) {
            throw new InvalidArgumentException('Value cannot be converted to int');
        }

        return $result;
    }
}
