<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Rule\ExistsIn;

/**
 * Contains logic for storing and checking rules on entities
 *
 */
class RulesChecker {

	protected $_rules = [];

	protected $_createRules = [];

	protected $_updateRules = [];

	protected $_options = [];

	public function __construct(array $options) {
		$this->_options = $options;
	}

	public function add(callable $rule, array $options = []) {
		$this->_rules[] = $this->_addError($rule, $options);
		return $this;
	}

	public function addCreate(callable $rule, array $options = []) {
		$this->_createRules[] = $this->_addError($rule, $options);
		return $this;
	}

	public function addUpdate(callable $rule, array $options = []) {
		$this->_updateRules[] = $this->_addError($rule, $options);
		return $this;
	}

	public function checkCreate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_createRules) as $rule) {
			$success = $rule($entity, $this->_options) && $success;
		}
		return $success;
	}

	public function checkUpdate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_updateRules) as $rule) {
			$success = $rule($entity, $this->_options) && $success;
		}
		return $success;
	}

	public function isUnique(array $fields, $message = 'This value is already in use') {
		$errorField = current($fields);
		return $this->_addError(new IsUnique($fields), compact('errorField', 'message'));
	}

	public function existsIn($field, $table, $message = 'This value does not exist') {
		$errorField = $field;
		return $this->_addError(new ExistsIn($field, $table), compact('errorField', 'message'));
	}

	protected function _addError($rule, $options) {
		return function ($entity, $scope) use ($rule, $options) {
			$pass = $rule($entity, $options + $scope);

			if ($pass || empty($options['errorField'])) {
				return $pass;
			}
			
			$message = isset($options['message']) ? $options['message'] : 'invalid';
			$entity->errors($options['errorField'], (array)$message);
			return $pass;
		};
	}

}
