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
use Cake\Core\Exception\CakeException;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * ValidationSet object. Holds all validation rules for a field and exposes
 * methods to dynamically add or remove validation rules
 *
 * @template-implements \ArrayAccess<string, \Cake\Validation\ValidationRule>
 * @template-implements \IteratorAggregate<string, \Cake\Validation\ValidationRule>
 */
class ValidationSet implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Holds the ValidationRule objects
     *
     * @var array<\Cake\Validation\ValidationRule>
     */
    protected array $rules = [];

    /**
     * Denotes whether the field name key must be present in data array
     *
     * @var callable|string|bool
     */
    protected $validatePresent = false;

    /**
     * Denotes if a field is allowed to be empty
     *
     * @var callable|string|bool
     */
    protected $allowEmpty = false;

    /**
     * Returns whether a field can be left out.
     *
     * @return callable|string|bool
     */
    public function isPresenceRequired(): callable|string|bool
    {
        return $this->validatePresent;
    }

    /**
     * Sets whether a field is required to be present in data array.
     *
     * @param callable|string|bool $validatePresent Valid values are true, false, 'create', 'update' or a callable.
     * @return $this
     */
    public function requirePresence(callable|string|bool $validatePresent): static
    {
        $this->validatePresent = $validatePresent;

        return $this;
    }

    /**
     * Returns whether a field can be left empty.
     *
     * @return callable|string|bool
     */
    public function isEmptyAllowed(): callable|string|bool
    {
        return $this->allowEmpty;
    }

    /**
     * Sets whether a field value is allowed to be empty.
     *
     * @param callable|string|bool $allowEmpty Valid values are true, false,
     * 'create', 'update' or a callable.
     * @return $this
     */
    public function allowEmpty(callable|string|bool $allowEmpty): static
    {
        $this->allowEmpty = $allowEmpty;

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
        if (empty($this->rules[$name])) {
            return null;
        }

        return $this->rules[$name];
    }

    /**
     * Returns all rules for this validation set
     *
     * @return array<\Cake\Validation\ValidationRule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Returns whether a validation rule with the given name exists in this set.
     *
     * @param string $name The name to check
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->rules);
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
     * @throws \Cake\Core\Exception\CakeException If a rule with the same name already exists
     */
    public function add(string $name, ValidationRule|array $rule): static
    {
        if (!($rule instanceof ValidationRule)) {
            $rule += ['name' => $name];
            $rule = new ValidationRule(...$rule);
        }
        if (array_key_exists($name, $this->rules)) {
            throw new CakeException("A validation rule with the name `{$name}` already exists");
        }
        $this->rules[$name] = $rule;

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
    public function remove(string $name): static
    {
        unset($this->rules[$name]);

        return $this;
    }

    /**
     * Returns whether an index exists in the rule set
     *
     * @param string $index name of the rule
     * @return bool
     */
    public function offsetExists(mixed $index): bool
    {
        return isset($this->rules[$index]);
    }

    /**
     * Returns a rule object by its index
     *
     * @param string $index name of the rule
     * @return \Cake\Validation\ValidationRule
     */
    public function offsetGet(mixed $index): ValidationRule
    {
        return $this->rules[$index];
    }

    /**
     * Sets or replace a validation rule
     *
     * @param string $offset name of the rule
     * @param \Cake\Validation\ValidationRule|array $value Rule to add to $index
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->add($offset, $value);
    }

    /**
     * Unsets a validation rule
     *
     * @param string $index name of the rule
     * @return void
     */
    public function offsetUnset(mixed $index): void
    {
        unset($this->rules[$index]);
    }

    /**
     * Returns an iterator for each of the rules to be applied
     *
     * @return \Traversable<string, \Cake\Validation\ValidationRule>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rules);
    }

    /**
     * Returns the number of rules in this set
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->rules);
    }
}
