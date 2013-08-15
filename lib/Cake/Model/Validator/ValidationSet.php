<?php
/**
 * ValidationSet.
 *
 * Provides the Model validation logic.
 *
 * PHP 5
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
 * @package       Cake.Model.Validator
 * @since         CakePHP(tm) v 2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Model\Validator;

/**
 * ValidationSet object. Holds all validation rules for a field and exposes
 * methods to dynamically add or remove validation rules
 *
 * @package       Cake.Model.Validator
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class ValidationSet implements \ArrayAccess, \IteratorAggregate, \Countable {

/**
 * Holds the ValidationRule objects
 *
 * @var array
 */
	protected $_rules = array();

/**
 * List of methods available for validation
 *
 * @var array
 */
	protected $_methods = array();

/**
 * I18n domain for validation messages.
 *
 * @var string
 */
	protected $_validationDomain = null;

/**
 * Whether the validation is stopped
 *
 * @var boolean
 */
	public $isStopped = false;

/**
 * Holds the fieldname
 *
 * @var string
 */
	public $field = null;

/**
 * Holds the original ruleSet
 *
 * @var array
 */
	public $ruleSet = array();

/**
 * Denotes whether the fieldname key must be present in data array
 *
 * @var boolean|string
 */
	protected $_validatePresent = false;

/**
 * Denotes if a field is allowed to be empty
 *
 * @var boolean|string
 */
	protected $_allowEmpty = false;

/**
 * Holds whether the record being validated is to be created or updated
 *
 * @var boolean
 */
	protected $_isUpdate = false;

/**
 * Constructor
 *
 * @param string $fieldName The fieldname
 * @param array $ruleset
 */
	public function __construct($fieldName, $ruleSet) {
		$this->field = $fieldName;

		if (!is_array($ruleSet) || (is_array($ruleSet) && isset($ruleSet['rule']))) {
			$ruleSet = array($ruleSet);
		}

		foreach ($ruleSet as $index => $validateProp) {
			if (in_array($index, array('_validatePresent', '_allowEmpty'), true)) {
				$this->{$index} = $validateProp;
			} else {
				$this->_rules[$index] = new ValidationRule($validateProp);
			}
		}
		$this->ruleSet = $ruleSet;
	}

/**
 * Sets the list of methods to use for validation
 *
 * @param array $methods Methods list
 * @return void
 */
	public function setMethods($methods) {
		$this->_methods = $methods;
	}

/**
 * Sets the I18n domain for validation messages.
 *
 * If no argument is passed the currently set domain will be returned.
 *
 * @param string $validationDomain The validation domain to be used.
 * @return string
 */
	public function validationDomain($validationDomain = null) {
		if ($validationDomain === null) {
			return $this->_validationDomain;
		}
		return $this->_validationDomain = $validationDomain;
	}

/**
 * Sets whether a field is required to be present in data array.
 *
 * If no argument is passed the currently set `validatePresent` value will be returned.
 *
 * @param boolean|string $validatePresent Valid values are true, false, 'create', 'update'
 * @return boolean|string
 */
	public function validatePresent($validatePresent = null) {
		if ($validatePresent === null) {
			return $this->_validatePresent;
		}
		return $this->_validatePresent = $validatePresent;
	}

/**
 * Sets whether a field value is allowed to be empty
 *
 * If no argument is passed the currently set `allowEmpty` value will be returned.
 *
 * @param boolean|string $allowEmpty Valid values are true, false, 'create', 'update'
 * @return boolean|string
 */
	public function allowEmpty($allowEmpty = null) {
		if ($allowEmpty === null) {
			return $this->_allowEmpty;
		}
		return $this->_allowEmpty = $allowEmpty;
	}

/**
 * Sets the isUpdate configuration value for this ruleset,
 * it refers to wheter the model record it is validating exists
 * in the collection or not (create or update operation)
 *
 * If called with no parameters it will return whether this ruleset
 * is configured for update operations or not.
 *
 * @return boolean
 */
	public function isUpdate($isUpdate = null) {
		if ($isUpdate === null) {
			return $this->_isUpdate;
		}
		foreach ($this->getRules() as $rule) {
			$rule->isUpdate($isUpdate);
		}
		return $this->_isUpdate = $isUpdate;
	}

/**
 * Runs all validation rules in this set and returns a list of
 * validation errors
 *
 * @param array $data Data array to validate
 * @param boolean $isUpdate Is record being updated or created
 * @return array list of validation errors for this field
 */
	public function validate($data, $isUpdate = false) {
		$this->reset();
		$this->_isUpdate = $isUpdate;

		if ($this->checkValidatePresent($this->field, $data)) {
			return array(__d('cake', 'This field must exist in data'));
		}

		if (!array_key_exists($this->field, $data)) {
			return array();
		}

		$errors = array();
		$checkEmpty = $this->checkEmpty($this->field, $data);
		foreach ($this->getRules() as $name => $rule) {
			if ($rule->skip()) {
				continue;
			}
			if ($checkEmpty) {
				break;
			}

			$rule->process($this->field, $data, $this->_methods);
			if (!$rule->isValid()) {
				$errors[] = $this->_processValidationResponse($name, $rule);
				if ($rule->isLast()) {
					break;
				}
			}
		}

		return $errors;
	}

/**
 * Returns whether the field can be left blank according to `allowEmpty`
 *
 * @return boolean
 */
	public function isEmptyAllowed() {
		if (in_array($this->_allowEmpty, array('create', 'update'), true)) {
			return (
				($this->_allowEmpty === 'create' && !$this->_isUpdate) ||
				($this->_allowEmpty === 'update' && $this->_isUpdate)
			);
		}

		return $this->_allowEmpty;
	}

/**
 * Checks if `validatePresent` property applies
 *
 * @param string $field Field to check
 * @param array $data data to check against
 * @return boolean
 */
	public function checkValidatePresent($field, $data) {
		if (array_key_exists($field, $data)) {
			return false;
		}

		if (in_array($this->_validatePresent, array('create', 'update'), true)) {
			return (
				($this->_validatePresent === 'create' && !$this->_isUpdate) ||
				($this->_validatePresent === 'update' && $this->_isUpdate)
			);
		}

		return $this->_validatePresent;
	}

/**
 * Checks if the `allowEmpty` property applies
 *
 * @param string $field Field to check
 * @param array $data data to check against
 * @return boolean
 */
	public function checkEmpty($field, $data) {
		if (!array_key_exists($field, $data)) {
			return false;
		}
		if (empty($data[$field]) && $data[$field] != '0' && $this->isEmptyAllowed()) {
			return true;
		}
		return false;
	}

/**
 * Resets internal state for all validation rules in this set
 *
 * @return void
 */
	public function reset() {
		foreach ($this->getRules() as $rule) {
			$rule->reset();
		}
	}

/**
 * Gets a rule for a given name if exists
 *
 * @param string $name
 * @return Cake\Model\Validator\ValidationRule
 */
	public function getRule($name) {
		if (!empty($this->_rules[$name])) {
			return $this->_rules[$name];
		}
	}

/**
 * Returns all rules for this validation set
 *
 * @return array
 */
	public function getRules() {
		return $this->_rules;
	}

/**
 * Sets a ValidationRule $rule with a $name
 *
 * ## Example:
 *
 * {{{
 *		$set
 *			->setRule('notEmpty', array('rule' => 'notEmpty'))
 *			->setRule('inRange', array('rule' => array('between', 4, 10))
 * }}}
 *
 * @param string $name The name under which the rule should be set
 * @param Cake\Model\Validator\ValidationRule|array $rule The validation rule to be set
 * @return Cake\Model\Validator\ValidationSet this instance
 */
	public function setRule($name, $rule) {
		if (!($rule instanceof ValidationRule)) {
			$rule = new ValidationRule($rule);
		}
		$this->_rules[$name] = $rule;
		return $this;
	}

/**
 * Removes a validation rule from the set
 *
 * ## Example:
 *
 * {{{
 *		$set
 *			->removeRule('notEmpty')
 *			->removeRule('inRange')
 * }}}
 *
 * @param string $name The name under which the rule should be unset
 * @return Cake\Model\Validator\ValidationSet this instance
 */
	public function removeRule($name) {
		unset($this->_rules[$name]);
		return $this;
	}

/**
 * Sets the rules for a given field
 *
 * ## Example:
 *
 * {{{
 *		$set->setRules(array(
 *			'notEmpty' => array('rule' => 'notEmpty'),
 *			'inRange' => array('rule' => array('between', 4, 10)
 * 		));
 * }}}
 *
 * @param array $rules The rules to be set
 * @param boolean $mergeVars [optional] If true, merges vars instead of replace. Defaults to true.
 * @return ModelField
 */
	public function setRules($rules = array(), $mergeVars = true) {
		if ($mergeVars === false) {
			$this->_rules = array();
		}
		foreach ($rules as $name => $rule) {
			$this->setRule($name, $rule);
		}
		return $this;
	}

/**
 * Fetches the correct error message for a failed validation
 *
 * @param string $name the name of the rule as it was configured
 * @param Cake\Model\Validator\ValidationRule $rule the object containing validation information
 * @return string
 */
	protected function _processValidationResponse($name, $rule) {
		$message = $rule->getValidationResult();
		if (is_string($message)) {
			return $message;
		}
		$message = $rule->message;

		if ($message !== null) {
			$args = null;
			if (is_array($message)) {
				$result = $message[0];
				$args = array_slice($message, 1);
			} else {
				$result = $message;
			}
			if (is_array($rule->rule) && $args === null) {
				$args = array_slice($rule->rule, 1);
			}
			$args = $this->_translateArgs($args);

			$message = __d($this->_validationDomain, $result, $args);
		} elseif (is_string($name)) {
			if (is_array($rule->rule)) {
				$args = array_slice($rule->rule, 1);
				$args = $this->_translateArgs($args);
				$message = __d($this->_validationDomain, $name, $args);
			} else {
				$message = __d($this->_validationDomain, $name);
			}
		} else {
			$message = __d('cake', 'The provided value is invalid');
		}

		return $message;
	}

/**
 * Applies translations to validator arguments.
 *
 * @param array $args The args to translate
 * @return array Translated args.
 */
	protected function _translateArgs($args) {
		foreach ((array)$args as $k => $arg) {
			if (is_string($arg)) {
				$args[$k] = __d($this->_validationDomain, $arg);
			}
		}
		return $args;
	}

/**
 * Returns whether an index exists in the rule set
 *
 * @param string $index name of the rule
 * @return boolean
 */
	public function offsetExists($index) {
		return isset($this->_rules[$index]);
	}

/**
 * Returns a rule object by its index
 *
 * @param string $index name of the rule
 * @return Cake\Model\Validator\ValidationRule
 */
	public function offsetGet($index) {
		return $this->_rules[$index];
	}

/**
 * Sets or replace a validation rule
 *
 * @param string $index name of the rule
 * @param Cake\Model\Validator\ValidationRule|array rule to add to $index
 * @return void
 */
	public function offsetSet($index, $rule) {
		$this->setRule($index, $rule);
	}

/**
 * Unsets a validation rule
 *
 * @param string $index name of the rule
 * @return void
 */
	public function offsetUnset($index) {
		unset($this->_rules[$index]);
	}

/**
 * Returns an iterator for each of the rules to be applied
 *
 * @return ArrayIterator
 */
	public function getIterator() {
		return new \ArrayIterator($this->_rules);
	}

/**
 * Returns the number of rules in this set
 *
 * @return int
 */
	public function count() {
		return count($this->_rules);
	}

}
