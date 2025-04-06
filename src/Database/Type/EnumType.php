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
    protected string $backingType;

    /**
     * The enum classname which is associated to the type instance
     *
     * @var class-string<\BackedEnum>
     */
    protected string $enumClassName;

    /**
     * @param string $name The name identifying this type
     * @param class-string<\BackedEnum> $enumClassName The associated enum class name
     */
    public function __construct(
        string $name,
        string $enumClassName,
    ) {
        parent::__construct($name);
        $this->enumClassName = $enumClassName;

        try {
            $reflectionEnum = new ReflectionEnum($enumClassName);
        } catch (ReflectionException $e) {
            throw new DatabaseException(sprintf(
                'Unable to use `%s` for type `%s`. %s.',
                $enumClassName,
                $name,
                $e->getMessage(),
            ));
        }

        $namedType = $reflectionEnum->getBackingType();
        if ($namedType == null) {
            throw new DatabaseException(
                sprintf('Unable to use enum `%s` for type `%s`, must be a backed enum.', $enumClassName, $name),
            );
        }

        $this->backingType = (string)$namedType;
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
            if (!$value instanceof $this->enumClassName) {
                throw new InvalidArgumentException(sprintf(
                    'Given value type `%s` does not match associated `%s` backed enum in `%s`',
                    get_debug_type($value),
                    $this->backingType,
                    $this->enumClassName,
                ));
            }

            return $value->value;
        }

        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot convert value `%s` of type `%s` to string or int',
                print_r($value, true),
                get_debug_type($value),
            ));
        }

        if ($this->enumClassName::tryFrom($value) === null) {
            throw new InvalidArgumentException(sprintf(
                '`%s` is not a valid value for `%s`',
                $value,
                $this->enumClassName,
            ));
        }

        return $value;
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

        if ($this->backingType === 'int' && is_string($value)) {
            $intVal = filter_var($value, FILTER_VALIDATE_INT);
            if ($intVal !== false) {
                $value = $intVal;
            }
        }

        return $this->enumClassName::from($value);
    }

    /**
     * @inheritDoc
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        if ($this->backingType === 'int') {
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

        if ($value instanceof $this->enumClassName) {
            return $value;
        }

        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to marshal value `%s` of type `%s` to `%s`',
                print_r($value, true),
                get_debug_type($value),
                $this->enumClassName,
            ));
        }

        if ($this->backingType === 'int') {
            if ($value === '') {
                return null;
            }

            if (is_numeric($value)) {
                $value = (int)$value;
            }
        }

        if (get_debug_type($value) !== $this->backingType) {
            throw new InvalidArgumentException(sprintf(
                'Given value type `%s` does not match associated `%s` backed enum in `%s`',
                get_debug_type($value),
                $this->backingType,
                $this->enumClassName,
            ));
        }

        $enumInstance = $this->enumClassName::tryFrom($value);
        if ($enumInstance === null) {
            if ($value === '' && $this->backingType === 'string') {
                return null;
            }

            throw new InvalidArgumentException(sprintf(
                'Unable to marshal value `%s` of type `%s` to `%s`',
                print_r($value, true),
                get_debug_type($value),
                $this->enumClassName,
            ));
        }

        return $enumInstance;
    }

    /**
     * Create an `EnumType` that is paired with the provided `$enumClassName`.
     *
     * ### Usage
     *
     * ```
     * // In a table class
     * $this->getSchema()->setColumnType('status', EnumType::from(StatusEnum::class));
     * ```
     *
     * @param class-string<\BackedEnum> $enumClassName The enum class name
     * @return string
     */
    public static function from(string $enumClassName): string
    {
        $typeName = 'enum-' . strtolower(Text::slug($enumClassName));
        $instance = new EnumType($typeName, $enumClassName);
        TypeFactory::set($typeName, $instance);

        return $typeName;
    }

    /**
     * @return class-string<\BackedEnum>
     */
    public function getEnumClassName(): string
    {
        return $this->enumClassName;
    }
}
