<?php
/**
 * CakeValidationSet.
 *
 * Provides the Model validation logic.
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

App::uses('CakeValidationRule', 'Model/Validator');

/**
 * CakeValidationSet object. Holds all validation rules for a field and exposes
 * methods to dynamically add or remove validation rules
 *
 * @package       Cake.Model.Validator
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class CakeValidationSet implements ArrayAccess, IteratorAggregate, Countable {

/**
 * Holds the CakeValidationRule objects
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
			$this->_rules[$index] = new CakeValidationRule($validateProp);
		}
		$this->ruleSet = $ruleSet;
	}

/**
 * Sets the list of methods to use for validation
 *
 * @param array $methods Methods list
 * @return void
 */
	public function setMethods(&$methods) {
		$this->_methods =& $methods;
	}

/**
 * Sets the I18n domain for validation messages.
 *
 * @param string $validationDomain The validation domain to be used.
 * @return void
 */
	public function setValidationDomain($validationDomain) {
		$this->_validationDomain = $validationDomain;
	}

/**
 * Runs all validation rules in this set and returns a list of
 * validation errors
 *
 * @param array $data Data array
 * @param boolean $isUpdate Is record being updated or created
 * @return array list of validation errors for this field
 */
	public function validate($data, $isUpdate = false) {
		$this->reset();
		$errors = array();
		foreach ($this->getRules() as $name => $rule) {
			$rule->isUpdate($isUpdate);
			if ($rule->skip()) {
				continue;
			}

			$checkRequired = $rule->checkRequired($this->field, $data);
			if (!$checkRequired && array_key_exists($this->field, $data)) {
				if ($rule->checkEmpty($this->field, $data)) {
					break;
				}
				$rule->process($this->field, $data, $this->_methods);
			}

			if ($checkRequired || !$rule->isValid()) {
				$errors[] = $this->_processValidationResponse($name, $rule);
				if ($rule->isLast()) {
					break;
				}
			}
		}

		return $errors;
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
 * @return CakeValidationRule
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
 * Sets a CakeValidationRule $rule with a $name
 *
 * ## Example:
 *
 * {{{
 *		$set
 *			->setRule('required', array('rule' => 'notEmpty', 'required' => true))
 *			->setRule('inRange', array('rule' => array('between', 4, 10))
 * }}}
 *
 * @param string $name The name under which the rule should be set
 * @param CakeValidationRule|array $rule The validation rule to be set
 * @return CakeValidationSet this instance
 */
	public function setRule($name, $rule) {
		if (!($rule instanceof CakeValidationRule)) {
			$rule = new CakeValidationRule($rule);
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
 *			->removeRule('required')
 *			->removeRule('inRange')
 * }}}
 *
 * @param string $name The name under which the rule should be unset
 * @return CakeValidationSet this instance
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
 *			'required' => array('rule' => 'notEmpty', 'required' => true),
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
 * @param CakeValidationRule $rule the object containing validation information
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
			$message = __d('cake', 'This field cannot be left blank');
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
 * @return CakeValidationRule
 */
	public function offsetGet($index) {
		return $this->_rules[$index];
	}

/**
 * Sets or replace a validation rule.
 *
 * This is a wrapper for ArrayAccess. Use setRule() directly for
 * chainable access.
 *
 * @see http://www.php.net/manual/en/arrayobject.offsetset.php
 *
 * @param string $index name of the rule
 * @param CakeValidationRule|array rule to add to $index
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
		return new ArrayIterator($this->_rules);
	}

/**
 * Returns the number of rules in this set
 *
 * @return integer
 */
	public function count() {
		return count($this->_rules);
	}

}
