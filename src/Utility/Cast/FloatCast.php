<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use InvalidArgumentException;
use Stringable;
use function filter_var;
use function is_float;
use function is_object;
use function method_exists;

class FloatCast
{
    /**
     * @param mixed $value
     * @param bool $strict
     * @param float|null $min
     * @param float|null $max
     */
    public static function tryFrom(mixed $value, bool $strict = false, ?float $min = null, ?float $max = null): ?float
    {
        if (is_object($value) && method_exists($value, 'toFloat')) {
            $value = $value->toFloat();
        }
        if ($value instanceof Stringable) {
            $value = (string)$value;
        }
        if (!$strict && is_scalar($value)) {
            //$value = (float)$value;
        }
        if (!is_float($value)) {
            $value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        }
        if (is_float($value) && $min !== null && $value < $min) {
            $value = null;
        }
        if (is_float($value) && $max !== null && $value > $max) {
            $value = null;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param bool $strict
     * @param float|null $min
     * @param float|null $max
     */
    public static function from(mixed $value, bool $strict = false, ?float $min = null, ?float $max = null): float
    {
        $result = self::tryFrom($value, $strict, $min, $max);
        if ($result === null) {
            throw new InvalidArgumentException('Value cannot be converted to float');
        }

        return $result;
    }
}
