<?php
/**
 * ACL behavior class.
 *
 * Enables objects to easily tie into an ACL system
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.libs.model.behaviors
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('AclNode', 'Model');

/**
 * ACL behavior
 *
 * @package       cake.libs.model.behaviors
 * @link http://book.cakephp.org/view/1320/ACL
 */
class AclBehavior extends ModelBehavior {

/**
 * Maps ACL type options to ACL models
 *
 * @var array
 */
	private $__typeMaps = array('requester' => 'Aro', 'controlled' => 'Aco', 'both' => array('Aro', 'Aco'));

/**
 * Sets up the configuation for the model, and loads ACL models if they haven't been already
 *
 * @param mixed $config
 * @return void
 */
	public function setup($model, $config = array()) {
		if (is_string($config)) {
			$config = array('type' => $config);
		}
		$this->settings[$model->name] = array_merge(array('type' => 'controlled'), (array)$config);
		$this->settings[$model->name]['type'] = strtolower($this->settings[$model->name]['type']);

		$types = $this->__typeMaps[$this->settings[$model->name]['type']];

		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$model->{$type} = ClassRegistry::init($type);
		}
		if (!method_exists($model, 'parentNode')) {
			trigger_error(__d('cake_dev', 'Callback parentNode() not defined in %s', $model->alias), E_USER_WARNING);
		}
	}

/**
 * Retrieves the Aro/Aco node for this model
 *
 * @param mixed $ref
 * @param string $type Only needed when Acl is set up as 'both', specify 'Aro' or 'Aco' to get the correct node
 * @return array
 * @link http://book.cakephp.org/view/1322/node
 */
	public function node($model, $ref = null, $type = null) {
		if (empty($type)) {
			$type = $this->__typeMaps[$this->settings[$model->name]['type']];
			if (is_array($type)) {
				trigger_error(__('AclBehavior is setup with more then one type, please specify type parameter for node()', true), E_USER_WARNING);
				return null;
			}
		}
		if (empty($ref)) {
			$ref = array('model' => $model->name, 'foreign_key' => $model->id);
		}
		return $model->{$type}->node($ref);
	}

/**
 * Creates a new ARO/ACO node bound to this record
 *
 * @param boolean $created True if this is a new record
 * @return void
 */
	public function afterSave($model, $created) {
		$types = $this->__typeMaps[$this->settings[$model->name]['type']];
		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$parent = $model->parentNode();
			if (!empty($parent)) {
				$parent = $this->node($model, $parent, $type);
			}
			$data = array(
				'parent_id' => isset($parent[0][$type]['id']) ? $parent[0][$type]['id'] : null,
				'model' => $model->alias,
				'foreign_key' => $model->id
			);
			if (!$created) {
				$node = $this->node($model, null, $type);
				$data['id'] = isset($node[0][$type]['id']) ? $node[0][$type]['id'] : null;
			}
			$model->{$type}->create();
			$model->{$type}->save($data);
		}
	}

/**
 * Destroys the ARO/ACO node bound to the deleted record
 *
 * @return void
 */
	public function afterDelete($model) {
		$types = $this->__typeMaps[$this->settings[$model->name]['type']];
		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$node = Set::extract($this->node($model, null, $type), "0.{$type}.id");
			if (!empty($node)) {
				$model->{$type}->delete($node);
			}
		}
	}
}
