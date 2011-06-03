<?php
/**
 * Socket and HttpSocket Group tests
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/** Socket and HttpSocket tests
 *
 * This test group will run socket class tests (socket, http_socket).
 *
 * @package       cake.tests
 * @subpackage    cake.tests.groups
 */

/**
 * SocketGroupTest class
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class SocketGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string 'Socket and HttpSocket tests'
 * @access public
 */
	var $label = 'CakeSocket and HttpSocket tests';

/**
 * SocketGroupTest method
 *
 * @access public
 * @return void
 */
	function SocketGroupTest() {
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'cake_socket');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'http_socket');
	}
}
