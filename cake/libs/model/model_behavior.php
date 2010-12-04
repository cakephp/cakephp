<?php
/**
 * Model behaviors base class.
 *
 * Adds methods and automagic functionality to Cake Models.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.model
 * @since         CakePHP(tm) v 1.2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Model behavior base class.
 *
 * Defines the Behavior interface, and contains common model interaction functionality.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 */
class ModelBehavior extends Object {

/**
 * Contains configuration settings for use with individual model objects.  This
 * is used because if multiple models use this Behavior, each will use the same
 * object instance.  Individual model settings should be stored as an
 * associative array, keyed off of the model name.
 *
 * @var array
 * @access public
 * @see Model::$alias
 */
	public $settings = array();

/**
 * Allows the mapping of preg-compatible regular expressions to public or
 * private methods in this class, where the array key is a /-delimited regular
 * expression, and the value is a class method.  Similar to the functionality of
 * the findBy* / findAllBy* magic methods.
 *
 * @var array
 * @access public
 */
	public $mapMethods = array();

/**
 * Setup this behavior with the specified configuration settings.
 *
 * @param object $model Model using this behavior
 * @param array $config Configuration settings for $model
 */
	public function setup($model, $config = array()) { }

/**
 * Clean up any initialization this behavior has done on a model.  Called when a behavior is dynamically
 * detached from a model using Model::detach().
 *
 * @param object $model Model using this behavior
 * @access public
 * @see BehaviorCollection::detach()
 */
	function cleanup($model) {
		if (isset($this->settings[$model->alias])) {
			unset($this->settings[$model->alias]);
		}
	}

/**
 * Before find callback
 *
 * @param object $model Model using this behavior
 * @param array $queryData Data used to execute this query, i.e. conditions, order, etc.
 * @return mixed False if the operation should abort. An array will replace the value of $query.
 * @access public
 */
	public function beforeFind($model, $query) { }

/**
 * After find callback. Can be used to modify any results returned by find and findAll.
 *
 * @param object $model Model using this behavior
 * @param mixed $results The results of the find operation
 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed An array value will replace the value of $results - any other value will be ignored.
 * @access public
 */
	public function afterFind($model, $results, $primary) { }

/**
 * Before validate callback
 *
 * @param object $model Model using this behavior
 * @return mixed False if the operation should abort. Any other result will continue.
 * @access public
 */
	public function beforeValidate($model) { }

/**
 * Before save callback
 *
 * @param object $model Model using this behavior
 * @return mixed False if the operation should abort. Any other result will continue.
 * @access public
 */
	public function beforeSave($model) { }

/**
 * After save callback
 *
 * @param object $model Model using this behavior
 * @param boolean $created True if this save created a new record
 */
	public function afterSave($model, $created) { }

/**
 * Before delete callback
 *
 * @param object $model Model using this behavior
 * @param boolean $cascade If true records that depend on this record will also be deleted
 * @return mixed False if the operation should abort. Any other result will continue.
 * @access public
 */
	public function beforeDelete($model, $cascade = true) { }

/**
 * After delete callback
 *
 * @param object $model Model using this behavior
 */
	public function afterDelete($model) { }

/**
 * DataSource error callback
 *
 * @param object $model Model using this behavior
 * @param string $error Error generated in DataSource
 */
	public function onError($model, $error) { }

/**
 * If $model's whitelist property is non-empty, $field will be added to it.
 * Note: this method should *only* be used in beforeValidate or beforeSave to ensure
 * that it only modifies the whitelist for the current save operation.  Also make sure
 * you explicitly set the value of the field which you are allowing.
 *
 * @param object $model Model using this behavior
 * @param string $field Field to be added to $model's whitelist
 * @access protected
 * @return void
 */
	function _addToWhitelist($model, $field) {
		if (is_array($field)) {
			foreach ($field as $f) {
				$this->_addToWhitelist($model, $f);
			}
			return;
		}
		if (!empty($model->whitelist) && !in_array($field, $model->whitelist)) {
			$model->whitelist[] = $field;
		}
	}
}

