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
 * @since         3.0.7
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use InvalidArgumentException;

/**
 * Contains logic for storing and checking rules on entities
 *
 * RulesCheckers are used by Table classes to ensure that the
 * current entity state satisfies the application logic and business rules.
 *
 * RulesCheckers afford different rules to be applied in the create and update
 * scenario.
 *
 * ### Adding rules
 *
 * Rules must be callable objects that return true/false depending on whether or
 * not the rule has been satisfied. You can use RulesChecker::add(), RulesChecker::addCreate(),
 * RulesChecker::addUpdate() and RulesChecker::addDelete to add rules to a checker.
 *
 * ### Running checks
 *
 * Generally a Table object will invoke the rules objects, but you can manually
 * invoke the checks by calling RulesChecker::checkCreate(), RulesChecker::checkUpdate() or
 * RulesChecker::checkDelete().
 */
class RulesChecker
{
    /**
     * Indicates that the checking rules to apply are those used for creating entities
     *
     * @var string
     */
    const CREATE = 'create';

    /**
     * Indicates that the checking rules to apply are those used for updating entities
     *
     * @var string
     */
    const UPDATE = 'update';

    /**
     * Indicates that the checking rules to apply are those used for deleting entities
     *
     * @var string
     */
    const DELETE = 'delete';

    /**
     * The list of rules to be checked on both create and update operations
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
     * The list of rules to check during delete operations
     *
     * @var array
     */
    protected $_deleteRules = [];

    /**
     * List of options to pass to every callable rule
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Whether or not to use I18n functions for translating default error messages
     *
     * @var bool
     */
    protected $_useI18n = false;

    /**
     * Constructor. Takes the options to be passed to all rules.
     *
     * @param array $options The options to pass to every rule
     */
    public function __construct(array $options = [])
    {
        $this->_options = $options;
        $this->_useI18n = function_exists('__d');
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
     * @param string $name The alias for a rule.
     * @param array $options List of extra options to pass to the rule callable as
     * second argument.
     * @return $this
     */
    public function add(callable $rule, $name = null, array $options = [])
    {
        $this->_rules[] = $this->_addError($rule, $name, $options);
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
     * @param string $name The alias for a rule.
     * @param array $options List of extra options to pass to the rule callable as
     * second argument.
     * @return $this
     */
    public function addCreate(callable $rule, $name = null, array $options = [])
    {
        $this->_createRules[] = $this->_addError($rule, $name, $options);
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
     * @param string $name The alias for a rule.
     * @param array $options List of extra options to pass to the rule callable as
     * second argument.
     * @return $this
     */
    public function addUpdate(callable $rule, $name = null, array $options = [])
    {
        $this->_updateRules[] = $this->_addError($rule, $name, $options);
        return $this;
    }

    /**
     * Adds a rule that will be applied to the entity on delete operations.
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
     * @param string $name The alias for a rule.
     * @param array $options List of extra options to pass to the rule callable as
     * second argument.
     * @return $this
     */
    public function addDelete(callable $rule, $name = null, array $options = [])
    {
        $this->_deleteRules[] = $this->_addError($rule, $name, $options);
        return $this;
    }

    /**
     * Runs each of the rules by passing the provided entity and returns true if all
     * of them pass. The rules to be applied are depended on the $mode parameter which
     * can only be RulesChecker::CREATE, RulesChecker::UPDATE or RulesChecker::DELETE
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
     * @param string $mode Either 'create, 'update' or 'delete'.
     * @param array $options Extra options to pass to checker functions.
     * @return bool
     * @throws \InvalidArgumentException if an invalid mode is passed.
     */
    public function check(EntityInterface $entity, $mode, array $options = [])
    {
        if ($mode === self::CREATE) {
            return $this->checkCreate($entity, $options);
        }

        if ($mode === self::UPDATE) {
            return $this->checkUpdate($entity, $options);
        }

        if ($mode === self::DELETE) {
            return $this->checkDelete($entity, $options);
        }

        throw new InvalidArgumentException('Wrong checking mode: ' . $mode);
    }

    /**
     * Runs each of the rules by passing the provided entity and returns true if all
     * of them pass. The rules selected will be only those specified to be run on 'create'
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
     * @param array $options Extra options to pass to checker functions.
     * @return bool
     */
    public function checkCreate(EntityInterface $entity, array $options = [])
    {
        return $this->_checkRules($entity, $options, array_merge($this->_rules, $this->_createRules));
    }

    /**
     * Runs each of the rules by passing the provided entity and returns true if all
     * of them pass. The rules selected will be only those specified to be run on 'update'
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
     * @param array $options Extra options to pass to checker functions.
     * @return bool
     */
    public function checkUpdate(EntityInterface $entity, array $options = [])
    {
        return $this->_checkRules($entity, $options, array_merge($this->_rules, $this->_updateRules));
    }

    /**
     * Runs each of the rules by passing the provided entity and returns true if all
     * of them pass. The rules selected will be only those specified to be run on 'delete'
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
     * @param array $options Extra options to pass to checker functions.
     * @return bool
     */
    public function checkDelete(EntityInterface $entity, array $options = [])
    {
        return $this->_checkRules($entity, $options, $this->_deleteRules);
    }

    /**
     * Used by top level functions checkDelete, checkCreate and checkUpdate, this function
     * iterates an array containing the rules to be checked and checks them all.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check for validity.
     * @param array $options Extra options to pass to checker functions.
     * @param array $rules The list of rules that must be checked.
     * @return bool
     */
    protected function _checkRules(EntityInterface $entity, array $options = [], array $rules = [])
    {
        $success = true;
        $options = $options + $this->_options;
        foreach ($rules as $rule) {
            $success = $rule($entity, $options) && $success;
        }
        return $success;
    }

    /**
     * Utility method for decorating any callable so that if it returns false, the correct
     * property in the entity is marked as invalid.
     *
     * @param callable $rule The rule to decorate
     * @param string $name The alias for a rule.
     * @param array $options The options containing the error message and field.
     * @return callable
     */
    protected function _addError($rule, $name, $options)
    {
        if (is_array($name)) {
            $options = $name;
            $name = null;
        }

        return function ($entity, $scope) use ($rule, $name, $options) {
            $pass = $rule($entity, $options + $scope);
            if ($pass === true || empty($options['errorField'])) {
                return $pass === true;
            }

            $message = 'invalid';
            if (isset($options['message'])) {
                $message = $options['message'];
            }
            if (is_string($pass)) {
                $message = $pass;
            }
            if ($name) {
                $message = [$name => $message];
            } else {
                $message = [$message];
            }
            $entity->errors($options['errorField'], $message);
            return $pass === true;
        };
    }
}
