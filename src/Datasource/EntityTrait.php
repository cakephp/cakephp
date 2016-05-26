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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Collection\Collection;
use Cake\Utility\Hash;
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
     * Holds all properties and their values for this entity
     *
     * @var array
     */
    protected $_properties = [];

    /**
     * Holds all properties that have been changed and their original values for this entity
     *
     * @var array
     */
    protected $_original = [];

    /**
     * List of property names that should **not** be included in JSON or Array
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
     * Holds the name of the class for the instance object
     *
     * @var string
     *
     * @deprecated 3.2 This field is no longer being used
     */
    protected $_className;

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
     * List of errors per field as stored in this object
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * List of invalid fields and their data for errors upon validation/patching
     *
     * @var array
     */
    protected $_invalid = [];

    /**
     * Map of properties in this entity that can be safely assigned, each
     * property name points to a boolean indicating its status. An empty array
     * means no properties are accessible
     *
     * The special property '\*' can also be mapped, meaning that any other property
     * not defined in the map will take its value. For example, `'\*' => true`
     * means that any property not defined in the map will be accessible by default
     *
     * @var array
     */
    protected $_accessible = ['*' => true];

    /**
     * The alias of the repository this entity came from
     *
     * @var string
     */
    protected $_registryAlias;

    /**
     * Magic getter to access properties that have been set in this entity
     *
     * @param string $property Name of the property to access
     * @return mixed
     */
    public function &__get($property)
    {
        return $this->get($property);
    }

    /**
     * Magic setter to add or edit a property in this entity
     *
     * @param string $property The name of the property to set
     * @param mixed $value The value to set to the property
     * @return void
     */
    public function __set($property, $value)
    {
        $this->set($property, $value);
    }

    /**
     * Returns whether this entity contains a property named $property
     * regardless of if it is empty.
     *
     * @param string $property The property to check.
     * @return bool
     * @see \Cake\ORM\Entity::has()
     */
    public function __isset($property)
    {
        return $this->has($property);
    }

    /**
     * Removes a property from this entity
     *
     * @param string $property The property to unset
     * @return void
     */
    public function __unset($property)
    {
        $this->unsetProperty($property);
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
     * @param string|array $property the name of property to set or a list of
     * properties with their respective values
     * @param mixed $value The value to set to the property or an array if the
     * first argument is also an array, in which case will be treated as $options
     * @param array $options options to be used for setting the property. Allowed option
     * keys are `setter` and `guard`
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function set($property, $value = null, array $options = [])
    {
        $isString = is_string($property);
        if ($isString && $property !== '') {
            $guard = false;
            $property = [$property => $value];
        } else {
            $guard = true;
            $options = (array)$value;
        }

        if (!is_array($property)) {
            throw new InvalidArgumentException('Cannot set an empty property');
        }
        $options += ['setter' => true, 'guard' => $guard];

        foreach ($property as $p => $value) {
            $nested = false;
            if (strpos($p, '.') > 0 && !array_key_exists($p, $this->_properties)) {
                list($p, $nested) = explode('.', $p, 2);
            }

            if ($options['guard'] === true && !$this->accessible($p)) {
                continue;
            }

            $this->dirty($p, true);

            if ($nested) {
                if (!array_key_exists($p, $this->_properties)
                    || is_null($this->_properties[$p])
                ) {
                    $value = new \Cake\ORM\Entity([$nested => $value]);
                    //$value = Hash::insert([], $nested, $value); //@TODO: Hash::insert() is the other option to generate arrays down the chain instead of Entities, but this has drawbacks like not preserving the ability to ::getOriginal() down the chain. The most fidelity is preserved by using Entities.
                } elseif ($this->_properties[$p] instanceof EntityInterface) {
                    $this->_properties[$p]->set($nested, $value, $options);
                    continue; // In the recursive case, don't update ::$_original till we get to the bottom of the path. ONLY traverse the chain until that point.
                } elseif (is_array($this->_properties[$p])
                    || $this->_properties[$p] instanceof ArrayAccess
                ) {
                    $value = Hash::insert($this->_properties[$p], $nested, $value);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot set dotted property when a traversal segment contains a scalar value: %s',
                        $p
                    ));
                }
            }

            if (!array_key_exists($p, $this->_original) &&
                array_key_exists($p, $this->_properties) &&
                $this->_properties[$p] !== $value
            ) {
                $this->_original[$p] = $this->_properties[$p];
            }

            if (!$options['setter']) {
                $this->_properties[$p] = $value;
                continue;
            }

            $setter = $this->_accessor($p, 'set');
            if ($setter) {
                $value = $this->{$setter}($value);
            }
            $this->_properties[$p] = $value;
        }

        return $this;
    }

    /**
     * Returns the value of a property by name
     *
     * Supports simple dotted paths for traversing deep into an Entity's
     * properties.
     *
     * ### Example:
     *
     * ```
     * $entity = new Entity(['sizes' => ['med' => 5, 'lrg' => 15]]);
     * $entity->get('sizes.med'); // 5
     * ```
     *
     * @param string $property the name of the property to retrieve
     * @return mixed
     * @throws \InvalidArgumentException if an empty property name is passed
     */
    public function &get($property)
    {
        if (!strlen((string)$property)) {
            throw new InvalidArgumentException('Cannot get an empty property');
        }

        $nested = false;
        if (strpos($property, '.') > 0 && !array_key_exists($property, $this->_properties)) {
            list($property, $nested) = explode('.', $property, 2);
        }

        if ($nested) {
            if ($this->_properties[$property] instanceof EntityInterface) {
                $result = $this->_properties[$property]->get($nested);
                return $result;
            } elseif (is_array($this->_properties[$property])
                || ($this->_properties[$property] instanceof \ArrayAccess)
            ) {
                $value = Hash::get($this->_properties[$property], $nested);
                return $value; //@TODO: Or remove this line and let getters engage below?
            }
        }

        $value = null;
        $method = $this->_accessor($property, 'get');

        if (isset($this->_properties[$property])) {
            $value =& $this->_properties[$property];
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
     * @param string $property the name of the property for which original value is retrieved.
     * @return mixed
     * @throws \InvalidArgumentException if an empty property name is passed.
     */
    public function getOriginal($property)
    {
        if (!strlen((string)$property)) {
            throw new InvalidArgumentException('Cannot get an empty property');
        }

        if (strpos($property, '.') > 0) {
            list($property, $nested) = explode('.', $property, 2);
            if ($this->_properties[$property] instanceof EntityInterface) {
                return $this->_properties[$property]->getOriginal($nested);
            }

            throw new \InvalidArgumentException(sprintf(
                'No original value is available for path: %s.%s',
                $property,
                $nested
            ));
        }

        if (array_key_exists($property, $this->_original)) {
            return $this->_original[$property];
        }

        return $this->get($property);
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
        foreach ($this->_properties as $key => $value) {
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
     * @param string|array $property The property or properties to check.
     * @return bool
     */
    public function has($property)
    {
        foreach ((array)$property as $prop) {
            if ($this->get($prop) === null) {
                return false;
            }
        }
        return true;
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
     * @param string|array $property The property to unset.
     * @return $this
     */
    public function unsetProperty($property)
    {
        $property = (array)$property;
        foreach ($property as $p) {
            unset($this->_properties[$p]);
            unset($this->_dirty[$p]);
        }

        return $this;
    }

    /**
     * Get/Set the hidden properties on this entity.
     *
     * If the properties argument is null, the currently hidden properties
     * will be returned. Otherwise the hidden properties will be set.
     *
     * @param null|array $properties Either an array of properties to hide or null to get properties
     * @return array|$this
     */
    public function hiddenProperties($properties = null)
    {
        if ($properties === null) {
            return $this->_hidden;
        }
        $this->_hidden = $properties;
        return $this;
    }

    /**
     * Get/Set the virtual properties on this entity.
     *
     * If the properties argument is null, the currently virtual properties
     * will be returned. Otherwise the virtual properties will be set.
     *
     * @param null|array $properties Either an array of properties to treat as virtual or null to get properties
     * @return array|$this
     */
    public function virtualProperties($properties = null)
    {
        if ($properties === null) {
            return $this->_virtual;
        }
        $this->_virtual = $properties;
        return $this;
    }

    /**
     * Get the list of visible properties.
     *
     * The list of visible properties is all standard properties
     * plus virtual properties minus hidden properties.
     *
     * @return array A list of properties that are 'visible' in all
     *     representations.
     */
    public function visibleProperties()
    {
        $properties = array_keys($this->_properties);
        $properties = array_merge($properties, $this->_virtual);
        return array_diff($properties, $this->_hidden);
    }

    /**
     * Returns an array with all the properties that have been set
     * to this entity
     *
     * This method will recursively transform entities assigned to properties
     * into arrays as well.
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->visibleProperties() as $property) {
            $value = $this->get($property);
            if (is_array($value)) {
                $result[$property] = [];
                foreach ($value as $k => $entity) {
                    if ($entity instanceof EntityInterface) {
                        $result[$property][$k] = $entity->toArray();
                    } else {
                        $result[$property][$k] = $entity;
                    }
                }
            } elseif ($value instanceof EntityInterface) {
                $result[$property] = $value->toArray();
            } else {
                $result[$property] = $value;
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
        return $this->toArray();
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
        $this->unsetProperty($offset);
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

        if ($class === 'Cake\ORM\Entity') {
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
     * @param array $properties list of properties to be returned
     * @param bool $onlyDirty Return the requested property only if it is dirty
     * @return array
     */
    public function extract(array $properties, $onlyDirty = false)
    {
        $result = [];
        foreach ($properties as $property) {
            if (!$onlyDirty || $this->dirty($property)) {
                $result[$property] = $this->get($property);
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
     * @param array $properties List of properties to be returned
     * @return array
     */
    public function extractOriginal(array $properties)
    {
        $result = [];
        foreach ($properties as $property) {
            $result[$property] = $this->getOriginal($property);
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
     * @param array $properties List of properties to be returned
     * @return array
     */
    public function extractOriginalChanged(array $properties)
    {
        $result = [];
        foreach ($properties as $property) {
            $original = $this->getOriginal($property);
            if ($original !== $this->get($property)) {
                $result[$property] = $original;
            }
        }
        return $result;
    }

    /**
     * Sets the dirty status of a single property. If called with no second
     * argument, it will return whether the property was modified or not
     * after the object creation.
     *
     * When called with no arguments it will return whether or not there are any
     * dirty property in the entity
     *
     * @param string|null $property the field to set or check status for
     * @param null|bool $isDirty true means the property was changed, false means
     * it was not changed and null will make the function return current state
     * for that property
     * @return bool Whether the property was changed or not
     */
    public function dirty($property = null, $isDirty = null)
    {
        if ($property === null) {
            return !empty($this->_dirty);
        }

        if ($isDirty === null) {
            return isset($this->_dirty[$property]);
        }

        if ($isDirty === false) {
            unset($this->_dirty[$property]);
            return false;
        }

        $this->_dirty[$property] = true;
        unset($this->_errors[$property], $this->_invalid[$property]);
        return true;
    }

    /**
     * Sets the entire entity as clean, which means that it will appear as
     * no properties being modified or added at all. This is an useful call
     * for an initial object hydration
     *
     * @return void
     */
    public function clean()
    {
        $this->_dirty = [];
        $this->_errors = [];
        $this->_invalid = [];
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
     * @param bool|null $new true if it is known this instance was persisted
     * @return bool Whether or not the entity has been persisted.
     */
    public function isNew($new = null)
    {
        if ($new === null) {
            return $this->_new;
        }

        $new = (bool)$new;

        if ($new) {
            foreach ($this->_properties as $k => $p) {
                $this->_dirty[$k] = true;
            }
        }

        return $this->_new = $new;
    }

    /**
     * Sets the error messages for a field or a list of fields. When called
     * without the second argument it returns the validation
     * errors for the specified fields. If called with no arguments it returns
     * all the validation error messages stored in this entity and any other nested
     * entity.
     *
     * ### Example
     *
     * ```
     * // Sets the error messages for a single field
     * $entity->errors('salary', ['must be numeric', 'must be a positive number']);
     *
     * // Returns the error messages for a single field
     * $entity->errors('salary');
     *
     * // Returns all error messages indexed by field name
     * $entity->errors();
     *
     * // Sets the error messages for multiple fields at once
     * $entity->errors(['salary' => ['message'], 'name' => ['another message']);
     * ```
     *
     * When used as a setter, this method will return this entity instance for method
     * chaining.
     *
     * @param string|array|null $field The field to get errors for, or the array of errors to set.
     * @param string|array|null $errors The errors to be set for $field
     * @param bool $overwrite Whether or not to overwrite pre-existing errors for $field
     * @return array|$this
     */
    public function errors($field = null, $errors = null, $overwrite = false)
    {
        if ($field === null) {
            $diff = array_diff_key($this->_properties, $this->_errors);
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

        if (is_string($field) && $errors === null) {
            $errors = isset($this->_errors[$field]) ? $this->_errors[$field] : [];
            if ($errors) {
                return $errors;
            }
            return $this->_nestedErrors($field);
        }

        if (!is_array($field)) {
            $field = [$field => $errors];
        }

        foreach ($field as $f => $error) {
            $this->_errors += [$f => []];
            $this->_errors[$f] = $overwrite ?
                (array)$error :
                array_merge($this->_errors[$f], (array)$error);
        }

        return $this;
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
            if ($entity instanceof EntityInterface) {
                $val = $entity->get($part);
            } elseif (is_array($entity)) {
                $val = isset($entity[$part]) ? $entity[$part] : false;
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
     * Read the error(s) from one or many objects.
     *
     * @param array|\Cake\Datasource\EntityTrait $object The object to read errors from.
     * @param string|null $path The field name for errors.
     * @return array
     */
    protected function _readError($object, $path = null)
    {
        if ($object instanceof EntityInterface) {
            return $object->errors($path);
        }
        if (is_array($object)) {
            $array = array_map(function ($val) {
                if ($val instanceof EntityInterface) {
                    return $val->errors();
                }
            }, $object);
            return array_filter($array);
        }
        return [];
    }

    /**
     * Sets a field as invalid and not patchable into the entity.
     *
     * This is useful for batch operations when one needs to get the original value for an error message after patching.
     * This value could not be patched into the entity and is simply copied into the _invalid property for debugging purposes
     * or to be able to log it away.
     *
     * @param string|array|null $field The field to get invalid value for, or the value to set.
     * @param mixed|null $value The invalid value to be set for $field.
     * @param bool $overwrite Whether or not to overwrite pre-existing values for $field.
     * @return $this|mixed
     */
    public function invalid($field = null, $value = null, $overwrite = false)
    {
        if ($field === null) {
            return $this->_invalid;
        }

        if (is_string($field) && $value === null) {
            $value = isset($this->_invalid[$field]) ? $this->_invalid[$field] : null;
            return $value;
        }

        if (!is_array($field)) {
            $field = [$field => $value];
        }

        foreach ($field as $f => $value) {
            if ($overwrite) {
                $this->_invalid[$f] = $value;
                continue;
            }
            $this->_invalid += [$f => $value];
        }

        return $this;
    }

    /**
     * Stores whether or not a property value can be changed or set in this entity.
     * The special property `*` can also be marked as accessible or protected, meaning
     * that any other property specified before will take its value. For example
     * `$entity->accessible('*', true)`  means that any property not specified already
     * will be accessible by default.
     *
     * You can also call this method with an array of properties, in which case they
     * will each take the accessibility value specified in the second argument.
     *
     * ### Example:
     *
     * ```
     * $entity->accessible('id', true); // Mark id as not protected
     * $entity->accessible('author_id', false); // Mark author_id as protected
     * $entity->accessible(['id', 'user_id'], true); // Mark both properties as accessible
     * $entity->accessible('*', false); // Mark all properties as protected
     * ```
     *
     * When called without the second param it will return whether or not the property
     * can be set.
     *
     * ### Example:
     *
     * ```
     * $entity->accessible('id'); // Returns whether it can be set or not
     * ```
     *
     * @param string|array $property single or list of properties to change its accessibility
     * @param bool|null $set true marks the property as accessible, false will
     * mark it as protected.
     * @return $this|bool
     */
    public function accessible($property, $set = null)
    {
        if ($set === null) {
            $value = isset($this->_accessible[$property]) ?
                $this->_accessible[$property] :
                null;

            return ($value === null && !empty($this->_accessible['*'])) || $value;
        }

        if ($property === '*') {
            $this->_accessible = array_map(function ($p) use ($set) {
                return (bool)$set;
            }, $this->_accessible);
            $this->_accessible['*'] = (bool)$set;
            return $this;
        }

        foreach ((array)$property as $prop) {
            $this->_accessible[$prop] = (bool)$set;
        }

        return $this;
    }

    /**
     * Returns the alias of the repository from which this entity came from.
     *
     * If called with no arguments, it returns the alias of the repository
     * this entity came from if it is known.
     *
     * @param string|null $alias the alias of the repository
     * @return string|$this
     */
    public function source($alias = null)
    {
        if ($alias === null) {
            return $this->_registryAlias;
        }
        $this->_registryAlias = $alias;

        return $this;
    }

    /**
     * Returns a string representation of this object in a human readable format.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->_properties + [
            '[new]' => $this->isNew(),
            '[accessible]' => array_filter($this->_accessible),
            '[dirty]' => $this->_dirty,
            '[original]' => $this->_original,
            '[virtual]' => $this->_virtual,
            '[errors]' => $this->_errors,
            '[invalid]' => $this->_invalid,
            '[repository]' => $this->_registryAlias
        ];
    }
}
