<?php
/**
 * CakeRule.
 *
 * Provides the Model validation logic.
 *
 * PHP versions 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Validator
 * @since         CakePHP(tm) v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ModelValidator', 'Model');
App::uses('CakeField', 'Model/Validator');
App::uses('Validation', 'Utility');

/**
 * CakeRule object.
 *
 * @package       Cake.Model.Validator
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class CakeRule {

/**
 * The 'valid' value
 *
 * @var mixed
 */
	protected $_valid = true;

/**
 * Holds the index under which the Validator was attached
 *
 * @var mixed
 */
	protected $_index = null;

/**
 * Create or Update transaction?
 *
 * @var boolean
 */
	protected $_recordExists = false;

/**
 * The parsed rule
 *
 * @var mixed
 */
	protected $_rule = null;

/**
 * The parsed rule parameters
 *
 * @var array
 */
	protected $_ruleParams = array();

/**
 * Holds passed in options
 *
 * @var array
 */
	protected $_passedOptions = array();

/**
 * The 'rule' key
 *
 * @var mixed
 */
	public $rule = 'blank';

/**
 * The 'required' key
 *
 * @var mixed
 */
	public $required = null;

/**
 * The 'allowEmpty' key
 *
 * @var boolean
 */
	public $allowEmpty = false;

/**
 * The 'on' key
 *
 * @var string
 */
	public $on = null;

/**
 * The 'last' key
 *
 * @var boolean
 */
	public $last = true;

/**
 * The 'message' key
 *
 * @var string
 */
	public $message = null;

/**
 * Constructor
 *
 * @param array $validator [optional] The validator properties
 * @param mixed $index [optional]
 */
	public function __construct($index = null, $validator = array()) {
		$this->_index = $index;
		$this->_addValidatorProps($validator);
	}

/**
 * Checks if the rule is valid
 *
 * @return boolean
 */
	public function isValid() {
		if (!$this->_valid || (is_string($this->_valid) && !empty($this->_valid))) {
			return false;
		}

		return true;
	}

/**
 * Checks if the field is required by the 'required' value
 *
 * @return boolean
 */
	public function isRequired() {
		if (is_bool($this->required)) {
			return $this->required;
		}
		if (in_array($this->required, array('create', 'update'), true)) {
			if ($this->required === 'create' && !$this->isUpdate() || $this->required === 'update' && $this->isUpdate()) {
				$this->required = true;
			} else {
				$this->required = false;
			}
		}

		return $this->required;
	}

/**
 * Checks if the field failed the required validation
 *
 * @param array $data data to check rule against
 * @return boolean
 */
	public function checkRequired($field, &$data) {
		return (
			(!isset($data[$field]) && $this->isRequired() === true) ||
			(
				isset($data[$field]) && (empty($data[$field]) &&
				!is_numeric($data[$field])) && $this->allowEmpty === false
			)
		);
	}

/**
 * Checks if the allowEmpty key applies
 *
 * @param array $data data to check rule against
 * @return boolean
 */
	public function checkEmpty($field, &$data) {
		if (empty($data[$field]) && $data[$field] != '0' && $this->allowEmpty === true) {
			return true;
		}
		return false;
	}

/**
 * Checks if the Validation rule can be skipped
 *
 * @return boolean True if the ValidationRule can be skipped
 */
	public function skip() {
		if (!empty($this->on)) {
			if ($this->on == 'create' && $this->isUpdate() || $this->on == 'update' && !$this->isUpdate()) {
				return true;
			}
		}
		return false;
	}

/**
 * Checks if the 'last' key is true
 *
 * @return boolean
 */
	public function isLast() {
		return (bool) $this->last;
	}

/**
 * Gets the validation error message
 *
 * @return string
 */
	public function getValidationResult() {
		return $this->_valid;
	}

/**
 * Gets an array with the rule properties
 *
 * @return array
 */
	public function getPropertiesArray() {
		$rule = $this->rule;
		if (!is_string($rule)) {
			unset($rule[0]);
		}
		return array(
			'rule' => $rule,
			'required' => $this->required,
			'allowEmpty' => $this->allowEmpty,
			'on' => $this->on,
			'last' => $this->last,
			'message' => $this->message
		);
	}

/**
 * Sets the recordExists configuration value for this rule,
 * ir refers to wheter the model record it is validating exists
 * exists in the collection or not (create or update operation)
 *
 * If called with no parameters it will return whether this rule
 * is configured for update operations or not.
 *
 * @return boolean 
 **/
	public function isUpdate($exists = null) {
		if ($exists === null) {
			return $this->_recordExists;
		}
		return $this->_recordExists = $exists;
	}

/**
 * Dispatches the validation rule to the given validator method
 *
 * @return boolean True if the rule could be dispatched, false otherwise
 */
	public function process($field, &$data, &$methods) {
		$this->_parseRule($field, $data);

		$validator = $this->getPropertiesArray();
		$rule = strtolower($this->_rule);
		if (isset($methods[$rule])) {
			$this->_ruleParams[] = array_merge($validator, $this->_passedOptions);
			$this->_ruleParams[0] = array($field => $this->_ruleParams[0]);
			$this->_valid =  call_user_func_array($methods[$rule], $this->_ruleParams);
		} elseif (class_exists('Validation') && method_exists('Validation', $this->_rule)) {
			$this->_valid = call_user_func_array(array('Validation', $this->_rule), $this->_ruleParams);
		} elseif (is_string($validator['rule'])) {
			$this->_valid = preg_match($this->_rule, $data[$field]);
		} elseif (Configure::read('debug') > 0) {
			trigger_error(__d('cake_dev', 'Could not find validation handler %s for %s', $this->_rule, $field), E_USER_WARNING);
			return false;
		}

		return true;
	}

	public function getOptions($key) {
		if (!isset($this->_passedOptions[$key])) {
			return null;
		}
		return $this->_passedOptions[$key];
	}

	public function getName() {
		return $this->_index;
	}

/**
 * Sets the rule properties from the rule entry in validate
 *
 * @param array $validator [optional]
 * @return void
 */
	protected function _addValidatorProps($validator = array()) {
		if (!is_array($validator)) {
			$validator = array('rule' => $validator);
		}
		foreach ($validator as $key => $value) {
			if (isset($value) || !empty($value)) {
				if (in_array($key, array('rule', 'required', 'allowEmpty', 'on', 'message', 'last'))) {
					$this->{$key} = $validator[$key];
				} else {
					$this->_passedOptions[$key] = $value;
				}
			}
		}
	}

/**
 * Parses the rule and sets the rule and ruleParams
 *
 * @return void
 */
	protected function _parseRule($field, &$data) {
		if (is_array($this->rule)) {
			$this->_rule = $this->rule[0];
			$this->_ruleParams = array_merge(array($data[$field]), array_values(array_slice($this->rule, 1)));
		} else {
			$this->_rule = $this->rule;
			$this->_ruleParams = array($data[$field]);
		}
	}

}
