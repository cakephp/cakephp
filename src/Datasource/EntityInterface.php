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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Datasource;

use ArrayAccess;
use Cake\Validation\Validator;
use JsonSerializable;

/**
 * Describes the methods that any class representing a data storage should
 * comply with.
 */
interface EntityInterface extends ArrayAccess, JsonSerializable {

/**
 * Sets one or multiple properties to the specified value
 *
 * @param string|array $property the name of property to set or a list of
 * properties with their respective values
 * @param mixed|array $value the value to set to the property or an array if the
 * first argument is also an array, in which case will be treated as $options
 * @param array $options options to be used for setting the property. Allowed option
 * keys are `setter` and `guard`
 * @return \Cake\Datasource\EntityInterface
 */
	public function set($property, $value = null, $options = []);

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
 * @param string $property
 * @return boolean
 */
	public function has($property);

/**
 * Removes a property or list of properties from this entity
 *
 * @param string|array $property
 * @return \Cake\ORM\
 */
	public function unsetProperty($property);

/**
 * Get/Set the hidden properties on this entity.
 *
 * If the properties argument is null, the currently hidden properties
 * will be returned. Otherwise the hidden properties will be set.
 *
 * @param null|array Either an array of properties to hide or null to get properties
 * @return array|Entity
 */
	public function hiddenProperties($properties = null);

/**
 * Get/Set the virtual properties on this entity.
 *
 * If the properties argument is null, the currently virtual properties
 * will be returned. Otherwise the virtual properties will be set.
 *
 * @param null|array Either an array of properties to treat as virtual or null to get properties
 * @return array|Entity
 */
	public function virtualProperties($properties = null);

/**
 * Get the list of visible properties.
 *
 * @return array A list of properties that are 'visible' in all representations.
 */
	public function visibleProperties();

/**
 * Returns an array with all the properties that have been set
 * to this entity
 *
 * @return array
 */
	public function toArray();

/**
 * Returns an array with the requested properties
 * stored in this entity, indexed by property name
 *
 * @param array $properties list of properties to be returned
 * @param boolean $onlyDirty Return the requested property only if it is dirty
 * @return array
 */
	public function extract(array $properties, $onlyDirty = false);

/**
 * Sets the dirty status of a single property. If called with no second
 * argument, it will return whether the property was modified or not
 * after the object creation.
 *
 * When called with no arguments it will return whether or not there are any
 * dirty property in the entity
 *
 * @param string $property the field to set or check status for
 * @param null|boolean true means the property was changed, false means
 * it was not changed and null will make the function return current state
 * for that property
 * @return boolean whether the property was changed or not
 */
	public function dirty($property = null, $isDirty = null);

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
 * If called with a boolean it will set the known status of this instance,
 * true means that the instance is not yet persisted in the database, false
 * that it already is.
 *
 * @param boolean $new true if it is known this instance was persisted
 * @return boolean if it is known whether the entity was already persisted
 * null otherwise
 */
	public function isNew($new = null);

/**
 * Validates the internal properties using a validator object. The resulting
 * errors will be copied inside this entity and can be retrieved using the
 * `errors` method.
 *
 * This function returns true if there were no validation errors or false
 * otherwise.
 *
 * @param \Cake\Validation\Validator $validator
 * @return boolean
 */
	public function validate(Validator $validator);

/**
 * Sets the error messages for a field or a list of fields. When called
 * without the second argument it returns the validation
 * errors for the specified fields. If called with no arguments it returns
 * all the validation error messages stored in this entity.
 *
 * When used as a setter, this method will return this entity instance for method
 * chaining.
 *
 * @param string|array $field
 * @param string|array $errors The errors to be set for $field
 * @return array|\Cake\Datasource\EntityInterface
 */
	public function errors($field = null, $errors = null);

/**
 * Stores whether or not a property value can be changed or set in this entity.
 * The special property '*' can also be marked as accessible or protected, meaning
 * that any other property specified before will take its value. For example
 * `$entity->accessible('*', true)`  means that any property not specified already
 * will be accessible by default.
 *
 * @param string|array single or list of properties to change its accessibility
 * @param boolean $set true marks the property as accessible, false will
 * mark it as protected.
 * @return \Cake\Datasource\EntityInterface|boolean
 */
	public function accessible($property, $set = null);

}
