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
 * @since         CakePHP(tm) v 2.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class ArmorFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Armor'
 */
	public $name = 'Armor';

/**
 * Datasource
 *
 * Used for Multi database fixture test
 *
 * @var string 'test2'
 */
	public $useDbConfig = 'test2';

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
		array('name' => 'Leather', 'created' => '2007-03-17 01:16:23'),
		array('name' => 'Chainmail', 'created' => '2007-03-17 01:18:23'),
		array('name' => 'Cloak', 'created' => '2007-03-17 01:20:23'),
		array('name' => 'Bikini', 'created' => '2007-03-17 01:22:23'),
	);
}
