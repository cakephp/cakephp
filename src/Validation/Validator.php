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
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\UploadedFileInterface;
use Traversable;
use function Cake\Core\deprecationWarning;
use function Cake\Core\getTypeName;
use function Cake\I18n\__d;

/**
 * Validator object encapsulates all methods related to data validations for a model
 * It also provides an API to dynamically change validation rules for each model field.
 *
 * Implements ArrayAccess to easily modify rules in the set
 *
 * @link https://book.cakephp.org/4/en/core-libraries/validation.html
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
     * When an array is given, if it has at least the `name`, `type`, `tmp_name` and `error` keys,
     * and the value of `error` is equal to `UPLOAD_ERR_NO_FILE`, the value will be recognized as
     * empty.
     *
     * When an instance of \Psr\Http\Message\UploadedFileInterface is given the
     * return value of it's getError() method must be equal to `UPLOAD_ERR_NO_FILE`.
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
    protected $_fields = [];

    /**
     * An associative array of objects or classes containing methods
     * used for validation
     *
     * @var array<string, object|string>
     * @psalm-var array<string, object|class-string>
     */
    protected $_providers = [];

    /**
     * An associative array of objects or classes used as a default provider list
     *
     * @var array<string, object|string>
     * @psalm-var array<string, object|class-string>
     */
    protected static $_defaultProviders = [];

    /**
     * Contains the validation messages associated with checking the presence
     * for each corresponding field.
     *
     * @var array<string, string>
     */
    protected $_presenceMessages = [];

    /**
     * Whether to use I18n functions for translating default error messages
     *
     * @var bool
     */
    protected $_useI18n = false;

    /**
     * Contains the validation messages associated with checking the emptiness
     * for each corresponding field.
     *
     * @var array<string, string>
     */
    protected $_allowEmptyMessages = [];

    /**
     * Contains the flags which specify what is empty for each corresponding field.
     *
     * @var array<string, int>
     */
    protected $_allowEmptyFlags = [];

    /**
     * Whether to apply last flag to generated rule(s).
     *
     * @var bool
     */
    protected $_stopOnFailure = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_useI18n = function_exists('\Cake\I18n\__d');
        $this->_providers = self::$_defaultProviders;
    }

    /**
     * Whether to stop validation rule evaluation on the first failed rule.
     *
     * When enabled the first failing rule per field will cause validation to stop.
     * When disabled all rules will be run even if there are failures.
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
     * @deprecated 3.9.0 Renamed to {@link validate()}.
     */
    public function errors(array $data, bool $newRecord = true): array
    {
        deprecationWarning('`Validator::errors()` is deprecated. Use `Validator::validate()` instead.');

        return $this->validate($data, $newRecord);
    }

    /**
     * Validates and returns an array of failed fields and their error messages.
     *
     * @param array<string|int, mixed> $data The data to be checked for errors
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
     * @param string $name [optional] The fieldname to fetch.
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
     * @psalm-param object|class-string $object
     * @return $this
     */
    public function setProvider(string $name, $object)
    {
        if (!is_string($object) && !is_object($object)) {
            deprecationWarning(sprintf(
                'The provider must be an object or class name string. Got `%s` instead.',
                getTypeName($object)
            ));
        }

        $this->_providers[$name] = $object;

        return $this;
    }

    /**
     * Returns the provider stored under that name if it exists.
     *
     * @param string $name The name under which the provider should be set.
     * @return object|string|null
     * @psalm-return object|class-string|null
     */
    public function getProvider(string $name)
    {
        if (isset($this->_providers[$name])) {
            return $this->_providers[$name];
        }
        if ($name !== 'default') {
            return null;
        }

        $this->_providers[$name] = new RulesProvider();

        return $this->_providers[$name];
    }

    /**
     * Returns the default provider stored under that name if it exists.
     *
     * @param string $name The name under which the provider should be retrieved.
     * @return object|string|null
     * @psalm-return object|class-string|null
     */
    public static function getDefaultProvider(string $name)
    {
        return self::$_defaultProviders[$name] ?? null;
    }

    /**
     * Associates an object to a name so it can be used as a default provider.
     *
     * @param string $name The name under which the provider should be set.
     * @param object|string $object Provider object or class name.
     * @psalm-param object|class-string $object
     * @return void
     */
    public static function addDefaultProvider(string $name, $object): void
    {
        if (!is_string($object) && !is_object($object)) {
            deprecationWarning(sprintf(
                'The provider must be an object or class name string. Got `%s` instead.',
                getTypeName($object)
            ));
        }

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
    public function offsetExists($field): bool
    {
        return isset($this->_fields[$field]);
    }

    /**
     * Returns the rule set for a field
     *
     * @param string|int $field name of the field to check
     * @return \Cake\Validation\ValidationSet
     */
    public function offsetGet($field): ValidationSet
    {
        return $this->field((string)$field);
    }

    /**
     * Sets the rule set for a field
     *
     * @param string $field name of the field to set
     * @param \Cake\Validation\ValidationSet|array $rules set of rules to apply to field
     * @return void
     */
    public function offsetSet($field, $rules): void
    {
        if (!$rules instanceof ValidationSet) {
            $set = new ValidationSet();
            foreach ($rules as $name => $rule) {
                $set->add($name, $rule);
            }
            $rules = $set;
        }
        $this->_fields[$field] = $rules;
    }

    /**
     * Unsets the rule set for a field
     *
     * @param string $field name of the field to unset
     * @return void
     */
    public function offsetUnset($field): void
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
    public function add(string $field, $name, $rule = [])
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
                /** @psalm-suppress PossiblyUndefinedMethod */
                $name = $rule['rule'];
                if (is_array($name)) {
                    $name = array_shift($name);
                }

                if ($validationSet->offsetExists($name)) {
                    $message = 'You cannot add a rule without a unique name, already existing rule found: ' . $name;
                    throw new InvalidArgumentException($message);
                }

                deprecationWarning(
                    'Adding validation rules without a name key is deprecated. Update rules array to have string keys.'
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @return $this
     */
    public function addNested(string $field, Validator $validator, ?string $message = null, $when = null)
    {
        $extra = array_filter(['message' => $message, 'on' => $when]);

        $validationSet = $this->field($field);
        $validationSet->add(static::NESTED, $extra + ['rule' => function ($value, $context) use ($validator, $message) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($this->providers() as $provider) {
                /** @psalm-suppress PossiblyNullArgument */
                $validator->setProvider($provider, $this->getProvider($provider));
            }
            $errors = $validator->validate($value, $context['newRecord']);

            $message = $message ? [static::NESTED => $message] : [];

            return empty($errors) ? true : $errors + $message;
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @return $this
     */
    public function addNestedMany(string $field, Validator $validator, ?string $message = null, $when = null)
    {
        $extra = array_filter(['message' => $message, 'on' => $when]);

        $validationSet = $this->field($field);
        $validationSet->add(static::NESTED, $extra + ['rule' => function ($value, $context) use ($validator, $message) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($this->providers() as $provider) {
                /** @psalm-suppress PossiblyNullArgument */
                $validator->setProvider($provider, $this->getProvider($provider));
            }
            $errors = [];
            foreach ($value as $i => $row) {
                if (!is_array($row)) {
                    return false;
                }
                $check = $validator->validate($row, $context['newRecord']);
                if (!empty($check)) {
                    $errors[$i] = $check;
                }
            }

            $message = $message ? [static::NESTED => $message] : [];

            return empty($errors) ? true : $errors + $message;
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
     * @param callable|string|bool $mode Valid values are true, false, 'create', 'update'.
     *   If a callable is passed then the field will be required only when the callback
     *   returns true.
     * @param string|null $message The message to show if the field presence validation fails.
     * @return $this
     */
    public function requirePresence($field, $mode = true, ?string $message = null)
    {
        $defaults = [
            'mode' => $mode,
            'message' => $message,
        ];

        if (!is_array($field)) {
            $field = $this->_convertValidatorToArray($field, $defaults);
        }

        foreach ($field as $fieldName => $setting) {
            $settings = $this->_convertValidatorToArray($fieldName, $defaults, $setting);
            $fieldName = current(array_keys($settings));

            $this->field((string)$fieldName)->requirePresence($settings[$fieldName]['mode']);
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
     * $validator->allowEmpty('email', Validator::WHEN_CREATE);
     *
     * // Email can be empty on update
     * $validator->allowEmpty('email', Validator::WHEN_UPDATE);
     *
     * // Email and subject can be empty on update
     * $validator->allowEmpty(['email', 'subject'], Validator::WHEN_UPDATE;
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
     *      Validator::WHEN_UPDATE
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
     * @deprecated 3.7.0 Use {@link allowEmptyString()}, {@link allowEmptyArray()}, {@link allowEmptyFile()},
     *   {@link allowEmptyDate()}, {@link allowEmptyTime()}, {@link allowEmptyDateTime()} or {@link allowEmptyFor()} instead.
     * @param array|string $field the name of the field or a list of fields
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true (always), 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @param string|null $message The message to show if the field is not
     * @return $this
     */
    public function allowEmpty($field, $when = true, $message = null)
    {
        deprecationWarning(
            'allowEmpty() is deprecated. '
            . 'Use allowEmptyString(), allowEmptyArray(), allowEmptyFile(), allowEmptyDate(), allowEmptyTime(), '
            . 'allowEmptyDateTime() or allowEmptyFor() instead.'
        );

        $defaults = [
            'when' => $when,
            'message' => $message,
        ];
        if (!is_array($field)) {
            $field = $this->_convertValidatorToArray($field, $defaults);
        }

        foreach ($field as $fieldName => $setting) {
            $settings = $this->_convertValidatorToArray($fieldName, $defaults, $setting);
            $fieldName = array_keys($settings)[0];
            $this->allowEmptyFor(
                $fieldName,
                static::EMPTY_ALL,
                $settings[$fieldName]['when'],
                $settings[$fieldName]['message']
            );
        }

        return $this;
    }

    /**
     * Low-level method to indicate that a field can be empty.
     *
     * This method should generally not be used and instead you should
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
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @param string|null $message The message to show if the field is not
     * @since 3.7.0
     * @return $this
     */
    public function allowEmptyFor(string $field, ?int $flags = null, $when = true, ?string $message = null)
    {
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
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyFor() For detail usage
     */
    public function allowEmptyString(string $field, ?string $message = null, $when = true)
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
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   callable is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyString()
     * @since 3.8.0
     */
    public function notEmptyString(string $field, ?string $message = null, $when = false)
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
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples.
     */
    public function allowEmptyArray(string $field, ?string $message = null, $when = true)
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
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   callable is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyArray()
     */
    public function notEmptyArray(string $field, ?string $message = null, $when = false)
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
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     *   Valid values are true, 'create', 'update'. If a callable is passed then
     *   the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() For detail usage
     */
    public function allowEmptyFile(string $field, ?string $message = null, $when = true)
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
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   callable is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @since 3.8.0
     * @see \Cake\Validation\Validator::allowEmptyFile()
     */
    public function notEmptyFile(string $field, ?string $message = null, $when = false)
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
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples
     */
    public function allowEmptyDate(string $field, ?string $message = null, $when = true)
    {
        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_DATE, $when, $message);
    }

    /**
     * Require a non-empty date value
     *
     * @param string $field The name of the field.
     * @param string|null $message The message to show if the field is empty.
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   callable is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @see \Cake\Validation\Validator::allowEmptyDate() for examples
     */
    public function notEmptyDate(string $field, ?string $message = null, $when = false)
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
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     * Valid values are true, false, 'create', 'update'. If a callable is passed then
     * the field will allowed to be empty only when the callback returns true.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples.
     */
    public function allowEmptyTime(string $field, ?string $message = null, $when = true)
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
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   callable is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @since 3.8.0
     * @see \Cake\Validation\Validator::allowEmptyTime()
     */
    public function notEmptyTime(string $field, ?string $message = null, $when = false)
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
     * @param callable|string|bool $when Indicates when the field is allowed to be empty
     *   Valid values are true, false, 'create', 'update'. If a callable is passed then
     *   the field will allowed to be empty only when the callback returns false.
     * @return $this
     * @since 3.7.0
     * @see \Cake\Validation\Validator::allowEmptyFor() for examples.
     */
    public function allowEmptyDateTime(string $field, ?string $message = null, $when = true)
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
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are false (never), 'create', 'update'. If a
     *   callable is passed then the field will be required to be not empty when
     *   the callback returns true.
     * @return $this
     * @since 3.8.0
     * @see \Cake\Validation\Validator::allowEmptyDateTime()
     */
    public function notEmptyDateTime(string $field, ?string $message = null, $when = false)
    {
        $when = $this->invertWhenClause($when);

        return $this->allowEmptyFor($field, self::EMPTY_STRING | self::EMPTY_DATE | self::EMPTY_TIME, $when, $message);
    }

    /**
     * Converts validator to fieldName => $settings array
     *
     * @param string|int $fieldName name of field
     * @param array<string, mixed> $defaults default settings
     * @param array<string|int, mixed>|string $settings settings from data
     * @return array<array>
     * @throws \InvalidArgumentException
     */
    protected function _convertValidatorToArray($fieldName, array $defaults = [], $settings = []): array
    {
        if (is_int($settings)) {
            $settings = (string)$settings;
        }
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
     * $validator->notEmpty('email', $message, Validator::WHEN_UPDATE);
     *
     * // Email and title can be empty on create, but are required on update.
     * $validator->notEmpty(['email', 'title'], $message, Validator::WHEN_UPDATE);
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
     *      Validator::WHEN_UPDATE
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
     * @deprecated 3.7.0 Use {@link notEmptyString()}, {@link notEmptyArray()}, {@link notEmptyFile()},
     *   {@link notEmptyDate()}, {@link notEmptyTime()} or {@link notEmptyDateTime()} instead.
     * @param array<string|int, mixed>|string $field the name of the field or list of fields
     * @param string|null $message The message to show if the field is not
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are true (always), 'create', 'update'. If a
     *   callable is passed then the field will allowed to be empty only when
     *   the callback returns false.
     * @return $this
     */
    public function notEmpty($field, ?string $message = null, $when = false)
    {
        deprecationWarning(
            'notEmpty() is deprecated. '
            . 'Use notEmptyString(), notEmptyArray(), notEmptyFile(), notEmptyDate(), notEmptyTime() '
            . 'or notEmptyDateTime() instead.'
        );

        $defaults = [
            'when' => $when,
            'message' => $message,
        ];

        if (!is_array($field)) {
            $field = $this->_convertValidatorToArray($field, $defaults);
        }

        foreach ($field as $fieldName => $setting) {
            $settings = $this->_convertValidatorToArray($fieldName, $defaults, $setting);
            $fieldName = current(array_keys($settings));

            $whenSetting = $this->invertWhenClause($settings[$fieldName]['when']);

            $this->field((string)$fieldName)->allowEmpty($whenSetting);
            $this->_allowEmptyFlags[$fieldName] = static::EMPTY_ALL;
            if ($settings[$fieldName]['message']) {
                $this->_allowEmptyMessages[$fieldName] = $settings[$fieldName]['message'];
            }
        }

        return $this;
    }

    /**
     * Invert a when clause for creating notEmpty rules
     *
     * @param callable|string|bool $when Indicates when the field is not allowed
     *   to be empty. Valid values are true (always), 'create', 'update'. If a
     *   callable is passed then the field will allowed to be empty only when
     *   the callback returns false.
     * @return callable|string|bool
     */
    protected function invertWhenClause($when)
    {
        if ($when === static::WHEN_CREATE || $when === static::WHEN_UPDATE) {
            return $when === static::WHEN_CREATE ? static::WHEN_UPDATE : static::WHEN_CREATE;
        }
        if (is_callable($when)) {
            return function ($context) use ($when) {
                return !$when($context);
            };
        }

        return $when;
    }

    /**
     * Add a notBlank rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::notBlank()
     * @return $this
     */
    public function notBlank(string $field, ?string $message = null, $when = null)
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::alphaNumeric()
     * @return $this
     */
    public function alphaNumeric(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::notAlphaNumeric()
     * @return $this
     */
    public function notAlphaNumeric(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::asciiAlphaNumeric()
     * @return $this
     */
    public function asciiAlphaNumeric(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::notAlphaNumeric()
     * @return $this
     */
    public function notAsciiAlphaNumeric(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::alphaNumeric()
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function lengthBetween(string $field, array $range, ?string $message = null, $when = null)
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::creditCard()
     * @return $this
     */
    public function creditCard(string $field, string $type = 'all', ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function greaterThan(string $field, $value, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function greaterThanOrEqual(string $field, $value, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function lessThan(string $field, $value, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function lessThanOrEqual(string $field, $value, ?string $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'lessThanOrEqual', $extra + [
            'rule' => ['comparison', Validation::COMPARE_LESS_OR_EQUAL, $value],
        ]);
    }

    /**
     * Add a equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param float|int $value The value user data must be equal to.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function equals(string $field, $value, ?string $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'equals', $extra + [
            'rule' => ['comparison', Validation::COMPARE_EQUAL, $value],
        ]);
    }

    /**
     * Add a not equal to comparison rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param float|int $value The value user data must be not be equal to.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::comparison()
     * @return $this
     */
    public function notEquals(string $field, $value, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     */
    public function sameAs(string $field, string $secondField, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function notSameAs(string $field, string $secondField, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function equalToField(string $field, string $secondField, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function notEqualToField(string $field, string $secondField, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function greaterThanField(string $field, string $secondField, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function greaterThanOrEqualToField(string $field, string $secondField, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function lessThanField(string $field, string $secondField, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::compareFields()
     * @return $this
     * @since 3.6.0
     */
    public function lessThanOrEqualToField(string $field, string $secondField, ?string $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'lessThanOrEqualToField', $extra + [
            'rule' => ['compareFields', $secondField, Validation::COMPARE_LESS_OR_EQUAL],
        ]);
    }

    /**
     * Add a rule to check if a field contains non alpha numeric characters.
     *
     * @param string $field The field you want to apply the rule to.
     * @param int $limit The minimum number of non-alphanumeric fields required.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::containsNonAlphaNumeric()
     * @return $this
     * @deprecated 4.0.0 Use {@link notAlphaNumeric()} instead. Will be removed in 5.0
     */
    public function containsNonAlphaNumeric(string $field, int $limit = 1, ?string $message = null, $when = null)
    {
        deprecationWarning('Validator::containsNonAlphaNumeric() is deprecated. Use notAlphaNumeric() instead.');
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'containsNonAlphaNumeric', $extra + [
            'rule' => ['containsNonAlphaNumeric', $limit],
        ]);
    }

    /**
     * Add a date format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array<string> $formats A list of accepted date formats.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::date()
     * @return $this
     */
    public function date(string $field, array $formats = ['ymd'], ?string $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'date', $extra + [
            'rule' => ['date', $formats],
        ]);
    }

    /**
     * Add a date time format validation rule to a field.
     *
     * @param string $field The field you want to apply the rule to.
     * @param array<string> $formats A list of accepted date formats.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::datetime()
     * @return $this
     */
    public function dateTime(string $field, array $formats = ['ymd'], ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::time()
     * @return $this
     */
    public function time(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::localizedTime()
     * @return $this
     */
    public function localizedTime(string $field, string $type = 'datetime', ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::boolean()
     * @return $this
     */
    public function boolean(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::decimal()
     * @return $this
     */
    public function decimal(string $field, ?int $places = null, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::email()
     * @return $this
     */
    public function email(string $field, bool $checkMX = false, ?string $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'email', $extra + [
            'rule' => ['email', $checkMX],
        ]);
    }

    /**
     * Add an IP validation rule to a field.
     *
     * This rule will accept both IPv4 and IPv6 addresses.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ip(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ipv4(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ip()
     * @return $this
     */
    public function ipv6(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::minLength()
     * @return $this
     */
    public function minLength(string $field, int $min, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::minLengthBytes()
     * @return $this
     */
    public function minLengthBytes(string $field, int $min, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::maxLength()
     * @return $this
     */
    public function maxLength(string $field, int $max, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::maxLengthBytes()
     * @return $this
     */
    public function maxLengthBytes(string $field, int $max, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numeric()
     * @return $this
     */
    public function numeric(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::naturalNumber()
     * @return $this
     */
    public function naturalNumber(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::naturalNumber()
     * @return $this
     */
    public function nonNegativeInteger(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::range()
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function range(string $field, array $range, ?string $message = null, $when = null)
    {
        if (count($range) !== 2) {
            throw new InvalidArgumentException('The $range argument requires 2 numbers');
        }
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'range', $extra + [
            'rule' => ['range', array_shift($range), array_shift($range)],
        ]);
    }

    /**
     * Add a validation rule to ensure a field is a URL.
     *
     * This validator does not require a protocol.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::url()
     * @return $this
     */
    public function url(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::url()
     * @return $this
     */
    public function urlWithProtocol(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::inList()
     * @return $this
     */
    public function inList(string $field, array $list, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uuid()
     * @return $this
     */
    public function uuid(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uploadedFile() For options
     * @return $this
     */
    public function uploadedFile(string $field, array $options, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::uuid()
     * @return $this
     */
    public function latLong(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::latitude()
     * @return $this
     */
    public function latitude(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::longitude()
     * @return $this
     */
    public function longitude(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::ascii()
     * @return $this
     */
    public function ascii(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::utf8()
     * @return $this
     */
    public function utf8(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::utf8()
     * @return $this
     */
    public function utf8Extended(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isInteger()
     * @return $this
     */
    public function integer(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isArray()
     * @return $this
     */
    public function array(string $field, ?string $message = null, $when = null)
    {
        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'array', $extra + [
            'rule' => 'isArray',
        ]);
    }

    /**
     * Add a validation rule to ensure that a field contains an array.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isArray()
     * @return $this
     * @deprecated 4.5.0 Use Validator::array() instead.
     */
    public function isArray(string $field, ?string $message = null, $when = null)
    {
        deprecationWarning('`Validator::isArray()` is deprecated, use `Validator::array()` instead');

        $extra = array_filter(['on' => $when, 'message' => $message]);

        return $this->add($field, 'isArray', $extra + [
                'rule' => 'isArray',
            ]);
    }

    /**
     * Add a validation rule to ensure that a field contains a scalar.
     *
     * @param string $field The field you want to apply the rule to.
     * @param string|null $message The error message when the rule fails.
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::isScalar()
     * @return $this
     */
    public function scalar(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::hexColor()
     * @return $this
     */
    public function hexColor(string $field, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::multiple()
     * @return $this
     */
    public function multipleOptions(string $field, array $options = [], ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numElements()
     * @return $this
     */
    public function hasAtLeast(string $field, int $count, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @see \Cake\Validation\Validation::numElements()
     * @return $this
     */
    public function hasAtMost(string $field, int $count, ?string $message = null, $when = null)
    {
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
     * @param callable|string|null $when Either 'create' or 'update' or a callable that returns
     *   true when the validation rule should be applied.
     * @return $this
     */
    public function regex(string $field, string $regex, ?string $message = null, $when = null)
    {
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

        $defaultMessage = 'This field is required';
        if ($this->_useI18n) {
            $defaultMessage = __d('cake', 'This field is required');
        }

        return $this->_presenceMessages[$field] ?? $defaultMessage;
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

        $defaultMessage = 'This field cannot be left empty';
        if ($this->_useI18n) {
            $defaultMessage = __d('cake', 'This field cannot be left empty');
        }

        foreach ($this->_fields[$field] as $rule) {
            if ($rule->get('rule') === 'notBlank' && $rule->get('message')) {
                return $rule->get('message');
            }
        }

        return $this->_allowEmptyMessages[$field] ?? $defaultMessage;
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

        if (!is_string($required) && is_callable($required)) {
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

        if (!is_string($allowed) && is_callable($allowed)) {
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
     * @return bool
     * @deprecated 3.7.0 Use {@link isEmpty()} instead
     */
    protected function _fieldIsEmpty($data): bool
    {
        return $this->isEmpty($data, static::EMPTY_ALL);
    }

    /**
     * Returns true if the field is empty in the passed data array
     *
     * @param mixed $data Value to check against.
     * @param int $flags A bitmask of EMPTY_* flags which specify what is empty
     * @return bool
     */
    protected function isEmpty($data, int $flags): bool
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
            if (
                ($flags & self::EMPTY_FILE)
                && isset($data['name'], $data['type'], $data['tmp_name'], $data['error'])
                && (int)$data['error'] === UPLOAD_ERR_NO_FILE
            ) {
                return true;
            }

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
        // Loading default provider in case there is none
        $this->getProvider('default');
        $message = 'The provided value is invalid';

        if ($this->_useI18n) {
            $message = __d('cake', 'The provided value is invalid');
        }

        /**
         * @var \Cake\Validation\ValidationRule $rule
         */
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
