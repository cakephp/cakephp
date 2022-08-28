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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use BackedEnum;
use Cake\Database\Driver;
use InvalidArgumentException;
use PDO;

/**
 * Enum type converter.
 *
 * Use to convert string data between PHP and the database types.
 */
class EnumType extends BaseType implements OptionalConvertInterface
{
    /**
     * Convert enum instances into the database format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|int|null
     */
    public function toDatabase(mixed $value, Driver $driver): string|int|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_int($value) || is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value of type `%s` to string or integer',
            get_debug_type($value)
        ));
    }

    /**
     * Directly return string or integer because enum conversion happens in behavior
     *
     * @see \Cake\ORM\Behavior\EnumBehavior::afterMarshal()
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|int|null
     */
    public function toPHP(mixed $value, Driver $driver): string|int|null
    {
        return $value;
    }

    /**
     * Get the correct PDO binding type for string or integer data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        return PDO::PARAM_STR;
    }

    /**
     * Marshals request data
     *
     * @param mixed $value The value to convert.
     * @return \BackedEnum|string|int|null Converted value.
     */
    public function marshal(mixed $value): BackedEnum|string|int|null
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool False since the type class doesn't need to do any conversion for values read from db
     */
    public function requiresToPhpCast(): bool
    {
        return false;
    }
}
