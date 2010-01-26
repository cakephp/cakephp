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
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE_TESTS_LIB . 'test_manager.php';

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
		'show' => 'groups',
		'show_passes' => false
	);

/**
 * The classname for the TestManager being used
 *
 * @var string
 */
	var $_managerClass = 'TestManager';

/**
 * The Instance of the Manager being used.
 *
 * @var TestManager subclass
 */
	var $Manager;

/**
 * Baseurl for the request
 *
 * @var string
 */
	var $_baseUrl;

/**
 * Base dir of the request.  Used for accessing assets.
 *
 * @var string
 */
	var $_baseDir;

/**
 * constructor
 *
 * @return void
 */
	function CakeTestSuiteDispatcher() {
		$this->_baseUrl = $_SERVER['PHP_SELF'];
		$dir = dirname($this->_baseUrl);
		$this->_baseDir = ($dir === '/') ? $dir : $dir . '/';
	}

/**
 * Runs the actions required by the URL parameters.
 *
 * @return void
 */
	function dispatch() {
		$this->_checkSimpleTest();
		$this->_parseParams();

		if ($this->params['group']) {
			$this->_runGroupTest();
		} elseif ($this->params['case']) {
			$this->_runTestCase();
		} elseif (isset($_GET['show']) && $_GET['show'] == 'cases') {
			$this->_testCaseList();
		} else {
			$this->_groupTestList();
		}

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
			$baseDir = $this->_baseDir;
			include CAKE_TESTS_LIB . 'templates' . DS . 'simpletest.php';
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
			$baseDir = $this->_baseDir;
			include CAKE_TESTS_LIB . 'templates' . DS . 'xdebug.php';
			exit();
		}
	}

/**
 * Generates a page containing the a list of test cases that could be run.
 *
 * @return void
 */
	function _testCaseList() {
		$Reporter =& $this->getReporter();
		$Reporter->paintDocumentStart();
		$Reporter->paintTestMenu();
		$Reporter->testCaseList();
		$Reporter->paintDocumentEnd();
	}

/**
 * Generates a page containing a list of group tests that could be run.
 *
 * @return void
 */
	function _groupTestList() {
		$Reporter =& $this->getReporter();
		$Reporter->paintDocumentStart();
		$Reporter->paintTestMenu();
		$Reporter->groupTestList();
		$Reporter->paintDocumentEnd();
	}

/**
 * Sets the Manager to use for the request.
 *
 * @return string The manager class name
 * @static
 */
	function &getManager() {
		if (empty($this->Manager)) {
			$this->Manager = new $this->_managerClass();
		}
		return $this->Manager;
	}

/**
 * Gets the reporter based on the request parameters
 *
 * @return void
 * @static
 */
	function &getReporter() {
		static $Reporter = NULL;
		if (!$Reporter) {
			$type = strtolower($this->params['output']);
			$coreClass = 'Cake' . ucwords($this->params['output']) . 'Reporter';
			$coreFile = CAKE_TESTS_LIB . 'reporter' . DS . 'cake_' . $type . '_reporter.php';

			$appClass = $this->params['output'] . 'Reporter';
			$appFile = APPLIBS . 'test_suite' . DS . 'reporter' . DS . $type . '_reporter.php';
			if (include_once $coreFile) {
				$Reporter =& new $coreClass(null, $this->params);
			} elseif (include_once $appFile) {
				$Reporter =& new $appClass(null, $this->params);
			}
		}
		return $Reporter;
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
		$this->params['baseUrl'] = $this->_baseUrl;
		$this->params['baseDir'] = $this->_baseDir;
		$this->getManager();
	}

/**
 * Runs the group test case.
 *
 * @return void
 */
	function _runGroupTest() {
		$Reporter =& CakeTestSuiteDispatcher::getReporter();
		if ($this->params['codeCoverage']) {
			CodeCoverageManager::init($this->params['group'], $Reporter);
		}
		if ('all' == $this->params['group']) {
			$this->Manager->runAllTests($Reporter);
		} else {
			$this->Manager->runGroupTest(ucfirst($this->params['group']), $Reporter);
		}
	}

/**
 * Runs a test case file.
 *
 * @return void
 */
	function _runTestCase() {
		$Reporter =& CakeTestSuiteDispatcher::getReporter();
		if ($this->params['codeCoverage']) {
			CodeCoverageManager::init($this->params['case'], $Reporter);
		}
		$this->Manager->runTestCase($this->params['case'], $Reporter);
	}
}
?>