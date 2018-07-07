<?php
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

use ArrayAccess;
use JsonSerializable;

/**
 * Describes the methods that any class representing a data storage should
 * comply with.
 *
 * @property mixed $id Alias for commonly used primary key.
 */
interface EntityInterface extends ArrayAccess, JsonSerializable
{

    /**
     * Sets hidden properties.
     *
     * @param array $properties An array of properties to hide from array exports.
     * @param bool $merge Merge the new properties with the existing. By default false.
     * @return $this
     */
    public function setHidden(array $properties, $merge = false);

    /**
     * Gets the hidden properties.
     *
     * @return array
     */
    public function getHidden();

    /**
     * Sets the virtual properties on this entity.
     *
     * @param array $properties An array of properties to treat as virtual.
     * @param bool $merge Merge the new properties with the existing. By default false.
     * @return $this
     */
    public function setVirtual(array $properties, $merge = false);

    /**
     * Gets the virtual properties on this entity.
     *
     * @return array
     */
    public function getVirtual();

    /**
     * Sets the dirty status of a single property.
     *
     * @param string $property the field to set or check status for
     * @param bool $isDirty true means the property was changed, false means
     * it was not changed
     * @return $this
     */
    public function setDirty($property, $isDirty);

    /**
     * Checks if the entity is dirty or if a single property of it is dirty.
     *
     * @param string|null $property The field to check the status for. Null for the whole entity.
     * @return bool Whether the property was changed or not
     */
    public function isDirty($property = null);

    /**
     * Returns whether this entity has errors.
     *
     * @param bool $includeNested true will check nested entities for hasErrors()
     * @return bool
     */
    public function hasErrors($includeNested = true);

    /**
     * Returns all validation errors.
     *
     * @return array
     */
    public function getErrors();

    /**
     * Returns validation errors of a field
     *
     * @param string $field Field name to get the errors from
     * @return array
     */
    public function getError($field);

    /**
     * Sets error messages to the entity
     *
     * @param array $fields The array of errors to set.
     * @param bool $overwrite Whether or not to overwrite pre-existing errors for $fields
     * @return $this
     */
    public function setErrors(array $fields, $overwrite = false);

    /**
     * Sets errors for a single field
     *
     * @param string $field The field to get errors for, or the array of errors to set.
     * @param string|array $errors The errors to be set for $field
     * @param bool $overwrite Whether or not to overwrite pre-existing errors for $field
     * @return $this
     */
    public function setError($field, $errors, $overwrite = false);

    /**
     * Stores whether or not a property value can be changed or set in this entity.
     *
     * @param string|array $property single or list of properties to change its accessibility
     * @param bool $set true marks the property as accessible, false will
     * mark it as protected.
     * @return $this
     */
    public function setAccess($property, $set);

    /**
     * Checks if a property is accessible
     *
     * @param string $property Property name to check
     * @return bool
     */
    public function isAccessible($property);

    /**
     * Sets the source alias
     *
     * @param string $alias the alias of the repository
     * @return $this
     */
    public function setSource($alias);

    /**
     * Returns the alias of the repository from which this entity came from.
     *
     * @return string
     */
    public function getSource();

    /**
     * Returns an array with the requested original properties
     * stored in this entity, indexed by property name.
     *
     * @param array $properties List of properties to be returned
     * @return array
     */
    public function extractOriginal(array $properties);

    /**
     * Returns an array with only the original properties
     * stored in this entity, indexed by property name.
     *
     * @param array $properties List of properties to be returned
     * @return array
     */
    public function extractOriginalChanged(array $properties);

    /**
     * Sets one or multiple properties to the specified value
     *
     * @param string|array $property the name of property to set or a list of
     * properties with their respective values
     * @param mixed $value The value to set to the property or an array if the
     * first argument is also an array, in which case will be treated as $options
     * @param array $options options to be used for setting the property. Allowed option
     * keys are `setter` and `guard`
     * @return \Cake\Datasource\EntityInterface
     */
    public function set($property, $value = null, array $options = []);

    /**
     * Returns the value of a property by name
     *
     * @param string $property the name of the property to retrieve
     * @return mixed
     */
    public function &get($property);

    /**
     * Returns whether this entity contains a property named $property
     * regardless of if it is empty.
     *
     * @param string|array $property The property to check.
     * @return bool
     */
    public function has($property);

    /**
     * Removes a property or list of properties from this entity
     *
     * @param string|array $property The property to unset.
     * @return \Cake\Datasource\EntityInterface
     */
    public function unsetProperty($property);

    /**
     * Get the list of visible properties.
     *
     * @return array A list of properties that are 'visible' in all representations.
     */
    public function visibleProperties();

    /**
     * Returns an array with all the visible properties set in this entity.
     *
     * *Note* hidden properties are not visible, and will not be output
     * by toArray().
     *
     * @return array
     */
    public function toArray();

    /**
     * Returns an array with the requested properties
     * stored in this entity, indexed by property name
     *
     * @param array $properties list of properties to be returned
     * @param bool $onlyDirty Return the requested property only if it is dirty
     * @return array
     */
    public function extract(array $properties, $onlyDirty = false);

    /**
     * Sets the entire entity as clean, which means that it will appear as
     * no properties being modified or added at all. This is an useful call
     * for an initial object hydration
     *
     * @return void
     */
    public function clean();

    /**
     * Returns whether or not this entity has already been persisted.
     * This method can return null in the case there is no prior information on
     * the status of this entity.
     *
     * If called with a boolean, this method will set the status of this instance.
     * Using `true` means that the instance has not been persisted in the database, `false`
     * that it already is.
     *
     * @param bool|null $new Indicate whether or not this instance has been persisted.
     * @return bool If it is known whether the entity was already persisted
     * null otherwise
     */
    public function isNew($new = null);
}
