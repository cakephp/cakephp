<?php

namespace Cake\ORM;

class Entity implements \ArrayAccess {

	protected $_properties = [];

	public function __get($property) {
		return $this->get($property);
	}

	public function __set($property, $value) {
		$this->set([$property => $value]);
	}

	public function set($properties = [], $useSetters = true) {
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

	public function get($property) {
		$method = 'get' . ucFirst($property);
		if (method_exists($this, $method)) {
			$value = $this->{$method}();
		} else {
			$value = $this->_properties[$property];
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

	public function offsetGet($offset) {
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
