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
use Cake\ORM\Rule\ExistsIn;
use Cake\ORM\Rule\IsUnique;
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
    public function __construct(array $options)
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
        $success = true;
        $options = $options + $this->_options;
        foreach (array_merge($this->_rules, $this->_createRules) as $rule) {
            $success = $rule($entity, $options) && $success;
        }
        return $success;
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
        $success = true;
        $options = $options + $this->_options;
        foreach (array_merge($this->_rules, $this->_updateRules) as $rule) {
            $success = $rule($entity, $options) && $success;
        }
        return $success;
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
        $success = true;
        $options = $options + $this->_options;
        foreach ($this->_deleteRules as $rule) {
            $success = $rule($entity, $options) && $success;
        }
        return $success;
    }

    /**
     * Returns a callable that can be used as a rule for checking the uniqueness of a value
     * in the table.
     *
     * ### Example:
     *
     * ```
     * $rules->add($rules->isUnique(['email'], 'The email should be unique'));
     * ```
     *
     * @param array $fields The list of fields to check for uniqueness.
     * @param string $message The error message to show in case the rule does not pass.
     * @return callable
     */
    public function isUnique(array $fields, $message = null)
    {
        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'This value is already in use');
            } else {
                $message = 'This value is already in use';
            }
        }

        $errorField = current($fields);
        return $this->_addError(new IsUnique($fields), '_isUnique', compact('errorField', 'message'));
    }

    /**
     * Returns a callable that can be used as a rule for checking that the values
     * extracted from the entity to check exist as the primary key in another table.
     *
     * This is useful for enforcing foreign key integrity checks.
     *
     * ### Example:
     *
     * ```
     * $rules->add($rules->existsIn('author_id', 'Authors', 'Invalid Author'));
     *
     * $rules->add($rules->existsIn('site_id', new SitesTable(), 'Invalid Site'));
     * ```
     *
     * @param string|array $field The field or list of fields to check for existence by
     * primary key lookup in the other table.
     * @param object|string $table The table name where the fields existence will be checked.
     * @param string $message The error message to show in case the rule does not pass.
     * @return callable
     */
    public function existsIn($field, $table, $message = null)
    {
        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'This value does not exist');
            } else {
                $message = 'This value does not exist';
            }
        }

        $errorField = $field;
        return $this->_addError(new ExistsIn($field, $table), '_existsIn', compact('errorField', 'message'));
    }

    /**
     * Utility method for decorating any callable so that if it returns false, the correct
     * property in the entity is marked as invalid.
     *
     * @param callable $rule The rule to decorate
     * @param string $name The alias for a rule.
     * @param array $options The options containing the error message and field
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

            if ($pass || empty($options['errorField'])) {
                return $pass;
            }

            $message = isset($options['message']) ? $options['message'] : 'invalid';
            if ($name) {
                $message = [$name => $message];
            } else {
                $message = (array)$message;
            }
            $entity->errors($options['errorField'], $message);
            return $pass;
        };
    }
}
