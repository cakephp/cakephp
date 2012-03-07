<?php
/**
 * Counter Cache Test Fixtures
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class CounterCachePostFixture extends CakeTestFixture {

	public $name = 'CounterCachePost';

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255),
		'user_id' => array('type' => 'integer', 'null' => true),
		'published' => array('type' => 'boolean', 'null' => false, 'default' => 0)
	);

	public $records = array(
		array('id' => 1, 'title' => 'Rock and Roll',  'user_id' => 66, 'published' => false),
		array('id' => 2, 'title' => 'Music',   'user_id' => 66, 'published' => true),
		array('id' => 3, 'title' => 'Food',   'user_id' => 301, 'published' => true),
	);
}
