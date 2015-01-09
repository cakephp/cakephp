<?php
/**
 * Model behaviors base class.
 *
 * Adds methods and automagic functionality to CakePHP Models.
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
 * @since         CakePHP(tm) v 1.2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Model behavior base class.
 *
 * Defines the Behavior interface, and contains common model interaction functionality. Behaviors
 * allow you to simulate mixins, and create reusable blocks of application logic, that can be reused across
 * several models. Behaviors also provide a way to hook into model callbacks and augment their behavior.
 *
 * ### Mixin methods
 *
 * Behaviors can provide mixin like features by declaring public methods. These methods should expect
 * the model instance to be shifted onto the parameter list.
 *
 * ```
 * function doSomething(Model $model, $arg1, $arg2) {
 *		//do something
 * }
 * ```
 *
 * Would be called like `$this->Model->doSomething($arg1, $arg2);`.
 *
 * ### Mapped methods
 *
 * Behaviors can also define mapped methods. Mapped methods use pattern matching for method invocation. This
 * allows you to create methods similar to Model::findAllByXXX methods on your behaviors. Mapped methods need to
 * be declared in your behaviors `$mapMethods` array. The method signature for a mapped method is slightly different
 * than a normal behavior mixin method.
 *
 * ```
 * public $mapMethods = array('/do(\w+)/' => 'doSomething');
 *
 * function doSomething(Model $model, $method, $arg1, $arg2) {
 *		//do something
 * }
 * ```
 *
 * The above will map every doXXX() method call to the behavior. As you can see, the model is
 * still the first parameter, but the called method name will be the 2nd parameter. This allows
 * you to munge the method name for additional information, much like Model::findAllByXX.
 *
 * @package       Cake.Model
 * @see Model::$actsAs
 * @see BehaviorCollection::load()
 */
class ModelBehavior extends Object {

/**
 * Contains configuration settings for use with individual model objects. This
 * is used because if multiple models use this Behavior, each will use the same
 * object instance. Individual model settings should be stored as an
 * associative array, keyed off of the model name.
 *
 * @var array
 * @see Model::$alias
 */
	public $settings = array();

/**
 * Allows the mapping of preg-compatible regular expressions to public or
 * private methods in this class, where the array key is a /-delimited regular
 * expression, and the value is a class method. Similar to the functionality of
 * the findBy* / findAllBy* magic methods.
 *
 * @var array
 */
	public $mapMethods = array();

/**
 * Setup this behavior with the specified configuration settings.
 *
 * @param Model $model Model using this behavior
 * @param array $config Configuration settings for $model
 * @return void
 */
	public function setup(Model $model, $config = array()) {
	}

/**
 * Clean up any initialization this behavior has done on a model. Called when a behavior is dynamically
 * detached from a model using Model::detach().
 *
 * @param Model $model Model using this behavior
 * @return void
 * @see BehaviorCollection::detach()
 */
	public function cleanup(Model $model) {
		if (isset($this->settings[$model->alias])) {
			unset($this->settings[$model->alias]);
		}
	}

/**
 * beforeFind can be used to cancel find operations, or modify the query that will be executed.
 * By returning null/false you can abort a find. By returning an array you can modify/replace the query
 * that is going to be run.
 *
 * @param Model $model Model using this behavior
 * @param array $query Data used to execute this query, i.e. conditions, order, etc.
 * @return bool|array False or null will abort the operation. You can return an array to replace the
 *   $query that will be eventually run.
 */
	public function beforeFind(Model $model, $query) {
		return true;
	}

/**
 * After find callback. Can be used to modify any results returned by find.
 *
 * @param Model $model Model using this behavior
 * @param mixed $results The results of the find operation
 * @param bool $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed An array value will replace the value of $results - any other value will be ignored.
 */
	public function afterFind(Model $model, $results, $primary = false) {
	}

/**
 * beforeValidate is called before a model is validated, you can use this callback to
 * add behavior validation rules into a models validate array. Returning false
 * will allow you to make the validation fail.
 *
 * @param Model $model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False or null will abort the operation. Any other result will continue.
 * @see Model::save()
 */
	public function beforeValidate(Model $model, $options = array()) {
		return true;
	}

/**
 * afterValidate is called just after model data was validated, you can use this callback
 * to perform any data cleanup or preparation if needed
 *
 * @param Model $model Model using this behavior
 * @return mixed False will stop this event from being passed to other behaviors
 */
	public function afterValidate(Model $model) {
		return true;
	}

/**
 * beforeSave is called before a model is saved. Returning false from a beforeSave callback
 * will abort the save operation.
 *
 * @param Model $model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False if the operation should abort. Any other result will continue.
 * @see Model::save()
 */
	public function beforeSave(Model $model, $options = array()) {
		return true;
	}

/**
 * afterSave is called after a model is saved.
 *
 * @param Model $model Model using this behavior
 * @param bool $created True if this save created a new record
 * @param array $options Options passed from Model::save().
 * @return bool
 * @see Model::save()
 */
	public function afterSave(Model $model, $created, $options = array()) {
		return true;
	}

/**
 * Before delete is called before any delete occurs on the attached model, but after the model's
 * beforeDelete is called. Returning false from a beforeDelete will abort the delete.
 *
 * @param Model $model Model using this behavior
 * @param bool $cascade If true records that depend on this record will also be deleted
 * @return mixed False if the operation should abort. Any other result will continue.
 */
	public function beforeDelete(Model $model, $cascade = true) {
		return true;
	}

/**
 * After delete is called after any delete occurs on the attached model.
 *
 * @param Model $model Model using this behavior
 * @return void
 */
	public function afterDelete(Model $model) {
	}

/**
 * DataSource error callback
 *
 * @param Model $model Model using this behavior
 * @param string $error Error generated in DataSource
 * @return void
 */
	public function onError(Model $model, $error) {
	}

/**
 * If $model's whitelist property is non-empty, $field will be added to it.
 * Note: this method should *only* be used in beforeValidate or beforeSave to ensure
 * that it only modifies the whitelist for the current save operation. Also make sure
 * you explicitly set the value of the field which you are allowing.
 *
 * @param Model $model Model using this behavior
 * @param string $field Field to be added to $model's whitelist
 * @return void
 */
	protected function _addToWhitelist(Model $model, $field) {
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
