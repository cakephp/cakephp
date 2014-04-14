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
 * Counter Cache Test Fixtures
 *
 */
class CounterCachePostFixture extends TestFixture {

	public $fields = array(
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255],
		'user_id' => ['type' => 'integer', 'null' => true],
		'published' => ['type' => 'boolean', 'null' => false, 'default' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

	public $records = array(
		array('title' => 'Rock and Roll', 'user_id' => 1, 'published' => 0),
		array('title' => 'Music', 'user_id' => 1, 'published' => 1),
		array('title' => 'Food', 'user_id' => 2, 'published' => 1),
	);
}
