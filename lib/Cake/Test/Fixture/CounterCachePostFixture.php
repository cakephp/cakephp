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
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class CounterCachePostFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255),
		'user_id' => array('type' => 'integer', 'null' => true),
		'published' => array('type' => 'boolean', 'null' => false, 'default' => 0)
	);

	public $records = array(
		array('id' => 1, 'title' => 'Rock and Roll', 'user_id' => 66, 'published' => false),
		array('id' => 2, 'title' => 'Music', 'user_id' => 66, 'published' => true),
		array('id' => 3, 'title' => 'Food', 'user_id' => 301, 'published' => true),
	);
}
