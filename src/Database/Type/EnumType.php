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
use Cake\Database\Exception\DatabaseException;
use Cake\Database\TypeFactory;
use Cake\Utility\Text;
use InvalidArgumentException;
use PDO;
use ReflectionEnum;
use ReflectionException;

/**
 * Enum type converter.
 *
 * Use to convert string data between PHP and the database types.
 */
class EnumType extends BaseType
{
    /**
     * The type of the enum which is either string or int
     *
     * @var string
     */
    protected string $typeOfEnum;

    /**
     * @param string $name The name identifying this type
     * @param string $enum The associated enum classname
     */
    public function __construct(
        string $name,
        protected string $enum
    ) {
        parent::__construct($name);
        try {
            $reflectionEnum = new ReflectionEnum($enum);
            $this->typeOfEnum = (string)$reflectionEnum->getBackingType();
        } catch (ReflectionException) {
            throw new DatabaseException(
                sprintf('Given enum %s is not a backed enum ', $enum)
            );
        }
    }

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
     * @param string $enumName The enum name
     * @return string
     */
    public static function for(string $enumName): string
    {
        $typeName = 'enum' . strtolower(Text::slug($enumName));
        $instance = new EnumType($typeName, $enumName);
        TypeFactory::set($typeName, $instance);

        return $typeName;
    }
}
