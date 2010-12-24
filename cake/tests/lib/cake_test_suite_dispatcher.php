<?php
/**
 * CakeTestSuiteDispatcher controls dispatching TestSuite web based requests.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.lib
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

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
	public $params = array(
		'codeCoverage' => false,
		'case' => null,
		'app' => false,
		'plugin' => null,
		'output' => 'html',
		'show' => 'groups',
		'show_passes' => false,
		'filter' => false
	);

/**
 * The classname for the TestManager being used
 *
 * @var string
 */
	protected $_managerClass = 'TestManager';

/**
 * The Instance of the Manager being used.
 *
 * @var TestManager subclass
 */
	public $Manager;

/**
 * Baseurl for the request
 *
 * @var string
 */
	protected $_baseUrl;

/**
 * Base dir of the request.  Used for accessing assets.
 *
 * @var string
 */
	protected $_baseDir;

/**
 * boolean to set auto parsing of params.
 *
 * @var boolean
 */
	protected $_paramsParsed = false;

/**
 * reporter instance used for the request
 *
 * @var CakeBaseReporter
 */
	protected static $_Reporter = null;

/**
 * constructor
 *
 * @return void
 */
	function __construct() {
		$this->_baseUrl = $_SERVER['PHP_SELF'];
		$dir = rtrim(dirname($this->_baseUrl), '\\');
		$this->_baseDir = ($dir === '/') ? $dir : $dir . '/';
	}

/**
 * Runs the actions required by the URL parameters.
 *
 * @return void
 */
	function dispatch() {
		$this->_checkPHPUnit();
		$this->_parseParams();

		if ($this->params['case']) {
			$value = $this->_runTestCase();
		} else {
			$value = $this->_testCaseList();
		}

		$output = ob_get_clean();
		echo $output;
		return $value;
	}

/**
 * Static method to initialize the test runner, keeps global space clean
 *
 * @return void
 */
	public static function run() {
		$dispatcher = new CakeTestSuiteDispatcher();
		$dispatcher->dispatch();
	}

/**
 * Checks that PHPUnit is installed.  Will exit if it doesn't
 *
 * @return void
 */
	protected function _checkPHPUnit() {
		$found = $this->loadTestFramework();
		if (!$found) {
			$baseDir = $this->_baseDir;
			include CAKE_TESTS_LIB . 'templates/phpunit.php';
			exit();
		}
	}

/**
 * Checks for the existence of the test framework files
 *
 * @return boolean true if found, false otherwise
 */
	public function loadTestFramework() {
		$found = $path = null;

		if (@include 'PHPUnit' . DS . 'Autoload.php') {
			$found = true;
		}

		if (!$found) {
			foreach (App::path('vendors') as $vendor) {
				if (is_dir($vendor . 'PHPUnit')) {
					$path = $vendor;
				}
			}

			if ($path && ini_set('include_path', $path . PATH_SEPARATOR . ini_get('include_path'))) {
				$found = include 'PHPUnit' . DS . 'Autoload.php';
			}
		}
		if ($found) {
			PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');
		}
		return $found;
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
			include CAKE_TESTS_LIB . 'templates/xdebug.php';
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
 * Sets the Manager to use for the request.
 *
 * @return string The manager class name
 * @static
 */
	function &getManager() {
		if (empty($this->Manager)) {
			require_once CAKE_TESTS_LIB . 'test_manager.php';
			$this->Manager = new $this->_managerClass($this->params);
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
		if (!self::$_Reporter) {
			$type = strtolower($this->params['output']);
			$coreClass = 'Cake' . ucwords($this->params['output']) . 'Reporter';
			$coreFile = CAKE_TESTS_LIB . 'reporter/cake_' . $type . '_reporter.php';

			$appClass = $this->params['output'] . 'Reporter';
			$appFile = APPLIBS . 'test_suite/reporter/' . $type . '_reporter.php';
			if (include_once $coreFile) {
				self::$_Reporter = new $coreClass(null, $this->params);
			} elseif (include_once $appFile) {
				self::$_Reporter = new $appClass(null, $this->params);
			}
		}
		return self::$_Reporter;
	}

/**
 * Sets the params, calling this will bypass the auto parameter parsing.
 *
 * @param array $params Array of parameters for the dispatcher
 * @return void
 */
	public function setParams($params) {
		$this->params = $params;
		$this->_paramsParsed = true;
	}

/**
 * Parse url params into a 'request'
 *
 * @return void
 */
	function _parseParams() {
		if (!$this->_paramsParsed) {
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
				$this->_checkXdebug();
			}
		}
		$this->params['baseUrl'] = $this->_baseUrl;
		$this->params['baseDir'] = $this->_baseDir;
		$this->getManager();
	}

/**
 * Runs a test case file.
 *
 * @return void
 */
	function _runTestCase() {
		try {
			$Reporter = CakeTestSuiteDispatcher::getReporter();
			return $this->Manager->runTestCase($this->params['case'], $Reporter, $this->params['codeCoverage']);
		} catch (MissingConnectionException $exception) {
			ob_end_clean();
			$baseDir = $this->_baseDir;
			include CAKE_TESTS_LIB . 'templates' . DS . 'missing_conenction.php';
			exit();
		}
	}
}
