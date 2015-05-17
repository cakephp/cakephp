<?php
/**
 * ModelValidator.
 *
 * Provides the Model validation logic.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeValidationSet', 'Model/Validator');
App::uses('Hash', 'Utility');

/**
 * ModelValidator object encapsulates all methods related to data validations for a model
 * It also provides an API to dynamically change validation rules for each model field.
 *
 * Implements ArrayAccess to easily modify rules as usually done with `Model::$validate`
 * definition array
 *
 * @package       Cake.Model
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class ModelValidator implements ArrayAccess, IteratorAggregate, Countable {

/**
 * Holds the CakeValidationSet objects array
 *
 * @var array
 */
	protected $_fields = array();

/**
 * Holds the reference to the model this Validator is attached to
 *
 * @var Model
 */
	protected $_model = array();

/**
 * The validators $validate property, used for checking whether validation
 * rules definition changed in the model and should be refreshed in this class
 *
 * @var array
 */
	protected $_validate = array();

/**
 * Holds the available custom callback methods, usually taken from model methods
 * and behavior methods
 *
 * @var array
 */
	protected $_methods = array();

/**
 * Holds the available custom callback methods from the model
 *
 * @var array
 */
	protected $_modelMethods = array();

/**
 * Holds the list of behavior names that were attached when this object was created
 *
 * @var array
 */
	protected $_behaviors = array();

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
 * that use the 'with' key as well. Since `Model::_saveMulti` is incapable of exiting a save operation.
 *
 * Will validate the currently set data. Use `Model::set()` or `Model::create()` to set the active data.
 *
 * @param array $options An optional array of custom options to be made available in the beforeValidate callback
 * @return bool True if there are no errors
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
 * Warning: This method could potentially change the passed argument `$data`,
 * If you do not want this to happen, make a copy of `$data` before passing it
 * to this method
 *
 * @param array &$data Record data to validate. This should be an array indexed by association name.
 * @param array $options Options to use when validating record data (see above), See also $options of validates().
 * @return array|bool If atomic: True on success, or false on failure.
 *    Otherwise: array similar to the $data array passed, but values are set to true/false
 *    depending on whether each record validated successfully.
 */
	public function validateAssociated(&$data, $options = array()) {
		$model = $this->getModel();
		$options += array('atomic' => true, 'deep' => false);
		$model->validationErrors = $validationErrors = $return = array();
		$model->create(null);
		$return[$model->alias] = true;
		if (!($model->set($data) && $model->validates($options))) {
			$validationErrors[$model->alias] = $model->validationErrors;
			$return[$model->alias] = false;
		}
		$data = $model->data;
		if (!empty($options['deep']) && isset($data[$model->alias])) {
			$recordData = $data[$model->alias];
			unset($data[$model->alias]);
			$data += $recordData;
		}

		$associations = $model->getAssociated();
		foreach ($data as $association => &$values) {
			$validates = true;
			if (isset($associations[$association])) {
				if (in_array($associations[$association], array('belongsTo', 'hasOne'))) {
					if ($options['deep']) {
						$validates = $model->{$association}->validateAssociated($values, $options);
					} else {
						$model->{$association}->create(null);
						$validates = $model->{$association}->set($values) && $model->{$association}->validates($options);
						$data[$association] = $model->{$association}->data[$model->{$association}->alias];
					}
					if (is_array($validates)) {
						$validates = !in_array(false, Hash::flatten($validates), true);
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
			unset($validationErrors[$model->alias]);
			$model->validationErrors = array_merge($model->validationErrors, $validationErrors);
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
 * Warning: This method could potentially change the passed argument `$data`,
 * If you do not want this to happen, make a copy of `$data` before passing it
 * to this method
 *
 * @param array &$data Record data to validate. This should be a numerically-indexed array
 * @param array $options Options to use when validating record data (see above), See also $options of validates().
 * @return mixed If atomic: True on success, or false on failure.
 *    Otherwise: array similar to the $data array passed, but values are set to true/false
 *    depending on whether each record validated successfully.
 */
	public function validateMany(&$data, $options = array()) {
		$model = $this->getModel();
		$options += array('atomic' => true, 'deep' => false);
		$model->validationErrors = $validationErrors = $return = array();
		foreach ($data as $key => &$record) {
			if ($options['deep']) {
				$validates = $model->validateAssociated($record, $options);
			} else {
				$model->create(null);
				$validates = $model->set($record) && $model->validates($options);
				$data[$key] = $model->data;
			}
			if ($validates === false || (is_array($validates) && in_array(false, Hash::flatten($validates), true))) {
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
		return empty($model->validationErrors);
	}

/**
 * Returns an array of fields that have failed validation. On the current model. This method will
 * actually run validation rules over data, not just return the messages.
 *
 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
 * @return array Array of invalid fields
 * @triggers Model.afterValidate $model
 * @see ModelValidator::validates()
 */
	public function errors($options = array()) {
		if (!$this->_triggerBeforeValidate($options)) {
			return false;
		}
		$model = $this->getModel();

		if (!$this->_parseRules()) {
			return $model->validationErrors;
		}

		$fieldList = $model->whitelist;
		if (empty($fieldList) && !empty($options['fieldList'])) {
			if (!empty($options['fieldList'][$model->alias]) && is_array($options['fieldList'][$model->alias])) {
				$fieldList = $options['fieldList'][$model->alias];
			} else {
				$fieldList = $options['fieldList'];
			}
		}

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

		$model->getEventManager()->dispatch(new CakeEvent('Model.afterValidate', $model));
		return $model->validationErrors;
	}

/**
 * Marks a field as invalid, optionally setting a message explaining
 * why the rule failed
 *
 * @param string $field The name of the field to invalidate
 * @param string $message Validation message explaining why the rule failed, defaults to true.
 * @return void
 */
	public function invalidate($field, $message = true) {
		$this->getModel()->validationErrors[$field][] = $message;
	}

/**
 * Gets all possible custom methods from the Model and attached Behaviors
 * to be used as validators
 *
 * @return array List of callables to be used as validation methods
 */
	public function getMethods() {
		$behaviors = $this->_model->Behaviors->enabled();
		if (!empty($this->_methods) && $behaviors === $this->_behaviors) {
			return $this->_methods;
		}
		$this->_behaviors = $behaviors;

		if (empty($this->_modelMethods)) {
			foreach (get_class_methods($this->_model) as $method) {
				$this->_modelMethods[strtolower($method)] = array($this->_model, $method);
			}
		}

		$methods = $this->_modelMethods;
		foreach (array_keys($this->_model->Behaviors->methods()) as $method) {
			$methods += array(strtolower($method) => array($this->_model, $method));
		}

		return $this->_methods = $methods;
	}

/**
 * Returns a CakeValidationSet object containing all validation rules for a field, if no
 * params are passed then it returns an array with all CakeValidationSet objects for each field
 *
 * @param string $name [optional] The fieldname to fetch. Defaults to null.
 * @return CakeValidationSet|array|null
 */
	public function getField($name = null) {
		$this->_parseRules();
		if ($name !== null) {
			if (!empty($this->_fields[$name])) {
				return $this->_fields[$name];
			}
			return null;
		}
		return $this->_fields;
	}

/**
 * Sets the CakeValidationSet objects from the `Model::$validate` property
 * If `Model::$validate` is not set or empty, this method returns false. True otherwise.
 *
 * @return bool true if `Model::$validate` was processed, false otherwise
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
			$this->_fields[$fieldName] = new CakeValidationSet($fieldName, $ruleSet);
			$this->_fields[$fieldName]->setMethods($methods);
		}
		return true;
	}

/**
 * Sets the I18n domain for validation messages. This method is chainable.
 *
 * @param string $validationDomain [optional] The validation domain to be used.
 * @return $this
 */
	public function setValidationDomain($validationDomain = null) {
		if (empty($validationDomain)) {
			$validationDomain = 'default';
		}
		$this->getModel()->validationDomain = $validationDomain;
		return $this;
	}

/**
 * Gets the model related to this validator
 *
 * @return Model
 */
	public function getModel() {
		return $this->_model;
	}

/**
 * Processes the passed fieldList and returns the list of fields to be validated
 *
 * @param array $fieldList list of fields to be used for validation
 * @return array List of validation rules to be applied
 */
	protected function _validationList($fieldList = array()) {
		if (empty($fieldList) || Hash::dimensions($fieldList) > 1) {
			return $this->_fields;
		}

		$validateList = array();
		$this->validationErrors = array();
		foreach ((array)$fieldList as $f) {
			if (!empty($this->_fields[$f])) {
				$validateList[$f] = $this->_fields[$f];
			}
		}

		return $validateList;
	}

/**
 * Runs validation for hasAndBelongsToMany associations that have 'with' keys
 * set and data in the data set.
 *
 * @param array $options Array of options to use on Validation of with models
 * @return bool Failure of validation on with models.
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
			foreach ($newData as $data) {
				$data[$model->hasAndBelongsToMany[$assoc]['foreignKey']] = $model->id;
				$model->{$join}->create($data);
				$valid = ($valid && $model->{$join}->validator()->validates($options));
			}
		}
		return $valid;
	}

/**
 * Propagates beforeValidate event
 *
 * @param array $options Options to pass to callback.
 * @return bool
 * @triggers Model.beforeValidate $model, array($options)
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
 * Returns whether a rule set is defined for a field or not
 *
 * @param string $field name of the field to check
 * @return bool
 */
	public function offsetExists($field) {
		$this->_parseRules();
		return isset($this->_fields[$field]);
	}

/**
 * Returns the rule set for a field
 *
 * @param string $field name of the field to check
 * @return CakeValidationSet
 */
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
 */
	public function offsetSet($field, $rules) {
		$this->_parseRules();
		if (!$rules instanceof CakeValidationSet) {
			$rules = new CakeValidationSet($field, $rules);
			$methods = $this->getMethods();
			$rules->setMethods($methods);
		}
		$this->_fields[$field] = $rules;
	}

/**
 * Unsets the rule set for a field
 *
 * @param string $field name of the field to unset
 * @return void
 */
	public function offsetUnset($field) {
		$this->_parseRules();
		unset($this->_fields[$field]);
	}

/**
 * Returns an iterator for each of the fields to be validated
 *
 * @return ArrayIterator
 */
	public function getIterator() {
		$this->_parseRules();
		return new ArrayIterator($this->_fields);
	}

/**
 * Returns the number of fields having validation rules
 *
 * @return int
 */
	public function count() {
		$this->_parseRules();
		return count($this->_fields);
	}

/**
 * Adds a new rule to a field's rule set. If second argument is an array or instance of
 * CakeValidationSet then rules list for the field will be replaced with second argument and
 * third argument will be ignored.
 *
 * ## Example:
 *
 * ```
 *		$validator
 *			->add('title', 'required', array('rule' => 'notBlank', 'required' => true))
 *			->add('user_id', 'valid', array('rule' => 'numeric', 'message' => 'Invalid User'))
 *
 *		$validator->add('password', array(
 *			'size' => array('rule' => array('lengthBetween', 8, 20)),
 *			'hasSpecialCharacter' => array('rule' => 'validateSpecialchar', 'message' => 'not valid')
 *		));
 * ```
 *
 * @param string $field The name of the field where the rule is to be added
 * @param string|array|CakeValidationSet $name name of the rule to be added or list of rules for the field
 * @param array|CakeValidationRule $rule or list of rules to be added to the field's rule set
 * @return $this
 */
	public function add($field, $name, $rule = null) {
		$this->_parseRules();
		if ($name instanceof CakeValidationSet) {
			$this->_fields[$field] = $name;
			return $this;
		}

		if (!isset($this->_fields[$field])) {
			$rule = (is_string($name)) ? array($name => $rule) : $name;
			$this->_fields[$field] = new CakeValidationSet($field, $rule);
		} else {
			if (is_string($name)) {
				$this->_fields[$field]->setRule($name, $rule);
			} else {
				$this->_fields[$field]->setRules($name);
			}
		}

		$methods = $this->getMethods();
		$this->_fields[$field]->setMethods($methods);

		return $this;
	}

/**
 * Removes a rule from the set by its name
 *
 * ## Example:
 *
 * ```
 *		$validator
 *			->remove('title', 'required')
 *			->remove('user_id')
 * ```
 *
 * @param string $field The name of the field from which the rule will be removed
 * @param string $rule the name of the rule to be removed
 * @return $this
 */
	public function remove($field, $rule = null) {
		$this->_parseRules();
		if ($rule === null) {
			unset($this->_fields[$field]);
		} else {
			$this->_fields[$field]->removeRule($rule);
		}
		return $this;
	}
}
