<?php
/**
 * CakeTestMenu Generates HTML based menus for CakePHP's built in test suite.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
class CakeTestMenu {

/**
 * Prints a list of test cases
 *
 * @return void
 * @access public
 */
	function testCaseList() {
		$class = CakeTestMenu::getTestManager();
		echo call_user_func(array($class, 'getTestCaseList'));
	}

/**
 * Prints a list of group tests
 *
 * @return void
 * @access public
 */
	function groupTestList() {
		$class = CakeTestMenu::getTestManager();
		echo call_user_func(array($class, 'getGroupTestList'));
	}

/**
 * Gets the correct test manager for the chosen output.
 *
 * @return void
 */
	function getTestManager() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				return 'HtmlTestManager';
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				return 'TextTestManager';
		}
	}

/**
 * Includes the Testsuite Header
 *
 * @return void
 * @access public
 */
	function testHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				if (!class_exists('dispatcher')) {
					require CAKE . 'dispatcher.php';
				}
				$dispatch =& new Dispatcher();
				$dispatch->baseUrl();
				define('BASE', $dispatch->webroot);
				$baseUrl = BASE;
				$characterSet = 'charset=utf-8';
				include CAKE_TESTS_LIB . 'header.php';

				break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				header('content-type: text/plain');
				break;
		}
	}

/**
 * Provides the testsuite footer text
 *
 * @return void
 * @access public
 */
	function footer() {
		switch ( CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				$baseUrl = BASE;
				include CAKE_TESTS_LIB . 'footer.php';
				break;
		}
	}
}
?>