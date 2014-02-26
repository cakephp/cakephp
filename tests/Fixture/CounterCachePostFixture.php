<?php
/**
 * Counter Cache Test Fixtures
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
class CounterCachePostFixture extends TestFixture {

	public $fields = array(
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255],
		'user_id' => ['type' => 'integer', 'null' => true],
		'published' => ['type' => 'boolean', 'null' => false, 'default' => 0],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

	public $records = array(
		array('id' => 1, 'title' => 'Rock and Roll', 'user_id' => 66, 'published' => 0),
		array('id' => 2, 'title' => 'Music', 'user_id' => 66, 'published' => 1),
		array('id' => 3, 'title' => 'Food', 'user_id' => 301, 'published' => 1),
	);
}
