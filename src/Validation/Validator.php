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
use BackedEnum;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\UploadedFileInterface;
use Traversable;
use function Cake\I18n\__d;

/**
 * Validator object encapsulates all methods related to data validations for a model
 * It also provides an API to dynamically change validation rules for each model field.
 *
 * Implements ArrayAccess to easily modify rules in the set
 *
 * @link https://book.cakephp.org/5/en/core-libraries/validation.html
 * @template-implements \ArrayAccess<string, \Cake\Validation\ValidationSet>
 * @template-implements \IteratorAggregate<string, \Cake\Validation\ValidationSet>
 */
class Validator implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * By using 'create' you can make fields required when records are first created.
     *
     * @var string
     */
    public const WHEN_CREATE = 'create';

    /**
     * By using 'update', you can make fields required when they are updated.
     *
     * @var string
     */
    public const WHEN_UPDATE = 'update';

    /**
     * Used to flag nested rules created with addNested() and addNestedMany()
     *
     * @var string
     */
    public const NESTED = '_nested';

    /**
     * A flag for allowEmptyFor()
     *
     * When `null` is given, it will be recognized as empty.
     *
     * @var int
     */
    public const EMPTY_NULL = 0;

    /**
     * A flag for allowEmptyFor()
     *
     * When an empty string is given, it will be recognized as empty.
     *
     * @var int
     */
    public const EMPTY_STRING = 1;

    /**
     * A flag for allowEmptyFor()
     *
     * When an empty array is given, it will be recognized as empty.
     *
     * @var int
     */
    public const EMPTY_ARRAY = 2;

    /**
     * A flag for allowEmptyFor()
     *
     * The return value of \Psr\Http\Message\UploadedFileInterface::getError()
     * method must be equal to `UPLOAD_ERR_NO_FILE`.
     *
     * @var int
     */
    public const EMPTY_FILE = 4;

    /**
     * A flag for allowEmptyFor()
     *
     * When an array is given, if it contains the `year` key, and only empty strings
     * or null values, it will be recognized as empty.
     *
     * @var int
     */
    public const EMPTY_DATE = 8;

    /**
     * A flag for allowEmptyFor()
     *
     * When an array is given, if it contains the `hour` key, and only empty strings
     * or null values, it will be recognized as empty.
     *
     * @var int
     */
    public const EMPTY_TIME = 16;

    /**
     * A combination of the all EMPTY_* flags
     *
     * @var int
     */
    public const EMPTY_ALL = self::EMPTY_STRING
        | self::EMPTY_ARRAY
        | self::EMPTY_FILE
        | self::EMPTY_DATE
        | self::EMPTY_TIME;

    /**
     * Holds the ValidationSet objects array
     *
     * @var array<string, \Cake\Validation\ValidationSet>
     */
    protected array $_fields = [];

    /**
     * An associative array of objects or classes containing methods
     * used for validation
     *
     * @var array<string, object|string>
     * @phpstan-var array<string, object|class-string>
     */
    protected array $_providers = [];

    /**
     * An associative array of objects or classes used as a default provider list
     *
     * @var array<string, object|string>
     * @phpstan-var array<string, object|class-string>
     */
    protected static array $_defaultProviders = [];

    /**
     * Contains the validation messages associated with checking the presence
     * for each corresponding field.
     *
     * @var array<string, string>
     */
    protected array $_presenceMessages = [];

    /**
     * Whether to use I18n functions for translating default error messages
     *
     * @var bool
     */
    protected bool $_useI18n;

    /**
     * Contains the validation messages associated with checking the emptiness
     * for each corresponding field.
     *
     * @var array<string, string>
     */
    protected array $_allowEmptyMessages = [];

    /**
     * Contains the flags which specify what is empty for each corresponding field.
     *
     * @var array<string, int>
     */
    protected array $_allowEmptyFlags = [];

    /**
     * Whether to apply last flag to generated rule(s).
     *
     * @var bool
     */
    protected bool $_stopOnFailure = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_useI18n ??= function_exists('\Cake\I18n\__d');
        $this->_providers = self::$_defaultProviders;
        $this->_providers['default'] ??= Validation::class;
    }

    /**
     * Whether to stop validation rule evaluation on the first failed rule.
     *
     * When enabled, the first failing rule per field will cause validation to stop.
     * When disabled, all rules will be run even if there are failures.
     *
     * @param bool $stopOnFailure If to apply last flag.
     * @return $this
     */
    public function setStopOnFailure(bool $stopOnFailure = true)
    {
        $this->_stopOnFailure = $stopOnFailure;

        return $this;
    }

    /**
     * Validates and returns an array of failed fields and their error messages.
     *
     * @param array $data The data to be checked for errors
     * @param bool $newRecord whether the data to be validated is new or to be updated.
     * @return array<array> Array of failed fields
     */
    public function validate(array $data, bool $newRecord = true): array
    {
        $errors = [];

        foreach ($this->_fields as $name => $field) {
            $name = (string)$name;
            $keyPresent = array_key_exists($name, $data);

            $providers = $this->_providers;
            $context = compact('data', 'newRecord', 'field', 'providers');

            if (!$keyPresent && !$this->_checkPresence($field, $context)) {
                $errors[$name]['_required'] = $this->getRequiredMessage($name);
                continue;
            }
            if (!$keyPresent) {
                continue;
            }

            $canBeEmpty = $this->_canBeEmpty($field, $context);

            $flags = static::EMPTY_NULL;
            if (isset($this->_allowEmptyFlags[$name])) {
                $flags = $this->_allowEmptyFlags[$name];
            }

            $isEmpty = $this->isEmpty($data[$name], $flags);

            if (!$canBeEmpty && $isEmpty) {
                $errors[$name]['_empty'] = $this->getNotEmptyMessage($name);
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
     * @param string $name [optional] The field name to fetch.
     * @param \Cake\Validation\ValidationSet|null $set The set of rules for field
     * @return \Cake\Validation\ValidationSet
     */
    public function field(string $name, ?ValidationSet $set = null): ValidationSet
    {
        if (empty($this->_fields[$name])) {
            $set = $set ?: new ValidationSet();
            $this->_fields[$name] = $set;
        }

        return $this->_fields[$name];
    }

    /**
     * Check whether a validator contains any rules for the given field.
     *
     * @param string $name The field name to check.
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return isset($this->_fields[$name]);
    }

    /**
     * Associates an object to a name so it can be used as a provider. Providers are
     * objects or class names that can contain methods used during validation of for
     * deciding whether a validation rule can be applied. All validation methods,
     * when called will receive the full list of providers stored in this validator.
     *
     * @param string $name The name under which the provider should be set.
     * @param object|string $object Provider object or class name.
     * @phpstan-param object|class-string $object
     * @return $this
     */
    public function setProvider(string $name, object|string $object)
    {
        $this->_providers[$name] = $object;

        return $this;
    }

    /**
     * Returns the provider stored under that name if it exists.
     *
     * @param string $name The name under which the provider should be set.
     * @return object|class-string|null
     */
    public function getProvider(string $name): object|string|null
    {
        return $this->_providers[$name] ?? null;
    }

    /**
     * Returns the default provider stored under that name if it exists.
     *
     * @param string $name The name under which the provider should be retrieved.
     * @return object|class-string|null
     */
    public static function getDefaultProvider(string $name): object|string|null
    {
        return self::$_defaultProviders[$name] ?? null;
    }

    /**
     * Associates an object to a name so it can be used as a default provider.
     *
     * @param string $name The name under which the provider should be set.
     * @param object|string $object Provider object or class name.
     * @phpstan-param object|class-string $object
     * @return void
     */
    public static function addDefaultProvider(string $name, object|string $object): void
    {
        self::$_defaultProviders[$name] = $object;
    }

    /**
     * Get the list of default providers.
     *
     * @return array<string>
     */
    public static function getDefaultProviders(): array
    {
        return array_keys(self::$_defaultProviders);
    }

    /**
     * Get the list of providers in this validator.
     *
     * @return array<string>
     */
    public function providers(): array
    {
        return array_keys($this->_providers);
    }

    /**
     * Returns whether a rule set is defined for a field or not
     *
     * @param string $field name of the field to check
     * @return bool
     */
    public function offsetExists(mixed $field): bool
    {
        return isset($this->_fields[$field]);
    }

    /**
     * Returns the rule set for a field
     *
     * @param string|int $field name of the field to check
     * @return \Cake\Validation\ValidationSet
     */
    public function offsetGet(mixed $field): ValidationSet
    {
        return $this->field((string)$field);
    }

    /**
     * Sets the rule set for a field
     *
     * @param string $offset name of the field to set
     * @param \Cake\Validation\ValidationSet|array $value set of rules to apply to field
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof ValidationSet) {
            $set = new ValidationSet();
            foreach ($value as $name => $rule) {
                $set->add($name, $rule);
            }
            $value = $set;
        }
        $this->_fields[$offset] = $value;
    }

    /**
     * Unsets the rule set for a field
     *
     * @param string $field name of the field to unset
     * @return void
     */
    public function offsetUnset(mixed $field): void
    {
        unset($this->_fields[$field]);
    }

    /**
     * Returns an iterator for each of the fields to be validated
     *
     * @return \Traversable<string, \Cake\Validation\ValidationSet>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_fields);
    }

    /**
     * Returns the number of fields having validation rules
     *
     * @return int
     */
    public function count(): int
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
     * @param string $field The name of the field from which the rule will be added
     * @param array|string $name The alias for a single rule or multiple rules array
     * @param \Cake\Validation\ValidationRule|array $rule the rule to add
     * @throws \InvalidArgumentException If numeric index cannot be resolved to a string one
     * @return $this
     */
    public function add(string $field, array|string $name, ValidationRule|array $rule = [])
    {
        $validationSet = $this->field($field);

        if (!is_array($name)) {
            $rules = [$name => $rule];
        } else {
            $rules = $name;
        }

        foreach ($rules as $name => $rule) {
            if (is_array($rule)) {
                $rule += [
                    'rule' => $name,
                    'last' => $this->_stopOnFailure,
                ];
            }
            if (!is_string($name)) {
                throw new InvalidArgumentException(
                    'You cannot add validation rules without a `name` key. Update rules array to have string keys.',
                );
            }

            $validationSet->add($name, $rule);
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
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @return $this
     */
    public function addNested(
        string $field,
        Validator $validator,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        $extra = array_filter(['message' => $message, 'on' => $when]);

        $validationSet = $this->field($field);
        $validationSet->add(static::NESTED, $extra + ['rule' => function ($value, $context) use ($validator, $message) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($this->providers() as $name) {
                /** @var object|class-string $provider */
                $provider = $this->getProvider($name);
                $validator->setProvider($name, $provider);
            }
            $errors = $validator->validate($value, $context['newRecord']);

            $message = $message ? [static::NESTED => $message] : [];

            return $errors === [] ? true : $errors + $message;
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
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @return $this
     */
    public function addNestedMany(
        string $field,
        Validator $validator,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        $extra = array_filter(['message' => $message, 'on' => $when]);

        $validationSet = $this->field($field);
        $validationSet->add(static::NESTED, $extra + ['rule' => function ($value, $context) use ($validator, $message) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($this->providers() as $name) {
                /** @var object|class-string $provider */
                $provider = $this->getProvider($name);
                $validator->setProvider($name, $provider);
            }
            $errors = [];
            foreach ($value as $i => $row) {
                if (!is_array($row)) {
                    return false;
                }
                $check = $validator->validate($row, $context['newRecord']);
                if ($check) {
                    $errors[$i] = $check;
                }
            }

            $message = $message ? [static::NESTED => $message] : [];

            return $errors === [] ? true : $errors + $message;
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
    public function remove(string $field, ?string $rule = null)
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
     * You can also pass array. Using an array will let you provide the following
     * keys:
     *
     * - `mode` individual mode for field
     * - `message` individual error message for field
     *
     * You can also set mode and message for all passed fields, the individual
     * setting takes precedence over group settings.
     *
     * @param array<string|int, mixed>|string $field the name of the field or list of fields.
     * @param \Closure|string|bool $mode Valid values are true, false, 'create', 'update'.
     *   If a Closure is passed then the field will be required only when the callback
     *   returns true.
     * @param string|null $message The message to show if the field presence validation fails.
     * @return $this
     */
    public function requirePresence(array|string $field, Closure|string|bool $mode = true, ?string $message = null)
    {
        $defaults = [
            'mode' => $mode,
            'message' => $message,
        ];

        if (!is_array($field)) {
            $field = $this->_convertValidatorToArray((string)$field, $defaults);
        }

        foreach ($field as $fieldName => $setting) {
            $settings = $this->_convertValidatorToArray((string)$fieldName, $defaults, $setting);
            /** @var string $fieldName */
            $fieldName = current(array_keys($settings));

            $this->field((string)$fieldName)->requirePresence($settings[$fieldName]['mode']);
            if ($settings[$fieldName]['message']) {
                $this->_presenceMessages[$fieldName] = $settings[$fieldName]['message'];
            }
        }

        return $this;
    }

    /**
     * Low-level method to indicate that a field can be empty.
     *
     * This method should generally not be used, and instead you should
     * use:
     *
     * - `allowEmptyString()`
     * - `allowEmptyArray()`
     * - `allowEmptyFile()`
     * - `allowEmptyDate()`
     * - `allowEmptyDatetime()`
     * - `allowEmptyTime()`
     *
     * Should be used as their APIs are simpler to operate and read.
     *
     * You can also set flags, when and message for all passed fields, the individual
     * setting takes precedence over group settings.
     *
     * ### Example:
     *
     * ```
     * // Email can be empty
     * $validator->allowEmptyFor('email', Validator::EMPTY_STRING);
     *
     * // Email can be empty on create
     * $validator->allowEmptyFor('email', Validator::EMPTY_STRING, Validator::WHEN_CREATE);
     *
     * // Email can be empty on update
     * $validator->allowEmptyFor('email', Validator::EMPTY_STRING, Validator::WHEN_UPDATE);
     * ```
     *
     * It is possible to conditionally allow emptiness on a field by passing a callback
     * as a second argument. The callback will receive the validation context array as
     * argument:
     *
     * ```
     * $validator->allowEmpty('email', Validator::EMPTY_STRING, function ($context) {
     *   return !$context['newRecord'] || $context['data']['role'] === 'admin';
     * });
     * ```
     *
     * If you want to allow other kind of empty data on a field, you need to pass other
     * flags:
     *
     * ```
     * $validator->allowEmptyFor('photo', Validator::EMPTY_FILE);
     * $validator->allowEmptyFor('published', Validator::EMPTY_STRING | Validator::EMPTY_DATE | Validator::EMPTY_TIME);
     * $validator->allowEmptyFor('items', Validator::EMPTY_STRING | Validator::EMPTY_ARRAY);
     * ```
     *
     * You can also use convenience wrappers of this method. The following calls are the
     * same as above:
     *
     * ```
     * $validator->allowEmptyFile('photo');
     * $validator->allowEmptyDateTime('published');
     * $validator->allowEmptyArray('items');
     * ```
     *
     * @param string $field The name of the field.
     * @param int|null $flags A bitmask of EMPTY_* flags which specify what is empty.
     *   If no flags/bitmask is provided only `null` will be allowed as empty value.
     * @param \Closure|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a Closure is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @param string|null $message The message to show if the field is not
     * @since 3.7.0
     * @return $this
     */
    public function allowEmptyFor(
        string $field,
        ?int $flags = null,
        Closure|string|bool $when = true,
        ?string $message = null,
    ) {
        $this->field($field)->allowEmpty($when);
        if ($message) {
            $this->_allowEmptyMessages[$field] = $message;
        }
        if ($flags !== null) {
            $this->_allowEmptyFlags[$field] = $flags;
        }

        return $this;
    }

    /**
     * Allows a field to be an empty string.
     *
     * This method is equivalent to calling allowEmptyFor() with EMPTY_STRING flag.
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is not
     * @param \Closure|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a Closure is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyFor() For detail usage
     */
    public function allowEmptyString(string $field, ?string $message = null, Closure|string|bool $when = true)
    {
        return $this->allowEmptyFor($field, self::EMPTY_STRING, $when, $message);
    }

    /**
     * Requires a field to not be an empty string.
     *
     * Opposite to allowEmptyString()
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is empty.
     * @param \Closure|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   Closure is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyString()
     * @since 3.8.0
     */
    public function notEmptyString(string $field, ?string $message = null, Closure|string|bool $when = false)
    {
        $when = $this->invertWhenClause($when);

        return $this->allowEmptyFor($field, self::EMPTY_STRING, $when, $message);
    }

    /**
     * Allows a field to be an empty array.
     *
     * This method is equivalent to calling allowEmptyFor() with EMPTY_STRING +
     * EMPTY_ARRAY flags.
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is not
     * @param \Closure|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a Closure is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples.
     */
    public function allowEmptyArray(string $field, ?string $message = null, Closure|string|bool $when = true)
    {
        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_ARRAY, $when, $message);
    }

    /**
     * Require a field to be a non-empty array
     *
     * Opposite to allowEmptyArray()
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is empty.
     * @param \Closure|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   Closure is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyArray()
     */
    public function notEmptyArray(string $field, ?string $message = null, Closure|string|bool $when = false)
    {
        $when = $this->invertWhenClause($when);

        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_ARRAY, $when, $message);
    }

    /**
     * Allows a field to be an empty file.
     *
     * This method is equivalent to calling allowEmptyFor() with EMPTY_FILE flag.
     * File fields will not accept `''`, or `[]` as empty values. Only `null` and a file
     * upload with `error` equal to `UPLOAD_ERR_NO_FILE` will be treated as empty.
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is not
     * @param \Closure|string|bool $when Indicates when the field is allowed to be empty
     *   Valid values are true, 'create', 'update'. If a Closure is passed then
     *   the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() For detail usage
     */
    public function allowEmptyFile(string $field, ?string $message = null, Closure|string|bool $when = true)
    {
        return $this->allowEmptyFor($field, self::EMPTY_FILE, $when, $message);
    }

    /**
     * Require a field to be a not-empty file.
     *
     * Opposite to allowEmptyFile()
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is empty.
     * @param \Closure|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   Closure is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @since 3.8.0
     * @see \Cake\Validation\Validator::allowEmptyFile()
     */
    public function notEmptyFile(string $field, ?string $message = null, Closure|string|bool $when = false)
    {
        $when = $this->invertWhenClause($when);

        return $this->allowEmptyFor($field, self::EMPTY_FILE, $when, $message);
    }

    /**
     * Allows a field to be an empty date.
     *
     * Empty date values are `null`, `''`, `[]` and arrays where all values are `''`
     * and the `year` key is present.
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is not
     * @param \Closure|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a Closure is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples
     */
    public function allowEmptyDate(string $field, ?string $message = null, Closure|string|bool $when = true)
    {
        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_DATE, $when, $message);
    }

    /**
     * Require a non-empty date value
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is empty.
     * @param \Closure|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   Closure is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyDate() for examples
     */
    public function notEmptyDate(string $field, ?string $message = null, Closure|string|bool $when = false)
    {
        $when = $this->invertWhenClause($when);

        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_DATE, $when, $message);
    }

    /**
     * Allows a field to be an empty time.
     *
     * Empty date values are `null`, `''`, `[]` and arrays where all values are `''`
     * and the `hour` key is present.
     *
     * This method is equivalent to calling allowEmptyFor() with EMPTY_STRING +
     * EMPTY_TIME flags.
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is not
     * @param \Closure|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a Closure is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples.
     */
    public function allowEmptyTime(string $field, ?string $message = null, Closure|string|bool $when = true)
    {
        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_TIME, $when, $message);
    }

    /**
     * Require a field to be a non-empty time.
     *
     * Opposite to allowEmptyTime()
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is empty.
     * @param \Closure|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   Closure is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @since 3.8.0
     * @see \Cake\Validation\Validator::allowEmptyTime()
     */
    public function notEmptyTime(string $field, ?string $message = null, Closure|string|bool $when = false)
    {
        $when = $this->invertWhenClause($when);

        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_TIME, $when, $message);
    }

    /**
     * Allows a field to be an empty date/time.
     *
     * Empty date values are `null`, `''`, `[]` and arrays where all values are `''`
     * and the `year` and `hour` keys are present.
     *
     * This method is equivalent to calling allowEmptyFor() with EMPTY_STRING +
     * EMPTY_DATE + EMPTY_TIME flags.
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is not
     * @param \Closure|string|bool $when Indicates when the field is allowed to be empty
     *   Valid values are true, false, 'create', 'update'. If a Closure is passed then
     *   the field will allowed to be empty only when the callback returns false.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples.
     */
    public function allowEmptyDateTime(string $field, ?string $message = null, Closure|string|bool $when = true)
    {
        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_DATE | self::EMPTY_TIME, $when, $message);
    }

    /**
     * Require a field to be a non empty date/time.
     *
     * Opposite to allowEmptyDateTime
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is empty.
     * @param \Closure|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   Closure is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @since 3.8.0
     * @see \Cake\Validation\Validator::allowEmptyDateTime()
     */
    public function notEmptyDateTime(string $field, ?string $message = null, Closure|string|bool $when = false)
    {
        $when = $this->invertWhenClause($when);

        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_DATE | self::EMPTY_TIME, $when, $message);
    }

    /**
     * Converts validator to fieldName => $settings array
     *
     * @param string $fieldName name of field
     * @param array<string, mixed> $defaults default settings
     * @param array<string|int, mixed>|string|int $settings settings from data
     * @return array<string, array<string|int, mixed>>
     * @throws \InvalidArgumentException
     */
    protected function _convertValidatorToArray(
        string $fieldName,
        array $defaults = [],
        array|string|int $settings = [],
    ): array {
        if (!is_array($settings)) {
            $fieldName = (string)$settings;
            $settings = [];
        }
        $settings += $defaults;

        return [$fieldName => $settings];
    }

    /**
     * Invert a when clause for creating notEmpty rules
     *
     * @param \Closure|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are true (always), 'create', 'update'. If a
     *   Closure is passed then the field will allowed to be empty only when
     *   the callback returns false.
     * @return \Closure|string|bool
     */
    protected function invertWhenClause(Closure|string|bool $when): Closure|string|bool
    {
        if ($when === static::WHEN_CREATE || $when === static::WHEN_UPDATE) {
            return $when === static::WHEN_CREATE ? static::WHEN_UPDATE : static::WHEN_CREATE;
        }
        if ($when instanceof Closure) {
            return fn($context) => !$when($context);
        }

        return $when;
    }

    /**
     * Add a notBlank rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::notBlank()
     * @return $this
     */
    public function notBlank(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'This field cannot be left empty';
            } else {
                $message = __d('cake', 'This field cannot be left empty');
            }
        }

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
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::alphaNumeric()
     * @return $this
     */
    public function alphaNumeric(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be alphanumeric';
            } else {
                $message = __d('cake', 'The provided value must be alphanumeric');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'alphaNumeric', $extra + [
            'rule' => 'alphaNumeric',
        ]);
    }

    /**
     * Add a non-alphanumeric rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::notAlphaNumeric()
     * @return $this
     */
    public function notAlphaNumeric(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must not be alphanumeric';
            } else {
                $message = __d('cake', 'The provided value must not be alphanumeric');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'notAlphaNumeric', $extra + [
            'rule' => 'notAlphaNumeric',
        ]);
    }

    /**
     * Add an ascii-alphanumeric rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::asciiAlphaNumeric()
     * @return $this
     */
    public function asciiAlphaNumeric(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be ASCII-alphanumeric';
            } else {
                $message = __d('cake', 'The provided value must be ASCII-alphanumeric');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'asciiAlphaNumeric', $extra + [
            'rule' => 'asciiAlphaNumeric',
        ]);
    }

    /**
     * Add a non-ascii alphanumeric rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::notAlphaNumeric()
     * @return $this
     */
    public function notAsciiAlphaNumeric(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must not be ASCII-alphanumeric';
            } else {
                $message = __d('cake', 'The provided value must not be ASCII-alphanumeric');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'notAsciiAlphaNumeric', $extra + [
            'rule' => 'notAsciiAlphaNumeric',
        ]);
    }

    /**
     * Add an rule that ensures a string length is within a range.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $range The inclusive minimum and maximum length you want permitted.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::alphaNumeric()
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function lengthBetween(
        string $field,
        array $range,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if (count($range) !== 2) {
            throw new InvalidArgumentException('The $range argument requires 2 numbers');
        }
        $lowerBound = array_shift($range);
        $upperBound = array_shift($range);

        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf(
                    'The length of the provided value must be between `%s` and `%s`, inclusively',
                    $lowerBound,
                    $upperBound,
                );
            } else {
                $message = __d(
                    'cake',
                    'The length of the provided value must be between `{0}` and `{1}`, inclusively',
                    $lowerBound,
                    $upperBound,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'lengthBetween', $extra + [
            'rule' => ['lengthBetween', $lowerBound, $upperBound],
        ]);
    }

    /**
     * Add a credit card rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array|string $type The type of cards you want to allow. Defaults to 'all'.
     *   You can also supply an array of accepted card types. e.g `['mastercard', 'visa', 'amex']`
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::creditCard()
     * @return $this
     */
    public function creditCard(
        string $field,
        array|string $type = 'all',
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if (is_array($type)) {
            $typeEnumeration = implode(', ', $type);
        } else {
            $typeEnumeration = $type;
        }

        if ($message === null) {
            if (!$this->_useI18n) {
                if ($type === 'all') {
                    $message = 'The provided value must be a valid credit card number of any type';
                } else {
                    $message = sprintf(
                        'The provided value must be a valid credit card number of these types: `%s`',
                        $typeEnumeration,
                    );
                }
            } elseif ($type === 'all') {
                $message = __d(
                    'cake',
                    'The provided value must be a valid credit card number of any type',
                );
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be a valid credit card number of these types: `{0}`',
                    $typeEnumeration,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'creditCard', $extra + [
            'rule' => ['creditCard', $type, true],
        ]);
    }

    /**
     * Add a greater than comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param float|int $value The value user data must be greater than.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function greaterThan(
        string $field,
        float|int $value,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be greater than `%s`', $value);
            } else {
                $message = __d('cake', 'The provided value must be greater than `{0}`', $value);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'greaterThan', $extra + [
            'rule' => ['comparison', Validation::COMPARE_GREATER, $value],
        ]);
    }

    /**
     * Add a greater than or equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param float|int $value The value user data must be greater than or equal to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function greaterThanOrEqual(
        string $field,
        float|int $value,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be greater than or equal to `%s`', $value);
            } else {
                $message = __d('cake', 'The provided value must be greater than or equal to `{0}`', $value);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'greaterThanOrEqual', $extra + [
            'rule' => ['comparison', Validation::COMPARE_GREATER_OR_EQUAL, $value],
        ]);
    }

    /**
     * Add a less than comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param float|int $value The value user data must be less than.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function lessThan(
        string $field,
        float|int $value,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be less than `%s`', $value);
            } else {
                $message = __d('cake', 'The provided value must be less than `{0}`', $value);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'lessThan', $extra + [
            'rule' => ['comparison', Validation::COMPARE_LESS, $value],
        ]);
    }

    /**
     * Add a less than or equal comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param float|int $value The value user data must be less than or equal to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function lessThanOrEqual(
        string $field,
        float|int $value,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be less than or equal to `%s`', $value);
            } else {
                $message = __d('cake', 'The provided value must be less than or equal to `{0}`', $value);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'lessThanOrEqual', $extra + [
            'rule' => ['comparison', Validation::COMPARE_LESS_OR_EQUAL, $value],
        ]);
    }

    /**
     * Add a equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param mixed $value The value user data must be equal to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function equals(
        string $field,
        mixed $value,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be equal to `%s`', $value);
            } else {
                $message = __d('cake', 'The provided value must be equal to `{0}`', $value);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'equals', $extra + [
            'rule' => ['comparison', Validation::COMPARE_EQUAL, $value],
        ]);
    }

    /**
     * Add a not equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param mixed $value The value user data must be not be equal to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function notEquals(
        string $field,
        mixed $value,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must not be equal to `%s`', $value);
            } else {
                $message = __d('cake', 'The provided value must not be equal to `{0}`', $value);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'notEquals', $extra + [
            'rule' => ['comparison', Validation::COMPARE_NOT_EQUAL, $value],
        ]);
    }

    /**
     * Add a rule to compare two fields to each other.
     *
     * If both fields have the exact same value the rule will pass.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     */
    public function sameAs(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be same as `%s`', $secondField);
            } else {
                $message = __d('cake', 'The provided value must be same as `{0}`', $secondField);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'sameAs', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_SAME],
        ]);
    }

    /**
     * Add a rule to compare that two fields have different values.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function notSameAs(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must not be same as `%s`', $secondField);
            } else {
                $message = __d('cake', 'The provided value must not be same as `{0}`', $secondField);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'notSameAs', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_NOT_SAME],
        ]);
    }

    /**
     * Add a rule to compare one field is equal to another.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function equalToField(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be equal to the one of field `%s`', $secondField);
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be equal to the one of field `{0}`',
                    $secondField,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'equalToField', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_EQUAL],
        ]);
    }

    /**
     * Add a rule to compare one field is not equal to another.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function notEqualToField(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must not be equal to the one of field `%s`', $secondField);
            } else {
                $message = __d(
                    'cake',
                    'The provided value must not be equal to the one of field `{0}`',
                    $secondField,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'notEqualToField', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_NOT_EQUAL],
        ]);
    }

    /**
     * Add a rule to compare one field is greater than another.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function greaterThanField(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be greater than the one of field `%s`', $secondField);
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be greater than the one of field `{0}`',
                    $secondField,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'greaterThanField', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_GREATER],
        ]);
    }

    /**
     * Add a rule to compare one field is greater than or equal to another.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function greaterThanOrEqualToField(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf(
                    'The provided value must be greater than or equal to the one of field `%s`',
                    $secondField,
                );
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be greater than or equal to the one of field `{0}`',
                    $secondField,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'greaterThanOrEqualToField', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_GREATER_OR_EQUAL],
        ]);
    }

    /**
     * Add a rule to compare one field is less than another.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function lessThanField(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be less than the one of field `%s`', $secondField);
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be less than the one of field `{0}`',
                    $secondField,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'lessThanField', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_LESS],
        ]);
    }

    /**
     * Add a rule to compare one field is less than or equal to another.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $secondField The field you want to compare against.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function lessThanOrEqualToField(
        string $field,
        string $secondField,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf(
                    'The provided value must be less than or equal to the one of field `%s`',
                    $secondField,
                );
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be less than or equal to the one of field `{0}`',
                    $secondField,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'lessThanOrEqualToField', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_LESS_OR_EQUAL],
        ]);
    }

    /**
     * Add a date format validation rule to a field.
     *
     * Years are valid from 0001 to 2999.
     *
     * ### Formats:
     *
     * - `ymd` 2006-12-27 or 06-12-27 separators can be a space, period, dash, forward slash
     * - `dmy` 27-12-2006 or 27-12-06 separators can be a space, period, dash, forward slash
     * - `mdy` 12-27-2006 or 12-27-06 separators can be a space, period, dash, forward slash
     * - `dMy` 27 December 2006 or 27 Dec 2006
     * - `Mdy` December 27, 2006 or Dec 27, 2006 comma is optional
     * - `My` December 2006 or Dec 2006
     * - `my` 12/2006 or 12/06 separators can be a space, period, dash, forward slash
     * - `ym` 2006/12 or 06/12 separators can be a space, period, dash, forward slash
     * - `y` 2006 just the year without any separators
     *
     * @param string $field The field you want to apply the rule to.
     * @param array<string> $formats A list of accepted date formats.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::date()
     * @return $this
     */
    public function date(
        string $field,
        array $formats = ['ymd'],
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        $formatEnumeration = implode(', ', $formats);

        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf(
                    'The provided value must be a date of one of these formats: `%s`',
                    $formatEnumeration,
                );
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be a date of one of these formats: `{0}`',
                    $formatEnumeration,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'date', $extra + [
            'rule' => ['date', $formats],
        ]);
    }

    /**
     * Add a date time format validation rule to a field.
     *
     * All values matching the "date" core validation rule, and the "time" one will be valid
     *
     * Years are valid from 0001 to 2999.
     *
     * ### Formats:
     *
     * - `ymd` 2006-12-27 or 06-12-27 separators can be a space, period, dash, forward slash
     * - `dmy` 27-12-2006 or 27-12-06 separators can be a space, period, dash, forward slash
     * - `mdy` 12-27-2006 or 12-27-06 separators can be a space, period, dash, forward slash
     * - `dMy` 27 December 2006 or 27 Dec 2006
     * - `Mdy` December 27, 2006 or Dec 27, 2006 comma is optional
     * - `My` December 2006 or Dec 2006
     * - `my` 12/2006 or 12/06 separators can be a space, period, dash, forward slash
     * - `ym` 2006/12 or 06/12 separators can be a space, period, dash, forward slash
     * - `y` 2006 just the year without any separators
     *
     * Time is validated as 24hr (HH:MM[:SS][.FFFFFF]) or am/pm ([H]H:MM[a|p]m)
     *
     * Seconds and fractional seconds (microseconds) are allowed but optional
     * in 24hr format.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array<string> $formats A list of accepted date formats.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::datetime()
     * @return $this
     */
    public function dateTime(
        string $field,
        array $formats = ['ymd'],
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        $formatEnumeration = implode(', ', $formats);

        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf(
                    'The provided value must be a date and time of one of these formats: `%s`',
                    $formatEnumeration,
                );
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be a date and time of one of these formats: `{0}`',
                    $formatEnumeration,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'dateTime', $extra + [
            'rule' => ['datetime', $formats],
        ]);
    }

    /**
     * Add a time format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::time()
     * @return $this
     */
    public function time(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a time';
            } else {
                $message = __d('cake', 'The provided value must be a time');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'time', $extra + [
            'rule' => 'time',
        ]);
    }

    /**
     * Add a localized time, date or datetime format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string $type Parser type, one out of 'date', 'time', and 'datetime'
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::localizedTime()
     * @return $this
     */
    public function localizedTime(
        string $field,
        string $type = 'datetime',
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a localized time, date or date and time';
            } else {
                $message = __d('cake', 'The provided value must be a localized time, date or date and time');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'localizedTime', $extra + [
            'rule' => ['localizedTime', $type],
        ]);
    }

    /**
     * Add a boolean validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::boolean()
     * @return $this
     */
    public function boolean(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a boolean';
            } else {
                $message = __d('cake', 'The provided value must be a boolean');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'boolean', $extra + [
            'rule' => 'boolean',
        ]);
    }

    /**
     * Add a decimal validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int|null $places The number of decimal places to require.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::decimal()
     * @return $this
     */
    public function decimal(
        string $field,
        ?int $places = null,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                if ($places === null) {
                    $message = 'The provided value must be decimal with any number of decimal places, including none';
                } else {
                    $message = sprintf('The provided value must be decimal with `%s` decimal places', $places);
                }
            } elseif ($places === null) {
                $message = __d(
                    'cake',
                    'The provided value must be decimal with any number of decimal places, including none',
                );
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be decimal with `{0}` decimal places',
                    $places,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'decimal', $extra + [
            'rule' => ['decimal', $places],
        ]);
    }

    /**
     * Add an email validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param bool $checkMX Whether to check the MX records.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::email()
     * @return $this
     */
    public function email(
        string $field,
        bool $checkMX = false,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be an e-mail address';
            } else {
                $message = __d('cake', 'The provided value must be an e-mail address');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'email', $extra + [
            'rule' => ['email', $checkMX],
        ]);
    }

    /**
     * Add a backed enum validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param class-string<\BackedEnum> $enumClassName The valid backed enum class name.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @return $this
     * @see \Cake\Validation\Validation::enum()
     * @since 5.0.3
     */
    public function enum(
        string $field,
        string $enumClassName,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if (!in_array(BackedEnum::class, (array)class_implements($enumClassName), true)) {
            throw new InvalidArgumentException(
                'The `$enumClassName` argument must be the classname of a valid backed enum.',
            );
        }

        if ($message === null) {
            $cases = array_map(fn($case) => $case->value, $enumClassName::cases());
            $caseOptions = implode('`, `', $cases);
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be one of `%s`', $caseOptions);
            } else {
                $message = __d('cake', 'The provided value must be one of `{0}`', $caseOptions);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'enum', $extra + [
            'rule' => ['enum', $enumClassName],
        ]);
    }

    /**
     * Add an IP validation rule to a field.
     *
     * This rule will accept both IPv4 and IPv6 addresses.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ip(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be an IP address';
            } else {
                $message = __d('cake', 'The provided value must be an IP address');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'ip', $extra + [
            'rule' => 'ip',
        ]);
    }

    /**
     * Add an IPv4 validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ipv4(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be an IPv4 address';
            } else {
                $message = __d('cake', 'The provided value must be an IPv4 address');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'ipv4', $extra + [
            'rule' => ['ip', 'ipv4'],
        ]);
    }

    /**
     * Add an IPv6 validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ipv6(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be an IPv6 address';
            } else {
                $message = __d('cake', 'The provided value must be an IPv6 address');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'ipv6', $extra + [
            'rule' => ['ip', 'ipv6'],
        ]);
    }

    /**
     * Add a string length validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $min The minimum length required.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::minLength()
     * @return $this
     */
    public function minLength(string $field, int $min, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be at least `%s` characters long', $min);
            } else {
                $message = __d('cake', 'The provided value must be at least `{0}` characters long', $min);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'minLength', $extra + [
            'rule' => ['minLength', $min],
        ]);
    }

    /**
     * Add a string length validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $min The minimum length required.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::minLengthBytes()
     * @return $this
     */
    public function minLengthBytes(string $field, int $min, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be at least `%s` bytes long', $min);
            } else {
                $message = __d('cake', 'The provided value must be at least `{0}` bytes long', $min);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'minLengthBytes', $extra + [
            'rule' => ['minLengthBytes', $min],
        ]);
    }

    /**
     * Add a string length validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $max The maximum length allowed.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::maxLength()
     * @return $this
     */
    public function maxLength(string $field, int $max, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be at most `%s` characters long', $max);
            } else {
                $message = __d('cake', 'The provided value must be at most `{0}` characters long', $max);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'maxLength', $extra + [
            'rule' => ['maxLength', $max],
        ]);
    }

    /**
     * Add a string length validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $max The maximum length allowed.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::maxLengthBytes()
     * @return $this
     */
    public function maxLengthBytes(string $field, int $max, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be at most `%s` bytes long', $max);
            } else {
                $message = __d('cake', 'The provided value must be at most `{0}` bytes long', $max);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'maxLengthBytes', $extra + [
            'rule' => ['maxLengthBytes', $max],
        ]);
    }

    /**
     * Add a numeric value validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numeric()
     * @return $this
     */
    public function numeric(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be numeric';
            } else {
                $message = __d('cake', 'The provided value must be numeric');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'numeric', $extra + [
            'rule' => 'numeric',
        ]);
    }

    /**
     * Add a natural number validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::naturalNumber()
     * @return $this
     */
    public function naturalNumber(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a natural number';
            } else {
                $message = __d('cake', 'The provided value must be a natural number');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'naturalNumber', $extra + [
            'rule' => ['naturalNumber', false],
        ]);
    }

    /**
     * Add a validation rule to ensure a field is a non negative integer.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::naturalNumber()
     * @return $this
     */
    public function nonNegativeInteger(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a non-negative integer';
            } else {
                $message = __d('cake', 'The provided value must be a non-negative integer');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'nonNegativeInteger', $extra + [
            'rule' => ['naturalNumber', true],
        ]);
    }

    /**
     * Add a validation rule to ensure a field is within a numeric range
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $range The inclusive upper and lower bounds of the valid range.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::range()
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function range(string $field, array $range, ?string $message = null, Closure|string|null $when = null)
    {
        if (count($range) !== 2) {
            throw new InvalidArgumentException('The $range argument requires 2 numbers');
        }
        $lowerBound = array_shift($range);
        $upperBound = array_shift($range);

        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf(
                    'The provided value must be between `%s` and `%s`, inclusively',
                    $lowerBound,
                    $upperBound,
                );
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be between `{0}` and `{1}`, inclusively',
                    $lowerBound,
                    $upperBound,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'range', $extra + [
            'rule' => ['range', $lowerBound, $upperBound],
        ]);
    }

    /**
     * Add a validation rule to ensure a field is a URL.
     *
     * This validator does not require a protocol.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::url()
     * @return $this
     */
    public function url(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a URL';
            } else {
                $message = __d('cake', 'The provided value must be a URL');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'url', $extra + [
            'rule' => ['url', false],
        ]);
    }

    /**
     * Add a validation rule to ensure a field is a URL.
     *
     * This validator requires the URL to have a protocol.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::url()
     * @return $this
     */
    public function urlWithProtocol(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a URL with protocol';
            } else {
                $message = __d('cake', 'The provided value must be a URL with protocol');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'urlWithProtocol', $extra + [
            'rule' => ['url', true],
        ]);
    }

    /**
     * Add a validation rule to ensure the field value is within an allowed list.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array $list The list of valid options.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::inList()
     * @return $this
     */
    public function inList(string $field, array $list, ?string $message = null, Closure|string|null $when = null)
    {
        $listEnumeration = implode(', ', $list);

        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must be one of: `%s`', $listEnumeration);
            } else {
                $message = __d(
                    'cake',
                    'The provided value must be one of: `{0}`',
                    $listEnumeration,
                );
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'inList', $extra + [
            'rule' => ['inList', $list],
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a UUID
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uuid()
     * @return $this
     */
    public function uuid(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a UUID';
            } else {
                $message = __d('cake', 'The provided value must be a UUID');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'uuid', $extra + [
            'rule' => 'uuid',
        ]);
    }

    /**
     * Add a validation rule to ensure the field is an uploaded file
     *
     * @param string $field The field you want to apply the rule to.
     * @param array<string, mixed> $options An array of options.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uploadedFile() For options
     * @return $this
     */
    public function uploadedFile(
        string $field,
        array $options,
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be an uploaded file';
            } else {
                $message = __d('cake', 'The provided value must be an uploaded file');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'uploadedFile', $extra + [
            'rule' => ['uploadedFile', $options],
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a lat/long tuple.
     *
     * e.g. `<lat>, <lng>`
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::geoCoordinate()
     * @return $this
     */
    public function latLong(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a latitude/longitude coordinate';
            } else {
                $message = __d('cake', 'The provided value must be a latitude/longitude coordinate');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'latLong', $extra + [
            'rule' => 'geoCoordinate',
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a latitude.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::latitude()
     * @return $this
     */
    public function latitude(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a latitude';
            } else {
                $message = __d('cake', 'The provided value must be a latitude');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'latitude', $extra + [
            'rule' => 'latitude',
        ]);
    }

    /**
     * Add a validation rule to ensure the field is a longitude.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::longitude()
     * @return $this
     */
    public function longitude(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be a longitude';
            } else {
                $message = __d('cake', 'The provided value must be a longitude');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'longitude', $extra + [
            'rule' => 'longitude',
        ]);
    }

    /**
     * Add a validation rule to ensure a field contains only ascii bytes
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ascii()
     * @return $this
     */
    public function ascii(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be ASCII bytes only';
            } else {
                $message = __d('cake', 'The provided value must be ASCII bytes only');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'ascii', $extra + [
            'rule' => 'ascii',
        ]);
    }

    /**
     * Add a validation rule to ensure a field contains only BMP utf8 bytes
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::utf8()
     * @return $this
     */
    public function utf8(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be UTF-8 bytes only';
            } else {
                $message = __d('cake', 'The provided value must be UTF-8 bytes only');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'utf8', $extra + [
            'rule' => ['utf8', ['extended' => false]],
        ]);
    }

    /**
     * Add a validation rule to ensure a field contains only utf8 bytes.
     *
     * This rule will accept 3 and 4 byte UTF8 sequences, which are necessary for emoji.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::utf8()
     * @return $this
     */
    public function utf8Extended(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be 3 and 4 byte UTF-8 sequences only';
            } else {
                $message = __d('cake', 'The provided value must be 3 and 4 byte UTF-8 sequences only');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'utf8Extended', $extra + [
            'rule' => ['utf8', ['extended' => true]],
        ]);
    }

    /**
     * Add a validation rule to ensure a field is an integer value.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isInteger()
     * @return $this
     */
    public function integer(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be an integer';
            } else {
                $message = __d('cake', 'The provided value must be an integer');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'integer', $extra + [
            'rule' => 'isInteger',
        ]);
    }

    /**
     * Add a validation rule to ensure that a field contains an array.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isArray()
     * @return $this
     */
    public function array(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = 'The provided value must be an array';
            } else {
                $message = __d('cake', 'The provided value must be an array');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'array', $extra + [
            'rule' => 'isArray',
        ]);
    }

    /**
     * Add a validation rule to ensure that a field contains a scalar.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isScalar()
     * @return $this
     */
    public function scalar(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            $message = 'The provided value must be scalar';
            if ($this->_useI18n) {
                $message = __d('cake', 'The provided value must be scalar');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'scalar', $extra + [
                'rule' => 'isScalar',
            ]);
    }

    /**
     * Add a validation rule to ensure a field is a 6 digits hex color value.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::hexColor()
     * @return $this
     */
    public function hexColor(string $field, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            $message = 'The provided value must be a hex color';
            if ($this->_useI18n) {
                $message = __d('cake', 'The provided value must be a hex color');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'hexColor', $extra + [
            'rule' => 'hexColor',
        ]);
    }

    /**
     * Add a validation rule for a multiple select. Comparison is case sensitive by default.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array<string, mixed> $options The options for the validator. Includes the options defined in
     *   \Cake\Validation\Validation::multiple() and the `caseInsensitive` parameter.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::multiple()
     * @return $this
     */
    public function multipleOptions(
        string $field,
        array $options = [],
        ?string $message = null,
        Closure|string|null $when = null,
    ) {
        if ($message === null) {
            $message = 'The provided value must be a set of multiple options';
            if ($this->_useI18n) {
                $message = __d('cake', 'The provided value must be a set of multiple options');
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);
        $caseInsensitive = $options['caseInsensitive'] ?? false;
        unset($options['caseInsensitive']);

        return $this->add($field, 'multipleOptions', $extra + [
            'rule' => ['multiple', $options, $caseInsensitive],
        ]);
    }

    /**
     * Add a validation rule to ensure that a field is an array containing at least
     * the specified amount of elements
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $count The number of elements the array should at least have
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numElements()
     * @return $this
     */
    public function hasAtLeast(string $field, int $count, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must have at least `%s` elements', $count);
            } else {
                $message = __d('cake', 'The provided value must have at least `{0}` elements', $count);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'hasAtLeast', $extra + [
            'rule' => function ($value) use ($count) {
                if (is_array($value) && isset($value['_ids'])) {
                    $value = $value['_ids'];
                }

                return Validation::numElements($value, Validation::COMPARE_GREATER_OR_EQUAL, $count);
            },
        ]);
    }

    /**
     * Add a validation rule to ensure that a field is an array containing at most
     * the specified amount of elements
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $count The number maximum amount of elements the field should have
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numElements()
     * @return $this
     */
    public function hasAtMost(string $field, int $count, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must have at most `%s` elements', $count);
            } else {
                $message = __d('cake', 'The provided value must have at most `{0}` elements', $count);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'hasAtMost', $extra + [
            'rule' => function ($value) use ($count) {
                if (is_array($value) && isset($value['_ids'])) {
                    $value = $value['_ids'];
                }

                return Validation::numElements($value, Validation::COMPARE_LESS_OR_EQUAL, $count);
            },
        ]);
    }

    /**
     * Returns whether a field can be left empty for a new or already existing
     * record.
     *
     * @param string $field Field name.
     * @param bool $newRecord whether the data to be validated is new or to be updated.
     * @return bool
     */
    public function isEmptyAllowed(string $field, bool $newRecord): bool
    {
        $providers = $this->_providers;
        $data = [];
        $context = compact('data', 'newRecord', 'field', 'providers');

        return $this->_canBeEmpty($this->field($field), $context);
    }

    /**
     * Returns whether a field can be left out for a new or already existing
     * record.
     *
     * @param string $field Field name.
     * @param bool $newRecord Whether the data to be validated is new or to be updated.
     * @return bool
     */
    public function isPresenceRequired(string $field, bool $newRecord): bool
    {
        $providers = $this->_providers;
        $data = [];
        $context = compact('data', 'newRecord', 'field', 'providers');

        return !$this->_checkPresence($this->field($field), $context);
    }

    /**
     * Returns whether a field matches against a regular expression.
     *
     * @param string $field Field name.
     * @param string $regex Regular expression.
     * @param string|null $message The error message when the rule fails.
     * @param \Closure|string|null $when Either 'create' or 'update' or a Closure that returns
     *   true when the validation rule should be applied.
     * @return $this
     */
    public function regex(string $field, string $regex, ?string $message = null, Closure|string|null $when = null)
    {
        if ($message === null) {
            if (!$this->_useI18n) {
                $message = sprintf('The provided value must match against the pattern `%s`', $regex);
            } else {
                $message = __d('cake', 'The provided value must match against the pattern `{0}`', $regex);
            }
        }

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'regex', $extra + [
            'rule' => ['custom', $regex],
        ]);
    }

    /**
     * Gets the required message for a field
     *
     * @param string $field Field name
     * @return string|null
     */
    public function getRequiredMessage(string $field): ?string
    {
        if (!isset($this->_fields[$field])) {
            return null;
        }

        if (isset($this->_presenceMessages[$field])) {
            return $this->_presenceMessages[$field];
        }

        if (!$this->_useI18n) {
            return 'This field is required';
        }

        return __d('cake', 'This field is required');
    }

    /**
     * Gets the notEmpty message for a field
     *
     * @param string $field Field name
     * @return string|null
     */
    public function getNotEmptyMessage(string $field): ?string
    {
        if (!isset($this->_fields[$field])) {
            return null;
        }

        foreach ($this->_fields[$field] as $rule) {
            if ($rule->get('rule') === 'notBlank' && $rule->get('message')) {
                return $rule->get('message');
            }
        }

        if (isset($this->_allowEmptyMessages[$field])) {
            return $this->_allowEmptyMessages[$field];
        }

        if (!$this->_useI18n) {
            return 'This field cannot be left empty';
        }

        return __d('cake', 'This field cannot be left empty');
    }

    /**
     * Returns false if any validation for the passed rule set should be stopped
     * due to the field missing in the data array
     *
     * @param \Cake\Validation\ValidationSet $field The set of rules for a field.
     * @param array<string, mixed> $context A key value list of data containing the validation context.
     * @return bool
     */
    protected function _checkPresence(ValidationSet $field, array $context): bool
    {
        $required = $field->isPresenceRequired();

        if ($required instanceof Closure) {
            return !$required($context);
        }

        $newRecord = $context['newRecord'];
        if (in_array($required, [static::WHEN_CREATE, static::WHEN_UPDATE], true)) {
            return ($required === static::WHEN_CREATE && !$newRecord) ||
                ($required === static::WHEN_UPDATE && $newRecord);
        }

        return !$required;
    }

    /**
     * Returns whether the field can be left blank according to `allowEmpty`
     *
     * @param \Cake\Validation\ValidationSet $field the set of rules for a field
     * @param array<string, mixed> $context a key value list of data containing the validation context.
     * @return bool
     */
    protected function _canBeEmpty(ValidationSet $field, array $context): bool
    {
        $allowed = $field->isEmptyAllowed();

        if ($allowed instanceof Closure) {
            return $allowed($context);
        }

        $newRecord = $context['newRecord'];
        if (in_array($allowed, [static::WHEN_CREATE, static::WHEN_UPDATE], true)) {
            $allowed = ($allowed === static::WHEN_CREATE && $newRecord) ||
                ($allowed === static::WHEN_UPDATE && !$newRecord);
        }

        return (bool)$allowed;
    }

    /**
     * Returns true if the field is empty in the passed data array
     *
     * @param mixed $data Value to check against.
     * @param int $flags A bitmask of EMPTY_* flags which specify what is empty
     * @return bool
     */
    protected function isEmpty(mixed $data, int $flags): bool
    {
        if ($data === null) {
            return true;
        }

        if ($data === '' && ($flags & self::EMPTY_STRING)) {
            return true;
        }

        $arrayTypes = self::EMPTY_ARRAY | self::EMPTY_DATE | self::EMPTY_TIME;
        if ($data === [] && ($flags & $arrayTypes)) {
            return true;
        }

        if (is_array($data)) {
            $allFieldsAreEmpty = true;
            foreach ($data as $field) {
                if ($field !== null && $field !== '') {
                    $allFieldsAreEmpty = false;
                    break;
                }
            }

            if ($allFieldsAreEmpty) {
                if (($flags & self::EMPTY_DATE) && isset($data['year'])) {
                    return true;
                }

                if (($flags & self::EMPTY_TIME) && isset($data['hour'])) {
                    return true;
                }
            }
        }

        if (
            ($flags & self::EMPTY_FILE)
            && $data instanceof UploadedFileInterface
            && $data->getError() === UPLOAD_ERR_NO_FILE
        ) {
            return true;
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
     * @return array<string, mixed>
     */
    protected function _processRules(string $field, ValidationSet $rules, array $data, bool $newRecord): array
    {
        $errors = [];

        if (!$this->_useI18n) {
            $message = 'The provided value is invalid';
        } else {
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
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
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
            '_allowEmptyFlags' => $this->_allowEmptyFlags,
            '_useI18n' => $this->_useI18n,
            '_stopOnFailure' => $this->_stopOnFailure,
            '_providers' => array_keys($this->_providers),
            '_fields' => $fields,
        ];
    }
}
