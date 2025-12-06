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

use Cake\Database\Driver;
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
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return bool|null
     */
    public function toDatabase(mixed $value, Driver $driver): ?bool
    {
        if (in_array($value, [true, false, null], true)) {
            return $value;
        }

        if (in_array($value, [1, 0, '1', '0'], true)) {
            return (bool)$value;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value `%s` of type `%s` to bool',
            print_r($value, true),
            get_debug_type($value),
        ));
    }

    /**
     * Convert bool values to PHP booleans
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return bool|null
     */
    public function toPHP(mixed $value, Driver $driver): ?bool
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
    public function manyToPHP(array $values, array $fields, Driver $driver): array
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
     * @inheritDoc
     */
    public function toStatement(mixed $value, Driver $driver): int
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
    public function marshal(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
