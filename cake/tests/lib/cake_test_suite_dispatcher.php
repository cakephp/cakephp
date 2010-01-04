<?php
/**
 * CakeTestSuiteDispatcher controls dispatching TestSuite web based requests.
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
require_once CAKE_TESTS_LIB . 'test_manager.php';
require_once CAKE_TESTS_LIB . 'cake_test_menu.php';

/**
 * CakeTestSuiteDispatcher handles web requests to the test suite and runs the correct action.
 *
 * @package cake.tests.libs
 */
class CakeTestSuiteDispatcher {
/**
 * 'Request' parameters
 *
 * @var array
 */
	var $params = array(
		'codeCoverage' => false,
		'group' => null,
		'case' => null,
		'app' => false,
		'plugin' => null,
		'output' => 'html',
		'show' => 'groups'
	);
/**
 * Runs the actions required by the URL parameters.
 *
 * @return void
 */
	function dispatch() {
		CakeTestMenu::testHeader();
		CakeTestMenu::testSuiteHeader();

		$this->_checkSimpleTest();
		$this->_parseParams();

		if ($this->params['group']) {
			$this->_runGroupTest();
		} elseif ($this->params['case']) {
			$this->_runTestCase();
		} elseif (isset($_GET['show']) && $_GET['show'] == 'cases') {
			CakeTestMenu::testCaseList();
		} else {
			CakeTestMenu::groupTestList();
		}

		CakeTestMenu::footer();
		$output = ob_get_clean();
		echo $output;
	}

/**
 * Checks that simpleTest is installed.  Will exit if it doesn't
 *
 * @return void
 */
	function _checkSimpleTest() {
		if (!App::import('Vendor', 'simpletest' . DS . 'reporter')) {
			CakeTestMenu::testHeader();
			include CAKE_TESTS_LIB . 'simpletest.php';
			CakeTestMenu::footer();
			exit();
		}
	}

/**
 * Checks for the xdebug extension required to do code coverage. Displays an error
 * if xdebug isn't installed.
 *
 * @return void
 */
	function _checkXdebug() {
		if (!extension_loaded('xdebug')) {
			CakeTestMenu::testHeader();
			include CAKE_TESTS_LIB . 'xdebug.php';
			CakeTestMenu::footer();
			exit();
		}
	}

/**
 * Parse url params into a 'request'
 *
 * @return void
 */
	function _parseParams() {
		if (!isset($_SERVER['SERVER_NAME'])) {
			$_SERVER['SERVER_NAME'] = '';
		}
		foreach ($this->params as $key => $value) {
			if (isset($_GET[$key])) {
				$this->params[$key] = $_GET[$key];
			}
		}
		if (isset($_GET['code_coverage'])) {
			$this->params['codeCoverage'] = true;
			require_once CAKE_TESTS_LIB . 'code_coverage_manager.php';
			$this->_checkXdebug();
		}
	}

/**
 * Runs the group test case.
 *
 * @return void
 */
	function _runGroupTest() {
		if ('all' == $this->params['group']) {
			TestManager::runAllTests(CakeTestsGetReporter());
		} else {
			if ($this->params['codeCoverage']) {
				CodeCoverageManager::start($this->params['group'], CakeTestsGetReporter());
			}
			TestManager::runGroupTest(ucfirst($this->params['group']), CakeTestsGetReporter());
			if ($this->params['codeCoverage']) {
				CodeCoverageManager::report();
			}
		}
		CakeTestMenu::runMore();
		CakeTestMenu::analyzeCodeCoverage();
	}

/**
 * Runs a test case file.
 *
 * @return void
 */
	function _runTestCase() {
		if ($this->params['codeCoverage']) {
			CodeCoverageManager::start($_GET['case'], CakeTestsGetReporter());
		}

		TestManager::runTestCase($_GET['case'], CakeTestsGetReporter());

		if ($this->params['codeCoverage']) {
			CodeCoverageManager::report();
		}
		CakeTestMenu::runMore();
		CakeTestMenu::analyzeCodeCoverage();
	}
}
?>