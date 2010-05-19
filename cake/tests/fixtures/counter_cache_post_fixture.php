<?php
/**
 * Counter Cache Test Fixtures
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class CounterCachePostFixture extends CakeTestFixture {

	var $name = 'CounterCachePost';

	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255, 'null' => false),
		'user_id' => array('type' => 'integer', 'null' => true),
	);

    var $records = array(
		array('id' => 1, 'title' => 'Rock and Roll',  'user_id' => 66),
		array('id' => 2, 'title' => 'Music',   'user_id' => 66),
		array('id' => 3, 'title' => 'Food',   'user_id' => 301),
    );
}
