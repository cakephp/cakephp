<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class UnsignedFixture extends CakeTestFixture {

/**
 * table property
 *
 * @var array
 */
	public $table = 'unsigned';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'uinteger' => array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key' => 'primary', 'unsigned' => true),
		'integer' => array('type' => 'integer', 'length' => '8', 'unsigned' => false),
		'usmallinteger' => array('type' => 'smallinteger', 'unsigned' => true),
		'smallinteger' => array('type' => 'smallinteger', 'unsigned' => false),
		'utinyinteger' => array('type' => 'tinyinteger', 'unsigned' => true),
		'tinyinteger' => array('type' => 'tinyinteger', 'unsigned' => false),
		'udecimal' => array('type' => 'decimal', 'length' => '4', 'unsigned' => true),
		'decimal' => array('type' => 'decimal', 'length' => '4'),
		'biginteger' => array('type' => 'biginteger', 'length' => '20', 'default' => 3),
		'ubiginteger' => array('type' => 'biginteger', 'length' => '20', 'default' => 3, 'unsigned' => true),
		'float' => array('type' => 'float', 'length' => '4'),
		'ufloat' => array('type' => 'float', 'length' => '4', 'unsigned' => true),
		'string' => array('type' => 'string', 'length' => '4'),
		'tableParameters' => array(
			'engine' => 'MyISAM'
		)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array();
}
