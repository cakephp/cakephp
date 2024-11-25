<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use InvalidArgumentException;
use function is_object;
use function method_exists;

class BoolCast
{
    /**
     * @param mixed $value
     * @param bool $strict
     */
    public static function tryFrom(mixed $value, bool $strict = false): ?bool
    {
        if ($value === '' || $value === null) {
            return $strict ? null : false;
        }
        if (is_int($value)) {
            return $strict ? null : (bool)$value;
        }
        if (is_object($value) && method_exists($value, 'toBool')) {
            $value = $value->toBool();
        }
        if (!$strict && is_scalar($value)) {
            return (bool)$value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param mixed $value
     * @param bool $strict
     */
    public static function from(mixed $value, bool $strict = false): bool
    {
        $result = self::tryFrom($value);
        if ($result === null) {
            throw new InvalidArgumentException('Value cannot be converted to bool');
        }

        return $result;
    }
}
