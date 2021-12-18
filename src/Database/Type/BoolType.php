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
 * @since         3.1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\DriverInterface;
use InvalidArgumentException;
use PDO;

/**
 * Bool type converter.
 *
 * Use to convert bool data between PHP and the database types.
 */
class BoolType extends BaseType implements BatchCastingInterface
{
    /**
     * Convert bool data into the database format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return bool|null
     */
    public function toDatabase($value, DriverInterface $driver): ?bool
    {
        if ($value === true || $value === false || $value === null) {
            return $value;
        }

        if (in_array($value, [1, 0, '1', '0'], true)) {
            return (bool)$value;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value of type `%s` to bool',
            getTypeName($value)
        ));
    }

    /**
     * Convert bool values to PHP booleans
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return bool|null
     */
    public function toPHP($value, DriverInterface $driver): ?bool
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }

        if (!is_numeric($value)) {
            return strtolower($value) === 'true';
        }

        return !empty($value);
    }

    /**
     * @inheritDoc
     */
    public function manyToPHP(array $values, array $fields, DriverInterface $driver): array
    {
        foreach ($fields as $field) {
            $value = $values[$field] ?? null;
            if ($value === null || is_bool($value)) {
                continue;
            }

            if (!is_numeric($value)) {
                $values[$field] = strtolower($value) === 'true';
                continue;
            }

            $values[$field] = !empty($value);
        }

        return $values;
    }

    /**
     * Get the correct PDO binding type for bool data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\DriverInterface $driver The driver.
     * @return int
     */
    public function toStatement($value, DriverInterface $driver): int
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_BOOL;
    }

    /**
     * Marshals request data into PHP booleans.
     *
     * @param mixed $value The value to convert.
     * @return bool|null Converted value.
     */
    public function marshal($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
