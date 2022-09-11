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
use Throwable;

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
     * @var string
     */
    protected string $enumClassName;

    /**
     * @param string $name The name identifying this type
     * @param string $enumClassName The associated enum class name
     */
    public function __construct(
        string $name,
        string $enumClassName
    ) {
        parent::__construct($name);
        $this->enumClassName = $enumClassName;
        try {
            $reflectionEnum = new ReflectionEnum($enumClassName);
            $this->backingType = (string)$reflectionEnum->getBackingType();
        } catch (ReflectionException) {
            throw new DatabaseException(
                sprintf('Unable to map enum %s for type %s, must be a backed enum.', $enumClassName, $name)
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
            if (!$value instanceof $this->enumClassName) {
                throw new InvalidArgumentException(sprintf(
                    'Given value type `%s` does not match associated `%s` backed enum',
                    get_debug_type($value),
                    $this->backingType
                ));
            }

            return $value->value;
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

        try {
            return $this->enumClassName::tryFrom($value);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(sprintf(
                'Cannot convert value of type %s to %s',
                get_debug_type($value),
                $this->enumClassName,
            ));
        }
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

        if (get_debug_type($value) !== $this->backingType) {
            throw new InvalidArgumentException(sprintf(
                'Given value type `%s` does not match associated `%s` backed enum',
                get_debug_type($value),
                $this->backingType
            ));
        }

        $enumInstance = $this->enumClassName::tryFrom($value);
        if ($enumInstance === null) {
            throw new InvalidArgumentException(sprintf(
                'Unable to marshal value to %s, got %s',
                $this->enumClassName,
                get_debug_type($value),
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
     * @param string $enumClassName The enum class name
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
     * @return string
     */
    public function getEnumClassName(): string
    {
        return $this->enumClassName;
    }
}
