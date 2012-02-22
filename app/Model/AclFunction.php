<?php
App::uses('AppModel', 'Model');
/**
 * AclFunction Model
 *
 * @property Acl $Acl
 * @property AclRole $AclRole
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
//TODO need to add where acl_id = ACL.id
	public $belongsTo = array(
		'Acl' => array(
			'className' => 'Acl',
			'foreignKey' => 'acl_id',
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
		'AclRole' => array(
			'className' => 'AclRole',
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
