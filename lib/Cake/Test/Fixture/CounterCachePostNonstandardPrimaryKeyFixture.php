<?php
/**
 * Short description for file.
 *
 * PHP 5
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
class CounterCachePostNonstandardPrimaryKeyFixture extends CakeTestFixture {

	public $name = 'CounterCachePostNonstandardPrimaryKey';

	public $fields = array(
		'pid' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255, 'null' => false),
		'uid' => array('type' => 'integer', 'null' => true),
	);

	public $records = array(
		array('pid' => 1, 'title' => 'Rock and Roll', 'uid' => 66),
		array('pid' => 2, 'title' => 'Music', 'uid' => 66),
		array('pid' => 3, 'title' => 'Food', 'uid' => 301),
	);
}
