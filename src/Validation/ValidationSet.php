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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * @var array
     */
    protected $_rules = [];

    /**
     * Denotes whether the fieldname key must be present in data array
     *
     * @var bool|string
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
     * @param bool|string|null $validatePresent Valid values are true, false, 'create', 'update'
     * @return bool|string
     */
    public function isPresenceRequired($validatePresent = null)
    {
        if ($validatePresent === null) {
            return $this->_validatePresent;
        }

        return $this->_validatePresent = $validatePresent;
    }

    /**
     * Sets whether a field value is allowed to be empty
     *
     * If no argument is passed the currently set `allowEmpty` value will be returned.
     *
     * @param bool|string|callable|null $allowEmpty Valid values are true, false,
     * 'create', 'update'
     * @return bool|string|callable
     */
    public function isEmptyAllowed($allowEmpty = null)
    {
        if ($allowEmpty === null) {
            return $this->_allowEmpty;
        }

        return $this->_allowEmpty = $allowEmpty;
    }

    /**
     * Gets a rule for a given name if exists
     *
     * @param string $name The name under which the rule is set.
     * @return \Cake\Validation\ValidationRule
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
     * @return array
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
