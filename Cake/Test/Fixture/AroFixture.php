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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class AroFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'parent_id' => ['type' => 'integer', 'length' => 10, 'null' => true],
		'model' => ['type' => 'string', 'null' => true],
		'foreign_key' => ['type' => 'integer', 'length' => 10, 'null' => true],
		'alias' => ['type' => 'string', 'default' => ''],
		'lft' => ['type' => 'integer', 'length' => 10, 'null' => true],
		'rght' => ['type' => 'integer', 'length' => 10, 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT', 'lft' => 1, 'rght' => 8),
		array('parent_id' => '1', 'model' => 'Group', 'foreign_key' => '1', 'alias' => 'admins', 'lft' => 2, 'rght' => 7),
		array('parent_id' => '2', 'model' => 'AuthUser', 'foreign_key' => '1', 'alias' => 'Gandalf', 'lft' => 3, 'rght' => 4),
		array('parent_id' => '2', 'model' => 'AuthUser', 'foreign_key' => '2', 'alias' => 'Elrond', 'lft' => 5, 'rght' => 6)
	);
}
