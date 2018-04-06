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
use PDO;

/**
 * Encapsulates all conversion functions for values coming from database into PHP and
 * going from PHP into database.
 */
class Type implements TypeInterface
{

    /**
     * List of supported database types. A human readable
     * identifier is used as key and a complete namespaced class name as value
     * representing the class that will do actual type conversions.
     *
     * @var string[]|\Cake\Database\Type[]
     */
    protected static $_types = [
        'tinyinteger' => 'Cake\Database\Type\IntegerType',
        'smallinteger' => 'Cake\Database\Type\IntegerType',
        'integer' => 'Cake\Database\Type\IntegerType',
        'biginteger' => 'Cake\Database\Type\IntegerType',
        'binary' => 'Cake\Database\Type\BinaryType',
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
     * List of basic type mappings, used to avoid having to instantiate a class
     * for doing conversion on these.
     *
     * @var array
     * @deprecated 3.1 All types will now use a specific class
     */
    protected static $_basicTypes = [
        'string' => ['callback' => ['\Cake\Database\Type', 'strval']],
        'text' => ['callback' => ['\Cake\Database\Type', 'strval']],
        'boolean' => [
            'callback' => ['\Cake\Database\Type', 'boolval'],
            'pdo' => PDO::PARAM_BOOL
        ],
    ];

    /**
     * Contains a map of type object instances to be reused if needed.
     *
     * @var \Cake\Database\Type[]
     */
    protected static $_builtTypes = [];

    /**
     * Identifier name for this type
     *
     * @var string|null
     */
    protected $_name;

    /**
     * Constructor
     *
     * @param string|null $name The name identifying this type
     */
    public function __construct($name = null)
    {
        $this->_name = $name;
    }

    /**
     * Returns a Type object capable of converting a type identified by name.
     *
     * @param string $name type identifier
     * @throws \InvalidArgumentException If type identifier is unknown
     * @return \Cake\Database\Type
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
            $result[$name] = isset(static::$_builtTypes[$name]) ? static::$_builtTypes[$name] : static::build($name);
        }

        return $result;
    }

    /**
     * Returns a Type object capable of converting a type identified by $name
     *
     * @param string $name The type identifier you want to set.
     * @param \Cake\Database\Type $instance The type instance you want to set.
     * @return void
     */
    public static function set($name, Type $instance)
    {
        static::$_builtTypes[$name] = $instance;
    }

    /**
     * Registers a new type identifier and maps it to a fully namespaced classname,
     * If called with no arguments it will return current types map array
     * If $className is omitted it will return mapped class for $type
     *
     * Deprecated: The usage of $type as \Cake\Database\Type[] is deprecated. Please always use string[] if you pass an array
     * as first argument.
     *
     * @param string|string[]|\Cake\Database\Type[]|null $type If string name of type to map, if array list of arrays to be mapped
     * @param string|\Cake\Database\Type|null $className The classname or object instance of it to register.
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

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseType()
    {
        return $this->_name;
    }

    /**
     * {@inheritDoc}
     */
    public function toDatabase($value, Driver $driver)
    {
        return $this->_basicTypeCast($value);
    }

    /**
     * Casts given value from a database type to PHP equivalent
     *
     * @param mixed $value Value to be converted to PHP equivalent
     * @param \Cake\Database\Driver $driver Object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toPHP($value, Driver $driver)
    {
        return $this->_basicTypeCast($value);
    }

    /**
     * Checks whether this type is a basic one and can be converted using a callback
     * If it is, returns converted value
     *
     * @param mixed $value Value to be converted to PHP equivalent
     * @return mixed
     * @deprecated 3.1 All types should now be a specific class
     */
    protected function _basicTypeCast($value)
    {
        if ($value === null) {
            return null;
        }
        if (!empty(static::$_basicTypes[$this->_name])) {
            $typeInfo = static::$_basicTypes[$this->_name];
            if (isset($typeInfo['callback'])) {
                return $typeInfo['callback']($value);
            }
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function toStatement($value, Driver $driver)
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    /**
     * Type converter for boolean values.
     *
     * Will convert string true/false into booleans.
     *
     * @param mixed $value The value to convert to a boolean.
     * @return bool
     * @deprecated 3.1.8 This method is now unused.
     */
    public static function boolval($value)
    {
        if (is_string($value) && !is_numeric($value)) {
            return strtolower($value) === 'true';
        }

        return !empty($value);
    }

    /**
     * Type converter for string values.
     *
     * Will convert values into strings
     *
     * @param mixed $value The value to convert to a string.
     * @return string
     * @deprecated 3.1.8 This method is now unused.
     */
    public static function strval($value)
    {
        if (is_array($value)) {
            $value = '';
        }

        return (string)$value;
    }

    /**
     * {@inheritDoc}
     */
    public function newId()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function marshal($value)
    {
        return $this->_basicTypeCast($value);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'name' => $this->_name,
        ];
    }
}
