<?php
/**
 * AclFunctionFixture
 *
 */
class AclFunctionFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'acl_controller_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'function' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 65, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'FK_acl_functions_acl_id' => array('column' => 'acl_controller_id', 'unique' => 0)),
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
			'function' => 'Lorem ipsum dolor sit amet'
		),
	);
}
