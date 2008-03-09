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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
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
set_time_limit(0);
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
if (!include(CORE_PATH . 'cake' . DS . 'bootstrap.php')) {
	trigger_error("Can't find CakePHP core.  Check the value of CAKE_CORE_INCLUDE_PATH in app/webroot/test.php.  It should point to the directory containing your " . DS . "cake core directory and your " . DS . "vendors root directory.", E_USER_ERROR);
}

$corePath = Configure::corePaths('cake');
if (isset($corePath[0])) {
	define('TEST_CAKE_CORE_INCLUDE_PATH', rtrim($corePath[0], DS) . DS);
} else {
	define('TEST_CAKE_CORE_INCLUDE_PATH', CAKE_CORE_INCLUDE_PATH);
}
require_once CAKE_TESTS_LIB . 'test_manager.php';

if (Configure::read('debug') < 1) {
	die(__('Debug setting does not allow access to this url.', true));
}

if (!isset($_SERVER['SERVER_NAME'])) {
	$_SERVER['SERVER_NAME'] = '';
}
if (empty( $_GET['output'])) {
	$_GET['output'] = 'html';
}
/**
 *
 * Used to determine output to display
 */
define('CAKE_TEST_OUTPUT_HTML', 1);
define('CAKE_TEST_OUTPUT_TEXT', 2);

if (isset($_GET['output']) && $_GET['output'] == 'html') {
	define('CAKE_TEST_OUTPUT', CAKE_TEST_OUTPUT_HTML);
} else {
	Debugger::output('txt');
	define('CAKE_TEST_OUTPUT', CAKE_TEST_OUTPUT_TEXT);
}

if (!App::import('Vendor', 'simpletest' . DS . 'reporter')) {
	CakePHPTestHeader();
	include CAKE_TESTS_LIB . 'simpletest.php';
	CakePHPTestSuiteFooter();
	exit();
}

CakePHPTestHeader();
CakePHPTestSuiteHeader();
define('RUN_TEST_LINK', $_SERVER['PHP_SELF']);

if (isset($_GET['group'])) {
	if ('all' == $_GET['group']) {
		TestManager::runAllTests(CakeTestsGetReporter());
	} else {
		TestManager::runGroupTest(ucfirst($_GET['group']), CakeTestsGetReporter());
	}
	CakePHPTestRunMore();
} elseif (isset($_GET['case'])) {
	TestManager::runTestCase($_GET['case'], CakeTestsGetReporter());
	CakePHPTestRunMore();
}elseif (isset($_GET['show']) && $_GET['show'] == 'cases') {
	CakePHPTestCaseList();
} else {
	CakePHPTestGroupTestList();
}
CakePHPTestSuiteFooter();
?>