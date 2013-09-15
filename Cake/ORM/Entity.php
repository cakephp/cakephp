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
 * Set a hashed array as properties in this entity by converting each
 * key => value pair into properties in this object.
 *
 * ## Example:
 *
 * {{
 *	$entity->set(['name' => 'andrew', 'id' => 1]);
 *	echo $entity->name // prints andrew
 *	echo $entity->id // prints 1
 * }}
 *
 * @param array $properties list of properties to set
 * @param boolean $useSetters whether to use setter functions in this object
 * or bypass them
 * @return \Cake\ORM\Entity
 */
	public function set(array $properties = [], $useSetters = true) {
		if (!$useSetters) {
			$this->_properties = $properties + $this->_properties;
			return $this;
		}

		foreach($properties as $property => $value) {
			if (method_exists($this, 'set' . ucFirst($property))) {
				$value = $this->{'set' . ucFirst($property)}($value);
			}
			$this->_properties[$property] = $value;
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
		if (method_exists($this, $method)) {
			$value =& $this->{$method}();
		} else {
			$value =& $this->_properties[$property];
		}
		return $value;
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
		return isset($this->_properties[$offset]);
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
		unset($this->_properties[$offset]);
	}

}
