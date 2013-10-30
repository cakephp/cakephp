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
use Cake\Utility\Hash;

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
 * List of errors found during validation
 *
 * @var array
 */
	protected $_errors = [];

/**
 * Returns true if all fields pass validation.
 *
 * @param array $options An optional array of custom options to be made available
 * in the beforeValidate callback
 * @return boolean True if there are no errors
 */
	public function validates($options = array()) {
		$errors = $this->errors($options);
		if (is_array($errors)) {
			return count($errors) === 0;
		}
		return $errors;
	}

/**
 * Returns an array of fields that have failed validation. On the current model. This method will
 * actually run validation rules over data, not just return the messages.
 *
 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
 * @return array Array of invalid fields
 * @see Validator::validates()
 */
	public function errors($options = array()) {
		$fieldList =  $options['fieldList'];
		$exists = $model->exists();
		$methods = $this->getMethods();
		$fields = $this->_validationList($fieldList);

		foreach ($fields as $field) {
			$field->setMethods($methods);
			$data = isset($model->data[$model->alias]) ? $model->data[$model->alias] : array();
			$errors = $field->validate($data, $exists);
			foreach ($errors as $error) {
				$this->invalidate($field->field, $error);
			}
		}

		return $this->_errors;
	}

/**
 * Marks a field as invalid in an entity, optionally setting a message explaining
 * why the rule failed
 *
 * @param \Cake\ORM\Entity $entity The name of the field to invalidate
 * @param string $field The name of the field to invalidate
 * @param string $message Validation message explaining why the rule failed, defaults to true.
 * @return void
 */
	public function invalidate($entity, $field, $message = true) {
	}

/**
 * Returns a Cake ValidationSet object containing all validation rules for a field, if no
 * params are passed then it returns an array with all Cake ValidationSet objects for each field
 *
 * @param string $name [optional] The fieldname to fetch. Defaults to null.
 * @return Cake\Model\Validator\ValidationSet|array
 */
	public function getField($name = null) {
		if ($name !== null) {
			if (!empty($this->_fields[$name])) {
				return $this->_fields[$name];
			}
			return null;
		}
		return $this->_fields;
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
		return $this->_fields[$field];
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
 * Adds a new rule to a field's rule set. If second argumet is an array or instance of
 * Cake\Model\Validator\ValidationSet then rules list for the field will be replaced with second argument and
 * third argument will be ignored.
 *
 * ## Example:
 *
 * {{{
 *		$validator
 *			->add('title', 'required', array('rule' => 'notEmpty', 'required' => true))
 *			->add('user_id', 'valid', array('rule' => 'numeric', 'message' => 'Invalid User'))
 *
 *		$validator->add('password', array(
 *			'size' => array('rule' => array('between', 8, 20)),
 *			'hasSpecialCharacter' => array('rule' => 'validateSpecialchar', 'message' => 'not valid')
 *		));
 * }}}
 *
 * @param string $field The name of the field from wich the rule will be removed
 * @param string|array|Cake\Model\Validator\ValidationSet $name name of the rule to be added or list of rules for the field
 * @param array|Cake\Model\Validator\ValidationRule $rule or list of rules to be added to the field's rule set
 * @return ModelValidator this instance
 */
	public function add($field, $name, $rule = null) {
		if ($name instanceof ValidationSet) {
			$this->_fields[$field] = $name;
			return $this;
		}

		if (!isset($this->_fields[$field])) {
			$rule = (is_string($name)) ? array($name => $rule) : $name;
			$this->_fields[$field] = new ValidationSet($field, $rule);
		} else {
			if (is_string($name)) {
				$this->_fields[$field]->setRule($name, $rule);
			} else {
				$this->_fields[$field]->setRules($name);
			}
		}

		$methods = $this->getMethods();
		$this->_fields[$field]->setMethods($methods);

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
 * @return ModelValidator this instance
 */
	public function remove($field, $rule = null) {
		if ($rule === null) {
			unset($this->_fields[$field]);
		} else {
			$this->_fields[$field]->removeRule($rule);
		}
		return $this;
	}
}
