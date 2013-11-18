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
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class ArmorsPlayerFixture extends TestFixture {

/**
 * Datasource
 *
 * Used for Multi database fixture test
 *
 * @var string 'test_database_three'
 */
	public $useDbConfig = 'test_database_three';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'player_id' => ['type' => 'integer', 'null' => false],
		'armor_id' => ['type' => 'integer', 'null' => false],
		'broken' => ['type' => 'boolean', 'null' => false, 'default' => false],
		'created' => 'datetime',
		'updated' => 'datetime',
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
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
