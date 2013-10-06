<?php

namespace Cake\ORM;

use Cake\Core\App;
use Cake\ORM\Table;

class Entity implements \ArrayAccess {

/**
 * Holds all properties and their values for this entity
 *
 * @var array
 */
	protected $_properties = [];

/**
 * Holds a reference to the objects acting as a repository for this type
 * of entity
 *
 * @var Cake\ORM\Table
 */
	protected static $_repository;

/**
 * A list of entity classes pointing to the class name of the repository
 * object to use
 *
 * @var array
 */
	protected static $_repositoryClass;

/**
 * List of Belongs To associations
 *
 * ### Basic usage
 *
 * `public $belongsTo = array('Group', 'Department');`
 *
 * ### Detailed configuration
 *
 * {{{
 * public $belongsTo = array(
 *     'Group',
 *     'Department' => array(
 *         'className' => 'Department',
 *         'foreignKey' => 'department_id'
 *     )
 * );
 * }}}
 *
 * @see \Cake\ORM\Table::belongsTo() for a list of accepted configuration keys
 * @var array
 */
	protected static $_belongsTo = [];

	protected static $_hasOne = [];

	protected static $_hasMany = [];

	protected static $_belongsToMany = [];

/**
 * Initializes the internal properties of this entity out of the
 * keys in an array
 *
 * ### Example:
 *
 * ``$entity = new Entity(['id' => 1, 'name' => 'Andrew'])``
 *
 * @param array $properties hash of properties to set in this entity
 * @param boolean $useSetters whether use internal setters for properties or not
 * @return void
 */
	public function __construct(array $properties = [], $useSetters = true) {
		$this->set($properties, $useSetters);
	}

/**
 * Magic getter to access properties that has be set in this entity
 *
 * @param string $property name of the property to access
 * @return mixed
 */
	public function &__get($property) {
		return $this->get($property);
	}

/**
 * Magic setter to add or edit a property in this entity
 *
 * @param string $property the name of the property to set
 * @param mixed $value the value to set to the property
 * @return void
 */
	public function __set($property, $value) {
		$this->set([$property => $value]);
	}

/**
 * Returns whether this entity contains a property named $property
 * regardless of if it is empty.
 *
 * @see \Cake\ORM\Entity::has()
 * @param string $property
 * @return boolean
 */
	public function __isset($property) {
		return $this->has($property);
	}

/**
 * Removes a property from this entity
 *
 * @param string $property
 * @return void
 */
	public function __unset($property) {
		$this->unsetProperty($property);
	}

/**
 * Sets a single property inside this entity.
 *
 * ### Example:
 *
 * ``$entity->set('name', 'Andrew');``
 *
 * It is also possible to mass-assign multiple properties to this entity
 * with one call by passing a hashed array as properties in the form of
 * property => value pairs
 *
 * ## Example:
 *
 * {{
 *	$entity->set(['name' => 'andrew', 'id' => 1]);
 *	echo $entity->name // prints andrew
 *	echo $entity->id // prints 1
 * }}
 *
 * Some times it is handy to bypass setter functions in this entity when assigning
 * properties. You can achieve this by setting the third argument to false when
 * assigning a single property or the second param when using an array of
 * properties.
 *
 * ### Example:
 *
 * ``$entity->set('name', 'Andrew', false);``
 *
 * ``$entity->set(['name' => 'Andrew', 'id' => 1], false);``
 *
 * @param string|array $property the name of property to set or a list of
 * properties with their respective values
 * @param mixed|boolean $value the value to set to the property or a boolean
 * signifying whether to use internal setter functions or not
 * @param boolean $useSetters whether to use setter functions in this object
 * or bypass them
 * @return \Cake\ORM\Entity
 */
	public function set($property, $value = true, $useSetters = true) {
		if (is_string($property)) {
			$property = [$property => $value];
		} else {
			$useSetters = $value;
		}

		if (!$useSetters) {
			$this->_properties = $property + $this->_properties;
			return $this;
		}

		foreach ($property as $p => $value) {
			if (method_exists($this, 'set' . ucFirst($p))) {
				$value = $this->{'set' . ucFirst($p)}($value);
			}
			$this->_properties[$p] = $value;
		}
		return $this;
	}

/**
 * Returns the value of a property by name
 *
 * @param string $property the name of the property to retrieve
 * @return mixed
 */
	public function &get($property) {
		$method = 'get' . ucFirst($property);
		$value = null;

		if (isset($this->_properties[$property])) {
			$value =& $this->_properties[$property];
		}

		if (method_exists($this, $method)) {
			$value = $this->{$method}($value);
		}
		return $value;
	}

/**
 * Returns whether this entity contains a property named $property
 * regardless of if it is empty.
 *
 * ### Example:
 *
 * {{{
 *		$entity = new Entity(['id' => 1, 'name' => null]);
 *		$entity->has('id'); // true
 *		$entity->has('name'); // false
 *		$entity->has('last_name'); // false
 * }}}
 *
 * @param string $property
 * @return boolean
 */
	public function has($property) {
		return $this->get($property) !== null;
	}

/**
 * Removes a property or list of properties from this entity
 *
 * ### Examples:
 *
 * ``$entity->unsetProperty('name');``
 *
 * ``$entity->unsetProperty(['name', 'last_name']);``
 *
 * @param string|array $property
 * @return \Cake\ORM\
 */
	public function unsetProperty($property) {
		$property = (array)$property;
		foreach ($property as $p) {
			unset($this->_properties[$p]);
		}

		return $this;
	}

/**
 * Returns an array with all the properties that have been set
 * to this entity
 *
 * @return array
 */
	public function toArray() {
		$result = [];
		foreach ($this->_properties as $property => $value) {
			$result[$property] = $this->get($property);
		}
		return $result;
	}

/**
 * Implements isset($entity);
 *
 * @param mixed $offset
 * @return void
 */
	public function offsetExists($offset) {
		return $this->has($offset);
	}
/**
 * Implements $entity[$offset];
 *
 * @param mixed $offset
 * @return void
 */

	public function &offsetGet($offset) {
		return $this->get($offset);
	}

/**
 * Implements $entity[$offset] = $value;
 *
 * @param mixed $offset
 * @param mixed $value
 * @return void
 */

	public function offsetSet($offset, $value) {
		$this->set([$offset => $value]);
	}

/**
 * Implements unset($result[$offset);
 *
 * @param mixed $offset
 * @return void
 */
	public function offsetUnset($offset) {
		$this->unsetProperty($offset);
	}

/**
 * Returns the instance of the table object associated to this entity.
 * If called with a Table object as first argument, it will be set as the default
 * repository object to use.
 *
 * @param \Cake\ORM\Table $table The table object to use as a repository of this
 * type of Entity
 * @return \Cake\ORM\Table
 */
	public static function repository(Table $table = null) {
		if ($table === null) {
			if (static::$_repository === null) {
				$className = static::repositoryClass();
				$self = get_called_class();
				list($namespace, $alias) = namespaceSplit($self);
				static::$_repository = $className::build($alias, [
					'entityClass' => $self
				]);
			}
			return static::$_repository;
		}
		$table->entityClass(get_called_class());
		return static::$_repository = $table;
	}

/**
 * Returns the fully namespaced class name for the repository object associated to
 * this entity. If a string is passed as first argument, it will be used to store
 * the name of the repository object to use.
 *
 * Plugin notation can be used to specify the name of the object to load. By
 * convention, classes will be loaded from `[PluginName]\Model\Repository\`
 * base namespace if a plugin is specified or from `App\Model\Repository` if
 * none is passed.
 *
 * ### Examples:
 *
 * - ``User::repositoryClass('\App\Model\Repository\SuperUserTable');``
 * - ``User::repositoryClass('My.User');`` // Looks inside My plugin
 *
 * @param string $class Fully namespaced class name or name of the object using
 * plugin notation.
 * @throws \Cake\ORM\Error\MissingTableClassException when the table class cannot be found
 * @return string|boolean the full name of the class or false if the class does not exist
 */
	public static function repositoryClass($class = null) {
		$self = get_called_class();
		if ($class) {
			self::$_repositoryClass[$self] = $class;
		}

		if (empty(static::$_repositoryClass[$self])) {
			if (!empty(self::$_repository)) {
				return self::$_repositoryClass[$self] = get_class(static::$_repository);
			}

			$default = '\Cake\ORM\Table';
			$self = get_called_class();
			$parts = explode('\\', $self);

			if ($self === __CLASS__ || count($parts) < 3) {
				return self::$_repositoryClass[$self] = $default;
			}

			$alias = array_pop($parts) . 'Table';
			$class = implode('\\', array_slice($parts, 0, -1)) . '\Repository\\' . $alias;
			if (!class_exists($class)) {
				return self::$_repositoryClass[$self] = $default;
			}

			self::$_repositoryClass[$self] = $class;
		}

		$result = App::className(self::$_repositoryClass[$self], 'Model\Repository', 'Table');
		if (!$result) {
			throw new Error\MissingTableClassException([static::$_repositoryClass[$self]]);
		}

		return $result;
	}

/**
 * Returns the BelongsTo associations as statically defined in the $_belongsTo
 * property
 *
 * @return array
 */
	public static function belongsTo() {
		return static::$_belongsTo;
	}

/**
 * Returns the HasOne associations as statically defined in the $_hasOne
 * property
 *
 * @return array
 */
	public static function hasOne() {
		return static::$_hasOne;
	}

/**
 * Returns the HasMany associations as statically defined in the $_hasMany
 * property
 *
 * @return array
 */
	public static function hasMany() {
		return static::$_hasMany;
	}

/**
 * Returns the BelongsToMany associations as statically defined in the
 * $_belongsToMany property
 *
 * @return array
 */
	public static function belongsToMany() {
		return static::$_belongsToMany;
	}

}
