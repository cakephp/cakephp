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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\DriverInterface;
use InvalidArgumentException;
use PDO;

/**
 * Integer type converter.
 *
 * Use to convert integer data between PHP and the database types.
 */
class IntegerType extends BaseType implements BatchCastingInterface
{
    /**
     * Checks if the value is not a numeric value
     *
     * @throws \InvalidArgumentException
     * @param mixed $value Value to check
     * @return void
     */
    protected function checkNumeric($value): void
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot convert value of type `%s` to integer',
                getTypeName($value)
            ));
        }
    }

    /**
     * Convert integer data into the database format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return int|null
     */
    public function toDatabase($value, DriverInterface $driver): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $this->checkNumeric($value);

        return (int)$value;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return int|null
     */
    public function toPHP($value, DriverInterface $driver): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    /**
     * @inheritDoc
     */
    public function manyToPHP(array $values, array $fields, DriverInterface $driver): array
    {
        foreach ($fields as $field) {
            if (!isset($values[$field])) {
                continue;
            }

            $this->checkNumeric($values[$field]);

            $values[$field] = (int)$values[$field];
        }

        return $values;
    }

    /**
     * Get the correct PDO binding type for integer data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\DriverInterface $driver The driver.
     * @return int
     */
    public function toStatement($value, DriverInterface $driver): int
    {
        return PDO::PARAM_INT;
    }

    /**
     * Marshals request data into PHP integers.
     *
     * @param mixed $value The value to convert.
     * @return int|null Converted value.
     */
    public function marshal($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }

        return null;
    }
}
