<?php
/* SVN FILE: $Id$ */
/**
 * ACL behavior class.
 *
 * Enables objects to easily tie into an ACL system
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.behaviors
 * @since			CakePHP v 1.2.0.4487
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.behaviors
 */
class AclBehavior extends ModelBehavior {

/**
 * Maps ACL type options to ACL models
 *
 * @var array
 * @access protected
 */
	var $__typeMaps = array('requester' => 'Aro', 'controlled' => 'Aco');
/**
 * Sets up the configuation for the model, and loads ACL models if they haven't been already
 *
 * @param mixed $config
 */
	function setup(&$model, $config = array()) {
		if (is_string($config)) {
			$config = array('type' => $config);
		}
		$this->settings[$model->alias] = array_merge(array('type' => 'requester'), (array)$config);
		$type = $this->__typeMaps[$this->settings[$model->alias]['type']];

		if (!ClassRegistry::isKeySet($type)) {
			uses('model' . DS . 'db_acl');
			$object =& new $type();
		} else {
			$object =& ClassRegistry::getObject($type);
		}
		$model->{$type} =& $object;
		if (!method_exists($model, 'parentNode')) {
			trigger_error("Callback parentNode() not defined in {$model->alias}", E_USER_WARNING);
		}
	}
/**
 * Retrieves the Aro/Aco node for this model
 *
 * @param mixed $ref
 * @return array
 */
	function node(&$model, $ref = null) {
		$type = $this->__typeMaps[strtolower($this->settings[$model->alias]['type'])];
		if (empty($ref)) {
			$ref = array('model' => $model->alias, 'foreign_key' => $model->id);
		}
		return $model->{$type}->node($ref);
	}
/**
 * Creates a new ARO/ACO node bound to this record
 *
 * @param boolean $created True if this is a new record
 */
	function afterSave(&$model, $created) {
		if ($created) {
			$type = $this->__typeMaps[strtolower($this->settings[$model->alias]['type'])];
			$parent = $model->parentNode();
			if (!empty($parent)) {
				$parent = $this->node($model, $parent);
			} else {
				$parent = null;
			}

			$model->{$type}->create();
			$model->{$type}->save(array(
				'parent_id'		=> Set::extract($parent, "0.{$type}.id"),
				'model'			=> $model->alias,
				'foreign_key'	=> $model->id
			));
		}
	}
/**
 * Destroys the ARO/ACO node bound to the deleted record
 *
 */
	function afterDelete(&$model) {
		$type = $this->__typeMaps[strtolower($this->settings[$model->alias]['type'])];
		$node = Set::extract($this->node($model), "0.{$type}.id");
		if (!empty($node)) {
			$model->{$type}->delete($node);
		}
	}
}

?>