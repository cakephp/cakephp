<?php
/**
 * Short description for file.
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
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class ArmorsPlayerFixture extends CakeTestFixture {

/**
 * Datasource
 *
 * Used for Multi database fixture test
 *
 * @var string
 */
	public $useDbConfig = 'test_database_three';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'player_id' => array('type' => 'integer', 'null' => false),
		'armor_id' => array('type' => 'integer', 'null' => false),
		'broken' => array('type' => 'boolean', 'null' => false, 'default' => false),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('player_id' => 1, 'armor_id' => 1, 'broken' => false),
		array('player_id' => 2, 'armor_id' => 2, 'broken' => false),
		array('player_id' => 3, 'armor_id' => 3, 'broken' => false),
	);
}
