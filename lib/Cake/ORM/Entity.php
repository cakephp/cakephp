<?php

namespace Cake\ORM;

class Entity {

	protected $_properties = [];

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

	public function __get($property) {
		return $this->get($property);
	}

	public function __set($property, $value) {
		$this->set([$property => $value]);
	}

}
