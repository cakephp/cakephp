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
 * @since         CakePHP(tm) v 1.2.0.7198
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class CdFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Cd'
 */
	public $name = 'Cd';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255),
		'artist' => array('type' => 'string', 'length' => 255, 'null' => true),
		'genre' => array('type' => 'string', 'length' => 255, 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'title' => 'Grace', 'artist' => 'Jeff Buckley', 'genre' => 'awesome')
	);
}
