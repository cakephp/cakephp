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
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Traversable;

/**
 * An entity represents a single result row from a repository. It exposes the
 * methods for retrieving and storing properties associated in this row.
 */
trait EntityTrait
{
    /**
     * Holds all fields and their values for this entity.
     *
     * @var array
     */
    protected $_fields = [];

    /**
     * Holds all fields that have been changed and their original values for this entity.
     *
     * @var array
     */
    protected $_original = [];

    /**
     * List of field names that should **not** be included in JSON or Array
     * representations of this Entity.
     *
     * @var array
     */
    protected $_hidden = [];

    /**
     * List of computed or virtual fields that **should** be included in JSON or array
     * representations of this Entity. If a field is present in both _hidden and _virtual
     * the field will **not** be in the array/json versions of the entity.
     *
     * @var array
     */
    protected $_virtual = [];

    /**
     * Holds a list of the properties that were modified or added after this object
     * was originally created.
     *
     * @var array
     */
    protected $_dirty = [];

    /**
     * Holds a cached list of getters/setters per class
     *
     * @var array
     */
    protected static $_accessors = [];

    /**
     * Indicates whether or not this entity is yet to be persisted.
     * Entities default to assuming they are new. You can use Table::persisted()
     * to set the new flag on an entity based on records in the database.
     *
     * @var bool
     */
    protected $_new = true;

    /**
     * List of errors per field as stored in this object.
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * List of invalid fields and their data for errors upon validation/patching.
     *
     * @var array
     */
    protected $_invalid = [];

    /**
     * Map of properties in this entity that can be safely assigned, each
     * field name points to a boolean indicating its status. An empty array
     * means no properties are accessible
     *
     * The special field '\*' can also be mapped, meaning that any other field
     * not defined in the map will take its value. For example, `'\*' => true`
     * means that any field not defined in the map will be accessible by default
     *
     * @var array
     */
    protected $_accessible = ['*' => true];

    /**
     * The alias of the repository this entity came from
     *
     * @var string
     */
    protected $_registryAlias = '';

    /**
     * Magic getter to access properties that have been set in this entity
     *
     * @param string $field Name of the field to access
     * @return mixed
     */
    public function &__get($field)
    {
        return $this->get($field);
    }

    /**
     * Magic setter to add or edit a property in this entity
     *
     * @param string $field The name of the property to set
     * @param mixed $value The value to set to the property
     * @return void
     */
    public function __set($field, $value)
    {
        $this->set($field, $value);
    }

    /**
     * Returns whether this entity contains a property named $property
     * regardless of if it is empty.
     *
     * @param string $field The property to check.
     * @return bool
     * @see \Cake\ORM\Entity::has()
     */
    public function __isset($field)
    {
        return $this->has($field);
    }

    /**
     * Removes a property from this entity
     *
     * @param string $field The property to unset
     * @return void
     */
    public function __unset($field)
    {
        $this->unsetField($field);
    }

    /**
     * Sets a single property inside this entity.
     *
     * ### Example:
     *
     * ```
     * $entity->set('name', 'Andrew');
     * ```
     *
     * It is also possible to mass-assign multiple properties to this entity
     * with one call by passing a hashed array as properties in the form of
     * property => value pairs
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
     * properties. You can achieve this by disabling the `setter` option using the
     * `$options` parameter:
     *
     * ```
     * $entity->set('name', 'Andrew', ['setter' => false]);
     * $entity->set(['name' => 'Andrew', 'id' => 1], ['setter' => false]);
     * ```
     *
     * Mass assignment should be treated carefully when accepting user input, by default
     * entities will guard all fields when properties are assigned in bulk. You can disable
     * the guarding for a single set call with the `guard` option:
     *
     * ```
     * $entity->set(['name' => 'Andrew', 'id' => 1], ['guard' => true]);
     * ```
     *
     * You do not need to use the guard option when assigning properties individually:
     *
     * ```
     * // No need to use the guard option.
     * $entity->set('name', 'Andrew');
     * ```
     *
     * @param string|array $field the name of property to set or a list of
     * properties with their respective values
     * @param mixed $value The value to set to the property or an array if the
     * first argument is also an array, in which case will be treated as $options
     * @param array $options options to be used for setting the property. Allowed option
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
            throw new InvalidArgumentException('Cannot set an empty property');
        }
        $options += ['setter' => true, 'guard' => $guard];

        foreach ($field as $name => $value) {
            if ($options['guard'] === true && !$this->isAccessible($name)) {
                continue;
            }

            $this->setDirty($name, true);

            if (!array_key_exists($name, $this->_original) &&
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
     * Returns the value of a property by name
     *
     * @param string $field the name of the property to retrieve
     * @return mixed
     * @throws \InvalidArgumentException if an empty property name is passed
     */
    public function &get($field)
    {
        if (!strlen((string)$field)) {
            throw new InvalidArgumentException('Cannot get an empty property');
        }

        $value = null;
        $method = static::_accessor($field, 'get');

        if (isset($this->_fields[$field])) {
            $value =& $this->_fields[$field];
        }

        if ($method) {
            $result = $this->{$method}($value);

            return $result;
        }

        return $value;
    }

    /**
     * Returns the value of an original property by name
     *
     * @param string $field the name of the property for which original value is retrieved.
     * @return mixed
     * @throws \InvalidArgumentException if an empty property name is passed.
     */
    public function getOriginal($field)
    {
        if (!strlen((string)$field)) {
            throw new InvalidArgumentException('Cannot get an empty property');
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
    public function getOriginalValues()
    {
        $originals = $this->_original;
        $originalKeys = array_keys($originals);
        foreach ($this->_fields as $key => $value) {
            if (!in_array($key, $originalKeys)) {
                $originals[$key] = $value;
            }
        }

        return $originals;
    }

    /**
     * Returns whether this entity contains a property named $property
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
     * You can check multiple properties by passing an array:
     *
     * ```
     * $entity->has(['name', 'last_name']);
     * ```
     *
     * All properties must not be null to get a truthy result.
     *
     * When checking multiple properties. All properties must not be null
     * in order for true to be returned.
     *
     * @param string|array $field The property or properties to check.
     * @return bool
     */
    public function has($field)
    {
        foreach ((array)$field as $prop) {
            if ($this->get($prop) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks that a property is empty
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
     * @param string $field The property to check.
     * @return bool
     */
    public function isEmpty($field)
    {
        $value = $this->get($field);
        if ($value === null
            || (is_array($value) && empty($value)
            || (is_string($value) && empty($value)))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks tha a property has a value.
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
     * @param string $field The property to check.
     * @return bool
     */
    public function hasValue($field)
    {
        return !$this->isEmpty($field);
    }

    /**
     * Removes a property or list of properties from this entity
     *
     * ### Examples:
     *
     * ```
     * $entity->unsetProperty('name');
     * $entity->unsetProperty(['name', 'last_name']);
     * ```
     *
     * @param string|array $field The property to unset.
     * @return $this
     */
    public function unsetField($field)
    {
        $field = (array)$field;
        foreach ($field as $p) {
            unset($this->_fields[$p], $this->_dirty[$p]);
        }

        return $this;
    }

    /**
     * Removes a property or list of properties from this entity
     *
     * @deprecated 4.0.0 Use unsetField() instead.
     * @param string|array $field The field to unset.
     * @return $this
     */
    public function unsetProperty($field) {
        return $this->unsetField($field);
    }

    /**
     * Sets hidden properties.
     *
     * @param array $fields An array of properties to hide from array exports.
     * @param bool $merge Merge the new properties with the existing. By default false.
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
     * Gets the hidden properties.
     *
     * @return array
     */
    public function getHidden(): array
    {
        return $this->_hidden;
    }

    /**
     * Sets the virtual properties on this entity.
     *
     * @param array $fields An array of properties to treat as virtual.
     * @param bool $merge Merge the new properties with the existing. By default false.
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
     * @return array
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
     * @return array A list of fields that are 'visible' in all
     *     representations.
     */
    public function getVisible(): array
    {
        $properties = array_keys($this->_fields);
        $properties = array_merge($properties, $this->_virtual);

        return array_diff($properties, $this->_hidden);
    }

    /**
     * Gets the list of visible fields.
     *
     * @deprecated 4.0.0 Use getVisible() instead.
     * @return array
     */
    public function visibleProperties(): array
    {
        return $this->getVisible();
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
    public function toArray()
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
     * Returns the properties that will be serialized as JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->extract($this->getVisible());
    }

    /**
     * Implements isset($entity);
     *
     * @param mixed $offset The offset to check.
     * @return bool Success
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Implements $entity[$offset];
     *
     * @param mixed $offset The offset to get.
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implements $entity[$offset] = $value;
     *
     * @param mixed $offset The offset to set.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Implements unset($result[$offset]);
     *
     * @param mixed $offset The offset to remove.
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->unsetField($offset);
    }

    /**
     * Fetch accessor method name
     * Accessor methods (available or not) are cached in $_accessors
     *
     * @param string $property the field name to derive getter name from
     * @param string $type the accessor type ('get' or 'set')
     * @return string method name or empty string (no method available)
     */
    protected static function _accessor($property, $type)
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
     * Returns an array with the requested properties
     * stored in this entity, indexed by property name
     *
     * @param array $fields list of properties to be returned
     * @param bool $onlyDirty Return the requested property only if it is dirty
     * @return array
     */
    public function extract(array $fields, $onlyDirty = false)
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
     * Returns an array with the requested original properties
     * stored in this entity, indexed by property name.
     *
     * Properties that are unchanged from their original value will be included in the
     * return of this method.
     *
     * @param array $fields List of properties to be returned
     * @return array
     */
    public function extractOriginal(array $fields)
    {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $this->getOriginal($field);
        }

        return $result;
    }

    /**
     * Returns an array with only the original properties
     * stored in this entity, indexed by property name.
     *
     * This method will only return properties that have been modified since
     * the entity was built. Unchanged properties will be omitted.
     *
     * @param array $fields List of properties to be returned
     * @return array
     */
    public function extractOriginalChanged(array $fields)
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
     * Sets the dirty status of a single property.
     *
     * @param string $field the field to set or check status for
     * @param bool $isDirty true means the property was changed, false means
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
     * Checks if the entity is dirty or if a single property of it is dirty.
     *
     * @param string|null $field The field to check the status for. Null for the whole entity.
     * @return bool Whether the property was changed or not
     */
    public function isDirty(?string $field = null): bool
    {
        if ($field === null) {
            return !empty($this->_dirty);
        }

        return isset($this->_dirty[$field]);
    }

    /**
     * Gets the dirty properties.
     *
     * @return string[]
     */
    public function getDirty(): array
    {
        return array_keys($this->_dirty);
    }

    /**
     * Sets the entire entity as clean, which means that it will appear as
     * no properties being modified or added at all. This is an useful call
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
     * Returns whether or not this entity has already been persisted.
     * This method can return null in the case there is no prior information on
     * the status of this entity.
     *
     * If called with a boolean it will set the known status of this instance,
     * true means that the instance is not yet persisted in the database, false
     * that it already is.
     *
     * @param bool|null $new true if it is known this instance was not yet persisted
     * @return bool Whether or not the entity has been persisted.
     */
    public function isNew(?bool $new = null): bool
    {
        if ($new === null) {
            return $this->_new;
        }

        $new = (bool)$new;

        if ($new) {
            foreach ($this->_fields as $k => $p) {
                $this->_dirty[$k] = true;
            }
        }

        return $this->_new = $new;
    }

    /**
     * Returns whether this entity has errors.
     *
     * @param bool $includeNested true will check nested entities for hasErrors()
     * @return bool
     */
    public function hasErrors(bool $includeNested = true): bool
    {
        if (!empty($this->_errors)) {
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
     * @param array $fields The array of errors to set.
     * @param bool $overwrite Whether or not to overwrite pre-existing errors for $fields
     * @return $this
     */
    public function setErrors(array $fields, bool $overwrite = false)
    {
        if ($overwrite) {
            foreach ($fields as $f => $error) {
                $this->_errors[$f] = (array)$error;
            }

            return $this;
        }

        foreach ($fields as $f => $error) {
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
     * @param string|array $errors The errors to be set for $field
     * @param bool $overwrite Whether or not to overwrite pre-existing errors for $field
     * @return $this
     */
    public function setError($field, $errors, bool $overwrite = false)
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
    protected function _nestedErrors($field)
    {
        $path = explode('.', $field);

        // Only one path element, check for nested entity with error.
        if (count($path) === 1) {
            return $this->_readError($this->get($path[0]));
        }

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

            if (is_array($val) ||
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
     * @param mixed $object The object to read errors from.
     * @return bool
     */
    protected function _readHasErrors($object)
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
     * @param array|\Cake\Datasource\EntityInterface $object The object to read errors from.
     * @param string|null $path The field name for errors.
     * @return array
     */
    protected function _readError($object, $path = null)
    {
        if ($path !== null && $object instanceof EntityInterface) {
            return $object->getError($path);
        }
        if ($object instanceof EntityInterface) {
            return $object->getErrors();
        }
        if (is_array($object)) {
            $array = array_map(function ($val) {
                if ($val instanceof EntityInterface) {
                    return $val->getErrors();
                }
            }, $object);

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
     * @return mixed
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
     * @param bool $overwrite Whether or not to overwrite pre-existing values for $field.
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
     * Stores whether or not a property value can be changed or set in this entity.
     * The special property `*` can also be marked as accessible or protected, meaning
     * that any other property specified before will take its value. For example
     * `$entity->setAccess('*', true)` means that any property not specified already
     * will be accessible by default.
     *
     * You can also call this method with an array of properties, in which case they
     * will each take the accessibility value specified in the second argument.
     *
     * ### Example:
     *
     * ```
     * $entity->setAccess('id', true); // Mark id as not protected
     * $entity->setAccess('author_id', false); // Mark author_id as protected
     * $entity->setAccess(['id', 'user_id'], true); // Mark both properties as accessible
     * $entity->setAccess('*', false); // Mark all properties as protected
     * ```
     *
     * @param string|array $field single or list of properties to change its accessibility
     * @param bool $set true marks the property as accessible, false will
     * mark it as protected.
     * @return $this
     */
    public function setAccess($field, bool $set)
    {
        if ($field === '*') {
            $this->_accessible = array_map(function ($p) use ($set) {
                return (bool)$set;
            }, $this->_accessible);
            $this->_accessible['*'] = (bool)$set;

            return $this;
        }

        foreach ((array)$field as $prop) {
            $this->_accessible[$prop] = (bool)$set;
        }

        return $this;
    }

    /**
     * Checks if a property is accessible
     *
     * ### Example:
     *
     * ```
     * $entity->isAccessible('id'); // Returns whether it can be set or not
     * ```
     *
     * @param string $field Property name to check
     * @return bool
     */
    public function isAccessible(string $field): bool
    {
        $value = $this->_accessible[$field] ??
            null;

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
     * @return array
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
