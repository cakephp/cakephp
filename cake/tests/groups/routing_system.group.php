<?php
/**
 * RoutingSystemGroupTest
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.2.0.5517
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * RoutingSystemGroupTest class
 *
 * This test group will run all the Router/Dispatcher (and related) tests
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class RoutingSystemGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string 'Routing system'
 * @access public
 */
	var $label = 'Router and Dispatcher';

/**
 * RoutingSystemGroupTest method
 *
 * @access public
 * @return void
 */
	function RoutingSystemGroupTest() {
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'dispatcher');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'router');
	}
}
