<?php
App::uses('AppModel', 'Model');
/**
 * Acl Model
 *
 * @property AclController $AclController
 * @property AclFunction $AclFunction
 * @property Role $Role
 */
class Acl extends AppModel {
/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'role_id';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'AclController' => array(
			'className' => 'AclController',
			'foreignKey' => 'acl_controller_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'AclFunction' => array(
			'className' => 'AclFunction',
			'foreignKey' => 'acl_function_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Role' => array(
			'className' => 'Role',
			'foreignKey' => 'role_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
