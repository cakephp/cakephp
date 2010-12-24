<?php
/**
 * TestManager for CakePHP Test suite.
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
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.lib
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
define('CORE_TEST_CASES', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'cases');
define('CORE_TEST_GROUPS', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'groups');
define('APP_TEST_CASES', TESTS . 'cases');
define('APP_TEST_GROUPS', TESTS . 'groups');

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');
require_once CAKE_TESTS_LIB . 'cake_test_suite.php';

/**
 * TestManager is the base class that handles loading and initiating the running
 * of TestCase and TestSuite classes that the user has selected.
 *
 * @package       cake.tests.lib
 */
class TestManager {
/**
 * Extension suffix for test case files.
 *
 * @var string
 */
	protected static $_testExtension = '.test.php';

/**
 * Is this test an AppTest?
 *
 * @var boolean
 */
	public $appTest = false;

/**
 * Is this test a plugin test?
 *
 * @var mixed boolean false or string name of the plugin being used.
 */
	public $pluginTest = false;

/**
 * String to filter test case method names by.
 *
 * @var string
 */
	public $filter = false;

/**
 * TestSuite container for single or grouped test files
 *
 * @var PHPUnit_Framework_TestSuite
 */
	protected $_testSuite = null;

/**
 * Object instance responsible for managing the test fixtures
 *
 * @var CakeFixtureManager
 */
	protected $_fixtureManager = null;

/**
 * Params to configure test runner
 *
 * @var CakeFixtureManager
 */
	public $params = array();

/**
 * Constructor for the TestManager class
 *
 * @return void
 */
	public function __construct($params = array()) {
		require_once(CAKE_TESTS_LIB . 'cake_test_case.php');
		require_once(CAKE_TESTS_LIB . 'controller_test_case.php');

		$this->params = $params;
		if (isset($params['app'])) {
			$this->appTest = true;
		}
		if (isset($params['plugin'])) {
			$this->pluginTest = htmlentities($params['plugin']);
		}
		if (
			isset($params['filter']) && 
			$params['filter'] !== false &&
			preg_match('/^[a-zA-Z0-9_]/', $params['filter'])
		) {
			$this->filter = '/' . $params['filter'] . '/';
		}
	}

/**
 * Runs a specific test case file
 *
 * @param string $testCaseFile Filename of the test to be run.
 * @param PHPUnit_Framework_TestListener $reporter Reporter instance to attach to the test case.
 * @throws InvalidArgumentException if the supplied $testCaseFile does not exists
 * @return mixed Result of test case being run.
 */
	public function runTestCase($testCaseFile, PHPUnit_Framework_TestListener $reporter, $codeCoverage = false) {
		$this->loadCase($testCaseFile);
		return $this->run($reporter, $codeCoverage);
	}

/**
 * Runs the main testSuite and attaches to it a reporter
 *
 * @param PHPUnit_Framework_TestListener $reporter Reporter instance to use with the group test being run.
 * @return PHPUnit_Framework_TestResult Result object of the test run.
 */
	protected function run($reporter, $codeCoverage = false) {
		restore_error_handler();
		restore_error_handler();

		$result = new PHPUnit_Framework_TestResult;
		$result->collectCodeCoverageInformation($codeCoverage);
		$result->addListener($reporter);
		$reporter->paintHeader();
		$testSuite = $this->getTestSuite();
		$testSuite->setFixtureManager($this->getFixtureManager());
		$testSuite->run($result, $this->filter);
		$reporter->paintResult($result);
		return $result;
	}

/**
 * Loads a test case in a test suite, if the test suite is null it will create it
 *
 * @param string Test file path
 * @param PHPUnit_Framework_TestSuite $suite the test suite to load the case in
 * @throws InvalidArgumentException if test case file is not found
 * @return PHPUnit_Framework_TestSuite the suite with the test case loaded
 */
	public function loadCase($testCaseFile, PHPUnit_Framework_TestSuite $suite = null) {
		$testCaseFileWithPath = $this->_getTestsPath($this->params) . DS . $testCaseFile;

		if (!file_exists($testCaseFileWithPath) || strpos($testCaseFileWithPath, '..')) {
			throw new InvalidArgumentException(__('Unable to load test file %s', htmlentities($testCaseFile)));
		}
		if (!$suite) {
			$suite = $this->getTestSuite(__('Individual test case: %s', $testCaseFile));
		}
		$suite->addTestFile($testCaseFileWithPath);

		return $suite;
	}

/**
 * Returns a list of test cases found in the current valid test case path
 *
 * @access public
 * @static
 */
	public static function getTestCaseList($params) {
		$directory = self::_getTestsPath($params);
		$fileList = self::_getTestFileList($directory);

		$testCases = array();
		foreach ($fileList as $testCaseFile) {
			$testCases[$testCaseFile] = str_replace($directory . DS, '', $testCaseFile);
		}
		return $testCases;
	}

/**
 * Returns a list of test files from a given directory
 *
 * @param string $directory Directory to get test case files from.
 * @static
 */
	protected static function &_getTestFileList($directory = '.') {
		$return = self::_getRecursiveFileList($directory, array('self', '_isTestCaseFile'));
		return $return;
	}

/**
 * Gets a recursive list of files from a given directory and matches then against
 * a given fileTestFunction, like isTestCaseFile()
 *
 * @param string $directory The directory to scan for files.
 * @param mixed $fileTestFunction
 * @static
 */
	protected static function &_getRecursiveFileList($directory = '.', $fileTestFunction) {
		$fileList = array();
		if (!is_dir($directory)) {
			return $fileList;
		}

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

		foreach ($files as $file) {
			if (!$file->isFile()) {
				continue;
			}
			$file = $file->getRealPath();

			if (call_user_func_array($fileTestFunction, array($file))) {
				$fileList[] = $file;
			}
		}
		return $fileList;
	}

/**
 * Tests if a file has the correct test case extension
 *
 * @param string $file
 * @return boolean Whether $file is a test case.
 * @static
 */
	protected static function _isTestCaseFile($file) {
		return self::_hasExpectedExtension($file, self::$_testExtension);
	}

/**
 * Check if a file has a specific extension
 *
 * @param string $file
 * @param string $extension
 * @return void
 * @static
 */
	protected static function _hasExpectedExtension($file, $extension) {
		return $extension == strtolower(substr($file, (0 - strlen($extension))));
	}

/**
 * Returns the given path to the test files depending on a given type of tests (core, app, plugin)
 *
 * @param array $params Array of parameters for getting test paths.
 *   Can contain app, type, and plugin params.
 * @return string The path tests are located on
 * @static
 */
	protected static function _getTestsPath($params) {
		$result = null;
		if (!empty($params['app'])) {
			$result = APP_TEST_CASES;
		} else if (!empty($params['plugin'])) {
			$pluginPath = App::pluginPath($params['plugin']);
			$result = $pluginPath . 'tests' . DS . 'cases';
		} else {
			$result = CORE_TEST_CASES;
		}
		return $result;
	}

/**
 * Get the extension for either 'group' or 'test' types.
 *
 * @param string $type Type of test to get, either 'test' or 'group'
 * @return string Extension suffix for test.
 */
	public static function getExtension($type = 'test') {
		return self::$_testExtension;
	}

/**
 * Get the container testSuite instance for this runner or creates a new one
 *
 * @param string $name The name for the container test suite
 * @return PHPUnit_Framework_TestSuite container test suite
 */
	public function getTestSuite($name = '') {
		if (!empty($this->_testSuite)) {
			return $this->_testSuite;
		}
		return $this->_testSuite = new CakeTestSuite($name);
	}

/**
 * Get an instance of a Fixture manager to be used by the test cases
 *
 * @return CakeFixtureManager fixture manager
 */
	public function getFixtureManager() {
		if (!empty($this->_fixtureManager)) {
			return $this->_fixtureManager;
		}
		return $this->_fixtureManager = new CakeFixtureManager;
	}
}
