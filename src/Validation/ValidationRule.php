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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

use InvalidArgumentException;

/**
 * ValidationRule object. Represents a validation method, error message and
 * rules for applying such method to a field.
 */
class ValidationRule
{

    /**
     * The method to be called for a given scope
     *
     * @var string|callable
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
     * @var bool
     */
    protected $_last = false;

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
    protected $_provider = 'default';

    /**
     * Extra arguments to be passed to the validation method
     *
     * @var array
     */
    protected $_pass = [];

    /**
     * Constructor
     *
     * @param array $validator [optional] The validator properties
     */
    public function __construct(array $validator = [])
    {
        $this->_addValidatorProps($validator);
    }

    /**
     * Returns whether this rule should break validation process for associated field
     * after it fails
     *
     * @return bool
     */
    public function isLast()
    {
        return (bool)$this->_last;
    }

    /**
     * Dispatches the validation rule to the given validator method and returns
     * a boolean indicating whether the rule passed or not. If a string is returned
     * it is assumed that the rule failed and the error message was given as a result.
     *
     * @param mixed $value The data to validate
     * @param array $providers associative array with objects or class names that will
     * be passed as the last argument for the validation method
     * @param array $context A key value list of data that could be used as context
     * during validation. Recognized keys are:
     * - newRecord: (boolean) whether or not the data to be validated belongs to a
     *   new record
     * - data: The full data that was passed to the validation process
     * - field: The name of the field that is being processed
     * @return bool|string
     * @throws \InvalidArgumentException when the supplied rule is not a valid
     * callable for the configured scope
     */
    public function process($value, array $providers, array $context = [])
    {
        $context += ['data' => [], 'newRecord' => true, 'providers' => $providers];

        if ($this->_skip($context)) {
            return true;
        }

        if (!is_string($this->_rule) && is_callable($this->_rule)) {
            $callable = $this->_rule;
            $isCallable = true;
        } else {
            $provider = $providers[$this->_provider];
            $callable = [$provider, $this->_rule];
            $isCallable = is_callable($callable);
        }

        if (!$isCallable) {
            $message = 'Unable to call method "%s" in "%s" provider for field "%s"';
            throw new InvalidArgumentException(
                sprintf($message, $this->_rule, $this->_provider, $context['field'])
            );
        }

        if ($this->_pass) {
            $args = array_merge([$value], $this->_pass, [$context]);
            $result = $callable(...$args);
        } else {
            $result = $callable($value, $context);
        }

        if ($result === false) {
            return $this->_message ?: false;
        }

        return $result;
    }

    /**
     * Checks if the validation rule should be skipped
     *
     * @param array $context A key value list of data that could be used as context
     * during validation. Recognized keys are:
     * - newRecord: (boolean) whether or not the data to be validated belongs to a
     *   new record
     * - data: The full data that was passed to the validation process
     * - providers associative array with objects or class names that will
     *   be passed as the last argument for the validation method
     * @return bool True if the ValidationRule should be skipped
     */
    protected function _skip($context)
    {
        if (!is_string($this->_on) && is_callable($this->_on)) {
            $function = $this->_on;

            return !$function($context);
        }

        $newRecord = $context['newRecord'];
        if (!empty($this->_on)) {
            if ($this->_on === 'create' && !$newRecord || $this->_on === 'update' && $newRecord) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the rule properties from the rule entry in validate
     *
     * @param array $validator [optional]
     * @return void
     */
    protected function _addValidatorProps($validator = [])
    {
        foreach ($validator as $key => $value) {
            if (!isset($value) || empty($value)) {
                continue;
            }
            if ($key === 'rule' && is_array($value) && !is_callable($value)) {
                $this->_pass = array_slice($value, 1);
                $value = array_shift($value);
            }
            if (in_array($key, ['rule', 'on', 'message', 'last', 'provider', 'pass'])) {
                $this->{"_$key"} = $value;
            }
        }
    }

    /**
     * Returns the value of a property by name
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed
     */
    public function get($property)
    {
        $property = '_' . $property;
        if (isset($this->{$property})) {
            return $this->{$property};
        }
    }
}
