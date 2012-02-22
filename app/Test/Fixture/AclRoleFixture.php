<?php
/* AclRole Fixture generated on: 2012-01-31 22:04:34 : 1328069074 */

/**
 * AclRoleFixture
 *
 */
class AclRoleFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'acl_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index', 'collate' => NULL, 'comment' => ''),
		'acl_function_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index', 'collate' => NULL, 'comment' => ''),
		'role_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index', 'collate' => NULL, 'comment' => ''),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'FK_acl_roles_acl_id' => array('column' => 'acl_id', 'unique' => 0), 'FK_acl_roles_acl_function_id' => array('column' => 'acl_function_id', 'unique' => 0), 'FK_acl_roles_role_id' => array('column' => 'role_id', 'unique' => 0)),
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
			'acl_id' => 1,
			'acl_function_id' => 1,
			'role_id' => 1
		),
	);
}
