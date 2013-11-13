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
 * Validation method arguments
 *
 * @var array
 */
	protected $_ruleParams = array();

/**
 * The method to be called for a given scope
 *
 * @var string|\Closure
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
	protected $_last = true;

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
 * Constructor
 *
 * @param array $validator [optional] The validator properties
 */
	public function __construct($validator = array()) {
		$this->_addValidatorProps($validator);
	}

/**
 * Checks if the validation rule should be skipped
 *
 * @return boolean True if the ValidationRule can be skipped
 */
	public function skip() {
		if (!empty($this->on)) {
			if ($this->on === 'create' && $this->isUpdate() || $this->on === 'update' && !$this->isUpdate()) {
				return true;
			}
		}
		return false;
	}

/**
 * Returns whether this rule should break validation process for associated field
 * after it fails
 *
 * @return boolean
 */
	public function isLast() {
		return (bool)$this->last;
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
 */
	public function process($data, $scopes, $newRecord) {
		$scope = $scopes[$this->_scope];
		$callable = [$scope, $this->_rule];
		$result = $callable($data, $scopes);
		if ($result === false) {
			return $this->_message ?: false;
		}
		return $result;
	}

/**
 * Sets the rule properties from the rule entry in validate
 *
 * @param array $validator [optional]
 * @return void
 */
	protected function _addValidatorProps($validator = array()) {
		foreach ($validator as $key => $value) {
			if (isset($value) || !empty($value)) {
				if (in_array($key, array('rule', 'on', 'message', 'last', 'scope'))) {
					$this->{"_$key"} = $validator[$key];
				}
			}
		}
	}

}
