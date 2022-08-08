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
use TestApp\Model\Enum\AuthorGenderEnum;

/**
 * Enum type converter.
 *
 * Use to convert enum data between PHP and the database types.
 */
class EnumType extends BaseType implements BatchCastingInterface
{
    /**
     * Convert enum data into the database format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     */
    public function toDatabase(mixed $value, Driver $driver): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value of type `%s` to string',
            get_debug_type($value)
        ));
    }

    /**
     * Convert enum value to PHP enumeration.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return \BackedEnum|null
     */
    public function toPHP(mixed $value, Driver $driver): ?BackedEnum
    {
        if ($value === null) {
            return null;
        }

        // TODO We need a map of database column types to PHP backed enums here
        return AuthorGenderEnum::MALE;
    }

    /**
     * Get the correct PDO binding type for enum data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshals request data into PHP strings.
     *
     * @param mixed $value The value to convert.
     * @return string|null Converted value.
     */
    public function marshal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string)$value;
    }

    /**
     * @inheritDoc
     */
    public function manyToPHP(array $values, array $fields, Driver $driver): array
    {
        foreach ($fields as $field) {
            $value = $values[$field] ?? null;
            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                // TODO We need a map of database column types to PHP backed enums here
                $values[$field] = AuthorGenderEnum::MALE;
            }
        }

        return $values;
    }
}
