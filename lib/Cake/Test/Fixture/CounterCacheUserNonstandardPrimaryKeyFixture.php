<?php
/**
 * Short description for file.
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
class CounterCacheUserNonstandardPrimaryKeyFixture extends CakeTestFixture {

	public $name = 'CounterCacheUserNonstandardPrimaryKey';

	public $fields = array(
		'uid' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => false),
		'post_count' => array('type' => 'integer', 'null' => true)
	);

	public $records = array(
		array('uid' => 66, 'name' => 'Alexander','post_count' => 2),
		array('uid' => 301, 'name' => 'Steven','post_count' => 1),
	);
}
