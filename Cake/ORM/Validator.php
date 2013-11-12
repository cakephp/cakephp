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
namespace Cake\ORM;

use Cake\ORM\Validation\ValidationSet;

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
	protected $_fields = array();

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
			}
			if ($keyPresent && !$this->_checkEmpty($field, $newRecord)) {
				if ($this->_fieldIsEmpty($data[$name])) {
					$errors[$name][] = __d('cake', 'This field cannot be left empty');
				}
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
 * @param \Cake\ORM\Validation\ValidationSet $set The set of rules for field
 * @return Cake\Model\Validator\ValidationSet
 */
	public function field($name, ValidationSet $set = null) {
		if (empty($this->_fields[$name])) {
			$set = $set ?: new ValidationSet;
			$this->_fields[$name] = $set;
		}
		return $this->_fields[$name];
	}

/**
 * Sets the I18n domain for validation messages. This method is chainable.
 *
 * @param string $validationDomain The validation domain to be used.
 * @return Cake\Model\Validator
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
 * @return Cake\Model\Validator\ValidationSet
 */
	public function offsetGet($field) {
		return $this->field($field);
	}

/**
 * Sets the rule set for a field
 *
 * @param string $field name of the field to set
 * @param array|Cake\Model\Validator\ValidationSet $rules set of rules to apply to field
 * @return void
 */
	public function offsetSet($field, $rules) {
		if (!$rules instanceof ValidationSet) {
			$rules = new ValidationSet($field, $rules);
			$methods = $this->getMethods();
			$rules->setMethods($methods);
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
 * @param array|Cake\ORM\Validation\ValidationRule $rule the rule to add
 * @return Validator this instance
 */
	public function add($field, $name, $rule = []) {
		$rules = $rule;
		$field = $this->field($field);

		if (!is_array($name)) {
			$rules = [$name => $rule];
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
 * 
 * Returns whether the field can be left blank according to `allowEmpty`
 *
 * @param ValidationSet $field the set of rules for a field
 * @param boolean $newRecord whether the data to be validated is new or to be updated.
 * @return boolean
 */
	protected function _checkEmpty($field, $newRecord) {
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

}
