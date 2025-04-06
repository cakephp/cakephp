<?php
declare(strict_types=1);

/**
 * ValidationRule.
 *
 * Provides the Model validation logic.
 *
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

use Cake\ORM\Table;
use Closure;
use ReflectionFunction;

/**
 * ValidationRule object. Represents a validation method, error message and
 * rules for applying such method to a field.
 */
class ValidationRule
{
    /**
     * The method to be called for a given scope
     *
     * @var callable|string
     */
    protected $_rule;

    /**
     * The 'on' key
     *
     * @var callable|string|null
     */
    protected $_on;

    /**
     * The 'last' key
     *
     * @var bool
     */
    protected bool $_last = false;

    /**
     * The 'message' key
     *
     * @var string|null
     */
    protected ?string $_message = null;

    /**
     * Key under which the object or class where the method to be used for
     * validation will be found
     *
     * @var string
     */
    protected string $_provider = 'default';

    /**
     * Extra arguments to be passed to the validation method
     *
     * @var array
     */
    protected array $_pass = [];

    /**
     * Constructor
     *
     * @param array<string, mixed> $validator The validator properties
     */
    public function __construct(array $validator)
    {
        $this->_addValidatorProps($validator);
    }

    /**
     * Returns whether this rule should break validation process for associated field
     * after it fails
     *
     * @return bool
     */
    public function isLast(): bool
    {
        return $this->_last;
    }

    /**
     * Dispatches the validation rule to the given validator method and returns
     * a boolean indicating whether the rule passed or not. If a string is returned
     * it is assumed that the rule failed and the error message was given as a result.
     *
     * @param mixed $value The data to validate
     * @param array<string, mixed> $providers Associative array with objects or class names that will
     * be passed as the last argument for the validation method
     * @param array<string, mixed> $context A key value list of data that could be used as context
     * during validation. Recognized keys are:
     * - newRecord: (boolean) whether the data to be validated belongs to a
     *   new record
     * - data: The full data that was passed to the validation process
     * - field: The name of the field that is being processed
     * @return array|string|bool
     * @throws \InvalidArgumentException when the supplied rule is not a valid
     * callable for the configured scope
     */
    public function process(mixed $value, array $providers, array $context = []): array|string|bool
    {
        $context += ['data' => [], 'newRecord' => true, 'providers' => $providers];

        if ($this->_skip($context)) {
            return true;
        }

        if (is_string($this->_rule)) {
            $provider = $providers[$this->_provider];
            if (
                class_exists(Table::class)
                && $provider instanceof Table
                && !method_exists($provider, $this->_rule)
                && $provider->behaviors()->hasMethod($this->_rule)
            ) {
                foreach ($provider->behaviors() as $behavior) {
                    if (in_array($this->_rule, $behavior->implementedMethods(), true)) {
                        $provider = $behavior;
                        break;
                    }
                }
            }

            /** @phpstan-ignore-next-line */
            $callable = [$provider, $this->_rule](...);
        } else {
            $callable = $this->_rule;
            if (!$callable instanceof Closure) {
                $callable = $callable(...);
            }
        }

        $args = [$value];

        if ($this->_pass) {
            $args = array_merge([$value], array_values($this->_pass));
        }

        $params = (new ReflectionFunction($callable))->getParameters();
        $lastParm = array_pop($params);
        if ($lastParm && $lastParm->getName() === 'context') {
            $args['context'] = $context;
        }

        $result = $callable(...$args);

        if ($result === false) {
            return $this->_message ?: false;
        }

        return $result;
    }

    /**
     * Checks if the validation rule should be skipped
     *
     * @param array<string, mixed> $context A key value list of data that could be used as context
     * during validation. Recognized keys are:
     * - newRecord: (boolean) whether the data to be validated belongs to a
     *   new record
     * - data: The full data that was passed to the validation process
     * - providers associative array with objects or class names that will
     *   be passed as the last argument for the validation method
     * @return bool True if the ValidationRule should be skipped
     */
    protected function _skip(array $context): bool
    {
        if (is_string($this->_on)) {
            $newRecord = $context['newRecord'];

            return ($this->_on === Validator::WHEN_CREATE && !$newRecord)
                || ($this->_on === Validator::WHEN_UPDATE && $newRecord);
        }

        if ($this->_on !== null) {
            $function = $this->_on;

            return !$function($context);
        }

        return false;
    }

    /**
     * Sets the rule properties from the rule entry in validate
     *
     * @param array<string, mixed> $validator [optional]
     * @return void
     */
    protected function _addValidatorProps(array $validator = []): void
    {
        foreach ($validator as $key => $value) {
            if (!$value) {
                continue;
            }
            if ($key === 'rule' && is_array($value) && !is_callable($value)) {
                $this->_pass = array_slice($value, 1);
                $value = array_shift($value);
            }
            if (in_array($key, ['rule', 'on', 'message', 'last', 'provider', 'pass'], true)) {
                $this->{"_{$key}"} = $value;
            }
        }
    }

    /**
     * Returns the value of a property by name
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed
     */
    public function get(string $property): mixed
    {
        $property = '_' . $property;

        return $this->{$property} ?? null;
    }
}
