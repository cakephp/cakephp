<?php
/**
 * ModelValidator.
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
App::uses('CakeRule', 'Model/Validator');

/**
 * CakeField object.
 *
 * @package       Cake.Model.Validator
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class CakeField {

/**
 * Holds the parent Validator instance
 *
 * @var ModelValidator
 */
	protected $_validator = null;

/**
 * Holds the ValidationRule objects
 *
 * @var array
 */
	protected $_rules = array();

/**
 * If the validation is stopped
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
 * @param ModelValidator $validator The parent ModelValidator
 * @param string $fieldName The fieldname
 * @param
 */
	public function __construct(ModelValidator $validator, $fieldName, $ruleSet) {
		$this->_validator = $validator;
		$this->data = &$this->getValidator()->data;
		$this->field = $fieldName;

		if (!is_array($ruleSet) || (is_array($ruleSet) && isset($ruleSet['rule']))) {
			$ruleSet = array($ruleSet);
		}

		foreach ($ruleSet as $index => $validateProp) {
			$this->_rules[$index] = new CakeRule($this, $validateProp, $index);
		}
		$this->ruleSet = $ruleSet;
		unset($ruleSet, $validateProp);
	}

/**
 * Validates a ModelField
 *
 * @return mixed
 */
	public function validate() {
		foreach ($this->getRules() as $rule) {
			if ($rule->skip()) {
				continue;
			}
			$rule->isRequired();

			if (!$rule->checkRequired() && array_key_exists($this->field, $this->data)) {
				if ($rule->checkEmpty()) {
					break;
				}
				$rule->dispatchValidation();
			}

			if ($rule->checkRequired() || !$rule->isValid()) {
				$this->getValidator()->invalidate($this->field, $rule->getMessage());

				if ($rule->isLast()) {
					return false;
				}
			}
		}

		return true;
	}

/**
 * Gets a rule for a certain index
 *
 * @param mixed index
 * @return ValidationRule
 */
	public function getRule($index) {
		if (!empty($this->_rules[$index])) {
			return $this->_rules[$index];
		}
	}

/**
 * Gets all rules for this ModelField
 *
 * @return array
 */
	public function getRules() {
		return $this->_rules;
	}

/**
 * Sets a ValidationRule $rule for key $key
 *
 * @param mixed $key The key under which the rule should be set
 * @param ValidationRule $rule The ValidationRule to be set
 * @return ModelField
 */
	public function setRule($key, CakeRule $rule) {
		$this->_rules[$key] = $rule;
		return $this;
	}

/**
 * Sets the rules for a given field
 *
 * @param array $rules The rules to be set
 * @param bolean $mergeVars [optional] If true, merges vars instead of replace. Defaults to true.
 * @return ModelField
 */
	public function setRules($rules = array(), $mergeVars = true) {
		if ($mergeVars === false) {
			$this->_rules = $rules;
		} else {
			$this->_rules = array_merge($this->_rules, $rules);
		}
		return $this;
	}

/**
 * Gets the validator this field is atached to
 *
 * @return ModelValidator The parent ModelValidator instance
 */
	public function getValidator() {
		return $this->_validator;
	}

/**
 * Magic isset
 *
 * @return true if the field exists in data, false otherwise
 */
	public function __isset($fieldName) {
		return array_key_exists($fieldName, $this->getValidator()->getData());
	}

}
