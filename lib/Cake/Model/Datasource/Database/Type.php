<?php

namespace Cake\Model\Datasource\Database;

use Cake\Model\Datasource\Database\Driver;
use Exception;
use PDO;

/**
 * Encapsulates all conversion functions for values coming from database into PHP and
 * going from PHP into database.
 **/
class Type {

/**
 * List of supported database types. A human readable
 * identifier is used as key and a complete namespaced class name as value
 * representing the class that will do actual type conversions.
 *
 * @var array
 **/
	protected static $_types = [
		'boolean' => '\Cake\Model\Datasource\Database\Type\BooleanType',
		'binary' => '\Cake\Model\Datasource\Database\Type\BinaryType',
		'date' => '\Cake\Model\Datasource\Database\Type\DateType',
		'datetime' => '\Cake\Model\Datasource\Database\Type\DateTimeType',
		'time' => '\Cake\Model\Datasource\Database\Type\TimeType'
	];

/**
 * List of basic type mappings, used to avoid having to instantiate a class
 * for doing conversion on these
 *
 * @var array
 **/
	protected static $_basicTypes = [
		'float' => ['php' => 'floatval'],
		'integer' => ['php' => 'intval', 'pdo' => PDO::PARAM_INT],
		'string' => ['php' => 'strval'],
		'text' => ['php' => 'strval'],
	];

/**
 * Contains a map of type object instances to be reused if needed
 *
 * @var array
 **/
	protected static $_builtTypes = [];

/**
 * Identifier name for this type
 *
 * @var string
 **/
	protected $_name = null;

/**
 * Constructor
 *
 * @param string $name The name identifying this type
 * @return void
 **/
	public function __construct($name = null) {
		$this->_name = $name;
	}

/**
 * Returns a Type object capable of converting a type identified by $name
 *
 * @param string $name type identifier
 * @return Type
 **/
	public static function build($name) {
		if (isset(self::$_builtTypes[$name])) {
			return self::$_builtTypes[$name];
		}
		if (isset(self::$_basicTypes[$name])) {
			return self::$_builtTypes[$name] = new self($name);
		}
		if (!isset(self::$_types[$name])) {
			throw new \InvalidArgumentException('No such type');
		}
		return self::$_builtTypes[$name] = new self::$_types[$name]($name);
	}

/**
 * Registers a new type identifier and maps it to a fully namespaced classname,
 * If called with no arguments it will return current types map array
 * If $className is omitted it will return mapped class for $type
 *
 * @param string|array $type if string name of type to map, if array list of arrays to be mapped
 * @param string $className
 * @return array|string|null if $type is null then array with current map, if $className is null string
 * configured class name for give $type, null otherwise
 **/
	public static function map($type = null, $className = null) {
		if ($type === null) {
			return self::$_types;
		}
		if (!is_string($type)) {
			self::$_types = $type;
			return;
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
 **/
	public static function clear() {
		self::$_types = [];
		self::$_builtTypes = [];
	}

/**
 * Returns type identifier name for this object
 *
 * @return string
 **/
	public function getName() {
		return $this->_name;
	}

/**
 * Casts given value to one acceptable by database
 *
 * @param mixed $value value to be converted to database equivalent
 * @param Driver $driver object from which database preferences and configuration will be extracted
 * @return mixed
 **/
	public function toDatabase($value, Driver $driver) {
		return $value;
	}

/**
 * Casts given value to PHP equivalent
 *
 * @param mixed $value value to be converted to PHP equivalent
 * @param Driver $driver object from which database preferences and configuration will be extracted
 * @return mixed
 **/
	public function toPHP($value, Driver $driver) {
		if (!empty(self::$_basicTypes[$this->_name])) {
			$typeInfo = self::$_basicTypes[$this->_name];
			$value = ($value === null) ? null : $value;
			if (isset($typeInfo['php'])) {
				return $typeInfo['php']($value);
			}
		}
		return $value;
	}

/**
 * Casts give value to Statement equivalent
 *
 * @param mixed $value value to be converted to PHP equivalent
 * @param Driver $driver object from which database preferences and configuration will be extracted
 * @return mixed
 **/
	public function toStatement($value, Driver $driver) {
		if (!empty(self::$_basicTypes[$this->_name])) {
			$typeInfo = self::$_basicTypes[$this->_name];
			return isset($typeInfo['pdo']) ? $typeInfo['pdo'] : PDO::PARAM_STR;
		}
		return PDO::PARAM_STR;
	}

}
