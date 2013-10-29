<?php
/**
 * PHP Version 5.4
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
 * @since         CakePHP(tm) v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model;

use Cake\ORM\Validation\ValidationSet;
use Cake\Utility\Hash;

/**
 * Validator object encapsulates all methods related to data validations for a model
 * It also provides an API to dynamically change validation rules for each model field.
 *
 * Implements ArrayAccess to easily modify rules in the set
 *
 * @link          http://book.cakephp.org/2.0/en/data-validation.html
 */
class ModelValidator implements \ArrayAccess, \IteratorAggregate, \Countable {

/**
 * Holds the ValidationSet objects array
 *
 * @var array
 */
	protected $_fields = array();

/**
 * Holds the reference to the table this Validator is attached to
 *
 * @var \Cake\ORM\Table
 */
	protected $_table = array();

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
 * @param \Cake\ORM\Table $table A reference to the table object this is bound to
 */
	public function __construct(Table $table) {
		$this->_model = $model;
	}

/**
 * Returns true if all fields pass validation.
 *
 * @param array $options An optional array of custom options to be made available
 * in the beforeValidate callback
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
 * Returns an array of fields that have failed validation. On the current model. This method will
 * actually run validation rules over data, not just return the messages.
 *
 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
 * @return array Array of invalid fields
 * @see ModelValidator::validates()
 */
	public function errors($options = array()) {
		if (!$this->_triggerBeforeValidate($options)) {
			return false;
		}
		$model = $this->getModel();
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
			$field->validationDomain($model->validationDomain);
			$data = isset($model->data[$model->alias]) ? $model->data[$model->alias] : array();
			$errors = $field->validate($data, $exists);
			foreach ($errors as $error) {
				$this->invalidate($field->field, $error);
			}
		}

		$model->getEventManager()->dispatch(new Event('Model.afterValidate', $model));
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
 * Returns a Cake ValidationSet object containing all validation rules for a field, if no
 * params are passed then it returns an array with all Cake ValidationSet objects for each field
 *
 * @param string $name [optional] The fieldname to fetch. Defaults to null.
 * @return Cake\Model\Validator\ValidationSet|array
 */
	public function getField($name = null) {
		if ($name !== null) {
			if (!empty($this->_fields[$name])) {
				return $this->_fields[$name];
			}
			return null;
		}
		return $this->_fields;
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
 * Propagates beforeValidate event
 *
 * @param array $options
 * @return boolean
 */
	protected function _triggerBeforeValidate($options = array()) {
		$model = $this->getModel();
		$event = new Event('Model.beforeValidate', $model, array($options));
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
 * @return boolean
 */
	public function offsetExists($field) {
		return isset($this->_fields[$field]);
	}

/**
 * Returns the rule set for a field
 *
 * @param string $field name of the field to check
 * @return Cake\Model\Validator\ValidationSet
 */
	public function offsetGet($field) {
		return $this->_fields[$field];
	}

/**
 * Sets the rule set for a field
 *
 * @param string $field name of the field to set
 * @param array|Cake\Model\Validator\ValidationSet $rules set of rules to apply to field
 * @return void
 */
	public function offsetSet($field, $rules) {
		if (!$rules instanceof ValidationSet) {
			$rules = new ValidationSet($field, $rules);
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
		unset($this->_fields[$field]);
	}

/**
 * Returns an iterator for each of the fields to be validated
 *
 * @return ArrayIterator
 */
	public function getIterator() {
		return new \ArrayIterator($this->_fields);
	}

/**
 * Returns the number of fields having validation rules
 *
 * @return integer
 */
	public function count() {
		return count($this->_fields);
	}

/**
 * Adds a new rule to a field's rule set. If second argumet is an array or instance of
 * Cake\Model\Validator\ValidationSet then rules list for the field will be replaced with second argument and
 * third argument will be ignored.
 *
 * ## Example:
 *
 * {{{
 *		$validator
 *			->add('title', 'required', array('rule' => 'notEmpty', 'required' => true))
 *			->add('user_id', 'valid', array('rule' => 'numeric', 'message' => 'Invalid User'))
 *
 *		$validator->add('password', array(
 *			'size' => array('rule' => array('between', 8, 20)),
 *			'hasSpecialCharacter' => array('rule' => 'validateSpecialchar', 'message' => 'not valid')
 *		));
 * }}}
 *
 * @param string $field The name of the field from wich the rule will be removed
 * @param string|array|Cake\Model\Validator\ValidationSet $name name of the rule to be added or list of rules for the field
 * @param array|Cake\Model\Validator\ValidationRule $rule or list of rules to be added to the field's rule set
 * @return ModelValidator this instance
 */
	public function add($field, $name, $rule = null) {
		if ($name instanceof ValidationSet) {
			$this->_fields[$field] = $name;
			return $this;
		}

		if (!isset($this->_fields[$field])) {
			$rule = (is_string($name)) ? array($name => $rule) : $name;
			$this->_fields[$field] = new ValidationSet($field, $rule);
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
 * {{{
 *		$validator
 *			->remove('title', 'required')
 *			->remove('user_id')
 * }}}
 *
 * @param string $field The name of the field from which the rule will be removed
 * @param string $rule the name of the rule to be removed
 * @return ModelValidator this instance
 */
	public function remove($field, $rule = null) {
		if ($rule === null) {
			unset($this->_fields[$field]);
		} else {
			$this->_fields[$field]->removeRule($rule);
		}
		return $this;
	}
}
