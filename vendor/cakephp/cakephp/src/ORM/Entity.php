<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM;

use Cake\Utility\Inflector;
use Cake\Validation\Validator;

/**
 * An entity represents a single result row from a repository. It exposes the
 * methods for retrieving and storing properties associated in this row.
 */
class Entity implements \ArrayAccess, \JsonSerializable {

/**
 * Holds all properties and their values for this entity
 *
 * @var array
 */
	protected $_properties = [];

/**
 * List of property names that should **not** be included in JSON or Array
 * representations of this Entity.
 *
 * @var array
 */
	protected $_hidden = [];

/**
 * List of computed or virtual fields that **should** be included in JSON or array
 * representations of this Entity. If a field is present in both _hidden and _virtual
 * the field will **not** be in the array/json versions of the entity.
 *
 * @var array
 */
	protected $_virtual = [];

/**
 * Holds the name of the class for the instance object
 *
 * @var string
 */
	protected $_className;

/**
 * Holds a list of the properties that were modified or added after this object
 * was originally created.
 *
 * @var array
 */
	protected $_dirty = [];

/**
 * Holds a cached list of methods that exist in the instanced class
 *
 * @var array
 */
	protected static $_accessors = [];

/**
 * Indicates whether or not this entity is yet to be persisted.
 * A null value indicates an unknown persistence status
 *
 * @var boolean
 */
	protected $_new = null;

/**
 * List of errors per field as stored in this object
 *
 * @var array
 */
	protected $_errors = [];

/**
 * Map of properties in this entity that can be safely assigned, each
 * property name points to a boolean indicating its status. An empty array
 * means no properties are accessible
 *
 * The special property '*' can also be mapped, meaning that any other property
 * not defined in the map will take its value. For example, `'*' => true`
 * means that any property not defined in the map will be accessible by default
 *
 * @var array
 */
	protected $_accessible = [];

/**
 * Initializes the internal properties of this entity out of the
 * keys in an array
 *
 * ### Example:
 *
 * ``$entity = new Entity(['id' => 1, 'name' => 'Andrew'])``
 *
 * @param array $properties hash of properties to set in this entity
 * @param array $options list of options to use when creating this entity
 * the following list of options can be used:
 *
 * - useSetters: whether use internal setters for properties or not
 * - markClean: whether to mark all properties as clean after setting them
 * - markNew: whether this instance has not yet been persisted
 * - guard: whether to prevent inaccessible properties from being set (default: false)
 */
	public function __construct(array $properties = [], array $options = []) {
		$options += [
			'useSetters' => true,
			'markClean' => false,
			'markNew' => null,
			'guard' => false
		];
		$this->_className = get_class($this);
		$this->set($properties, [
			'setter' => $options['useSetters'],
			'guard' => $options['guard']
		]);

		if ($options['markClean']) {
			$this->clean();
		}

		if ($options['markNew'] !== null) {
			$this->isNew($options['markNew']);
		}
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
		$this->set($property, $value);
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
 * {{{
 * $entity->set(['name' => 'andrew', 'id' => 1]);
 * echo $entity->name // prints andrew
 * echo $entity->id // prints 1
 * }}}
 *
 * Some times it is handy to bypass setter functions in this entity when assigning
 * properties. You can achieve this by disabling the `setter` option using the
 * `$options` parameter:
 *
 * {{{
 * $entity->set('name', 'Andrew', ['setter' => false]);
 * $entity->set(['name' => 'Andrew', 'id' => 1], ['setter' => false]);
 * }}}
 *
 * Mass assignment should be treated carefully when accepting user input, by default
 * entities will guard all fields when properties are assigned in bulk. You can disable
 * the guarding for a single set call with the `guard` option:
 *
 * {{{
 * $entity->set(['name' => 'Andrew', 'id' => 1], ['guard' => true]);
 * }}}
 *
 * You do not need to use the guard option when assigning properties individually:
 *
 * {{{
 * // No need to use the guard option.
 * $entity->set('name', 'Andrew');
 * }}}
 *
 * @param string|array $property the name of property to set or a list of
 * properties with their respective values
 * @param mixed|array $value the value to set to the property or an array if the
 * first argument is also an array, in which case will be treated as $options
 * @param array $options options to be used for setting the property. Allowed option
 * keys are `setter` and `guard`
 * @return \Cake\ORM\Entity
 */
	public function set($property, $value = null, $options = []) {
		if (is_string($property)) {
			$guard = false;
			$property = [$property => $value];
		} else {
			$guard = true;
			$options = (array)$value;
		}

		$options += ['setter' => true, 'guard' => $guard];

		foreach ($property as $p => $value) {
			if ($options['guard'] === true && !$this->accessible($p)) {
				continue;
			}

			$markDirty = true;
			if (isset($this->_properties[$p])) {
				$markDirty = $value !== $this->_properties[$p];
			}

			if ($markDirty) {
				$this->dirty($p, true);
			}

			if (!$options['setter']) {
				$this->_properties[$p] = $value;
				continue;
			}

			$setter = 'set' . Inflector::camelize($p);
			if ($this->_methodExists($setter)) {
				$value = $this->{$setter}($value);
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
		$method = 'get' . Inflector::camelize($property);
		$value = null;

		if (isset($this->_properties[$property])) {
			$value =& $this->_properties[$property];
		}

		if ($this->_methodExists($method)) {
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
 * Get/Set the hidden properties on this entity.
 *
 * If the properties argument is null, the currently hidden properties
 * will be returned. Otherwise the hidden properties will be set.
 *
 * @param null|array Either an array of properties to hide or null to get properties
 * @return array|Entity
 */
	public function hiddenProperties($properties = null) {
		if ($properties === null) {
			return $this->_hidden;
		}
		$this->_hidden = $properties;
		return $this;
	}

/**
 * Get/Set the virtual properties on this entity.
 *
 * If the properties argument is null, the currently virtual properties
 * will be returned. Otherwise the virtual properties will be set.
 *
 * @param null|array Either an array of properties to treat as virtual or null to get properties
 * @return array|Entity
 */
	public function virtualProperties($properties = null) {
		if ($properties === null) {
			return $this->_virtual;
		}
		$this->_virtual = $properties;
		return $this;
	}

/**
 * Get the list of visible properties.
 *
 * The list of visible properties is all standard properties
 * plus virtual properties minus hidden properties.
 *
 * @return array A list of properties that are 'visible' in all
 *     representations.
 */
	public function visibleProperties() {
		$properties = array_keys($this->_properties);
		$properties = array_merge($properties, $this->_virtual);
		return array_diff($properties, $this->_hidden);
	}

/**
 * Returns an array with all the properties that have been set
 * to this entity
 *
 * This method will recursively transform entities assigned to properties
 * into arrays as well.
 *
 * @return array
 */
	public function toArray() {
		$result = [];
		foreach ($this->visibleProperties() as $property) {
			$value = $this->get($property);
			if (is_array($value) && isset($value[0]) && $value[0] instanceof self) {
				$result[$property] = [];
				foreach ($value as $k => $entity) {
					$result[$property][$k] = $entity->toArray();
				}
			} elseif ($value instanceof self) {
				$result[$property] = $value->toArray();
			} else {
				$result[$property] = $value;
			}
		}
		return $result;
	}

/**
 * Returns the properties that will be serialized as JSON
 *
 * @return array
 */
	public function jsonSerialize() {
		return $this->toArray();
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
		$this->set($offset, $value);
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
 * Determines whether a method exists in this class
 *
 * @param string $method the method to check for existence
 * @return boolean true if method exists
 */
	protected function _methodExists($method) {
		if (empty(static::$_accessors[$this->_className])) {
			static::$_accessors[$this->_className] = array_flip(get_class_methods($this));
		}
		return isset(static::$_accessors[$this->_className][$method]);
	}

/**
 * Returns an array with the requested properties
 * stored in this entity, indexed by property name
 *
 * @param array $properties list of properties to be returned
 * @param boolean $onlyDirty Return the requested property only if it is dirty
 * @return array
 */
	public function extract(array $properties, $onlyDirty = false) {
		$result = [];
		foreach ($properties as $property) {
			if (!$onlyDirty || $this->dirty($property)) {
				$result[$property] = $this->get($property);
			}
		}
		return $result;
	}

/**
 * Sets the dirty status of a single property. If called with no second
 * argument, it will return whether the property was modified or not
 * after the object creation.
 *
 * When called with no arguments it will return whether or not there are any
 * dirty property in the entity
 *
 * @param string $property the field to set or check status for
 * @param null|boolean true means the property was changed, false means
 * it was not changed and null will make the function return current state
 * for that property
 * @return boolean whether the property was changed or not
 */
	public function dirty($property = null, $isDirty = null) {
		if ($property === null) {
			return !empty($this->_dirty);
		}

		if ($isDirty === null) {
			return isset($this->_dirty[$property]);
		}

		if (!$isDirty) {
			unset($this->_dirty[$property]);
			return false;
		}

		$this->_dirty[$property] = true;
		unset($this->_errors[$property]);
		return true;
	}

/**
 * Sets the entire entity as clean, which means that it will appear as
 * no properties being modified or added at all. This is an useful call
 * for an initial object hydration
 *
 * @return void
 */
	public function clean() {
		$this->_dirty = [];
		$this->_errors = [];
	}

/**
 * Returns whether or not this entity has already been persisted.
 * This method can return null in the case there is no prior information on
 * the status of this entity.
 *
 * If called with a boolean it will set the known status of this instance,
 * true means that the instance is not yet persisted in the database, false
 * that it already is.
 *
 * @param boolean $new true if it is known this instance was persisted
 * @return boolean if it is known whether the entity was already persisted
 * null otherwise
 */
	public function isNew($new = null) {
		if ($new === null) {
			return $this->_new;
		}
		return $this->_new = (bool)$new;
	}

/**
 * Validates the internal properties using a validator object. The resulting
 * errors will be copied inside this entity and can be retrieved using the
 * `errors` method.
 *
 * This function returns true if there were no validation errors or false
 * otherwise.
 *
 * @param \Cake\Validation\Validator $validator
 * @return boolean
 */
	public function validate(Validator $validator) {
		$data = $this->_properties;
		$new = $this->isNew();
		$validator->provider('entity', $this);
		$this->errors($validator->errors($data, $new === null ? true : $new));
		return empty($this->_errors);
	}

/**
 * Sets the error messages for a field or a list of fields. When called
 * without the second argument it returns the validation
 * errors for the specified fields. If called with no arguments it returns
 * all the validation error messages stored in this entity.
 *
 * ### Example
 *
 * {{{
 * // Sets the error messages for a single field
 * $entity->errors('salary', ['must be numeric', 'must be a positive number']);
 *
 * // Returns the error messages for a single field
 * $entity->errors('salary');
 *
 * // Returns all error messages indexed by field name
 * $entity->errors();
 *
 * // Sets the error messages for multiple fields at once
 * $entity->errors(['salary' => ['message'], 'name' => ['another message']);
 * }}}
 *
 * When used as a setter, this method will return this entity instance for method
 * chaining.
 *
 * @param string|array $field
 * @param string|array $errors The errors to be set for $field
 * @return array|Entity
 */
	public function errors($field = null, $errors = null) {
		if ($field === null) {
			return $this->_errors;
		}

		if (is_string($field) && $errors === null) {
			$errors = isset($this->_errors[$field]) ? $this->_errors[$field] : [];
			if (!$errors) {
				$errors = $this->_nestedErrors($field);
			}
			return $errors;
		}

		if (!is_array($field)) {
			$field = [$field => $errors];
		}

		foreach ($field as $f => $error) {
			$this->_errors[$f] = (array)$error;
		}

		return $this;
	}

/**
 * Auxiliary method for getting errors in nested entities
 *
 * @param string field the field in this entity to check for errors
 * @return array errors in nested entity if any
 */
	protected function _nestedErrors($field) {
		if (!isset($this->_properties[$field])) {
			return [];
		}

		$value = $this->_properties[$field];
		$errors = [];
		if (is_array($value) || $value instanceof \Traversable) {
			foreach ($value as $k => $v) {
				if (!($v instanceof self)) {
					break;
				}
				$errors[$k] = $v->errors();
			}
			return $errors;
		}

		if ($value instanceof self) {
			return $value->errors();
		}

		return [];
	}

/**
 * Stores whether or not a property value can be changed or set in this entity.
 * The special property '*' can also be marked as accessible or protected, meaning
 * that any other property specified before will take its value. For example
 * `$entity->accessible('*', true)`  means that any property not specified already
 * will be accessible by default.
 *
 * You can also call this method with an array of properties, in which case they
 * will each take the accessibility value specified in the second argument.
 *
 * ### Example:
 *
 * {{{
 * $entity->accessible('id', true); // Mark id as not protected
 * $entity->accessible('author_id', true); // Mark author_id as protected
 * $entity->accessible(['id', 'user_id'], true); // Mark both properties as accessible
 * $entity->accessible('*', false); // Mark all properties as protected
 * }}}
 *
 * When called without the second param it will return whether or not the property
 * can be set.
 *
 * ### Example:
 *
 * {{{
 * $entity->accessible('id'); // Returns whether it can be set or not
 * }}}
 *
 * @param string|array single or list of properties to change its accessibility
 * @param boolean $set true marks the property as accessible, false will
 * mark it as protected.
 * @return Entity|boolean
 */
	public function accessible($property, $set = null) {
		if ($set === null) {
			return !empty($this->_accessible[$property]) || !empty($this->_accessible['*']);
		}

		if ($property === '*') {
			$this->_accessible = array_map(function($p) use ($set) {
				return (bool)$set;
			}, $this->_accessible);
			$this->_accessible['*'] = (bool)$set;
			return $this;
		}

		foreach ((array)$property as $prop) {
			$this->_accessible[$prop] = (bool)$set;
		}

		return $this;
	}

}
