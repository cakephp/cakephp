<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class PlayerFixture
 *
 * @package       Cake.Test.Fixture
 */
class PlayerFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'mark', 'created' => '2007-03-17 01:16:23'),
		array('name' => 'jack', 'created' => '2007-03-17 01:18:23'),
		array('name' => 'larry', 'created' => '2007-03-17 01:20:23'),
		array('name' => 'jose', 'created' => '2007-03-17 01:22:23'),
	);
}
