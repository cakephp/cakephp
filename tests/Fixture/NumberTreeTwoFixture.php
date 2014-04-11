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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class NumberTreeTwoFixture
 *
 * Generates a tree of data for use testing the tree behavior
 *
 */
class NumberTreeTwoFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'null' => false],
		'number_tree_id' => ['type' => 'integer', 'null' => false],
		'parent_id' => 'integer',
		'lft' => ['type' => 'integer', 'null' => false],
		'rght' => ['type' => 'integer', 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);
}
