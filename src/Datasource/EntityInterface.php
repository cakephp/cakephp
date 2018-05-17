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
 * In 4.x the following methods will officially be added to the interface:
 *
 * @method $this setHidden(array $properties, $merge = false)
 * @method array getHidden()
 * @method $this setVirtual(array $properties, $merge = false)
 * @method array getVirtual()
 * @method $this setDirty($property, $isDirty)
 * @method bool isDirty($property = null)
 * @method array getErrors()
 * @method array getError($field)
 * @method array setErrors(array $fields, $overwrite = false)
 * @method array setError($field, $errors, $overwrite = false)
 * @method $this setAccess($property, $set)
 * @method bool isAccessible($property)
 * @method $this setSource($source)
 * @method string getSource()
 * @method array extractOriginal(array $properties)
 * @method array extractOriginalChanged(array $properties)
 *
 * @property mixed $id Alias for commonly used primary key.
 */
interface EntityInterface extends ArrayAccess, JsonSerializable
{

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
