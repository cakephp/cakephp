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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use InvalidArgumentException;

/**
 * Factory for building database type classes.
 */
class TypeFactory
{
    /**
     * List of supported database types. A human readable
     * identifier is used as key and a complete namespaced class name as value
     * representing the class that will do actual type conversions.
     *
     * @var string[]
     */
    protected static $_types = [
        'tinyinteger' => 'Cake\Database\Type\IntegerType',
        'smallinteger' => 'Cake\Database\Type\IntegerType',
        'integer' => 'Cake\Database\Type\IntegerType',
        'biginteger' => 'Cake\Database\Type\IntegerType',
        'binary' => 'Cake\Database\Type\BinaryType',
        'binaryuuid' => 'Cake\Database\Type\BinaryUuidType',
        'boolean' => 'Cake\Database\Type\BoolType',
        'date' => 'Cake\Database\Type\DateType',
        'datetime' => 'Cake\Database\Type\DateTimeType',
        'decimal' => 'Cake\Database\Type\DecimalType',
        'float' => 'Cake\Database\Type\FloatType',
        'json' => 'Cake\Database\Type\JsonType',
        'string' => 'Cake\Database\Type\StringType',
        'text' => 'Cake\Database\Type\StringType',
        'time' => 'Cake\Database\Type\TimeType',
        'timestamp' => 'Cake\Database\Type\DateTimeType',
        'uuid' => 'Cake\Database\Type\UuidType',
    ];

    /**
     * Contains a map of type object instances to be reused if needed.
     *
     * @var \Cake\Database\TypeInterface[]
     */
    protected static $_builtTypes = [];

    /**
     * Returns a Type object capable of converting a type identified by name.
     *
     * @param string $name type identifier
     * @throws \InvalidArgumentException If type identifier is unknown
     * @return \Cake\Database\TypeInterface
     */
    public static function build(string $name): TypeInterface
    {
        if (isset(static::$_builtTypes[$name])) {
            return static::$_builtTypes[$name];
        }
        if (!isset(static::$_types[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown type "%s"', $name));
        }

        return static::$_builtTypes[$name] = new static::$_types[$name]($name);
    }

    /**
     * Returns an arrays with all the mapped type objects, indexed by name.
     *
     * @return array
     */
    public static function buildAll(): array
    {
        $result = [];
        foreach (static::$_types as $name => $type) {
            $result[$name] = static::$_builtTypes[$name] ?? static::build($name);
        }

        return $result;
    }

    /**
     * Set TypeInterface instance capable of converting a type identified by $name
     *
     * @param string $name The type identifier you want to set.
     * @param \Cake\Database\TypeInterface $instance The type instance you want to set.
     * @return void
     */
    public static function set(string $name, TypeInterface $instance): void
    {
        static::$_builtTypes[$name] = $instance;
    }

    /**
     * Registers a new type identifier and maps it to a fully namespaced classname.
     *
     * @param string $type Name of type to map.
     * @param string $className The classname to register.
     * @return void
     */
    public static function map(string $type, string $className): void
    {
        static::$_types[$type] = $className;
        unset(static::$_builtTypes[$type]);
    }

    /**
     * Set type to classname mapping.
     *
     * @param string[] $map List of types to be mapped.
     * @return void
     */
    public static function setMap(array $map): void
    {
        static::$_types = $map;
        static::$_builtTypes = [];
    }

    /**
     * Get mapped class name for given type or map array.
     *
     * @param string|null $type Type name to get mapped class for or null to get map array.
     * @return array|string|null Configured class name for given $type or map array.
     */
    public static function getMap(?string $type = null)
    {
        if ($type === null) {
            return static::$_types;
        }

        return static::$_types[$type] ?? null;
    }

    /**
     * Clears out all created instances and mapped types classes, useful for testing
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$_types = [];
        static::$_builtTypes = [];
    }
}
