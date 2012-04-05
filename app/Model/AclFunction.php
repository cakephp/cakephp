<?php
App::uses('AppModel', 'Model');
/**
 * AclFunction Model
 *
 * @property AclController $AclController
 * @property Acl $Acl
 */
class AclFunction extends AppModel {
/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'function';

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
		)
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Acl' => array(
			'className' => 'Acl',
			'foreignKey' => 'acl_function_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}
