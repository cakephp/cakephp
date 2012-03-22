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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ModelValidator', 'Model');
App::uses('CakeField', 'Model/Validator');
App::uses('Validation', 'Utility');

/**
 * ValidationRule object.
 *
 * @package       Cake.Model
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class CakeRule {

/**
 * Holds a reference to the parent field
 * 
 * @var ModelField
 */
	protected $_field = null;

/**
 * Has the required check failed?
 * 
 * @var boolean
 */
	protected $_requiredFail = null;

/**
 * The 'valid' value
 * 
 * @var mixed
 */
	protected $_valid = true;

/**
 * Holds the index under which the Vaildator was attached
 * 
 * @var mixed
 */
	protected $_index = null;

/**
 * Create or Update transaction?
 * 
 * @var boolean
 */
	protected $_modelExists = null;

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
 * The errorMessage
 * 
 * @var string
 */
	protected $_errorMessage = null;

/**
 * Holds passed in options
 * 
 * @var array
 */
	protected $_passedOptions = array();

/**
 * Flag indicating wether the allowEmpty check has failed
 * 
 * @var boolean 
 */
	protected $_emptyFail = null;

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
 * @param ModelField $field
 * @param array $validator [optional] The validator properties
 * @param mixed $index [optional]
 */
	public function __construct(CakeField $field, $validator = array(), $index = null) {
		$this->_field = $field;
		$this->_index = $index;
		unset($field, $index);

		$this->data = &$this->getField()
				->data;

		$this->_modelExists = $this->getField()
				->getValidator()
				->getModel()
				->exists();

		$this->_addValidatorProps($validator);
		unset($validator);
	}

/**
 * Checks if the rule is valid
 * 
 * @return boolean
 */
	public function isValid() {
		if (!$this->_valid || (is_string($this->_valid) && strlen($this->_valid) > 0)) {
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
		if ($this->required === true || $this->required === false) {
			return $this->required;
		}

		if (in_array($this->required, array('create', 'update'), true)) {
			if ($this->required === 'create' && !$this->_modelExists || $this->required === 'update' && $this->_modelExists) {
				$this->required = true;
			}
		}

		return $this->required;
	}

/**
 * Checks if the field failed the required validation
 * 
 * @return boolean
 */
	public function checkRequired() {
		if ($this->_requiredFail !== null) {
			return $this->_requiredFail;
		}
		$this->_requiredFail = (
			(!isset($this->data[$this->getField()->field]) && $this->required === true) ||
			(
				isset($this->data[$this->getField()->field]) && (empty($this->data[$this->getField()->field]) &&
				!is_numeric($this->data[$this->getField()->field])) && $this->allowEmpty === false
			)
		);
		return $this->_requiredFail;
	}

/**
 * Checks if the allowEmpty key applies
 * 
 * @return boolean
 */
	public function checkEmpty() {
		if ($this->_emptyFail !== null) {
			return $this->_emptyFail;
		}
		$this->_emptyFail = false;

		if (empty($this->data[$this->getField()->field]) && $this->data[$this->getField()->field] != '0' && $this->allowEmpty === true) {
			$this->_emptyFail = true;
		}
		return $this->_emptyFail;
	}

/**
 * Checks if the Validation rule can be skipped
 * 
 * @return boolean True if the ValidaitonRule can be skipped
 */
	public function skip() {
		if (!empty($this->on)) {
			if ($this->on == 'create' && $this->_modelExists || $this->on == 'update' && !$this->_modelExists) {
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
	public function getMessage() {
		return $this->_processValidationResponse();
	}

/**
 * Gets the parent field
 * 
 * @return ModelField
 */
	public function getField() {
		return $this->_field;
	}

/**
 * Gets an array with the rule properties
 * 
 * @return array
 */
	public function getPropertiesArray() {
		return array(
			'rule' => $this->rule,
			'required' => $this->required,
			'allowEmpty' => $this->allowEmpty,
			'on' => $this->on,
			'last' => $this->last,
			'message' => $this->message
		);
	}

/**
 * Dispatches the validation rule to the given validator method
 * 
 * @return boolean True if the rule could be dispatched, false otherwise
 */
	public function dispatchValidation() {
		$this->_parseRule();

		$validator = $this->getPropertiesArray();
		$methods = $this->getField()->getValidator()->getMethods();
		$Model = $this->getField()->getValidator()->getModel();

		if (in_array(strtolower($this->_rule), $methods['model'])) {
			$this->_ruleParams[] = array_merge($validator, $this->_passedOptions);
			$this->_ruleParams[0] = array($this->getField()->field => $this->_ruleParams[0]);
			$this->_valid = $Model->dispatchMethod($this->_rule, $this->_ruleParams);
		} elseif (in_array($this->_rule, $methods['behaviors']) || in_array(strtolower($this->_rule), $methods['behaviors'])) {
			$this->_ruleParams[] = array_merge($validator, $this->_passedOptions);
			$this->_ruleParams[0] = array($this->getField()->field => $this->_ruleParams[0]);
			$this->_valid = $Model->Behaviors->dispatchMethod($Model, $this->_rule, $this->_ruleParams);
		} elseif (method_exists('Validation', $this->_rule)) {
			$this->_valid = call_user_func_array(array('Validation', $this->_rule), $this->_ruleParams);
		} elseif (!is_array($validator['rule'])) {
			$this->_valid = preg_match($this->_rule, $this->data[$this->getField()->field]);
		} elseif (Configure::read('debug') > 0) {
			trigger_error(__d('cake_dev', 'Could not find validation handler %s for %s', $this->_rule, $this->_field->field), E_USER_WARNING);
			return false;
		}
		unset($validator, $methods, $Model);

		return true;
	}

/**
 * Fetches the correct error message for a failed validation
 * 
 * @return string
 */
	protected function _processValidationResponse() {
		$validationDomain = $this->_field->getValidator()->validationDomain;

		if (is_string($this->_valid)) {
			$this->_errorMessage = $this->_valid;
		} elseif ($this->message !== null) {
			$args = null;
			if (is_array($this->message)) {
				$this->_errorMessage = $this->message[0];
				$args = array_slice($this->message, 1);
			} else {
				$this->_errorMessage = $this->message;
			}
			if (is_array($this->rule) && $args === null) {
				$args = array_slice($this->getField()->ruleSet[$this->_index]['rule'], 1);
			}
			$this->_errorMessage = __d($validationDomain, $this->_errorMessage, $args);
		} elseif (is_string($this->_index)) {
			if (is_array($this->rule)) {
				$args = array_slice($this->getField()->ruleSet[$this->_index]['rule'], 1);
				$this->_errorMessage = __d($validationDomain, $this->_index, $args);
			} else {
				$this->_errorMessage = __d($validationDomain, $this->_index);
			}
		} elseif (!$this->checkRequired() && is_numeric($this->_index) && count($this->getField()->ruleSet) > 1) {
			$this->_errorMessage = $this->_index + 1;
		} else {
			$this->_errorMessage = __d('cake_dev', 'This field cannot be left blank');
		}
		unset($validationDomain);

		return $this->_errorMessage;
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
					$this->$key = $validator[$key];
				} else {
					$this->_passedOptions[$key] = $value;
				}
			}
		}
		unset($validator);
	}

/**
 * Parses the rule and sets the rule and ruleParams
 * 
 * @return void
 */
	protected function _parseRule() {
		if (is_array($this->rule)) {
			$this->_rule = $this->rule[0];
			unset($this->rule[0]);
			$this->_ruleParams = array_merge(array($this->data[$this->getField()->field]), array_values($this->rule));
		} else {
			$this->_rule = $this->rule;
			$this->_ruleParams = array($this->data[$this->getField()->field]);
		}
	}

}
