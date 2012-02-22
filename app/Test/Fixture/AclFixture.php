<?php
/* Acl Fixture generated on: 2012-01-31 22:00:43 : 1328068843 */

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
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary', 'collate' => NULL, 'comment' => ''),
		'controller' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 65, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'charset' => 'latin1'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
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
			'controller' => 'Lorem ipsum dolor sit amet'
		),
	);
}
