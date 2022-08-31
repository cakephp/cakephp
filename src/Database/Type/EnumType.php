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
use Cake\Database\TypeFactory;
use InvalidArgumentException;
use PDO;

/**
 * Enum type converter.
 *
 * Use to convert string data between PHP and the database types.
 */
class EnumType extends BaseType
{
    /**
     * The backed enum
     *
     * @var \BackedEnum|string
     */
    protected BackedEnum|string $enum;

    /**
     * The type of the enum which is either string or int
     *
     * @var string
     */
    protected string $typeOfEnum;

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
     * Transform DB value to backed enum instance
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

        if (get_debug_type($value) !== $this->typeOfEnum) {
            throw new InvalidArgumentException(sprintf(
                'Given value type `%s` does not match associated `%s` backed enum',
                get_debug_type($value),
                $this->typeOfEnum
            ));
        }

        return $this->enum::tryFrom($value);
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
     * @return \BackedEnum|null Converted value.
     */
    public function marshal(mixed $value): ?BackedEnum
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof $this->enum) {
            return $value;
        }

        if (get_debug_type($value) !== $this->typeOfEnum) {
            throw new InvalidArgumentException(sprintf(
                'Given value type `%s` does not match associated `%s` backed enum',
                get_debug_type($value),
                $this->typeOfEnum
            ));
        }

        $enumInstance = $this->enum::tryFrom($value);
        if ($enumInstance === null) {
            throw new InvalidArgumentException(sprintf(
                'Given value `%s` is not present inside associated `%s` backed enum',
                $value,
                $this->enum
            ));
        }

        return $enumInstance;
    }

    /**
     * @param \BackedEnum|string $enumName The enum name
     * @return string
     */
    public static function for(BackedEnum|string $enumName): string
    {
        $typeName = 'enum' . $enumName;
        $instance = new self();
        $instance->setEnum($enumName);
        TypeFactory::set($typeName, $instance);

        return $typeName;
    }

    /**
     * @return \BackedEnum|string
     */
    public function getEnum(): BackedEnum|string
    {
        return $this->enum;
    }

    /**
     * @param \BackedEnum|string $enum
     */
    public function setEnum(BackedEnum|string $enum): void
    {
        $this->typeOfEnum = get_debug_type($enum::cases()[0]->value);
        $this->enum = $enum;
    }
}
