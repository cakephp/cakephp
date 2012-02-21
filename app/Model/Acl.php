<?php
App::uses('AppModel', 'Model');
/**
 * Acl Model
 *
 * @property AclFunction $AclFunction
 * @property AclRole $AclRole
 */
class Acl extends AppModel {
/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'controller';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'AclFunction' => array(
			'className' => 'AclFunction',
			'foreignKey' => 'acl_id',
			'dependent' => false,
			'conditions' => array('AclFunction.acl_id' => 'Acl.id'),
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'AclRole' => array(
			'className' => 'AclRole',
			'foreignKey' => 'acl_id',
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
