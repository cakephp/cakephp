<?php

namespace Cake\ORM;

class Entity implements \ArrayAccess {

/**
 * Holds all properties and their values for this entity
 *
 * @var array
 */
	protected $_properties = [];

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

}
