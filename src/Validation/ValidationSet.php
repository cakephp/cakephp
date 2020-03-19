<?php
declare(strict_types=1);

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
use Traversable;

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
     * Returns whether or not a field can be left out.
     *
     * @return bool|string|callable
     */
    public function isPresenceRequired()
    {
        return $this->_validatePresent;
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
     * Returns whether or not a field can be left empty.
     *
     * @return bool|string|callable
     */
    public function isEmptyAllowed()
    {
        return $this->_allowEmpty;
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
    public function rule(string $name): ?ValidationRule
    {
        if (!empty($this->_rules[$name])) {
            return $this->_rules[$name];
        }

        return null;
    }

    /**
     * Returns all rules for this validation set
     *
     * @return \Cake\Validation\ValidationRule[]
     */
    public function rules(): array
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
    public function add(string $name, $rule)
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
    public function remove(string $name)
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
    public function offsetExists($index): bool
    {
        return isset($this->_rules[$index]);
    }

    /**
     * Returns a rule object by its index
     *
     * @param string $index name of the rule
     * @return \Cake\Validation\ValidationRule
     */
    public function offsetGet($index): ValidationRule
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
    public function offsetSet($index, $rule): void
    {
        $this->add($index, $rule);
    }

    /**
     * Unsets a validation rule
     *
     * @param string $index name of the rule
     * @return void
     */
    public function offsetUnset($index): void
    {
        unset($this->_rules[$index]);
    }

    /**
     * Returns an iterator for each of the rules to be applied
     *
     * @return \Cake\Validation\ValidationRule[]
     * @psalm-return \Traversable<string, \Cake\Validation\ValidationRule>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_rules);
    }

    /**
     * Returns the number of rules in this set
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->_rules);
    }
}
