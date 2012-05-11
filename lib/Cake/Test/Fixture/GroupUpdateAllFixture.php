<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class GroupUpdateAllFixture extends CakeTestFixture {

	public $name = 'GroupUpdateAll';

	public $table = 'group_update_all';

	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'length' => 29),
		'code' => array('type' => 'integer', 'null' => false, 'length' => 4),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

	public $records = array(
		array(
			'id' => 1,
			'name' => 'group one',
			'code' => 120
		),
		array(
			'id' => 2,
			'name' => 'group two',
			'code' => 125
		),
		array(
			'id' => 3,
			'name' => 'group three',
			'code' => 130
		),
		array(
			'id' => 4,
			'name' => 'group four',
			'code' => 135
		),
	);
}
