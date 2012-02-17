<?php
/**
 * CakeTestSuiteDispatcher controls dispatching TestSuite web based requests.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

define('CORE_TEST_CASES', CAKE . 'Test' . DS . 'Case');
define('APP_TEST_CASES', TESTS . 'Case');

App::uses('CakeTestSuiteCommand', 'TestSuite');

/**
 * CakeTestSuiteDispatcher handles web requests to the test suite and runs the correct action.
 *
 * @package       Cake.TestSuite
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
		'core' => false,
		'app' => true,
		'plugin' => null,
		'output' => 'html',
		'show' => 'groups',
		'show_passes' => false,
		'filter' => false,
		'fixture' => null
	);

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
	public function dispatch() {
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
			include CAKE . 'TestSuite' . DS . 'templates' . DS . 'phpunit.php';
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
			include CAKE . 'TestSuite' . DS . 'templates' . DS . 'xdebug.php';
			exit();
		}
	}

/**
 * Generates a page containing the a list of test cases that could be run.
 *
 * @return void
 */
	function _testCaseList() {
		$command = new CakeTestSuiteCommand('', $this->params);
		$Reporter = $command->handleReporter($this->params['output']);
		$Reporter->paintDocumentStart();
		$Reporter->paintTestMenu();
		$Reporter->testCaseList();
		$Reporter->paintDocumentEnd();
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
		if (empty($this->params['plugin']) && empty($this->params['core'])) {
			$this->params['app'] = true;
		}
		$this->params['baseUrl'] = $this->_baseUrl;
		$this->params['baseDir'] = $this->_baseDir;
	}

/**
 * Runs a test case file.
 *
 * @return void
 */
	function _runTestCase() {
		$commandArgs = array(
			'case' => $this->params['case'],
			'core' => $this->params['core'],
			'app' => $this->params['app'],
			'plugin' => $this->params['plugin'],
			'codeCoverage' => $this->params['codeCoverage'],
			'showPasses' => !empty($this->params['show_passes']),
			'baseUrl' => $this->_baseUrl,
			'baseDir' => $this->_baseDir,
		);

		$options = array(
			'--filter', $this->params['filter'],
			'--output', $this->params['output'],
			'--fixture', $this->params['fixture']
		);
		restore_error_handler();

		try {
			$command = new CakeTestSuiteCommand('CakeTestLoader', $commandArgs);
			$result = $command->run($options);
		} catch (MissingConnectionException $exception) {
			ob_end_clean();
			$baseDir = $this->_baseDir;
			include CAKE . 'TestSuite' . DS . 'templates' . DS . 'missing_connection.php';
			exit();
		}
	}
}
