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
 * @since         CakePHP(tm) v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Validation;

use Cake\Validation\RulesProvider;
use Cake\Validation\ValidationSet;

/**
 * Validator object encapsulates all methods related to data validations for a model
 * It also provides an API to dynamically change validation rules for each model field.
 *
 * Implements ArrayAccess to easily modify rules in the set
 *
 * @link http://book.cakephp.org/2.0/en/data-validation.html
 */
class Validator implements \ArrayAccess, \IteratorAggregate, \Countable {

/**
 * Holds the ValidationSet objects array
 *
 * @var array
 */
	protected $_fields = [];

/**
 * An associative array of objects or classes containing methods
 * used for validation
 *
 * @var array
 */
	protected $_providers = [];

/**
 * The translation domain to use when setting the error messages
 *
 * @var string
 */
	protected $_validationDomain = 'default';

/**
 * Returns an array of fields that have failed validation. On the current model. This method will
 * actually run validation rules over data, not just return the messages.
 *
 * @param array $data The data to be checked for errors
 * @param boolean $newRecord whether the data to be validated is new or to be updated.
 * @return array Array of invalid fields
 * @see Validator::validates()
 */
	public function errors(array $data, $newRecord = true) {
		$errors = [];
		foreach ($this->_fields as $name => $field) {
			$keyPresent = array_key_exists($name, $data);

			if (!$keyPresent && !$this->_checkPresence($field, $newRecord)) {
				$errors[$name][] = __d('cake', 'This field is required');
				continue;
			}

			if (!$keyPresent) {
				continue;
			}

			$canBeEmpty = $this->_canBeEmpty($field, $newRecord);
			$isEmpty = $this->_fieldIsEmpty($data[$name]);

			if (!$canBeEmpty && $isEmpty) {
				$errors[$name][] = __d('cake', 'This field cannot be left empty');
				continue;
			}

			if ($isEmpty) {
				continue;
			}

			$result = $this->_processRules($field, $data[$name], $data, $newRecord);
			if ($result) {
				$errors[$name] = $result;
			}
		}

		return $errors;
	}

/**
 * Returns a ValidationSet object containing all validation rules for a field, if
 * passed a ValidationSet as second argument, it will replace any other rule set defined
 * before
 *
 * @param string $name [optional] The fieldname to fetch.
 * @param \Cake\Validation\ValidationSet $set The set of rules for field
 * @return \Cake\Validation\ValidationSet
 */
	public function field($name, ValidationSet $set = null) {
		if (empty($this->_fields[$name])) {
			$set = $set ?: new ValidationSet;
			$this->_fields[$name] = $set;
		}
		return $this->_fields[$name];
	}

/**
 * Check whether or not a validator contains any rules for the given field.
 *
 * @param string $name The field name to check.
 * @return boolean
 */
	public function hasField($name) {
		return isset($this->_fields[$name]);
	}

/**
 * Associates an object to a name so it can be used as a provider. Providers are
 * objects or class names that can contain methods used during validation of for
 * deciding whether a validation rule can be applied. All validation methods,
 * when called will receive the full list of providers stored in this validator.
 *
 * If called with no arguments, it will return the provider stored under that name if
 * it exists, otherwise it returns this instance of chaining.
 *
 * @param string $name
 * @param null|object|string $object
 * @return Validator|object|string
 */
	public function provider($name, $object = null) {
		if ($object === null) {
			if (isset($this->_providers[$name])) {
				return $this->_providers[$name];
			}
			if ($name === 'default') {
				return $this->_providers[$name] = new RulesProvider;
			}
			return null;
		}
		$this->_providers[$name] = $object;
		return $this;
	}

/**
 * Sets the I18n domain for validation messages. This method is chainable.
 *
 * @param string $validationDomain The validation domain to be used.
 * @return \Cake\Validation
 */
	public function setValidationDomain($validationDomain) {
		$this->_validationDomain = $validationDomain;
		return $this;
	}

/**
 * Returns whether a rule set is defined for a field or not
 *
 * @param string $field name of the field to check
 * @return boolean
 */
	public function offsetExists($field) {
		return isset($this->_fields[$field]);
	}

/**
 * Returns the rule set for a field
 *
 * @param string $field name of the field to check
 * @return \Cake\Validation\ValidationSet
 */
	public function offsetGet($field) {
		return $this->field($field);
	}

/**
 * Sets the rule set for a field
 *
 * @param string $field name of the field to set
 * @param array|\Cake\Validation\ValidationSet $rules set of rules to apply to field
 * @return void
 */
	public function offsetSet($field, $rules) {
		if (!$rules instanceof ValidationSet) {
			$set = new ValidationSet;
			foreach ((array)$rules as $name => $rule) {
				$set->add($name, $rule);
			}
		}
		$this->_fields[$field] = $rules;
	}

/**
 * Unsets the rule set for a field
 *
 * @param string $field name of the field to unset
 * @return void
 */
	public function offsetUnset($field) {
		unset($this->_fields[$field]);
	}

/**
 * Returns an iterator for each of the fields to be validated
 *
 * @return ArrayIterator
 */
	public function getIterator() {
		return new \ArrayIterator($this->_fields);
	}

/**
 * Returns the number of fields having validation rules
 *
 * @return integer
 */
	public function count() {
		return count($this->_fields);
	}

/**
 * Adds a new rule to a field's rule set. If second argument is an array
 * then rules list for the field will be replaced with second argument and
 * third argument will be ignored.
 *
 * ## Example:
 *
 * {{{
 *		$validator
 *			->add('title', 'required', array('rule' => 'notEmpty'))
 *			->add('user_id', 'valid', array('rule' => 'numeric', 'message' => 'Invalid User'))
 *
 *		$validator->add('password', array(
 *			'size' => array('rule' => array('between', 8, 20)),
 *			'hasSpecialCharacter' => array('rule' => 'validateSpecialchar', 'message' => 'not valid')
 *		));
 * }}}
 *
 * @param string $field The name of the field from wich the rule will be removed
 * @param array|string $name The alias for a single rule or multiple rules array
 * @param array|\Cake\Validation\ValidationRule $rule the rule to add
 * @return Validator this instance
 */
	public function add($field, $name, $rule = []) {
		$field = $this->field($field);

		if (!is_array($name)) {
			$rules = [$name => $rule];
		} else {
			$rules = $name;
		}

		foreach ($rules as $name => $rule) {
			$field->add($name, $rule);
		}

		return $this;
	}

/**
 * Removes a rule from the set by its name
 *
 * ## Example:
 *
 * {{{
 *		$validator
 *			->remove('title', 'required')
 *			->remove('user_id')
 * }}}
 *
 * @param string $field The name of the field from which the rule will be removed
 * @param string $rule the name of the rule to be removed
 * @return Validator this instance
 */
	public function remove($field, $rule = null) {
		if ($rule === null) {
			unset($this->_fields[$field]);
		} else {
			$this->field($field)->remove($rule);
		}
		return $this;
	}

/**
 * Sets whether a field is required to be present in data array.
 *
 * @param string $field the name of the field
 * @param boolean|string $mode Valid values are true, false, 'create', 'update'
 * @return Validator this instance
 */
	public function validatePresence($field, $mode = true) {
		$this->field($field)->isPresenceRequired($mode);
		return $this;
	}

/**
 * Sets whether a field is allowed to be empty. If it is,  all other validation
 * rules will be ignored
 *
 * @param string $field the name of the field
 * @param boolean|string $mode Valid values are true, false, 'create', 'update'
 * @return Validator this instance
 */
	public function allowEmpty($field, $mode = true) {
		$this->field($field)->isEmptyAllowed($mode);
		return $this;
	}

/**
 * Returns whether or not a field can be left empty for a new or already existing
 * record.
 *
 * @param string field
 * @param boolean $newRecord whether the data to be validated is new or to be updated.
 * @return boolean
 */
	public function isEmptyAllowed($field, $newRecord) {
		return $this->_canBeEmpty($this->field($field), $newRecord);
	}

/**
 * Returns whether or not a field can be left out for a new or already existing
 * record.
 *
 * @param string field
 * @param boolean $newRecord whether the data to be validated is new or to be updated.
 * @return boolean
 */
	public function isPresenceRequired($field, $newRecord) {
		return !$this->_checkPresence($this->field($field), $newRecord);
	}

/**
 * Returns false if any validation for the passed rule set should be stopped
 * due to the field missing in the data array
 *
 * @param ValidationSet $field the set of rules for a field
 * @param boolean $newRecord whether the data to be validated is new or to be updated.
 * @return boolean
 */
	protected function _checkPresence($field, $newRecord) {
		$required = $field->isPresenceRequired();
		if (in_array($required, ['create', 'update'], true)) {
			return (
				($required === 'create' && !$newRecord) ||
				($required === 'update' && $newRecord)
			);
		}

		return !$required;
	}

/**
 * Returns whether the field can be left blank according to `allowEmpty`
 *
 * @param ValidationSet $field the set of rules for a field
 * @param boolean $newRecord whether the data to be validated is new or to be updated.
 * @return boolean
 */
	protected function _canBeEmpty($field, $newRecord) {
		$allowed = $field->isEmptyAllowed();
		if (in_array($allowed, array('create', 'update'), true)) {
			$allowed = (
				($allowed === 'create' && $newRecord) ||
				($allowed === 'update' && !$newRecord)
			);
		}

		return $allowed;
	}

/**
 * Returns true if the field is empty in the passed data array
 *
 * @param mixed $data value to check against
 * @return boolean
 */
	protected function _fieldIsEmpty($data) {
		if (empty($data) && $data !== '0' && $data !== false && $data !== 0) {
			return true;
		}
		return false;
	}

/**
 * Iterates over each rule in the validation set and collects the errors resulting
 * from executing them
 *
 * @param ValidationSet $rules the list of rules for a field
 * @param mixed $value the value to be checked
 * @param array $data the full data passed to the validator
 * @param boolean $newRecord whether is it a new record or an existing one
 * @return array
 */
	protected function _processRules(ValidationSet $rules, $value, $data, $newRecord) {
		$errors = [];
		// Loading default provider in case there is none
		$this->provider('default');
		foreach ($rules as $name => $rule) {
			$result = $rule->process($value, $this->_providers, compact('newRecord', 'data'));
			if ($result === true) {
				continue;
			}

			$errors[$name] = __d('cake', 'The provided value is invalid');
			if (is_string($result)) {
				$errors[$name] = __d($this->_validationDomain, $result);
			}

			if ($rule->isLast()) {
				break;
			}
		}
		return $errors;
	}

}
