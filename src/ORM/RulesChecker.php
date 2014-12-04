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

/**
 * The list of rules to be checked on every case
 *
 * @var array
 */
	protected $_rules = [];

/**
 * The list of rules to check during create operations
 *
 * @var array
 */
	protected $_createRules = [];

/**
 * The list of rules to check during update operations
 *
 * @var array
 */
	protected $_updateRules = [];

/**
 * List of options to pass to every callable rule
 *
 * @var array
 */
	protected $_options = [];

/**
 * Constructor. Takes the options to be passed to all rules.
 *
 * @param array $options The options to pass to every rule
 */
	public function __construct(array $options) {
		$this->_options = $options;
	}

/**
 * Adds a rule that will be applied to the entity both on create and update
 * operations.
 *
 * ### Options
 *
 * The options array accept the following special keys:
 *
 * - `errorField`: The name of the entity field that will be marked as invalid
 *    if the rule does not pass.
 * - `message`: The error message to set to `errorField` if the rule does not pass.
 *
 * @param callable $rule A callable function or object that will return whether
 * the entity is valid or not.
 * @param array $options List of extra options to pass to the rule callable as
 * second argument.
 * @return $this
 */
	public function add(callable $rule, array $options = []) {
		$this->_rules[] = $this->_addError($rule, $options);
		return $this;
	}

/**
 * Adds a rule that will be applied to the entity on create operations.
 *
 * ### Options
 *
 * The options array accept the following special keys:
 *
 * - `errorField`: The name of the entity field that will be marked as invalid
 *    if the rule does not pass.
 * - `message`: The error message to set to `errorField` if the rule does not pass.
 *
 * @param callable $rule A callable function or object that will return whether
 * the entity is valid or not.
 * @param array $options List of extra options to pass to the rule callable as
 * second argument.
 * @return $this
 */
	public function addCreate(callable $rule, array $options = []) {
		$this->_createRules[] = $this->_addError($rule, $options);
		return $this;
	}

/**
 * Adds a rule that will be applied to the entity on update operations.
 *
 * ### Options
 *
 * The options array accept the following special keys:
 *
 * - `errorField`: The name of the entity field that will be marked as invalid
 *    if the rule does not pass.
 * - `message`: The error message to set to `errorField` if the rule does not pass.
 *
 * @param callable $rule A callable function or object that will return whether
 * the entity is valid or not.
 * @param array $options List of extra options to pass to the rule callable as
 * second argument.
 * @return $this
 */
	public function addUpdate(callable $rule, array $options = []) {
		$this->_updateRules[] = $this->_addError($rule, $options);
		return $this;
	}

/**
 * Runs each of the rules by passing the provided entity and returns true if all
 * of them pass. The rules selected will be only those specified to be run on 'create'
 *
 * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
 * @return bool
 */
	public function checkCreate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_createRules) as $rule) {
			$success = $rule($entity, $this->_options) && $success;
		}
		return $success;
	}

/**
 * Runs each of the rules by passing the provided entity and returns true if all
 * of them pass. The rules selected will be only those specified to be run on 'update'
 *
 * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
 * @return bool
 */
	public function checkUpdate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_updateRules) as $rule) {
			$success = $rule($entity, $this->_options) && $success;
		}
		return $success;
	}

/**
 * Returns a callable that can be used as a rule for checking the uniqueness of a value
 * in the table.
 *
 * ### Example:
 *
 * {{{
 * $rules->add($rules->isUnique('email', 'The email should be unique'));
 * }}}
 *
 * @param array $fields The list of fields to check for uniqueness.
 * @param string $message The error message to show in case the rule does not pass.
 * @return callable
 */
	public function isUnique(array $fields, $message = 'This value is already in use') {
		$errorField = current($fields);
		return $this->_addError(new IsUnique($fields), compact('errorField', 'message'));
	}

/**
 * Returns a callable that can be used as a rule for checking that the values
 * extracted from the entity to check exist as the primary key in another table.
 *
 * This is useful for enforcing foreign key integrity checks.
 *
 * ### Example:
 *
 * {{{
 * $rules->add($rules->existsIn('author_id', 'Authors', 'Invalid Author'));
 *
 * $rules->add($rules->existsIn('site_id', new SitesTable(), 'Invalid Site'));
 * }}}
 *
 * @param string|array $fields The field or list of fields to check for existence by
 * primary key lookup in the other table.
 * @param string $message The error message to show in case the rule does not pass.
 * @return callable
 */
	public function existsIn($field, $table, $message = 'This value does not exist') {
		$errorField = $field;
		return $this->_addError(new ExistsIn($field, $table), compact('errorField', 'message'));
	}

/**
 * Utility method for decorating any callable so that if it returns false, the correct
 * property in the entity is marked as invalid.
 *
 * @param callable $rule The rule to decorate
 * @param array $options The options containing the error message and field
 * @return callable
 */
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
