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
use Cake\Validation\RulesProvider;
use Cake\Validation\ValidationSet;
use Countable;
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

            if (!$keyPresent && !$this->_checkPresence($field, $newRecord)) {
                $errors[$name]['_required'] = isset($this->_presenceMessages[$name])
                    ? $this->_presenceMessages[$name]
                    : $requiredMessage;
                continue;
            }
            if (!$keyPresent) {
                continue;
            }

            $providers = $this->_providers;
            $context = compact('data', 'newRecord', 'field', 'providers');
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
     * @param string $name  The name under which the provider should be set.
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
        return new \ArrayIterator($this->_fields);
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
            $errors = $validator->errors($value);
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
                $check = $validator->errors($row);
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
     *
     * @param string $field the name of the field
     * @param bool|string $mode Valid values are true, false, 'create', 'update'
     * @param string|null $message The message to show if the field presence validation fails.
     * @return $this
     */
    public function requirePresence($field, $mode = true, $message = null)
    {
        $this->field($field)->isPresenceRequired($mode);
        if ($message) {
            $this->_presenceMessages[$field] = $message;
        }
        return $this;
    }

    /**
     * Allows a field to be empty.
     *
     * This is the opposite of notEmpty() which requires a field to not be empty.
     * By using $mode equal to 'create' or 'update', you can allow fields to be empty
     * when records are first created, or when they are updated.
     *
     * ### Example:
     *
     * ```
     * $validator->allowEmpty('email'); // Email can be empty
     * $validator->allowEmpty('email', 'create'); // Email can be empty on create
     * $validator->allowEmpty('email', 'update'); // Email can be empty on update
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
     * @param string $field the name of the field
     * @param bool|string|callable $when Indicates when the field is allowed to be empty
     * Valid values are true (always), 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     */
    public function allowEmpty($field, $when = true)
    {
        $this->field($field)->isEmptyAllowed($when);
        return $this;
    }

    /**
     * Sets a field to require a non-empty value.
     *
     * This is the opposite of allowEmpty() which allows a field to be empty.
     * By using $mode equal to 'create' or 'update', you can make fields required
     * when records are first created, or when they are updated.
     *
     * ### Example:
     *
     * ```
     * $message = 'This field cannot be empty';
     * $validator->notEmpty('email'); // Email cannot be empty
     * $validator->notEmpty('email', $message, 'create'); // Email can be empty on update
     * $validator->notEmpty('email', $message, 'update'); // Email can be empty on create
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
     * @param string $field the name of the field
     * @param string $message The validation message to show if the field is not
     * @param bool|string|callable $when  Indicates when the field is not allowed
     * to be empty. Valid values are true (always), 'create', 'update'. If a
     * callable is passed then the field will allowed be empty only when
     * the callback returns false.
     * @return $this
     */
    public function notEmpty($field, $message = null, $when = false)
    {
        if ($when === 'create' || $when === 'update') {
            $when = $when === 'create' ? 'update' : 'create';
        } elseif (is_callable($when)) {
            $when = function ($context) use ($when) {
                return !$when($context);
            };
        }

        $this->field($field)->isEmptyAllowed($when);
        if ($message) {
            $this->_allowEmptyMessages[$field] = $message;
        }
        return $this;
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
     * @param bool $newRecord whether the data to be validated is new or to be updated.
     * @return bool
     */
    public function isPresenceRequired($field, $newRecord)
    {
        return !$this->_checkPresence($this->field($field), $newRecord);
    }

    /**
     * Returns false if any validation for the passed rule set should be stopped
     * due to the field missing in the data array
     *
     * @param ValidationSet $field the set of rules for a field
     * @param bool $newRecord whether the data to be validated is new or to be updated.
     * @return bool
     */
    protected function _checkPresence($field, $newRecord)
    {
        $required = $field->isPresenceRequired();
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
     * @param ValidationSet $field the set of rules for a field
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
     * @param ValidationSet $rules the list of rules for a field
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
