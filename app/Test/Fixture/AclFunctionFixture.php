<?php
/* AclFunction Fixture generated on: 2012-01-31 21:59:27 : 1328068767 */

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
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'acl_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index', 'collate' => NULL, 'comment' => ''),
		'function' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 65, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'charset' => 'latin1'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'FK_acl_functions_acl_id' => array('column' => 'acl_id', 'unique' => 0)),
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
			'function' => 'Lorem ipsum dolor sit amet'
		),
	);
}
