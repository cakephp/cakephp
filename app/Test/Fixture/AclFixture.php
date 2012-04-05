<?php
/**
 * AclFixture
 *
 */
class AclFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'acl_controller_id' => array('type' => 'integer', 'null' => false, 'default' => '1', 'key' => 'index'),
		'acl_function_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index'),
		'role_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'FK_acl_roles_acl_id' => array('column' => 'acl_controller_id', 'unique' => 0), 'FK_acl_roles_acl_function_id' => array('column' => 'acl_function_id', 'unique' => 0), 'FK_acl_roles_role_id' => array('column' => 'role_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'InnoDB')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'acl_controller_id' => 1,
			'acl_function_id' => 1,
			'role_id' => 1
		),
	);
}
