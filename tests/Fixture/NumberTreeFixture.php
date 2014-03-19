<?php
/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
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
 * @since         CakePHP(tm) v 1.2.0.5331
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class NumberTreeFixture
 *
 * Generates a tree of data for use testing the tree behavior
 *
 */
class NumberTreeFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'null' => false],
		'parent_id' => 'integer',
		'lft' => ['type' => 'integer', 'null' => false],
		'rght' => ['type' => 'integer', 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '1',
			'name' => 'electronics',
			'parent_id' => null,
			'lft' => '1',
			'rght' => '20'
		),
		array(
			'id' => '2',
			'name' => 'televisions',
			'parent_id' => '1',
			'lft' => '2',
			'rght' => '9'
		),
		array(
			'id' => '3',
			'name' => 'tube',
			'parent_id' => '2',
			'lft' => '3',
			'rght' => '4'
		),
		array(
			'id' => '4',
			'name' => 'lcd',
			'parent_id' => '2',
			'lft' => '5',
			'rght' => '6'
		),
		array(
			'id' => '5',
			'name' => 'plasma',
			'parent_id' => '2',
			'lft' => '7',
			'rght' => '8'
		),
		array(
			'id' => '6',
			'name' => 'portable',
			'parent_id' => '1',
			'lft' => '10',
			'rght' => '19'
		),
		array(
			'id' => '7',
			'name' => 'mp3',
			'parent_id' => '6',
			'lft' => '11',
			'rght' => '14'
		),
		array(
			'id' => '8',
			'name' => 'flash',
			'parent_id' => '7',
			'lft' => '12',
			'rght' => '13'
		),
		array(
			'id' => '9',
			'name' => 'cd',
			'parent_id' => '6',
			'lft' => '15',
			'rght' => '16'
		),
		array(
			'id' => '10',
			'name' => 'radios',
			'parent_id' => '6',
			'lft' => '17',
			'rght' => '18'
		),
		array(
			'id' => '11',
			'name' => 'alien hardware',
			'parent_id' => null,
			'lft' => '21',
			'rght' => '21'
		)
	);

}
