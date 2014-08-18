<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
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
