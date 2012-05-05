<?php
/**
 * ModelValidator.
 *
 * Provides the Model validation logic.
 *
 * PHP versions 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeValidationSet', 'Model/Validator');

/**
 * ModelValidator object.
 *
 * @package       Cake.Model
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class ModelValidator implements ArrayAccess, IteratorAggregate {

/**
 * Holds the CakeValidationSet objects array
 *
 * @var array
 */
	protected $_fields = array();

/**
 * Holds the reference to the model the Validator is attached to
 *
 * @var Model
 */
	protected $_model = array();

/**
 * The validators $validate property
 *
 * @var array
 */
	protected $_validate = array();

/**
 * Holds the available custom callback methods
 *
 * @var array
 */
	protected $_methods = array();

/**
 * Constructor
 *
 * @param Model $Model A reference to the Model the Validator is attached to
 */
	public function __construct(Model $Model) {
		$this->_model = $Model;
	}

/**
 * Returns true if all fields pass validation. Will validate hasAndBelongsToMany associations
 * that use the 'with' key as well. Since _saveMulti is incapable of exiting a save operation.
 *
 * Will validate the currently set data.  Use Model::set() or Model::create() to set the active data.
 *
 * @param array $options An optional array of custom options to be made available in the beforeValidate callback
 * @return boolean True if there are no errors
 */
	public function validates($options = array()) {
		$errors = $this->errors($options);
		if (empty($errors) && $errors !== false) {
			$errors = $this->_validateWithModels($options);
		}
		if (is_array($errors)) {
			return count($errors) === 0;
		}
		return $errors;
	}

/**
 * Validates a single record, as well as all its directly associated records.
 *
 * #### Options
 *
 * - atomic: If true (default), returns boolean. If false returns array.
 * - fieldList: Equivalent to the $fieldList parameter in Model::save()
 * - deep: If set to true, not only directly associated data , but deeper nested associated data is validated as well.
 *
 * @param array $data Record data to validate. This should be an array indexed by association name.
 * @param array $options Options to use when validating record data (see above), See also $options of validates().
 * @return array|boolean If atomic: True on success, or false on failure.
 *    Otherwise: array similar to the $data array passed, but values are set to true/false
 *    depending on whether each record validated successfully.
 */
	public function validateAssociated($data, $options = array()) {
		$model = $this->getModel();

		$options = array_merge(array('atomic' => true, 'deep' => false), $options);
		$model->validationErrors = $validationErrors = $return = array();
		if (!($model->create($data) && $model->validates($options))) {
			$validationErrors[$model->alias] = $model->validationErrors;
			$return[$model->alias] = false;
		} else {
			$return[$model->alias] = true;
		}
		$associations = $model->getAssociated();
		foreach ($data as $association => $values) {
			$validates = true;
			if (isset($associations[$association])) {
				if (in_array($associations[$association], array('belongsTo', 'hasOne'))) {
					if ($options['deep']) {
						$validates = $model->{$association}->validateAssociated($values, $options);
					} else {
						$validates = $model->{$association}->create($values) !== null && $model->{$association}->validates($options);
					}
					if (is_array($validates)) {
						if (in_array(false, $validates, true)) {
							$validates = false;
						} else {
							$validates = true;
						}
					}
					$return[$association] = $validates;
				} elseif ($associations[$association] === 'hasMany') {
					$validates = $model->{$association}->validateMany($values, $options);
					$return[$association] = $validates;
				}
				if (!$validates || (is_array($validates) && in_array(false, $validates, true))) {
					$validationErrors[$association] = $model->{$association}->validationErrors;
				}
			}
		}

		$model->validationErrors = $validationErrors;
		if (isset($validationErrors[$model->alias])) {
			$model->validationErrors = $validationErrors[$model->alias];
		}
		if (!$options['atomic']) {
			return $return;
		}
		if ($return[$model->alias] === false || !empty($model->validationErrors)) {
			return false;
		}
		return true;
	}

/**
 * Validates multiple individual records for a single model
 *
 * #### Options
 *
 * - atomic: If true (default), returns boolean. If false returns array.
 * - fieldList: Equivalent to the $fieldList parameter in Model::save()
 * - deep: If set to true, all associated data will be validated as well.
 *
 * @param array $data Record data to validate. This should be a numerically-indexed array
 * @param array $options Options to use when validating record data (see above), See also $options of validates().
 * @return boolean True on success, or false on failure.
 * @return mixed If atomic: True on success, or false on failure.
 *    Otherwise: array similar to the $data array passed, but values are set to true/false
 *    depending on whether each record validated successfully.
 */
	public function validateMany($data, $options = array()) {
		$model = $this->getModel();
		$options = array_merge(array('atomic' => true, 'deep' => false), $options);
		$model->validationErrors = $validationErrors = $return = array();
		foreach ($data as $key => $record) {
			if ($options['deep']) {
				$validates = $model->validateAssociated($record, $options);
			} else {
				$validates = $model->create($record) && $model->validates($options);
			}
			if ($validates === false || (is_array($validates) && in_array(false, $validates, true))) {
				$validationErrors[$key] = $model->validationErrors;
				$validates = false;
			} else {
				$validates = true;
			}
			$return[$key] = $validates;
		}
		$model->validationErrors = $validationErrors;
		if (!$options['atomic']) {
			return $return;
		}
		if (empty($model->validationErrors)) {
			return true;
		}
		return false;
	}

/**
 * Returns an array of fields that have failed validation. On the current model.
 *
 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
 * @return array Array of invalid fields
 * @see Model::validates()
 */
	public function errors($options = array()) {
		if (!$this->_triggerBeforeValidate($options)) {
			return false;
		}
		$model = $this->getModel();

		if (!$this->_parseRules()) {
			return $model->validationErrors;
		}

		$fieldList = isset($options['fieldList']) ? $options['fieldList'] : array();
		$exists = $model->exists();
		$methods = $this->getMethods();
		$fields = $this->_validationList($fieldList);

		foreach ($fields as $field) {
			$field->setMethods($methods);
			$field->setValidationDomain($model->validationDomain);
			$data = isset($model->data[$model->alias]) ? $model->data[$model->alias] : array();
			$errors = $field->validate($data, $exists);
			foreach ($errors as $error) {
				$this->invalidate($field->field, $error);
			}
		}

		return $model->validationErrors;
	}

/**
 * Marks a field as invalid, optionally setting the name of validation
 * rule (in case of multiple validation for field) that was broken.
 *
 * @param string $field The name of the field to invalidate
 * @param string $value Name of validation rule that failed, or validation message to
 *    be returned. If no validation key is provided, defaults to true.
 * @return void
 */
	public function invalidate($field, $value = true) {
		$this->getModel()->validationErrors[$field][] = $value;
	}

/**
 * Gets all possible custom methods from the Model, Behaviors and the Validator.
 * gets the corresponding methods.
 *
 * @return array The requested methods
 */
	public function getMethods() {
		if (!empty($this->_methods)) {
			return $this->_methods;
		}

		$methods = array();
		foreach (get_class_methods($this->_model) as $method) {
			$methods[strtolower($method)] = array($this->_model, $method);
		}

		foreach (array_keys($this->_model->Behaviors->methods()) as $mthod) {
			$methods += array(strtolower($method) => array($this->_model, $method));
		}

		return $this->_methods = $methods;
	}

/**
 * Gets all fields if $name is null (default), or the field for fieldname $name if it's found.
 *
 * @param string $name [optional] The fieldname to fetch. Defaults to null.
 * @return mixed Either array of CakeValidationSet objects , single object for $name or false when $name not present in fields
 */
	public function getFields($name = null) {
		if ($name !== null && !empty($this->_fields[$name])) {
			return $this->_fields[$name];
		} elseif ($name !==null) {
			return false;
		}
		return $this->_fields;
	}

/**
 * Sets the CakeValidationSet instances from the Model::$validate property after processing the fieldList and whiteList.
 * If Model::$validate is not set or empty, this method returns false. True otherwise.
 *
 * @return boolean True if Model::$validate was processed, false otherwise
 */
	protected function _parseRules() {
		if ($this->_validate === $this->_model->validate) {
			return true;
		}

		if (empty($this->_model->validate)) {
			$this->_validate = array();
			$this->_fields = array();
			return false;
		}

		$this->_validate = $this->_model->validate;
		$this->_fields = array();
		$methods = $this->getMethods();
		foreach ($this->_validate as $fieldName => $ruleSet) {
			$this->_fields[$fieldName] = new CakeValidationSet($fieldName, $ruleSet, $methods);
		}
		return true;
	}

/**
 * Sets the I18n domain for validation messages. This method is chainable.
 *
 * @param string $validationDomain [optional] The validation domain to be used.
 * @return ModelValidator
 */
	public function setValidationDomain($validationDomain = null) {
		if (empty($validationDomain)) {
			$validationDomain = 'default';
		}
		$this->getModel()->validationDomain = $validationDomain;
		return $this;
	}

/**
 * Gets the parent Model
 *
 * @return Model
 */
	public function getModel() {
		return $this->_model;
	}

/**
 * Processes the Model's whitelist and returns the list of fields
 * to be validated
 *
 * @return array List of validation rules to be applied
 */
	protected function _validationList($fieldList = array()) {
		$model = $this->getModel();
		$whitelist = $model->whitelist;

		if (!empty($fieldList)) {
			if (!empty($fieldList[$model->alias]) && is_array($fieldList[$model->alias])) {
				$whitelist = $fieldList[$model->alias];
			} else {
				$whitelist = $fieldList;
			}
		}
		unset($fieldList);

		$validateList = array();
		if (!empty($whitelist)) {
			$this->validationErrors = array();

			foreach ((array)$whitelist as $f) {
				if (!empty($this->_fields[$f])) {
					$validateList[$f] = $this->_fields[$f];
				}
			}
		} else {
			return $this->_fields;
		}

		return $validateList;
	}

/**
 * Runs validation for hasAndBelongsToMany associations that have 'with' keys
 * set. And data in the set() data set.
 *
 * @param array $options Array of options to use on Validation of with models
 * @return boolean Failure of validation on with models.
 * @see Model::validates()
 */
	protected function _validateWithModels($options) {
		$valid = true;
		$model = $this->getModel();

		foreach ($model->hasAndBelongsToMany as $assoc => $association) {
			if (empty($association['with']) || !isset($model->data[$assoc])) {
				continue;
			}
			list($join) = $model->joinModel($model->hasAndBelongsToMany[$assoc]['with']);
			$data = $model->data[$assoc];

			$newData = array();
			foreach ((array)$data as $row) {
				if (isset($row[$model->hasAndBelongsToMany[$assoc]['associationForeignKey']])) {
					$newData[] = $row;
				} elseif (isset($row[$join]) && isset($row[$join][$model->hasAndBelongsToMany[$assoc]['associationForeignKey']])) {
					$newData[] = $row[$join];
				}
			}
			if (empty($newData)) {
				continue;
			}
			foreach ($newData as $data) {
				$data[$model->hasAndBelongsToMany[$assoc]['foreignKey']] = $model->id;
				$model->{$join}->create($data);
				$valid = ($valid && $model->{$join}->validator()->validates($options));
			}
		}
		return $valid;
	}

/**
 * Propagates the beforeValidate event
 *
 * @param array $options
 * @return boolean
 */
	protected function _triggerBeforeValidate($options = array()) {
		$model = $this->getModel();
		$event = new CakeEvent('Model.beforeValidate', $model, array($options));
		list($event->break, $event->breakOn) = array(true, false);
		$model->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return false;
		}
		return true;
	}

/**
 * Returns wheter a rule set is defined for a field or not
 *
 * @param string $field name of the field to check
 * @return boolean
 **/
	public function offsetExists($field) {
		$this->_parseRules();
		return isset($this->_fields[$field]);
	}

/**
 * Returns the rule set for a field
 *
 * @param string $field name of the field to check
 * @return CakeValidationSet
 **/
	public function offsetGet($field) {
		$this->_parseRules();
		return $this->_fields[$field];
	}

/**
 * Sets the rule set for a field
 *
 * @param string $field name of the field to set
 * @param array|CakeValidationSet $rules set of rules to apply to field
 * @return void
 **/
	public function offsetSet($field, $rules) {
		$this->_parseRules();
		if (!$rules instanceof CakeValidationSet) {
			$rules = new CakeValidationSet($field, $rules, $this->getMethods());
		}
		$this->_fields[$field] = $rules;
	}

/**
 * Unsets the rulset for a field
 *
 * @param string $field name of the field to unset
 * @return void
 **/
	public function offsetUnset($field) {
		unset($this->_fields[$field]);
	}

/**
 * Returns an iterator for each of the fields to be validated
 *
 * @return ArrayIterator
 **/
	public function getIterator() {
		$this->_parseRules();
		return new ArrayIterator($this->_fields);
	}
}
