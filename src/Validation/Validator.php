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
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Validator object encapsulates all methods related to data validations for a model
 * It also provides an API to dynamically change validation rules for each model field.
 *
 * Implements ArrayAccess to easily modify rules in the set
 *
 * @link http://book.cakephp.org/3.0/en/core-libraries/validation.html
 */
class Validator implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Used to flag nested rules created with addNested() and addNestedMany()
     *
     * @var string
     */
    const NESTED = '_nested';

    /**
     * Holds the ValidationSet objects array
     *
     * @var array
     */
    protected $_fields = [];

    /**
     * An associative array of objects or classes containing methods
     * used for validation
     *
     * @var array
     */
    protected $_providers = [];

    /**
     * Contains the validation messages associated with checking the presence
     * for each corresponding field.
     *
     * @var array
     */
    protected $_presenceMessages = [];

    /**
     * Whether or not to use I18n functions for translating default error messages
     *
     * @var bool
     */
    protected $_useI18n = false;

    /**
     * Contains the validation messages associated with checking the emptiness
     * for each corresponding field.
     *
     * @var array
     */
    protected $_allowEmptyMessages = [];

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->_useI18n = function_exists('__d');
    }

    /**
     * Returns an array of fields that have failed validation. On the current model. This method will
     * actually run validation rules over data, not just return the messages.
     *
     * @param array $data The data to be checked for errors
     * @param bool $newRecord whether the data to be validated is new or to be updated.
     * @return array Array of invalid fields
     */
    public function errors(array $data, $newRecord = true)
    {
        $errors = [];

        $requiredMessage = 'This field is required';
        $emptyMessage = 'This field cannot be left empty';

        if ($this->_useI18n) {
            $requiredMessage = __d('cake', 'This field is required');
            $emptyMessage = __d('cake', 'This field cannot be left empty');
        }

        foreach ($this->_fields as $name => $field) {
            $keyPresent = array_key_exists($name, $data);

            $providers = $this->_providers;
            $context = compact('data', 'newRecord', 'field', 'providers');

            if (!$keyPresent && !$this->_checkPresence($field, $context)) {
                $errors[$name]['_required'] = isset($this->_presenceMessages[$name])
                    ? $this->_presenceMessages[$name]
                    : $requiredMessage;
                continue;
            }
            if (!$keyPresent) {
                continue;
            }

            $canBeEmpty = $this->_canBeEmpty($field, $context);
            $isEmpty = $this->_fieldIsEmpty($data[$name]);

            if (!$canBeEmpty && $isEmpty) {
                $errors[$name]['_empty'] = isset($this->_allowEmptyMessages[$name])
                    ? $this->_allowEmptyMessages[$name]
                    : $emptyMessage;
                continue;
            }

            if ($isEmpty) {
                continue;
            }

            $result = $this->_processRules($name, $field, $data, $newRecord);
            if ($result) {
                $errors[$name] = $result;
            }
        }

        return $errors;
    }

    /**
     * Returns a ValidationSet object containing all validation rules for a field, if
     * passed a ValidationSet as second argument, it will replace any other rule set defined
     * before
     *
     * @param string $name [optional] The fieldname to fetch.
     * @param \Cake\Validation\ValidationSet|null $set The set of rules for field
     * @return \Cake\Validation\ValidationSet
     */
    public function field($name, ValidationSet $set = null)
    {
        if (empty($this->_fields[$name])) {
            $set = $set ?: new ValidationSet;
            $this->_fields[$name] = $set;
        }
        return $this->_fields[$name];
    }

    /**
     * Check whether or not a validator contains any rules for the given field.
     *
     * @param string $name The field name to check.
     * @return bool
     */
    public function hasField($name)
    {
        return isset($this->_fields[$name]);
    }

    /**
     * Associates an object to a name so it can be used as a provider. Providers are
     * objects or class names that can contain methods used during validation of for
     * deciding whether a validation rule can be applied. All validation methods,
     * when called will receive the full list of providers stored in this validator.
     *
     * If called with no arguments, it will return the provider stored under that name if
     * it exists, otherwise it returns this instance of chaining.
     *
     * @param string $name The name under which the provider should be set.
     * @param null|object|string $object Provider object or class name.
     * @return $this|object|string|null
     */
    public function provider($name, $object = null)
    {
        if ($object === null) {
            if (isset($this->_providers[$name])) {
                return $this->_providers[$name];
            }
            if ($name === 'default') {
                return $this->_providers[$name] = new RulesProvider;
            }
            return null;
        }
        $this->_providers[$name] = $object;
        return $this;
    }

    /**
     * Get the list of providers in this validator.
     *
     * @return array
     */
    public function providers()
    {
        return array_keys($this->_providers);
    }

    /**
     * Returns whether a rule set is defined for a field or not
     *
     * @param string $field name of the field to check
     * @return bool
     */
    public function offsetExists($field)
    {
        return isset($this->_fields[$field]);
    }

    /**
     * Returns the rule set for a field
     *
     * @param string $field name of the field to check
     * @return \Cake\Validation\ValidationSet
     */
    public function offsetGet($field)
    {
        return $this->field($field);
    }

    /**
     * Sets the rule set for a field
     *
     * @param string $field name of the field to set
     * @param array|\Cake\Validation\ValidationSet $rules set of rules to apply to field
     * @return void
     */
    public function offsetSet($field, $rules)
    {
        if (!$rules instanceof ValidationSet) {
            $set = new ValidationSet;
            foreach ((array)$rules as $name => $rule) {
                $set->add($name, $rule);
            }
        }
        $this->_fields[$field] = $rules;
    }

    /**
     * Unsets the rule set for a field
     *
     * @param string $field name of the field to unset
     * @return void
     */
    public function offsetUnset($field)
    {
        unset($this->_fields[$field]);
    }

    /**
     * Returns an iterator for each of the fields to be validated
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_fields);
    }

    /**
     * Returns the number of fields having validation rules
     *
     * @return int
     */
    public function count()
    {
        return count($this->_fields);
    }

    /**
     * Adds a new rule to a field's rule set. If second argument is an array
     * then rules list for the field will be replaced with second argument and
     * third argument will be ignored.
     *
     * ### Example:
     *
     * ```
     *      $validator
     *          ->add('title', 'required', ['rule' => 'notBlank'])
     *          ->add('user_id', 'valid', ['rule' => 'numeric', 'message' => 'Invalid User'])
     *
     *      $validator->add('password', [
     *          'size' => ['rule' => ['lengthBetween', 8, 20]],
     *          'hasSpecialCharacter' => ['rule' => 'validateSpecialchar', 'message' => 'not valid']
     *      ]);
     * ```
     *
     * @param string $field The name of the field from which the rule will be removed
     * @param array|string $name The alias for a single rule or multiple rules array
     * @param array|\Cake\Validation\ValidationRule $rule the rule to add
     * @return $this
     */
    public function add($field, $name, $rule = [])
    {
        $field = $this->field($field);

        if (!is_array($name)) {
            $rules = [$name => $rule];
        } else {
            $rules = $name;
        }

        foreach ($rules as $name => $rule) {
            $field->add($name, $rule);
        }

        return $this;
    }

    /**
     * Adds a nested validator.
     *
     * Nesting validators allows you to define validators for array
     * types. For example, nested validators are ideal when you want to validate a
     * sub-document, or complex array type.
     *
     * This method assumes that the sub-document has a 1:1 relationship with the parent.
     *
     * The providers of the parent validator will be synced into the nested validator, when
     * errors are checked. This ensures that any validation rule providers connected
     * in the parent will have the same values in the nested validator when rules are evaluated.
     *
     * @param string $field The root field for the nested validator.
     * @param \Cake\Validation\Validator $validator The nested validator.
     * @return $this
     */
    public function addNested($field, Validator $validator)
    {
        $field = $this->field($field);
        $field->add(static::NESTED, ['rule' => function ($value, $context) use ($validator) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($this->providers() as $provider) {
                $validator->provider($provider, $this->provider($provider));
            }
            $errors = $validator->errors($value, $context['newRecord']);
            return empty($errors) ? true : $errors;
        }]);
        return $this;
    }

    /**
     * Adds a nested validator.
     *
     * Nesting validators allows you to define validators for array
     * types. For example, nested validators are ideal when you want to validate many
     * similar sub-documents or complex array types.
     *
     * This method assumes that the sub-document has a 1:N relationship with the parent.
     *
     * The providers of the parent validator will be synced into the nested validator, when
     * errors are checked. This ensures that any validation rule providers connected
     * in the parent will have the same values in the nested validator when rules are evaluated.
     *
     * @param string $field The root field for the nested validator.
     * @param \Cake\Validation\Validator $validator The nested validator.
     * @return $this
     */
    public function addNestedMany($field, Validator $validator)
    {
        $field = $this->field($field);
        $field->add(static::NESTED, ['rule' => function ($value, $context) use ($validator) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($this->providers() as $provider) {
                $validator->provider($provider, $this->provider($provider));
            }
            $errors = [];
            foreach ($value as $i => $row) {
                if (!is_array($row)) {
                    return false;
                }
                $check = $validator->errors($row, $context['newRecord']);
                if (!empty($check)) {
                    $errors[$i] = $check;
                }
            }
            return empty($errors) ? true : $errors;
        }]);
        return $this;
    }

    /**
     * Removes a rule from the set by its name
     *
     * ### Example:
     *
     * ```
     *      $validator
     *          ->remove('title', 'required')
     *          ->remove('user_id')
     * ```
     *
     * @param string $field The name of the field from which the rule will be removed
     * @param string|null $rule the name of the rule to be removed
     * @return $this
     */
    public function remove($field, $rule = null)
    {
        if ($rule === null) {
            unset($this->_fields[$field]);
        } else {
            $this->field($field)->remove($rule);
        }
        return $this;
    }

    /**
     * Sets whether a field is required to be present in data array.
     * You can also pass array. Using an array will let you provide  the following
     * keys:
     *
     * - `mode` individual mode for field
     * - `message` individual error message for field
     *
     * You can also set mode and message for all passed fields, the individual
     * setting takes precedence over group settings.
     *
     * @param string|array $field the name of the field or list of fields.
     * @param bool|string|callable $mode Valid values are true, false, 'create', 'update'.
     *   If a callable is passed then the field will be required only when the callback
     *   returns true.
     * @param string|null $message The message to show if the field presence validation fails.
     * @return $this
     */
    public function requirePresence($field, $mode = true, $message = null)
    {
        $defaults = [
            'mode' => $mode,
            'message' => $message
        ];

        if (!is_array($field)) {
            $field = $this->_convertValidatorToArray($field, $defaults);
        }

        foreach ($field as $fieldName => $setting) {
            $settings = $this->_convertValidatorToArray($fieldName, $defaults, $setting);
            $fieldName = current(array_keys($settings));

            $this->field($fieldName)->isPresenceRequired($settings[$fieldName]['mode']);
            if ($settings[$fieldName]['message']) {
                $this->_presenceMessages[$fieldName] = $settings[$fieldName]['message'];
            }
        }
        return $this;
    }

    /**
     * Allows a field to be empty. You can also pass array.
     * Using an array will let you provide the following keys:
     *
     * - `when` individual when condition for field
     * - 'message' individual message for field
     *
     * You can also set when and message for all passed fields, the individual setting
     * takes precedence over group settings.
     *
     * This is the opposite of notEmpty() which requires a field to not be empty.
     * By using $mode equal to 'create' or 'update', you can allow fields to be empty
     * when records are first created, or when they are updated.
     *
     * ### Example:
     *
     * ```
     * // Email can be empty
     * $validator->allowEmpty('email');
     *
     * // Email can be empty on create
     * $validator->allowEmpty('email', 'create');
     *
     * // Email can be empty on update
     * $validator->allowEmpty('email', 'update');
     *
     * // Email and subject can be empty on update
     * $validator->allowEmpty(['email', 'subject'], 'update');
     *
     * // Email can be always empty, subject and content can be empty on update.
     * $validator->allowEmpty(
     *      [
     *          'email' => [
     *              'when' => true
     *          ],
     *          'content' => [
     *              'message' => 'Content cannot be empty'
     *          ],
     *          'subject'
     *      ],
     *      'update'
     * );
     * ```
     *
     * It is possible to conditionally allow emptiness on a field by passing a callback
     * as a second argument. The callback will receive the validation context array as
     * argument:
     *
     * ```
     * $validator->allowEmpty('email', function ($context) {
     *  return !$context['newRecord'] || $context['data']['role'] === 'admin';
     * });
     * ```
     *
     * This method will correctly detect empty file uploads and date/time/datetime fields.
     *
     * Because this and `notEmpty()` modify the same internal state, the last
     * method called will take precedence.
     *
     * @param string|array $field the name of the field or a list of fields
     * @param bool|string|callable $when Indicates when the field is allowed to be empty
     * Valid values are true (always), 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @param string|null $message The message to show if the field is not
     * @return $this
     */
    public function allowEmpty($field, $when = true, $message = null)
    {
        $settingsDefault = [
            'when' => $when,
            'message' => $message
        ];

        if (!is_array($field)) {
            $field = $this->_convertValidatorToArray($field, $settingsDefault);
        }

        foreach ($field as $fieldName => $setting) {
            $settings = $this->_convertValidatorToArray($fieldName, $settingsDefault, $setting);
            $fieldName = current(array_keys($settings));

            $this->field($fieldName)->isEmptyAllowed($settings[$fieldName]['when']);
            if ($settings[$fieldName]['message']) {
                $this->_allowEmptyMessages[$fieldName] = $settings[$fieldName]['message'];
            }
        }
        return $this;
    }

    /**
     * Converts validator to fieldName => $settings array
     *
     * @param int|string $fieldName name of field
     * @param array $defaults default settings
     * @param string|array $settings settings from data
     * @return array
     */
    protected function _convertValidatorToArray($fieldName, $defaults = [], $settings = [])
    {
        if (is_string($settings)) {
            $fieldName = $settings;
            $settings = [];
        }
        if (!is_array($settings)) {
            throw new InvalidArgumentException(
                sprintf('Invalid settings for "%s". Settings must be an array.', $fieldName)
            );
        }
        $settings += $defaults;
        return [$fieldName => $settings];
    }

    /**
     * Sets a field to require a non-empty value. You can also pass array.
     * Using an array will let you provide the following keys:
     *
     * - `when` individual when condition for field
     * - `message` individual error message for field
     *
     * You can also set `when` and `message` for all passed fields, the individual setting
     * takes precedence over group settings.
     *
     * This is the opposite of `allowEmpty()` which allows a field to be empty.
     * By using $mode equal to 'create' or 'update', you can make fields required
     * when records are first created, or when they are updated.
     *
     * ### Example:
     *
     * ```
     * $message = 'This field cannot be empty';
     *
     * // Email cannot be empty
     * $validator->notEmpty('email');
     *
     * // Email can be empty on update, but not create
     * $validator->notEmpty('email', $message, 'create');
     *
     * // Email can be empty on create, but required on update.
     * $validator->notEmpty('email', $message, 'update');
     *
     * // Email and title can be empty on create, but are required on update.
     * $validator->notEmpty(['email', 'title'], $message, 'update');
     *
     * // Email can be empty on create, title must always be not empty
     * $validator->notEmpty(
     *      [
     *          'email',
     *          'title' => [
     *              'when' => true,
     *              'message' => 'Title cannot be empty'
     *          ]
     *      ],
     *      $message,
     *      'update'
     * );
     * ```
     *
     * It is possible to conditionally disallow emptiness on a field by passing a callback
     * as the third argument. The callback will receive the validation context array as
     * argument:
     *
     * ```
     * $validator->notEmpty('email', 'Email is required', function ($context) {
     *   return $context['newRecord'] && $context['data']['role'] !== 'admin';
     * });
     * ```
     *
     * Because this and `allowEmpty()` modify the same internal state, the last
     * method called will take precedence.
     *
     * @param string|array $field the name of the field or list of fields
     * @param string|null $message The message to show if the field is not
     * @param bool|string|callable $when Indicates when the field is not allowed
     *   to be empty. Valid values are true (always), 'create', 'update'. If a
     *   callable is passed then the field will allowed to be empty only when
     *   the callback returns false.
     * @return $this
     */
    public function notEmpty($field, $message = null, $when = false)
    {
        $defaults = [
            'when' => $when,
            'message' => $message
        ];

        if (!is_array($field)) {
            $field = $this->_convertValidatorToArray($field, $defaults);
        }

        foreach ($field as $fieldName => $setting) {
            $settings = $this->_convertValidatorToArray($fieldName, $defaults, $setting);
            $fieldName = current(array_keys($settings));
            $whenSetting = $settings[$fieldName]['when'];

            if ($whenSetting === 'create' || $whenSetting === 'update') {
                $whenSetting = $whenSetting === 'create' ? 'update' : 'create';
            } elseif (is_callable($whenSetting)) {
                $whenSetting = function ($context) use ($whenSetting) {
                    return !$whenSetting($context);
                };
            }

            $this->field($fieldName)->isEmptyAllowed($whenSetting);
            if ($settings[$fieldName]['message']) {
                $this->_allowEmptyMessages[$fieldName] = $settings[$fieldName]['message'];
            }
        }
        return $this;
    }

    /**
     * Add a notBlank rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::notBlank()
     * @return $this
     */
    public function notBlank($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'notBlank', $extra + [
            'rule' => 'notBlank',
        ]);
    }

    /**
     * Add an alphanumeric rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::alphaNumeric()
     * @return $this
     */
    public function alphaNumeric($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'alphaNumeric', $extra + [
            'rule' => 'alphaNumeric',
        ]);
    }

    /**
     * Add an rule that ensures a string length is within a range.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $range The inclusive minimum and maximum length you want permitted.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::alphaNumeric()
     * @return $this
     */
    public function lengthBetween($field, array $range, $message = null, $when = null)
    {
        if (count($range) !== 2) {
            throw new InvalidArgumentException('The $range argument requires 2 numbers');
        }
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'lengthBetween', $extra + [
            'rule' => ['lengthBetween', array_shift($range), array_shift($range)],
        ]);
    }

    /**
     * Add a credit card rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $type The type of cards you want to allow. Defaults to 'all'.
     *   You can also supply an array of accepted card types. e.g `['mastercard', 'visa', 'amex']`
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::cc()
     * @return $this
     */
    public function creditCard($field, $type = 'all', $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'creditCard', $extra + [
            'rule' => ['cc', $type, true],
        ]);
    }

    /**
     * Add a greater than comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|float $value The value user data must be greater than.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function greaterThan($field, $value, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'greaterThan', $extra + [
            'rule' => ['comparison', '>', $value]
        ]);
    }

    /**
     * Add a greater than or equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|float $value The value user data must be greater than or equal to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function greaterThanOrEqual($field, $value, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'greaterThanOrEqual', $extra + [
            'rule' => ['comparison', '>=', $value]
        ]);
    }

    /**
     * Add a less than comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|float $value The value user data must be less than.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function lessThan($field, $value, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'lessThan', $extra + [
            'rule' => ['comparison', '<', $value]
        ]);
    }

    /**
     * Add a less than or equal comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|float $value The value user data must be less than or equal to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function lessThanOrEqual($field, $value, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'lessThanOrEqual', $extra + [
            'rule' => ['comparison', '<=', $value]
        ]);
    }

    /**
     * Add a equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|float $value The value user data must be equal to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function equals($field, $value, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'equals', $extra + [
            'rule' => ['comparison', '=', $value]
        ]);
    }

    /**
     * Add a not equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|float $value The value user data must be not be equal to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function notEquals($field, $value, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'notEquals', $extra + [
            'rule' => ['comparison', '!=', $value]
        ]);
    }

    /**
     * Add a rule to compare two fields to each other.
     *
     * If both fields have the exact same value the rule will pass.
     *
     * @param mixed $field The field you want to apply the rule to.
     * @param mixed $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareWith()
     * @return $this
     */
    public function sameAs($field, $secondField, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'sameAs', $extra + [
            'rule' => ['compareWith', $secondField]
        ]);
    }

    /**
     * Add a rule to check if a field contains non alpha numeric characters.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $limit The minimum number of non-alphanumeric fields required.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::containsNonAlphaNumeric()
     * @return $this
     */
    public function containsNonAlphaNumeric($field, $limit = 1, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'containsNonAlphaNumeric', $extra + [
            'rule' => ['containsNonAlphaNumeric', $limit]
        ]);
    }

    /**
     * Add a date format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $formats A list of accepted date formats.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::date()
     * @return $this
     */
    public function date($field, $formats = ['ymd'], $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'date', $extra + [
            'rule' => ['date', $formats]
        ]);
    }

    /**
     * Add a date time format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $formats A list of accepted date formats.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::datetime()
     * @return $this
     */
    public function dateTime($field, $formats = ['ymd'], $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'dateTime', $extra + [
            'rule' => ['datetime', $formats]
        ]);
    }

    /**
     * Add a time format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::time()
     * @return $this
     */
    public function time($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'time', $extra + [
            'rule' => 'time'
        ]);
    }

    /**
     * Add a localized time, date or datetime format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $type Parser type, one out of 'date', 'time', and 'datetime'
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::localizedTime()
     * @return $this
     */
    public function localizedTime($field, $type = 'datetime', $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'localizedTime', $extra + [
            'rule' => ['localizedTime', $type]
        ]);
    }

    /**
     * Add a boolean validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::boolean()
     * @return $this
     */
    public function boolean($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'boolean', $extra + [
            'rule' => 'boolean'
        ]);
    }

    /**
     * Add a decimal validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|null $places The number of decimal places to require.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::decimal()
     * @return $this
     */
    public function decimal($field, $places = null, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'decimal', $extra + [
            'rule' => ['decimal', $places]
        ]);
    }

    /**
     * Add an email validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param bool $checkMX Whether or not to check the MX records.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::email()
     * @return $this
     */
    public function email($field, $checkMX = false, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'email', $extra + [
            'rule' => ['email', $checkMX]
        ]);
    }

    /**
     * Add an IP validation rule to a field.
     *
     * This rule will accept both IPv4 and IPv6 addresses.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ip($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'ip', $extra + [
            'rule' => 'ip'
        ]);
    }

    /**
     * Add an IPv4 validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ipv4($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'ipv4', $extra + [
            'rule' => ['ip', 'ipv4']
        ]);
    }

    /**
     * Add an IPv6 validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ipv6($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'ipv6', $extra + [
            'rule' => ['ip', 'ipv6']
        ]);
    }

    /**
     * Add a string length validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $min The minimum length required.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::minLength()
     * @return $this
     */
    public function minLength($field, $min, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'minLength', $extra + [
            'rule' => ['minLength', $min]
        ]);
    }

    /**
     * Add a string length validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $max The maximum length allowed.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::maxLength()
     * @return $this
     */
    public function maxLength($field, $max, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'maxLength', $extra + [
            'rule' => ['maxLength', $max]
        ]);
    }

    /**
     * Add a numeric value validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numeric()
     * @return $this
     */
    public function numeric($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'numeric', $extra + [
            'rule' => 'numeric'
        ]);
    }

    /**
     * Add a natural number validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::naturalNumber()
     * @return $this
     */
    public function naturalNumber($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'naturalNumber', $extra + [
            'rule' => ['naturalNumber', false]
        ]);
    }

    /**
     * Add a validation rule to ensure a field is a non negative integer.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::naturalNumber()
     * @return $this
     */
    public function nonNegativeInteger($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'nonNegativeInteger', $extra + [
            'rule' => ['naturalNumber', true]
        ]);
    }

    /**
     * Add a validation rule to ensure a field is within a numeric range
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $range The inclusive upper and lower bounds of the valid range.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::range()
     * @return $this
     */
    public function range($field, array $range, $message = null, $when = null)
    {
        if (count($range) !== 2) {
            throw new InvalidArgumentException('The $range argument requires 2 numbers');
        }
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'range', $extra + [
            'rule' => ['range', array_shift($range), array_shift($range)]
        ]);
    }

    /**
     * Add a validation rule to ensure a field is a URL.
     *
     * This validator does not require a protocol.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::url()
     * @return $this
     */
    public function url($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'url', $extra + [
            'rule' => ['url', false]
        ]);
    }

    /**
     * Add a validation rule to ensure a field is a URL.
     *
     * This validator requires the URL to have a protocol.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::url()
     * @return $this
     */
    public function urlWithProtocol($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'urlWithProtocol', $extra + [
            'rule' => ['url', true]
        ]);
    }

    /**
     * Add a validation rule to ensure the field value is within a whitelist.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $list The list of valid options.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::inList()
     * @return $this
     */
    public function inList($field, array $list, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'inList', $extra + [
            'rule' => ['inList', $list]
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a UUID
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uuid()
     * @return $this
     */
    public function uuid($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'uuid', $extra + [
            'rule' => 'uuid'
        ]);
    }

    /**
     * Add a validation rule to ensure the field is an uploaded file
     *
     * For options see Cake\Validation\Validation::uploadedFile()
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $options An array of options.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uploadedFile()
     * @return $this
     */
    public function uploadedFile($field, array $options, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'uploadedFile', $extra + [
            'rule' => ['uploadedFile', $options]
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a lat/long tuple.
     *
     * e.g. `<lat>, <lng>`
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uuid()
     * @return $this
     */
    public function latLong($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'latLong', $extra + [
            'rule' => 'geoCoordinate'
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a latitude.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::latitude()
     * @return $this
     */
    public function latitude($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'latitude', $extra + [
            'rule' => 'latitude'
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a longitude.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::longitude()
     * @return $this
     */
    public function longitude($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'longitude', $extra + [
            'rule' => 'longitude'
        ]);
    }

    /**
     * Add a validation rule to ensure a field contains only ascii bytes
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ascii()
     * @return $this
     */
    public function ascii($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'ascii', $extra + [
            'rule' => 'ascii'
        ]);
    }

    /**
     * Add a validation rule to ensure a field contains only BMP utf8 bytes
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::utf8()
     * @return $this
     */
    public function utf8($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'utf8', $extra + [
            'rule' => ['utf8', ['extended' => false]]
        ]);
    }

    /**
     * Add a validation rule to ensure a field contains only utf8 bytes.
     *
     * This rule will accept 3 and 4 byte UTF8 sequences, which are necessary for emoji.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::utf8()
     * @return $this
     */
    public function utf8Extended($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'utf8Extended', $extra + [
            'rule' => ['utf8', ['extended' => true]]
        ]);
    }

    /**
     * Add a validation rule to ensure a field is an integer value.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isInteger()
     * @return $this
     */
    public function integer($field, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'integer', $extra + [
            'rule' => 'isInteger'
        ]);
    }

    /**
     * Add a validation rule for a multiple select. Comparison is case sensitive by default.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $options The options for the validator. Includes the options defined in
     *   \Cake\Validation\Validation::multiple() and the `caseInsensitive` parameter.
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::multiple()
     * @return $this
     */
    public function multipleOptions($field, array $options = [], $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        $caseInsensitive = isset($options['caseInsenstive']) ? $options['caseInsensitive'] : false;
        unset($options['caseInsensitive']);
        return $this->add($field, 'multipleOptions', $extra + [
            'rule' => ['multiple', $options, $caseInsensitive]
        ]);
    }

    /**
     * Add a validation rule to ensure that a field is an array containing at least
     * the specified amount of elements
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $count The number of elements the array should at least have
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numElements()
     * @return $this
     */
    public function hasAtLeast($field, $count, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'hasAtLeast', $extra + [
            'rule' => function ($value) use ($count) {
                if (is_array($value) && isset($value['_ids'])) {
                    $value = $value['_ids'];
                }
                return Validation::numElements($value, '>=', $count);
            }
        ]);
    }

    /**
     * Add a validation rule to ensure that a field is an array containing at most
     * the specified amount of elements
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $count The number maximim amount of elements the field should have
     * @param string|null $message The error message when the rule fails.
     * @param string|callable|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numElements()
     * @return $this
     */
    public function hasAtMost($field, $count, $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);
        return $this->add($field, 'hasAtMost', $extra + [
            'rule' => function ($value) use ($count) {
                if (is_array($value) && isset($value['_ids'])) {
                    $value = $value['_ids'];
                }
                return Validation::numElements($value, '<=', $count);
            }
        ]);
    }

    /**
     * Returns whether or not a field can be left empty for a new or already existing
     * record.
     *
     * @param string $field Field name.
     * @param bool $newRecord whether the data to be validated is new or to be updated.
     * @return bool
     */
    public function isEmptyAllowed($field, $newRecord)
    {
        $providers = $this->_providers;
        $data = [];
        $context = compact('data', 'newRecord', 'field', 'providers');
        return $this->_canBeEmpty($this->field($field), $context);
    }

    /**
     * Returns whether or not a field can be left out for a new or already existing
     * record.
     *
     * @param string $field Field name.
     * @param bool $newRecord Whether the data to be validated is new or to be updated.
     * @return bool
     */
    public function isPresenceRequired($field, $newRecord)
    {
        $providers = $this->_providers;
        $data = [];
        $context = compact('data', 'newRecord', 'field', 'providers');
        return !$this->_checkPresence($this->field($field), $context);
    }

    /**
     * Returns false if any validation for the passed rule set should be stopped
     * due to the field missing in the data array
     *
     * @param \Cake\Validation\ValidationSet $field The set of rules for a field.
     * @param array $context A key value list of data containing the validation context.
     * @return bool
     */
    protected function _checkPresence($field, $context)
    {
        $required = $field->isPresenceRequired();

        if (!is_string($required) && is_callable($required)) {
            return !$required($context);
        }

        $newRecord = $context['newRecord'];
        if (in_array($required, ['create', 'update'], true)) {
            return (
                ($required === 'create' && !$newRecord) ||
                ($required === 'update' && $newRecord)
            );
        }

        return !$required;
    }

    /**
     * Returns whether the field can be left blank according to `allowEmpty`
     *
     * @param \Cake\Validation\ValidationSet $field the set of rules for a field
     * @param array $context a key value list of data containing the validation context.
     * @return bool
     */
    protected function _canBeEmpty($field, $context)
    {
        $allowed = $field->isEmptyAllowed();

        if (!is_string($allowed) && is_callable($allowed)) {
            return $allowed($context);
        }

        $newRecord = $context['newRecord'];
        if (in_array($allowed, ['create', 'update'], true)) {
            $allowed = (
                ($allowed === 'create' && $newRecord) ||
                ($allowed === 'update' && !$newRecord)
            );
        }

        return $allowed;
    }

    /**
     * Returns true if the field is empty in the passed data array
     *
     * @param mixed $data value to check against
     * @return bool
     */
    protected function _fieldIsEmpty($data)
    {
        if (empty($data) && $data !== '0' && $data !== false && $data !== 0 && $data !== 0.0) {
            return true;
        }
        $isArray = is_array($data);
        if ($isArray && (isset($data['year']) || isset($data['hour']))) {
            $value = implode('', $data);
            return strlen($value) === 0;
        }
        if ($isArray && isset($data['name'], $data['type'], $data['tmp_name'], $data['error'])) {
            return (int)$data['error'] === UPLOAD_ERR_NO_FILE;
        }
        return false;
    }

    /**
     * Iterates over each rule in the validation set and collects the errors resulting
     * from executing them
     *
     * @param string $field The name of the field that is being processed
     * @param \Cake\Validation\ValidationSet $rules the list of rules for a field
     * @param array $data the full data passed to the validator
     * @param bool $newRecord whether is it a new record or an existing one
     * @return array
     */
    protected function _processRules($field, ValidationSet $rules, $data, $newRecord)
    {
        $errors = [];
        // Loading default provider in case there is none
        $this->provider('default');
        $message = 'The provided value is invalid';

        if ($this->_useI18n) {
            $message = __d('cake', 'The provided value is invalid');
        }

        foreach ($rules as $name => $rule) {
            $result = $rule->process($data[$field], $this->_providers, compact('newRecord', 'data', 'field'));
            if ($result === true) {
                continue;
            }

            $errors[$name] = $message;
            if (is_array($result) && $name === static::NESTED) {
                $errors = $result;
            }
            if (is_string($result)) {
                $errors[$name] = $result;
            }

            if ($rule->isLast()) {
                break;
            }
        }
        return $errors;
    }

    /**
     * Get the printable version of this object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $fields = [];
        foreach ($this->_fields as $name => $fieldSet) {
            $fields[$name] = [
                'isPresenceRequired' => $fieldSet->isPresenceRequired(),
                'isEmptyAllowed' => $fieldSet->isEmptyAllowed(),
                'rules' => array_keys($fieldSet->rules()),
            ];
        }
        return [
            '_presenceMessages' => $this->_presenceMessages,
            '_allowEmptyMessages' => $this->_allowEmptyMessages,
            '_useI18n' => $this->_useI18n,
            '_providers' => array_keys($this->_providers),
            '_fields' => $fields
        ];
    }
}
