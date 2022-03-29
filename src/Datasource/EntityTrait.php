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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Collection\Collection;
use Cake\ORM\Entity;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Traversable;

/**
 * An entity represents a single result row from a repository. It exposes the
 * methods for retrieving and storing fields associated in this row.
 */
trait EntityTrait
{
    /**
     * Holds all fields and their values for this entity.
     *
     * @var array<string, mixed>
     */
    protected $_fields = [];

    /**
     * Holds all fields that have been changed and their original values for this entity.
     *
     * @var array<string, mixed>
     */
    protected $_original = [];

    /**
     * List of field names that should **not** be included in JSON or Array
     * representations of this Entity.
     *
     * @var array<string>
     */
    protected $_hidden = [];

    /**
     * List of computed or virtual fields that **should** be included in JSON or array
     * representations of this Entity. If a field is present in both _hidden and _virtual
     * the field will **not** be in the array/JSON versions of the entity.
     *
     * @var array<string>
     */
    protected $_virtual = [];

    /**
     * Holds a list of the fields that were modified or added after this object
     * was originally created.
     *
     * @var array<bool>
     */
    protected $_dirty = [];

    /**
     * Holds a cached list of getters/setters per class
     *
     * @var array<string, array<string, array<string, string>>>
     */
    protected static $_accessors = [];

    /**
     * Indicates whether this entity is yet to be persisted.
     * Entities default to assuming they are new. You can use Table::persisted()
     * to set the new flag on an entity based on records in the database.
     *
     * @var bool
     */
    protected $_new = true;

    /**
     * List of errors per field as stored in this object.
     *
     * @var array<string, mixed>
     */
    protected $_errors = [];

    /**
     * List of invalid fields and their data for errors upon validation/patching.
     *
     * @var array<string, mixed>
     */
    protected $_invalid = [];

    /**
     * Map of fields in this entity that can be safely assigned, each
     * field name points to a boolean indicating its status. An empty array
     * means no fields are accessible
     *
     * The special field '\*' can also be mapped, meaning that any other field
     * not defined in the map will take its value. For example, `'*' => true`
     * means that any field not defined in the map will be accessible by default
     *
     * @var array<bool>
     */
    protected $_accessible = ['*' => true];

    /**
     * The alias of the repository this entity came from
     *
     * @var string
     */
    protected $_registryAlias = '';

    /**
     * Magic getter to access fields that have been set in this entity
     *
     * @param string $field Name of the field to access
     * @return mixed
     */
    public function &__get(string $field)
    {
        return $this->get($field);
    }

    /**
     * Magic setter to add or edit a field in this entity
     *
     * @param string $field The name of the field to set
     * @param mixed $value The value to set to the field
     * @return void
     */
    public function __set(string $field, $value): void
    {
        $this->set($field, $value);
    }

    /**
     * Returns whether this entity contains a field named $field
     * and is not set to null.
     *
     * @param string $field The field to check.
     * @return bool
     * @see \Cake\ORM\Entity::has()
     */
    public function __isset(string $field): bool
    {
        return $this->has($field);
    }

    /**
     * Removes a field from this entity
     *
     * @param string $field The field to unset
     * @return void
     */
    public function __unset(string $field): void
    {
        $this->unset($field);
    }

    /**
     * Sets a single field inside this entity.
     *
     * ### Example:
     *
     * ```
     * $entity->set('name', 'Andrew');
     * ```
     *
     * It is also possible to mass-assign multiple fields to this entity
     * with one call by passing a hashed array as fields in the form of
     * field => value pairs
     *
     * ### Example:
     *
     * ```
     * $entity->set(['name' => 'andrew', 'id' => 1]);
     * echo $entity->name // prints andrew
     * echo $entity->id // prints 1
     * ```
     *
     * Some times it is handy to bypass setter functions in this entity when assigning
     * fields. You can achieve this by disabling the `setter` option using the
     * `$options` parameter:
     *
     * ```
     * $entity->set('name', 'Andrew', ['setter' => false]);
     * $entity->set(['name' => 'Andrew', 'id' => 1], ['setter' => false]);
     * ```
     *
     * Mass assignment should be treated carefully when accepting user input, by default
     * entities will guard all fields when fields are assigned in bulk. You can disable
     * the guarding for a single set call with the `guard` option:
     *
     * ```
     * $entity->set(['name' => 'Andrew', 'id' => 1], ['guard' => false]);
     * ```
     *
     * You do not need to use the guard option when assigning fields individually:
     *
     * ```
     * // No need to use the guard option.
     * $entity->set('name', 'Andrew');
     * ```
     *
     * @param array<string, mixed>|string $field the name of field to set or a list of
     * fields with their respective values
     * @param mixed $value The value to set to the field or an array if the
     * first argument is also an array, in which case will be treated as $options
     * @param array<string, mixed> $options Options to be used for setting the field. Allowed option
     * keys are `setter` and `guard`
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function set($field, $value = null, array $options = [])
    {
        if (is_string($field) && $field !== '') {
            $guard = false;
            $field = [$field => $value];
        } else {
            $guard = true;
            $options = (array)$value;
        }

        if (!is_array($field)) {
            throw new InvalidArgumentException('Cannot set an empty field');
        }
        $options += ['setter' => true, 'guard' => $guard];

        foreach ($field as $name => $value) {
            $name = (string)$name;
            if ($options['guard'] === true && !$this->isAccessible($name)) {
                continue;
            }

            $this->setDirty($name, true);

            if (
                !array_key_exists($name, $this->_original) &&
                array_key_exists($name, $this->_fields) &&
                $this->_fields[$name] !== $value
            ) {
                $this->_original[$name] = $this->_fields[$name];
            }

            if (!$options['setter']) {
                $this->_fields[$name] = $value;
                continue;
            }

            $setter = static::_accessor($name, 'set');
            if ($setter) {
                $value = $this->{$setter}($value);
            }
            $this->_fields[$name] = $value;
        }

        return $this;
    }

    /**
     * Returns the value of a field by name
     *
     * @param string $field the name of the field to retrieve
     * @return mixed
     * @throws \InvalidArgumentException if an empty field name is passed
     */
    public function &get(string $field)
    {
        if ($field === '') {
            throw new InvalidArgumentException('Cannot get an empty field');
        }

        $value = null;
        $method = static::_accessor($field, 'get');

        if (isset($this->_fields[$field])) {
            $value = &$this->_fields[$field];
        }

        if ($method) {
            $result = $this->{$method}($value);

            return $result;
        }

        return $value;
    }

    /**
     * Returns the value of an original field by name
     *
     * @param string $field the name of the field for which original value is retrieved.
     * @return mixed
     * @throws \InvalidArgumentException if an empty field name is passed.
     */
    public function getOriginal(string $field)
    {
        if ($field === '') {
            throw new InvalidArgumentException('Cannot get an empty field');
        }
        if (array_key_exists($field, $this->_original)) {
            return $this->_original[$field];
        }

        return $this->get($field);
    }

    /**
     * Gets all original values of the entity.
     *
     * @return array
     */
    public function getOriginalValues(): array
    {
        $originals = $this->_original;
        $originalKeys = array_keys($originals);
        foreach ($this->_fields as $key => $value) {
            if (!in_array($key, $originalKeys, true)) {
                $originals[$key] = $value;
            }
        }

        return $originals;
    }

    /**
     * Returns whether this entity contains a field named $field
     * that contains a non-null value.
     *
     * ### Example:
     *
     * ```
     * $entity = new Entity(['id' => 1, 'name' => null]);
     * $entity->has('id'); // true
     * $entity->has('name'); // false
     * $entity->has('last_name'); // false
     * ```
     *
     * You can check multiple fields by passing an array:
     *
     * ```
     * $entity->has(['name', 'last_name']);
     * ```
     *
     * All fields must not be null to get a truthy result.
     *
     * When checking multiple fields. All fields must not be null
     * in order for true to be returned.
     *
     * @param array<string>|string $field The field or fields to check.
     * @return bool
     */
    public function has($field): bool
    {
        foreach ((array)$field as $prop) {
            if ($this->get($prop) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks that a field is empty
     *
     * This is not working like the PHP `empty()` function. The method will
     * return true for:
     *
     * - `''` (empty string)
     * - `null`
     * - `[]`
     *
     * and false in all other cases.
     *
     * @param string $field The field to check.
     * @return bool
     */
    public function isEmpty(string $field): bool
    {
        $value = $this->get($field);
        if (
            $value === null ||
            (
                is_array($value) &&
                empty($value) ||
                (
                    is_string($value) &&
                    $value === ''
                )
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks that a field has a value.
     *
     * This method will return true for
     *
     * - Non-empty strings
     * - Non-empty arrays
     * - Any object
     * - Integer, even `0`
     * - Float, even 0.0
     *
     * and false in all other cases.
     *
     * @param string $field The field to check.
     * @return bool
     */
    public function hasValue(string $field): bool
    {
        return !$this->isEmpty($field);
    }

    /**
     * Removes a field or list of fields from this entity
     *
     * ### Examples:
     *
     * ```
     * $entity->unset('name');
     * $entity->unset(['name', 'last_name']);
     * ```
     *
     * @param array<string>|string $field The field to unset.
     * @return $this
     */
    public function unset($field)
    {
        $field = (array)$field;
        foreach ($field as $p) {
            unset($this->_fields[$p], $this->_original[$p], $this->_dirty[$p]);
        }

        return $this;
    }

    /**
     * Removes a field or list of fields from this entity
     *
     * @deprecated 4.0.0 Use {@link unset()} instead. Will be removed in 5.0.
     * @param array<string>|string $field The field to unset.
     * @return $this
     */
    public function unsetProperty($field)
    {
        deprecationWarning('EntityTrait::unsetProperty() is deprecated. Use unset() instead.');

        return $this->unset($field);
    }

    /**
     * Sets hidden fields.
     *
     * @param array<string> $fields An array of fields to hide from array exports.
     * @param bool $merge Merge the new fields with the existing. By default false.
     * @return $this
     */
    public function setHidden(array $fields, bool $merge = false)
    {
        if ($merge === false) {
            $this->_hidden = $fields;

            return $this;
        }

        $fields = array_merge($this->_hidden, $fields);
        $this->_hidden = array_unique($fields);

        return $this;
    }

    /**
     * Gets the hidden fields.
     *
     * @return array<string>
     */
    public function getHidden(): array
    {
        return $this->_hidden;
    }

    /**
     * Sets the virtual fields on this entity.
     *
     * @param array<string> $fields An array of fields to treat as virtual.
     * @param bool $merge Merge the new fields with the existing. By default false.
     * @return $this
     */
    public function setVirtual(array $fields, bool $merge = false)
    {
        if ($merge === false) {
            $this->_virtual = $fields;

            return $this;
        }

        $fields = array_merge($this->_virtual, $fields);
        $this->_virtual = array_unique($fields);

        return $this;
    }

    /**
     * Gets the virtual fields on this entity.
     *
     * @return array<string>
     */
    public function getVirtual(): array
    {
        return $this->_virtual;
    }

    /**
     * Gets the list of visible fields.
     *
     * The list of visible fields is all standard fields
     * plus virtual fields minus hidden fields.
     *
     * @return array<string> A list of fields that are 'visible' in all
     *     representations.
     */
    public function getVisible(): array
    {
        $fields = array_keys($this->_fields);
        $fields = array_merge($fields, $this->_virtual);

        return array_diff($fields, $this->_hidden);
    }

    /**
     * Returns an array with all the fields that have been set
     * to this entity
     *
     * This method will recursively transform entities assigned to fields
     * into arrays as well.
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->getVisible() as $field) {
            $value = $this->get($field);
            if (is_array($value)) {
                $result[$field] = [];
                foreach ($value as $k => $entity) {
                    if ($entity instanceof EntityInterface) {
                        $result[$field][$k] = $entity->toArray();
                    } else {
                        $result[$field][$k] = $entity;
                    }
                }
            } elseif ($value instanceof EntityInterface) {
                $result[$field] = $value->toArray();
            } else {
                $result[$field] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns the fields that will be serialized as JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->extract($this->getVisible());
    }

    /**
     * Implements isset($entity);
     *
     * @param string $offset The offset to check.
     * @return bool Success
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Implements $entity[$offset];
     *
     * @param string $offset The offset to get.
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function &offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implements $entity[$offset] = $value;
     *
     * @param string $offset The offset to set.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Implements unset($result[$offset]);
     *
     * @param string $offset The offset to remove.
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->unset($offset);
    }

    /**
     * Fetch accessor method name
     * Accessor methods (available or not) are cached in $_accessors
     *
     * @param string $property the field name to derive getter name from
     * @param string $type the accessor type ('get' or 'set')
     * @return string method name or empty string (no method available)
     */
    protected static function _accessor(string $property, string $type): string
    {
        $class = static::class;

        if (isset(static::$_accessors[$class][$type][$property])) {
            return static::$_accessors[$class][$type][$property];
        }

        if (!empty(static::$_accessors[$class])) {
            return static::$_accessors[$class][$type][$property] = '';
        }

        if (static::class === Entity::class) {
            return '';
        }

        foreach (get_class_methods($class) as $method) {
            $prefix = substr($method, 1, 3);
            if ($method[0] !== '_' || ($prefix !== 'get' && $prefix !== 'set')) {
                continue;
            }
            $field = lcfirst(substr($method, 4));
            $snakeField = Inflector::underscore($field);
            $titleField = ucfirst($field);
            static::$_accessors[$class][$prefix][$snakeField] = $method;
            static::$_accessors[$class][$prefix][$field] = $method;
            static::$_accessors[$class][$prefix][$titleField] = $method;
        }

        if (!isset(static::$_accessors[$class][$type][$property])) {
            static::$_accessors[$class][$type][$property] = '';
        }

        return static::$_accessors[$class][$type][$property];
    }

    /**
     * Returns an array with the requested fields
     * stored in this entity, indexed by field name
     *
     * @param array<string> $fields list of fields to be returned
     * @param bool $onlyDirty Return the requested field only if it is dirty
     * @return array
     */
    public function extract(array $fields, bool $onlyDirty = false): array
    {
        $result = [];
        foreach ($fields as $field) {
            if (!$onlyDirty || $this->isDirty($field)) {
                $result[$field] = $this->get($field);
            }
        }

        return $result;
    }

    /**
     * Returns an array with the requested original fields
     * stored in this entity, indexed by field name.
     *
     * Fields that are unchanged from their original value will be included in the
     * return of this method.
     *
     * @param array<string> $fields List of fields to be returned
     * @return array
     */
    public function extractOriginal(array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $this->getOriginal($field);
        }

        return $result;
    }

    /**
     * Returns an array with only the original fields
     * stored in this entity, indexed by field name.
     *
     * This method will only return fields that have been modified since
     * the entity was built. Unchanged fields will be omitted.
     *
     * @param array<string> $fields List of fields to be returned
     * @return array
     */
    public function extractOriginalChanged(array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            $original = $this->getOriginal($field);
            if ($original !== $this->get($field)) {
                $result[$field] = $original;
            }
        }

        return $result;
    }

    /**
     * Sets the dirty status of a single field.
     *
     * @param string $field the field to set or check status for
     * @param bool $isDirty true means the field was changed, false means
     * it was not changed. Defaults to true.
     * @return $this
     */
    public function setDirty(string $field, bool $isDirty = true)
    {
        if ($isDirty === false) {
            unset($this->_dirty[$field]);

            return $this;
        }

        $this->_dirty[$field] = true;
        unset($this->_errors[$field], $this->_invalid[$field]);

        return $this;
    }

    /**
     * Checks if the entity is dirty or if a single field of it is dirty.
     *
     * @param string|null $field The field to check the status for. Null for the whole entity.
     * @return bool Whether the field was changed or not
     */
    public function isDirty(?string $field = null): bool
    {
        if ($field === null) {
            return !empty($this->_dirty);
        }

        return isset($this->_dirty[$field]);
    }

    /**
     * Gets the dirty fields.
     *
     * @return array<string>
     */
    public function getDirty(): array
    {
        return array_keys($this->_dirty);
    }

    /**
     * Sets the entire entity as clean, which means that it will appear as
     * no fields being modified or added at all. This is an useful call
     * for an initial object hydration
     *
     * @return void
     */
    public function clean(): void
    {
        $this->_dirty = [];
        $this->_errors = [];
        $this->_invalid = [];
        $this->_original = [];
    }

    /**
     * Set the status of this entity.
     *
     * Using `true` means that the entity has not been persisted in the database,
     * `false` that it already is.
     *
     * @param bool $new Indicate whether this entity has been persisted.
     * @return $this
     */
    public function setNew(bool $new)
    {
        if ($new) {
            foreach ($this->_fields as $k => $p) {
                $this->_dirty[$k] = true;
            }
        }

        $this->_new = $new;

        return $this;
    }

    /**
     * Returns whether this entity has already been persisted.
     *
     * @return bool Whether the entity has been persisted.
     */
    public function isNew(): bool
    {
        if (func_num_args()) {
            deprecationWarning('Using isNew() as setter is deprecated. Use setNew() instead.');

            $this->setNew(func_get_arg(0));
        }

        return $this->_new;
    }

    /**
     * Returns whether this entity has errors.
     *
     * @param bool $includeNested true will check nested entities for hasErrors()
     * @return bool
     */
    public function hasErrors(bool $includeNested = true): bool
    {
        if (Hash::filter($this->_errors)) {
            return true;
        }

        if ($includeNested === false) {
            return false;
        }

        foreach ($this->_fields as $field) {
            if ($this->_readHasErrors($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns all validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        $diff = array_diff_key($this->_fields, $this->_errors);

        return $this->_errors + (new Collection($diff))
            ->filter(function ($value) {
                return is_array($value) || $value instanceof EntityInterface;
            })
            ->map(function ($value) {
                return $this->_readError($value);
            })
            ->filter()
            ->toArray();
    }

    /**
     * Returns validation errors of a field
     *
     * @param string $field Field name to get the errors from
     * @return array
     */
    public function getError(string $field): array
    {
        $errors = $this->_errors[$field] ?? [];
        if ($errors) {
            return $errors;
        }

        return $this->_nestedErrors($field);
    }

    /**
     * Sets error messages to the entity
     *
     * ## Example
     *
     * ```
     * // Sets the error messages for multiple fields at once
     * $entity->setErrors(['salary' => ['message'], 'name' => ['another message']]);
     * ```
     *
     * @param array $errors The array of errors to set.
     * @param bool $overwrite Whether to overwrite pre-existing errors for $fields
     * @return $this
     */
    public function setErrors(array $errors, bool $overwrite = false)
    {
        if ($overwrite) {
            foreach ($errors as $f => $error) {
                $this->_errors[$f] = (array)$error;
            }

            return $this;
        }

        foreach ($errors as $f => $error) {
            $this->_errors += [$f => []];

            // String messages are appended to the list,
            // while more complex error structures need their
            // keys preserved for nested validator.
            if (is_string($error)) {
                $this->_errors[$f][] = $error;
            } else {
                foreach ($error as $k => $v) {
                    $this->_errors[$f][$k] = $v;
                }
            }
        }

        return $this;
    }

    /**
     * Sets errors for a single field
     *
     * ### Example
     *
     * ```
     * // Sets the error messages for a single field
     * $entity->setError('salary', ['must be numeric', 'must be a positive number']);
     * ```
     *
     * @param string $field The field to get errors for, or the array of errors to set.
     * @param array|string $errors The errors to be set for $field
     * @param bool $overwrite Whether to overwrite pre-existing errors for $field
     * @return $this
     */
    public function setError(string $field, $errors, bool $overwrite = false)
    {
        if (is_string($errors)) {
            $errors = [$errors];
        }

        return $this->setErrors([$field => $errors], $overwrite);
    }

    /**
     * Auxiliary method for getting errors in nested entities
     *
     * @param string $field the field in this entity to check for errors
     * @return array errors in nested entity if any
     */
    protected function _nestedErrors(string $field): array
    {
        // Only one path element, check for nested entity with error.
        if (strpos($field, '.') === false) {
            return $this->_readError($this->get($field));
        }
        // Try reading the errors data with field as a simple path
        $error = Hash::get($this->_errors, $field);
        if ($error !== null) {
            return $error;
        }
        $path = explode('.', $field);

        // Traverse down the related entities/arrays for
        // the relevant entity.
        $entity = $this;
        $len = count($path);
        while ($len) {
            $part = array_shift($path);
            $len = count($path);
            $val = null;
            if ($entity instanceof EntityInterface) {
                $val = $entity->get($part);
            } elseif (is_array($entity)) {
                $val = $entity[$part] ?? false;
            }

            if (
                is_array($val) ||
                $val instanceof Traversable ||
                $val instanceof EntityInterface
            ) {
                $entity = $val;
            } else {
                $path[] = $part;
                break;
            }
        }
        if (count($path) <= 1) {
            return $this->_readError($entity, array_pop($path));
        }

        return [];
    }

    /**
     * Reads if there are errors for one or many objects.
     *
     * @param \Cake\Datasource\EntityInterface|array $object The object to read errors from.
     * @return bool
     */
    protected function _readHasErrors($object): bool
    {
        if ($object instanceof EntityInterface && $object->hasErrors()) {
            return true;
        }

        if (is_array($object)) {
            foreach ($object as $value) {
                if ($this->_readHasErrors($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Read the error(s) from one or many objects.
     *
     * @param \Cake\Datasource\EntityInterface|iterable $object The object to read errors from.
     * @param string|null $path The field name for errors.
     * @return array
     */
    protected function _readError($object, $path = null): array
    {
        if ($path !== null && $object instanceof EntityInterface) {
            return $object->getError($path);
        }
        if ($object instanceof EntityInterface) {
            return $object->getErrors();
        }
        if (is_iterable($object)) {
            $array = array_map(function ($val) {
                if ($val instanceof EntityInterface) {
                    return $val->getErrors();
                }

                return null;
            }, (array)$object);

            return array_filter($array);
        }

        return [];
    }

    /**
     * Get a list of invalid fields and their data for errors upon validation/patching
     *
     * @return array
     */
    public function getInvalid(): array
    {
        return $this->_invalid;
    }

    /**
     * Get a single value of an invalid field. Returns null if not set.
     *
     * @param string $field The name of the field.
     * @return mixed|null
     */
    public function getInvalidField(string $field)
    {
        return $this->_invalid[$field] ?? null;
    }

    /**
     * Set fields as invalid and not patchable into the entity.
     *
     * This is useful for batch operations when one needs to get the original value for an error message after patching.
     * This value could not be patched into the entity and is simply copied into the _invalid property for debugging
     * purposes or to be able to log it away.
     *
     * @param array $fields The values to set.
     * @param bool $overwrite Whether to overwrite pre-existing values for $field.
     * @return $this
     */
    public function setInvalid(array $fields, bool $overwrite = false)
    {
        foreach ($fields as $field => $value) {
            if ($overwrite === true) {
                $this->_invalid[$field] = $value;
                continue;
            }
            $this->_invalid += [$field => $value];
        }

        return $this;
    }

    /**
     * Sets a field as invalid and not patchable into the entity.
     *
     * @param string $field The value to set.
     * @param mixed $value The invalid value to be set for $field.
     * @return $this
     */
    public function setInvalidField(string $field, $value)
    {
        $this->_invalid[$field] = $value;

        return $this;
    }

    /**
     * Stores whether a field value can be changed or set in this entity.
     * The special field `*` can also be marked as accessible or protected, meaning
     * that any other field specified before will take its value. For example
     * `$entity->setAccess('*', true)` means that any field not specified already
     * will be accessible by default.
     *
     * You can also call this method with an array of fields, in which case they
     * will each take the accessibility value specified in the second argument.
     *
     * ### Example:
     *
     * ```
     * $entity->setAccess('id', true); // Mark id as not protected
     * $entity->setAccess('author_id', false); // Mark author_id as protected
     * $entity->setAccess(['id', 'user_id'], true); // Mark both fields as accessible
     * $entity->setAccess('*', false); // Mark all fields as protected
     * ```
     *
     * @param array<string>|string $field Single or list of fields to change its accessibility
     * @param bool $set True marks the field as accessible, false will
     * mark it as protected.
     * @return $this
     */
    public function setAccess($field, bool $set)
    {
        if ($field === '*') {
            $this->_accessible = array_map(function ($p) use ($set) {
                return $set;
            }, $this->_accessible);
            $this->_accessible['*'] = $set;

            return $this;
        }

        foreach ((array)$field as $prop) {
            $this->_accessible[$prop] = $set;
        }

        return $this;
    }

    /**
     * Returns the raw accessible configuration for this entity.
     * The `*` wildcard refers to all fields.
     *
     * @return array<bool>
     */
    public function getAccessible(): array
    {
        return $this->_accessible;
    }

    /**
     * Checks if a field is accessible
     *
     * ### Example:
     *
     * ```
     * $entity->isAccessible('id'); // Returns whether it can be set or not
     * ```
     *
     * @param string $field Field name to check
     * @return bool
     */
    public function isAccessible(string $field): bool
    {
        $value = $this->_accessible[$field] ?? null;

        return ($value === null && !empty($this->_accessible['*'])) || $value;
    }

    /**
     * Returns the alias of the repository from which this entity came from.
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->_registryAlias;
    }

    /**
     * Sets the source alias
     *
     * @param string $alias the alias of the repository
     * @return $this
     */
    public function setSource(string $alias)
    {
        $this->_registryAlias = $alias;

        return $this;
    }

    /**
     * Returns a string representation of this object in a human readable format.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)json_encode($this, JSON_PRETTY_PRINT);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $fields = $this->_fields;
        foreach ($this->_virtual as $field) {
            $fields[$field] = $this->$field;
        }

        return $fields + [
            '[new]' => $this->isNew(),
            '[accessible]' => $this->_accessible,
            '[dirty]' => $this->_dirty,
            '[original]' => $this->_original,
            '[virtual]' => $this->_virtual,
            '[hasErrors]' => $this->hasErrors(),
            '[errors]' => $this->_errors,
            '[invalid]' => $this->_invalid,
            '[repository]' => $this->_registryAlias,
        ];
    }
}
