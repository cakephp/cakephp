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
     */
    public static function tryFrom(mixed $value, ?int $min = null, ?int $max = null): ?int
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
     */
    public static function from(mixed $value, ?int $min = null, ?int $max = null): int
    {
        $result = self::tryFrom($value, $min, $max);
        if ($result === null) {
            throw new InvalidArgumentException('Value cannot be converted to int');
        }

        return $result;
    }
}
