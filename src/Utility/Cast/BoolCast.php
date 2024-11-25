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
     */
    public static function tryFrom(mixed $value): ?bool
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (is_object($value) && method_exists($value, 'toBool')) {
            $value = $value->toBool();
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param mixed $value
     */
    public static function from(mixed $value): bool
    {
        $result = self::tryFrom($value);
        if ($result === null) {
            throw new InvalidArgumentException('Value cannot be converted to bool');
        }

        return $result;
    }
}
