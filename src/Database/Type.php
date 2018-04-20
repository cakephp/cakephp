<?php
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
namespace Cake\Database;

use InvalidArgumentException;

/**
 * Factory for building database type classes.
 */
class Type
{

    /**
     * List of supported database types. A human readable
     * identifier is used as key and a complete namespaced class name as value
     * representing the class that will do actual type conversions.
     *
     * @var string[]|\Cake\Database\TypeInterface[]
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
    public static function build($name)
    {
        if (isset(static::$_builtTypes[$name])) {
            return static::$_builtTypes[$name];
        }
        if (!isset(static::$_types[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown type "%s"', $name));
        }
        if (is_string(static::$_types[$name])) {
            return static::$_builtTypes[$name] = new static::$_types[$name]($name);
        }

        return static::$_builtTypes[$name] = static::$_types[$name];
    }

    /**
     * Returns an arrays with all the mapped type objects, indexed by name.
     *
     * @return array
     */
    public static function buildAll()
    {
        $result = [];
        foreach (static::$_types as $name => $type) {
            $result[$name] = isset(static::$_builtTypes[$name])
                ? static::$_builtTypes[$name]
                : static::build($name);
        }

        return $result;
    }

    /**
     * Returns a Type object capable of converting a type identified by $name
     *
     * @param string $name The type identifier you want to set.
     * @param \Cake\Database\TypeInterface $instance The type instance you want to set.
     * @return void
     */
    public static function set($name, TypeInterface $instance)
    {
        static::$_builtTypes[$name] = $instance;
    }

    /**
     * Registers a new type identifier and maps it to a fully namespaced classname,
     * If called with no arguments it will return current types map array
     * If $className is omitted it will return mapped class for $type
     *
     * @param string|string[]|null $type If string name of type to map, if array list of arrays to be mapped
     * @param string|\Cake\Database\TypeInterface|null $className The classname or object instance of it to register.
     * @return array|string|null If $type is null then array with current map, if $className is null string
     * configured class name for give $type, null otherwise
     */
    public static function map($type = null, $className = null)
    {
        if ($type === null) {
            return static::$_types;
        }
        if (is_array($type)) {
            static::$_types = $type;

            return null;
        }
        if ($className === null) {
            return isset(static::$_types[$type]) ? static::$_types[$type] : null;
        }

        static::$_types[$type] = $className;
        unset(static::$_builtTypes[$type]);
    }

    /**
     * Clears out all created instances and mapped types classes, useful for testing
     *
     * @return void
     */
    public static function clear()
    {
        static::$_types = [];
        static::$_builtTypes = [];
    }
}
