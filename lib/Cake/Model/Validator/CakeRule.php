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
 * Holds a reference to the parent field
 *
 * @var CakeField
 */
	protected $_field = null;

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
	public function __construct($field, $validator = array(), $index = null) {
		$this->_field = $field;
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
			if ($this->required === 'create' && !$this->_recordExists || $this->required === 'update' && $this->_recordExists) {
				$this->required = true;
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
	public function checkRequired(&$data) {
		return (
			(!isset($data[$this->_field]) && $this->isRequired() === true) ||
			(
				isset($data[$this->_field]) && (empty($data[$this->_field]) &&
				!is_numeric($data[$this->_field])) && $this->allowEmpty === false
			)
		);
	}

/**
 * Checks if the allowEmpty key applies
 *
 * @param array $data data to check rule against
 * @return boolean
 */
	public function checkEmpty(&$data) {
		if (empty($data[$this->_field]) && $data[$this->_field] != '0' && $this->allowEmpty === true) {
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
			if ($this->on == 'create' && $this->_recordExists || $this->on == 'update' && !$this->_recordExists) {
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
 * @return void 
 **/
	public function isUpdate($exists = false) {
		$this->_recordExists = $exists;
	}

/**
 * Dispatches the validation rule to the given validator method
 *
 * @return boolean True if the rule could be dispatched, false otherwise
 */
	public function dispatchValidation(&$data, &$methods) {
		$this->_parseRule($data);

		$validator = $this->getPropertiesArray();
		$rule = strtolower($this->_rule);
		if (isset($methods[$rule])) {
			$this->_ruleParams[] = array_merge($validator, $this->_passedOptions);
			$this->_ruleParams[0] = array($this->_field => $this->_ruleParams[0]);
			$this->_valid =  call_user_func_array($methods[$rule], $this->_ruleParams);
		} elseif (class_exists('Validation') && method_exists('Validation', $this->_rule)) {
			$this->_valid = call_user_func_array(array('Validation', $this->_rule), $this->_ruleParams);
		} elseif (is_string($validator['rule'])) {
			$this->_valid = preg_match($this->_rule, $data[$this->_field]);
		} elseif (Configure::read('debug') > 0) {
			trigger_error(__d('cake_dev', 'Could not find validation handler %s for %s', $this->_rule, $this->_field), E_USER_WARNING);
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
	protected function _parseRule(&$data) {
		if (is_array($this->rule)) {
			$this->_rule = $this->rule[0];
			$this->_ruleParams = array_merge(array($data[$this->_field]), array_values(array_slice($this->rule, 1)));
		} else {
			$this->_rule = $this->rule;
			$this->_ruleParams = array($data[$this->_field]);
		}
	}

}
