<?php
/**
 * Short description for file.
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
 * @since         CakePHP(tm) v 1.2.0.6317
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * JoinABFixture
 *
 * @package       Cake.Test.Fixture
 */
class JoinABFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string
 */
	public $name = 'JoinAsJoinB';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'join_a_id' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'join_b_id' => array('type' => 'integer', 'default' => null),
		'other' => array('type' => 'string', 'default' => ''),
		'created' => array('type' => 'datetime', 'null' => true),
		'updated' => array('type' => 'datetime', 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('join_a_id' => 1, 'join_b_id' => 2, 'other' => 'Data for Join A 1 Join B 2', 'created' => '2008-01-03 10:56:33', 'updated' => '2008-01-03 10:56:33'),
		array('join_a_id' => 2, 'join_b_id' => 3, 'other' => 'Data for Join A 2 Join B 3', 'created' => '2008-01-03 10:56:34', 'updated' => '2008-01-03 10:56:34'),
		array('join_a_id' => 3, 'join_b_id' => 1, 'other' => 'Data for Join A 3 Join B 1', 'created' => '2008-01-03 10:56:35', 'updated' => '2008-01-03 10:56:35')
	);
}
