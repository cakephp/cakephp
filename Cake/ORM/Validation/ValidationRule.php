<?php
/**
 * ValidationRule.
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
 * @since         CakePHP(tm) v 2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Validation;

/**
 * ValidationRule object. Represents a validation method, error message and
 * rules for applying such method to a field.
 *
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class ValidationRule {

/**
 * The method to be called for a given scope
 *
 * @var string|callable
 */
	protected $_rule;

/**
 * The 'on' key
 *
 * @var string
 */
	protected $_on = null;

/**
 * The 'last' key
 *
 * @var boolean
 */
	protected $_last = false;

/**
 * The 'message' key
 *
 * @var string
 */
	protected $_message = null;

/**
 * Key under which the object or class where the method to be used for
 * validation will be found
 *
 * @var string
 */
	protected $_scope = 'default';

/**
 * Extra arguments to be passed to the validation method
 *
 * @var array
 */
	protected $_pass = [];

/**
 * Constructor
 *
 * @param array $validator [optional] The validator properties
 */
	public function __construct($validator = array()) {
		$this->_addValidatorProps($validator);
	}

/**
 * Returns whether this rule should break validation process for associated field
 * after it fails
 *
 * @return boolean
 */
	public function isLast() {
		return (bool)$this->_last;
	}

/**
 * Dispatches the validation rule to the given validator method and returns
 * a boolean indicating whether the rule passed or not. If a string is returned
 * it is assumed that the rule failed and the error message was given as a result.
 *
 * @param mixed $data The data to validate
 * @param array $scopes associative array with objects or class names that will
 * be passed as the last argument for the validation method
 * @param boolean $newRecord whether or not the data to be validated belongs to
 * a new record
 * @return boolean|string
 * @throws \InvalidArgumentException when the supplied rule is not a valid
 * callable for the configured scope
 */
	public function process($data, $scopes, $newRecord) {
		if ($this->_skip($newRecord, $scopes)) {
			return true;
		}

		if (is_callable($this->_rule)) {
			$callable = $this->_rule;
			$isCallable = true;
		} else {
			$scope = $scopes[$this->_scope];
			$callable = [$scope, $this->_rule];
			$isCallable = is_callable($callable);
		}

		if (!$isCallable) {
			throw new \InvalidArgumentException('Invalid validation callable');
		}

		if ($this->_pass) {
			$args = array_merge([$data], $this->_pass, [$scopes]);
			$result = call_user_func_array($callable, $args);
		} else {
			$result = $callable($data, $scopes);
		}

		if ($result === false) {
			return $this->_message ?: false;
		}
		return $result;
	}

/**
 * Checks if the validation rule should be skipped
 *
 * @param boolean $newRecord whether the rule to be processed is new or pre-existent
 * @param array $scopes associative array with objects or class names that will
 * be passed as the last argument for the validation method
 * @return boolean True if the ValidationRule should be skipped
 */
	protected function _skip($newRecord, $scopes) {
		if (is_callable($this->_on)) {
			$function = $this->_on;
			return !$function($scopes);
		}

		if (!empty($this->_on)) {
			if ($this->_on === 'create' && !$newRecord || $this->_on === 'update' && $newRecord) {
				return true;
			}
		}
		return false;
	}


/**
 * Sets the rule properties from the rule entry in validate
 *
 * @param array $validator [optional]
 * @return void
 */
	protected function _addValidatorProps($validator = array()) {
		foreach ($validator as $key => $value) {
			if (!isset($value) || empty($value)) {
				continue;
			}
			if ($key === 'rule' && is_array($value)) {
				$this->_pass = array_slice($value, 1);
				$value = array_shift($value);
			}
			if (in_array($key, ['rule', 'on', 'message', 'last', 'scope', 'pass'])) {
				$this->{"_$key"} = $value;
			}
		}
	}

}
