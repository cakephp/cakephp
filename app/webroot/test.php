<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake
 * @subpackage		cake.cake.tests.libs
 * @since			CakePHP(tm) v 1.2.0.4433
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
error_reporting(E_ALL);
set_time_limit(600);
ini_set('memory_limit','128M');
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
	define('ROOT', dirname(dirname(dirname(__FILE__))));
}
if (!defined('APP_DIR')) {
	define('APP_DIR', basename(dirname(dirname(__FILE__))));
}
if (!defined('CAKE_CORE_INCLUDE_PATH')) {
	define('CAKE_CORE_INCLUDE_PATH', ROOT);
}
if (!defined('WEBROOT_DIR')) {
	define('WEBROOT_DIR', basename(dirname(__FILE__)));
}
if (!defined('WWW_ROOT')) {
	define('WWW_ROOT', dirname(__FILE__) . DS);
}
if (!defined('CORE_PATH')) {
	if (function_exists('ini_set')) {
		ini_set('include_path', CAKE_CORE_INCLUDE_PATH . PATH_SEPARATOR . ROOT . DS . APP_DIR . DS . PATH_SEPARATOR . ini_get('include_path'));
		define('APP_PATH', null);
		define('CORE_PATH', null);
	} else {
		define('APP_PATH', ROOT . DS . APP_DIR . DS);
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
	}
}

ini_set('display_errors', 1);
require_once CORE_PATH . 'cake' . DS . 'bootstrap.php';
require_once CAKE . 'basics.php';
require_once CAKE . 'config' . DS . 'paths.php';
require_once CAKE . 'tests' . DS . 'lib' . DS . 'test_manager.php';
if(DEBUG < 1) {
	die('Invalid url.');
}
if(!vendor('simpletest' . DS . 'reporter')) {
	die('SimpleTest is not installed.');
}

if (!isset($_SERVER['SERVER_NAME'])) {
	$_SERVER['SERVER_NAME'] = '';
}
if (empty( $_GET['output'])) {
	$_GET['output'] = 'html';
}

if (!defined('BASE_URL')){
	$dispatch =& new Dispatcher();
	define('BASE_URL', $dispatch->baseUrl());
}
/**
 *
 * Used to determine output to display
 */
define('CAKE_TEST_OUTPUT_HTML',1);
define('CAKE_TEST_OUTPUT_TEXT',2);

if(isset($_GET['output']) && $_GET['output'] == 'html') {
	define('CAKE_TEST_OUTPUT', CAKE_TEST_OUTPUT_HTML);
} else {
	define('CAKE_TEST_OUTPUT', CAKE_TEST_OUTPUT_TEXT);
}

	function &CakeTestsGetReporter() {
		static $Reporter = NULL;
		if (!$Reporter) {
			switch (CAKE_TEST_OUTPUT) {
				case CAKE_TEST_OUTPUT_HTML:
					require_once LIB_TESTS . 'cake_reporter.php';
					$Reporter = new CakeHtmlReporter();
				break;
				default:
					$Reporter = new TextReporter();
				break;
			}
		}
		return $Reporter;
	}

	function CakePHPTestRunMore() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				echo "<p><a href='" . $_SERVER['PHP_SELF'] . "'>Run more tests</a></p>\n";
			break;
		}
	}

	function CakePHPTestCaseList() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				if (isset($_GET['app'])) {
					echo HtmlTestManager::getTestCaseList(APP_TEST_CASES);
				} else {
					echo HtmlTestManager::getTestCaseList(CORE_TEST_CASES);
				}
			break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				if (isset($_GET['app'])) {
					echo TextTestManager::getTestCaseList(APP_TEST_CASES);
				} else {
					echo TextTestManager::getTestCaseList(CORE_TEST_CASES);
				}
			break;
		}
	}

	function CakePHPTestGroupTestList() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				if (isset($_GET['app'])) {
					echo HtmlTestManager::getGroupTestList(APP_TEST_GROUPS);
				} else {
					echo HtmlTestManager::getGroupTestList(CORE_TEST_GROUPS);
				}
			break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				if (isset($_GET['app'])) {
					echo TextTestManager::getGroupTestList(APP_TEST_GROUPS);
				} else {
					echo TextTestManager::getGroupTestList(CORE_TEST_GROUPS);
				}
				break;
		}
	}

	function CakePHPTestHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				$baseUrl = BASE_URL;
				include CAKE . 'tests' . DS . 'lib' . DS . 'header.php';
			break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				header(' content-type: text/plain');
			break;
		}
	}

	function CakePHPTestSuiteHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				$groups = $_SERVER['PHP_SELF'].'?show=groups';
				$cases = $_SERVER['PHP_SELF'].'?show=cases';
				include CAKE . 'tests' . DS . 'lib' . DS . 'content.php';
			break;
		}
	}

	function CakePHPTestSuiteFooter() {
		switch ( CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				$baseUrl = BASE_URL;
				include CAKE . 'tests' . DS . 'lib' . DS . 'footer.php';
			break;
		}
	}
	
	CakePHPTestHeader();
	CakePHPTestSuiteHeader();
	
	if (isset($_GET['group'])) {
		if ('all' == $_GET['group']) {
			TestManager::runAllTests(CakeTestsGetReporter());
		} else {
			if (isset($_GET['app'])) {
				TestManager::runGroupTest(ucfirst($_GET['group']), APP_TEST_GROUPS, CakeTestsGetReporter());
			} else {
				TestManager::runGroupTest(ucfirst($_GET['group']), CORE_TEST_GROUPS, CakeTestsGetReporter());
			}
		}
		CakePHPTestRunMore();
		CakePHPTestSuiteFooter();
		exit();
	}

	if (isset($_GET['case'])) {
		TestManager::runTestCase($_GET['case'], CakeTestsGetReporter());
		CakePHPTestRunMore();
		CakePHPTestSuiteFooter();
		exit();
	}

	if (isset($_GET['show']) && $_GET['show'] == 'cases') {
		CakePHPTestCaseList();
	} else {
		CakePHPTestGroupTestList();
	}
	CakePHPTestSuiteFooter();
?>