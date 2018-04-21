<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * ValidationSet object. Holds all validation rules for a field and exposes
 * methods to dynamically add or remove validation rules
 */
class ValidationSet implements ArrayAccess, IteratorAggregate, Countable
{

    /**
     * Holds the ValidationRule objects
     *
     * @var \Cake\Validation\ValidationRule[]
     */
    protected $_rules = [];

    /**
     * Denotes whether the fieldname key must be present in data array
     *
     * @var bool|string|callable
     */
    protected $_validatePresent = false;

    /**
     * Denotes if a field is allowed to be empty
     *
     * @var bool|string|callable
     */
    protected $_allowEmpty = false;

    /**
     * Sets whether a field is required to be present in data array.
     *
     * If no argument is passed the currently set `validatePresent` value will be returned.
     *
     * @param bool|string|callable|null $validatePresent Deprecated since 3.6.0 ValidationSet::isPresenceRequired() is deprecated as a setter
     * Use ValidationSet::requirePresence() instead.
     * @return bool|string|callable
     */
    public function isPresenceRequired($validatePresent = null)
    {
        if ($validatePresent === null) {
            return $this->_validatePresent;
        }

        deprecationWarning(
            'ValidationSet::isPresenceRequired() is deprecated as a setter. ' .
            'Use ValidationSet::requirePresence() instead.'
        );

        return $this->requirePresence($validatePresent);
    }

    /**
     * Sets whether a field is required to be present in data array.
     *
     * @param bool|string|callable $validatePresent Valid values are true, false, 'create', 'update' or a callable.
     * @return $this
     */
    public function requirePresence($validatePresent)
    {
        $this->_validatePresent = $validatePresent;

        return $this;
    }

    /**
     * Sets whether a field value is allowed to be empty.
     *
     * If no argument is passed the currently set `allowEmpty` value will be returned.
     *
     * @param bool|string|callable|null $allowEmpty Deprecated since 3.6.0 ValidationSet::isEmptyAllowed() is deprecated as a setter.
     * Use ValidationSet::allowEmpty() instead.
     * @return bool|string|callable
     */
    public function isEmptyAllowed($allowEmpty = null)
    {
        if ($allowEmpty === null) {
            return $this->_allowEmpty;
        }

        deprecationWarning(
            'ValidationSet::isEmptyAllowed() is deprecated as a setter. ' .
            'Use ValidationSet::allowEmpty() instead.'
        );

        return $this->allowEmpty($allowEmpty);
    }

    /**
     * Sets whether a field value is allowed to be empty.
     *
     * @param bool|string|callable $allowEmpty Valid values are true, false,
     * 'create', 'update' or a callable.
     * @return $this
     */
    public function allowEmpty($allowEmpty)
    {
        $this->_allowEmpty = $allowEmpty;

        return $this;
    }

    /**
     * Gets a rule for a given name if exists
     *
     * @param string $name The name under which the rule is set.
     * @return \Cake\Validation\ValidationRule|null
     */
    public function rule($name)
    {
        if (!empty($this->_rules[$name])) {
            return $this->_rules[$name];
        }
    }

    /**
     * Returns all rules for this validation set
     *
     * @return \Cake\Validation\ValidationRule[]
     */
    public function rules()
    {
        return $this->_rules;
    }

    /**
     * Sets a ValidationRule $rule with a $name
     *
     * ### Example:
     *
     * ```
     *      $set
     *          ->add('notBlank', ['rule' => 'notBlank'])
     *          ->add('inRange', ['rule' => ['between', 4, 10])
     * ```
     *
     * @param string $name The name under which the rule should be set
     * @param \Cake\Validation\ValidationRule|array $rule The validation rule to be set
     * @return $this
     */
    public function add($name, $rule)
    {
        if (!($rule instanceof ValidationRule)) {
            $rule = new ValidationRule($rule);
        }
        $this->_rules[$name] = $rule;

        return $this;
    }

    /**
     * Removes a validation rule from the set
     *
     * ### Example:
     *
     * ```
     *      $set
     *          ->remove('notBlank')
     *          ->remove('inRange')
     * ```
     *
     * @param string $name The name under which the rule should be unset
     * @return $this
     */
    public function remove($name)
    {
        unset($this->_rules[$name]);

        return $this;
    }

    /**
     * Returns whether an index exists in the rule set
     *
     * @param string $index name of the rule
     * @return bool
     */
    public function offsetExists($index)
    {
        return isset($this->_rules[$index]);
    }

    /**
     * Returns a rule object by its index
     *
     * @param string $index name of the rule
     * @return \Cake\Validation\ValidationRule
     */
    public function offsetGet($index)
    {
        return $this->_rules[$index];
    }

    /**
     * Sets or replace a validation rule
     *
     * @param string $index name of the rule
     * @param \Cake\Validation\ValidationRule|array $rule Rule to add to $index
     * @return void
     */
    public function offsetSet($index, $rule)
    {
        $this->add($index, $rule);
    }

    /**
     * Unsets a validation rule
     *
     * @param string $index name of the rule
     * @return void
     */
    public function offsetUnset($index)
    {
        unset($this->_rules[$index]);
    }

    /**
     * Returns an iterator for each of the rules to be applied
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_rules);
    }

    /**
     * Returns the number of rules in this set
     *
     * @return int
     */
    public function count()
    {
        return count($this->_rules);
    }
}
