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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class CounterCacheCategoriesFixture extends TestFixture {

	public $fields = array(
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'length' => 255, 'null' => false],
		'post_count' => ['type' => 'integer', 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

	public $records = array(
		array('name' => 'Sport', 'post_count' => 1),
		array('name' => 'Music', 'post_count' => 2),
	);
}
