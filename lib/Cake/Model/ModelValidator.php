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
App::uses('CakeField', 'Model/Validator');
App::uses('CakeRule', 'Model/Validator');

/**
 * ModelValidator object.
 *
 * @package       Cake.Model
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class ModelValidator {

/**
 * The default ModelValidator class name
 *
 * @var string
 */
	const DEFAULT_VALIDATOR = 'ModelValidator';

/**
 * The default validation domain
 *
 * @var string
 */
	const DEFAULT_DOMAIN = 'default';

/**
 * Holds the data array from the Model
 *
 * @var array
 */
	public $data = array();

/**
 * The default ValidationDomain
 *
 * @var string
 */
	public $validationDomain = 'default';

/**
 * Holds the validationErrors
 *
 * @var array
 */
	public $validationErrors = array();

/**
 * Holds the options
 *
 * @var array
 */
	public $options = array();

/**
 * Holds the CakeField objects array
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
		$this->validationErrors = array();
		$errors = $this->invalidFields($options);
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
		$options = array_merge(array('atomic' => true, 'deep' => false), $options);
		$this->validationErrors = $this->getModel()->validationErrors = $return = array();
		if (!($this->getModel()->create($data) && $this->validates($options))) {
			$this->validationErrors = array($this->getModel()->alias => $this->validationErrors);
			$return[$this->getModel()->alias] = false;
		} else {
			$return[$this->getModel()->alias] = true;
		}
		$associations = $this->getModel()->getAssociated();
		foreach ($data as $association => $values) {
			$validates = true;
			if (isset($associations[$association])) {
				if (in_array($associations[$association], array('belongsTo', 'hasOne'))) {
					if ($options['deep']) {
						$validates = $this->getModel()->{$association}->getValidator()->validateAssociated($values, $options);
					} else {
						$validates = $this->getModel()->{$association}->create($values) !== null && $this->getModel()->{$association}->getValidator()->validates($options);
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
					$validates = $this->getModel()->{$association}->getValidator()->validateMany($values, $options);
					$return[$association] = $validates;
				}
				if (!$validates || (is_array($validates) && in_array(false, $validates, true))) {
					$this->validationErrors[$association] = $this->getModel()->{$association}->getValidator()->validationErrors;
				}
			}
		}

		if (isset($this->validationErrors[$this->getModel()->alias])) {
			$this->validationErrors = $this->validationErrors[$this->getModel()->alias];
		}
		$this->getModel()->validationErrors = $this->validationErrors;
		if (!$options['atomic']) {
			return $return;
		}
		if ($return[$this->getModel()->alias] === false || !empty($this->validationErrors)) {
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
		$options = array_merge(array('atomic' => true, 'deep' => false), $options);
		$this->validationErrors = $validationErrors = $this->getModel()->validationErrors = $return = array();
		foreach ($data as $key => $record) {
			if ($options['deep']) {
				$validates = $this->validateAssociated($record, $options);
			} else {
				$validates = $this->getModel()->create($record) && $this->validates($options);
			}
			if ($validates === false || (is_array($validates) && in_array(false, $validates, true))) {
				$validationErrors[$key] = $this->validationErrors;
				$validates = false;
			} else {
				$validates = true;
			}
			$return[$key] = $validates;
		}
		$this->validationErrors = $this->getModel()->validationErrors = $validationErrors;
		if (!$options['atomic']) {
			return $return;
		}
		if (empty($this->validationErrors)) {
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
	public function invalidFields($options = array()) {
		if (!$this->propagateBeforeValidate($options)) {
			return false;
		}
		$this->data = array();

		$this->setOptions($options);

		if (!$this->setFields()) {
			return $this->getModel()->validationErrors = $this->validationErrors;
		}

		$this->getData();
		$this->getMethods();
		$this->setValidationDomain();

		foreach ($this->_fields as $field) {
			$field->validate();
		}

		$this->setFields(true);

		return $this->getModel()->validationErrors = $this->validationErrors;
	}

/**
 * Marks a field as invalid, optionally setting the name of validation
 * rule (in case of multiple validation for field) that was broken.
 *
 * @param string $field The name of the field to invalidate
 * @param mixed $value Name of validation rule that was not failed, or validation message to
 *    be returned. If no validation key is provided, defaults to true.
 * @return void
 */
	public function invalidate($field, $value = true) {
		if (!is_array($this->validationErrors)) {
			$this->validationErrors = array();
		}
		$this->validationErrors[$field][] = $this->getModel()->validationErrors[$field][] = $value;
	}

/**
 * Gets the current data from the model and sets it to $this->data
 *
 * @param string $field [optional]
 * @return array The data
 */
	public function getData($field = null, $all = false) {
		if (!empty($this->data)) {
			if ($field !== null && isset($this->data[$field])) {
				return $this->data[$field];
			}
			return $this->data;
		}

		$this->data = $this->_model->data;
		if (FALSE === $all && isset($this->data[$this->_model->alias])) {
			$this->data = $this->data[$this->_model->alias];
		} elseif (!is_array($this->data)) {
			$this->data = array();
		}

		if ($field !== null && isset($this->data[$field])) {
			return $this->data[$field];
		}

		return $this->data;
	}

/**
 * Gets all possible custom methods from the Model, Behaviors and the Validator.
 * If $type is null (default) gets all methods. If $type is one of 'model', 'behaviors' or 'validator',
 * gets the corresponding methods.
 *
 * @param string $type [optional] The methods type to get. Defaults to null
 * @return array The requested methods
 */
	public function getMethods($type = null) {
		if (!empty($this->_methods)) {
			if ($type !== null && !empty($this->_methods[$type])) {
				return $this->_methods[$type];
			}
			return $this->_methods;
		}

		$this->_methods['model'] = array_map('strtolower', get_class_methods($this->_model));
		$this->_methods['behaviors'] = array_keys($this->_model->Behaviors->methods());
		$this->_methods['validator'] = get_class_methods($this);

		if ($type !== null && !empty($this->_methods[$type])) {
			return $this->_methods[$type];
		}
		unset($type);

		return $this->_methods;
	}

/**
 * Gets all fields if $name is null (default), or the field for fieldname $name if it's found.
 *
 * @param string $name [optional] The fieldname to fetch. Defaults to null.
 * @return mixed Either array of CakeField objects , single object for $name or false when $name not present in fields
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
 * Sets the CakeField isntances from the Model::$validate property after processing the fieldList and whiteList.
 * If Model::$validate is not set or empty, this method returns false. True otherwise.
 *
 * @param boolean $reset If true will reset the Validator $validate array to the Model's default
 * @return boolean True if Model::$validate was processed, false otherwise
 */
	public function setFields($reset = false) {
		if (!isset($this->_model->validate) || empty($this->_model->validate)) {
			$this->_validate = array();
			return false;
		}

		$this->_validate = $this->_model->validate;

		if ($reset === true) {
			return true;
		}

		$this->_processWhitelist();

		$this->_fields = array();
		foreach ($this->_validate as $fieldName => $ruleSet) {
			$this->_fields[$fieldName] = new CakeField($this, $fieldName, $ruleSet);
		}
		unset($fieldName, $ruleSet);
		return true;
	}

/**
 * Sets an options array. If $mergeVars is true, the options will be merged with the existing ones.
 * Otherwise they will get replaced. The default is merging the vars.
 *
 * @param array $options [optional] The options to be set
 * @param boolean $mergeVars [optional] If true, the options will be merged, otherwise they get replaced
 * @return ModelValidator
 */
	public function setOptions($options = array(), $mergeVars = false) {
		if ($mergeVars === false) {
			$this->options = $options;
		} else {
			$this->options = array_merge($this->options, $options);
		}
		return $this;
	}

/**
 * Sets an option $name with $value. This method is chainable
 *
 * @param string $name The options name to be set
 * @param mixed $value [optional] The value to be set. Defaults to null.
 * @return ModelValidator
 */
	public function setOption($name, $value = null) {
		$this->options[$name] = $value;
		return $this;
	}

/**
 * Gets an options value by $name. If $name is not set or no option has been found, returns null.
 *
 * @param string $name The options name to look up
 * @return mixed Either null or the option value
 */
	public function getOptions($name = NULL) {
		if (NULL !== $name) {
			if (!isset($this->options[$name])) {
				return NULL;
			}
			return $this->options[$name];
		}
		return $this->options;
	}

/**
 * Sets the I18n domain for validation messages. This method is chainable.
 *
 * @param string $validationDomain [optional] The validation domain to be used. If none is given, uses Model::$validationDomain
 * @return ModelValidator
 */
	public function setValidationDomain($validationDomain = null) {
		if ($validationDomain !== null) {
			$this->validationDomain = $validationDomain;
		} elseif ($this->_model->validationDomain !== null) {
			$this->validationDomain = $this->_model->validationDomain;
		} else {
			$this->validationDomain = ModelValidator::DEFAULT_DOMAIN;
		}

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
 * Processes the Model's whitelist and adjusts the validate array accordingly
 *
 * @return void
 */
	protected function _processWhitelist() {
		$whitelist = $this->getModel()->whitelist;
		$fieldList = $this->getOptions('fieldList');

		if (!empty($fieldList)) {
			if (!empty($fieldList[$this->getModel()->alias]) && is_array($fieldList[$this->getModel()->alias])) {
				$whitelist = $fieldList[$this->getModel()->alias];
			} else {
				$whitelist = $fieldList;
			}
		}
		unset($fieldList);

		if (!empty($whitelist)) {
			$this->validationErrors = array();
			$validate = array();
			foreach ((array) $whitelist as $f) {
				if (!empty($this->_validate[$f])) {
					$validate[$f] = $this->_validate[$f];
				}
			}
			$this->_validate = $validate;
		}
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
		$this->getData(null, true);

		foreach ($this->getModel()->hasAndBelongsToMany as $assoc => $association) {
			if (empty($association['with']) || !isset($this->data[$assoc])) {
				continue;
			}
			list($join) = $this->getModel()->joinModel($this->getModel()->hasAndBelongsToMany[$assoc]['with']);
			$data = $this->data[$assoc];

			$newData = array();
			foreach ((array)$data as $row) {
				if (isset($row[$this->getModel()->hasAndBelongsToMany[$assoc]['associationForeignKey']])) {
					$newData[] = $row;
				} elseif (isset($row[$join]) && isset($row[$join][$this->getModel()->hasAndBelongsToMany[$assoc]['associationForeignKey']])) {
					$newData[] = $row[$join];
				}
			}
			if (empty($newData)) {
				continue;
			}
			foreach ($newData as $data) {
				$data[$this->getModel()->hasAndBelongsToMany[$assoc]['foreignKey']] = $this->getModel()->id;
				$this->getModel()->{$join}->create($data);
				$valid = ($valid && $this->getModel()->{$join}->getValidator()->validates($options));
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
	public function propagateBeforeValidate($options = array()) {
		$event = new CakeEvent('Model.beforeValidate', $this->getModel(), array($options));
		list($event->break, $event->breakOn) = array(true, false);
		$this->getModel()->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return false;
		}
		return true;
	}

}
