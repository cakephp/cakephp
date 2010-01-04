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
 * Provides the "Run More" links in the testsuite interface
 *
 * @return void
 * @access public
 */
	function runMore() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				if (isset($_GET['group'])) {
					if (isset($_GET['app'])) {
						$show = '?show=groups&amp;app=true';
					} else if (isset($_GET['plugin'])) {
						$show = '?show=groups&amp;plugin=' . $_GET['plugin'];
					} else {
						$show = '?show=groups';
					}
					$query = '?group='.$_GET['group'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				}
				if (isset($_GET['case'])) {
					if (isset($_GET['app'])) {
						$show = '?show=cases&amp;app=true';
					} else if (isset($_GET['plugin'])) {
						$show = '?show=cases&amp;plugin=' . $_GET['plugin'];
					} else {
						$show = '?show=cases';
					}
					$query = '?case='.$_GET['case'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				}
				ob_start();
				echo "<p><a href='" . RUN_TEST_LINK . $show . "'>Run more tests</a> | <a href='" . RUN_TEST_LINK . $query . "&show_passes=1'>Show Passes</a> | \n";

				break;
		}
	}

/**
 * Provides the links to analyzing code coverage
 *
 * @return void
 * @access public
 */
	function analyzeCodeCoverage() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				if (isset($_GET['case'])) {
					$query = '?case=' . $_GET['case'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				} else {
					$query = '?group='.$_GET['group'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				}
				$query .= '&amp;code_coverage=true';
				ob_start();
				echo " <a href='" . RUN_TEST_LINK . $query . "'>Analyze Code Coverage</a></p>\n";

				break;
		}
	}

/**
 * Prints a list of test cases
 *
 * @return void
 * @access public
 */
	function testCaseList() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				echo HtmlTestManager::getTestCaseList();
				break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				echo TextTestManager::getTestCaseList();
				break;
		}
	}

/**
 * Prints a list of group tests
 *
 * @return void
 * @access public
 */
	function groupTestList() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				echo HtmlTestManager::getGroupTestList();
				break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				echo TextTestManager::getGroupTestList();
				break;
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
 * Provides the left hand navigation for the testsuite
 *
 * @return void
 * @access public
 */
	function testSuiteHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				$groups = $_SERVER['PHP_SELF'] . '?show=groups';
				$cases = $_SERVER['PHP_SELF'] . '?show=cases';
				$plugins = App::objects('plugin');
				include CAKE_TESTS_LIB . 'content.php';
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