<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Utility;

use JsonException;
use Stringable;

/**
 * Methods for converting mixed values to specific data types, ensuring a type-safe approach to data manipulation.
 * This utility is useful for safely narrowing down the data types.
 */
class Filter
{
    /**
     * Converts the given value to a string.
     *
     * This method attempts to convert the given value to a string.
     * If the value is already a string, it returns the value as it is.
     * If the conversion is not possible, it returns NULL.
     *
     * @param mixed $value The value to be converted.
     * @return ?string Returns the string representation of the value, or null if the value is not a string.
     */
    public static function toString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        } elseif (is_int($value)) {
            return (string)$value;
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }
            try {
                $return = json_encode($value, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $return = null;
            }

            if ($return === null || str_contains($return, 'e')) {
                $return = rtrim(sprintf('%.' . (PHP_FLOAT_DIG + 3) . 'F', $value), '.0');
            }

            return $return;
        } elseif ($value instanceof Stringable) {
            return (string)$value;
        } else {
            return null;
        }
    }

    /**
     * Converts a value to an integer.
     *
     * This method attempts to convert the given value to an integer.
     * If the conversion is successful, it returns the value as an integer.
     * If the conversion fails, it returns NULL.
     *
     * String values are trimmed using trim().
     *
     * @param mixed $value The value to be converted to an integer.
     * @return int|null Returns the converted integer value or null if the conversion fails.
     */
    public static function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

            return $value === PHP_INT_MIN ? null : $value;
        } elseif (is_float($value)) {
            /**
             * @link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/MAX_SAFE_INTEGER
             * 9007199254740991 = 2^53-1 = the maximum safe integer that can be represented without losing precision.
             * Beyond this numerical limit, the equality (int)9007199254740993.0 === 9007199254740992 returns true.
             */
            if ($value >= -9007199254740991 && $value <= 9007199254740991) {
                return (int)$value;
            }

            return null;
        } elseif (is_bool($value)) {
            return (int)$value;
        } else {
            return null;
        }
    }

    /**
     * Converts a value to boolean.
     *
     *  1 | '1' | 1.0 | true  - values returns as true
     *  0 | '0' | 0.0 | false - values returns as false
     *  Other values returns as null.
     *
     * @param mixed $value The value to convert to boolean.
     * @return bool|null Returns true if the value is truthy, false if it's falsy, or NULL otherwise.
     */
    public static function toBool(mixed $value): ?bool
    {
        if ($value === '1' || $value === 1 || $value === 1.0 || $value === true) {
            return true;
        } elseif ($value === '0' || $value === 0 || $value === 0.0 || $value === false) {
            return false;
        } else {
            return null;
        }
    }
}
