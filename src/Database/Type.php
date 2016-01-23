<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use InvalidArgumentException;
use PDO;

/**
 * Encapsulates all conversion functions for values coming from database into PHP and
 * going from PHP into database.
 */
class Type
{

    /**
     * List of supported database types. A human readable
     * identifier is used as key and a complete namespaced class name as value
     * representing the class that will do actual type conversions.
     *
     * @var array
     */
    protected static $_types = [
        'biginteger' => 'Cake\Database\Type\IntegerType',
        'binary' => 'Cake\Database\Type\BinaryType',
        'boolean' => 'Cake\Database\Type\BoolType',
        'date' => 'Cake\Database\Type\DateType',
        'datetime' => 'Cake\Database\Type\DateTimeType',
        'decimal' => 'Cake\Database\Type\FloatType',
        'float' => 'Cake\Database\Type\FloatType',
        'integer' => 'Cake\Database\Type\IntegerType',
        'string' => 'Cake\Database\Type\StringType',
        'text' => 'Cake\Database\Type\StringType',
        'time' => 'Cake\Database\Type\TimeType',
        'timestamp' => 'Cake\Database\Type\DateTimeType',
        'uuid' => 'Cake\Database\Type\UuidType',
    ];

    /**
     * List of basic type mappings, used to avoid having to instantiate a class
     * for doing conversion on these
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
     * Contains a map of type object instances to be reused if needed
     *
     * @var array
     */
    protected static $_builtTypes = [];

    /**
     * Identifier name for this type
     *
     * @var string
     */
    protected $_name = null;

    /**
     * Constructor
     *
     * @param string $name The name identifying this type
     */
    public function __construct($name = null)
    {
        $this->_name = $name;
    }

    /**
     * Returns a Type object capable of converting a type identified by $name
     *
     * @param string $name type identifier
     * @throws \InvalidArgumentException If type identifier is unknown
     * @return Type
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
     * Returns an arrays with all the mapped type objects, indexed by name
     *
     * @return array
     */
    public static function buildAll()
    {
        $result = [];
        foreach (self::$_types as $name => $type) {
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
     * @param string|array|\Cake\Database\Type|null $type if string name of type to map, if array list of arrays to be mapped
     * @param string|null $className The classname to register.
     * @return array|string|null if $type is null then array with current map, if $className is null string
     * configured class name for give $type, null otherwise
     */
    public static function map($type = null, $className = null)
    {
        if ($type === null) {
            return self::$_types;
        }
        if (is_array($type)) {
            self::$_types = $type;
            return null;
        }
        if ($className === null) {
            return isset(self::$_types[$type]) ? self::$_types[$type] : null;
        }
        self::$_types[$type] = $className;
    }

    /**
     * Clears out all created instances and mapped types classes, useful for testing
     *
     * @return void
     */
    public static function clear()
    {
        self::$_types = [];
        self::$_builtTypes = [];
    }

    /**
     * Returns type identifier name for this object
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Returns the base type name that this class is inheriting.
     * This is useful when extending base type for adding extra functionality
     * but still want the rest of the framework to use the same assumptions it would
     * do about the base type it inherits from.
     *
     * @return string
     */
    public function getBaseType()
    {
        return $this->_name;
    }

    /**
     * Casts given value from a PHP type to one acceptable by database
     *
     * @param mixed $value value to be converted to database equivalent
     * @param \Cake\Database\Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
     */
    public function toDatabase($value, Driver $driver)
    {
        return $this->_basicTypeCast($value);
    }

    /**
     * Casts given value from a database type to PHP equivalent
     *
     * @param mixed $value value to be converted to PHP equivalent
     * @param \Cake\Database\Driver $driver object from which database preferences and configuration will be extracted
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
     * @param mixed $value value to be converted to PHP equivalent
     * @return mixed
     * @deprecated 3.1 All types should now be a specific class
     */
    protected function _basicTypeCast($value)
    {
        if ($value === null) {
            return null;
        }
        if (!empty(self::$_basicTypes[$this->_name])) {
            $typeInfo = self::$_basicTypes[$this->_name];
            if (isset($typeInfo['callback'])) {
                return $typeInfo['callback']($value);
            }
        }
        return $value;
    }

    /**
     * Casts give value to Statement equivalent
     *
     * @param mixed $value value to be converted to PHP equivalent
     * @param \Cake\Database\Driver $driver object from which database preferences and configuration will be extracted
     * @return mixed
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
            return strtolower($value) === 'true' ? true : false;
        }
        return !empty($value);
    }

    /**
     * Type converter for string values.
     *
     * Will convert values into strings
     *
     * @param mixed $value The value to convert to a string.
     * @return bool
     * @deprecated 3.1.8 This method is now unused.
     */
    public static function strval($value)
    {
        if (is_array($value)) {
            $value = '';
        }
        return strval($value);
    }

    /**
     * Generate a new primary key value for a given type.
     *
     * This method can be used by types to create new primary key values
     * when entities are inserted.
     *
     * @return mixed A new primary key value.
     * @see \Cake\Database\Type\UuidType
     */
    public function newId()
    {
        return null;
    }

    /**
     * Marshalls flat data into PHP objects.
     *
     * Most useful for converting request data into PHP objects
     * that make sense for the rest of the ORM/Database layers.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        return $this->_basicTypeCast($value);
    }
}
