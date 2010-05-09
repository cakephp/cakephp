<?php
/**
 * TestManager for CakePHP Test suite.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
define('CORE_TEST_CASES', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'cases');
define('CORE_TEST_GROUPS', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'groups');
define('APP_TEST_CASES', TESTS . 'cases');
define('APP_TEST_GROUPS', TESTS . 'groups');

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'DEFAULT');

/**
 * TestManager is the base class that handles loading and initiating the running
 * of TestCase and TestSuite classes that the user has selected.
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class TestManager {
/**
 * Extension suffix for test case files.
 *
 * @var string
 */
	protected static $_testExtension = '.test.php';

/**
 * Extension suffix for group test case files.
 *
 * @var string
 */
	protected static $_groupExtension = '.group.php';

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
 * TestSuite container for single or grouped test files
 *
 * @var PHPUnit_Framework_TestSuiteboolean
 */
	protected $_testSuit = null;

/**
 * Constructor for the TestManager class
 *
 * @return void
 */
	public function __construct() {
		//require_once(CAKE_TESTS_LIB . 'cake_web_test_case.php');
		require_once(CAKE_TESTS_LIB . 'cake_test_case.php');
		if (isset($_GET['app'])) {
			$this->appTest = true;
		}
		if (isset($_GET['plugin'])) {
			$this->pluginTest = $_GET['plugin'];
		}
	}

/**
 * Runs all tests in the Application depending on the current appTest setting
 *
 * @param PHPUnit_Framework_TestListener $reporter Reporter instance to attach to the test case.
 * @return mixed
 */
	public function runAllTests(&$reporter) {
		$testCases = $this->_getTestFileList($this->_getTestsPath());

		if ($this->appTest) {
			$test = $this->getTestSuite(__('All App Tests', true));
		} else if ($this->pluginTest) {
			$test =  $this->getTestSuite(sprintf(__('All %s Plugin Tests', true), Inflector::humanize($this->pluginTest)));
		} else {
			$test =  $this->getTestSuite(__('All Core Tests', true));
		}

		foreach ($testCases as $testCase) {
			$test->addTestFile($testCase);
		}

		return $this->run($reporter);
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
		$testCaseFileWithPath = $this->_getTestsPath() . DS . $testCaseFile;

		if (!file_exists($testCaseFileWithPath)) {
			throw new InvalidArgumentException(sprintf(__('Unable to load test file %s'), $testCaseFile));
		}

		$testSuite = $this->getTestSuite(sprintf(__('Individual test case: %s', true), $testCaseFile));
		$testSuite->addTestFile($testCaseFileWithPath);
		return $this->run($reporter, $codeCoverage);
	}

/**
 * Runs a specific group test file
 *
 * @param string $groupTestName GroupTest that you want to run.
 * @param PHPUnit_Framework_TestListener $reporter Reporter instance to use with the group test being run.
 * @throws InvalidArgumentException if it was not possible to locate the filename for $groupTestName
 * @return mixed Results of group test being run.
 */
	public function runGroupTest($groupTestName, $reporter, $codeCoverage = false) {
		$filePath = $this->_getTestsPath('groups') . DS . strtolower($groupTestName) . $this->getExtension('group');

		if (!file_exists($filePath)) {
			throw new InvalidArgumentException(sprintf(__('Group test %s cannot be found at %s', true), $groupTestName, $filePath));
		}

		require_once $filePath;
		$suite = $this->getTestSuite(sprintf(__('%s group test', true), $groupTestName));
		$groupClassName = Inflector::classify($groupTestName) . 'GroupTest';
		$group = new $groupClassName();
		$suite->addTestSuite($group);
		if (isset($group->label)) {
			$suite->setName($group->label);
		}

		return $this->run($reporter, $codeCoverage);
	}

/**
 * Runs the main testSuite and attaches to it a reporter
 *
 * @param PHPUnit_Framework_TestListener $reporter Reporter instance to use with the group test being run.
 * @return mixed Results of group test being run.
 */
	protected function run($reporter, $codeCoverage = false) {
		$result = new PHPUnit_Framework_TestResult;
		$result->collectRawCodeCoverageInformation($codeCoverage);
		$result->addListener($reporter);
		$reporter->paintHeader();
		$this->getTestSuite()->run($result);
		$reporter->paintResult($result);
		return $result;
	}

/**
 * Adds all testcases in a given directory to a given GroupTest object
 *
 * @param object $groupTest Instance of TestSuite/GroupTest that files are to be added to.
 * @param string $directory The directory to add tests from.
 * @return void
 * @access public
 * @static
 */
	public static function addTestCasesFromDirectory(&$groupTest, $directory = '.') {
		$testCases = self::_getTestFileList($directory);
		foreach ($testCases as $testCase) {
			$groupTest->addTestFile($testCase);
		}
	}

/**
 * Adds a specific test file and thereby all of its test cases and group tests to a given group test file
 *
 * @param object $groupTest Instance of TestSuite/GroupTest that a file should be added to.
 * @param string $file The file name, minus the suffix to add.
 * @return void
 * @access public
 * @static
 */
	public static function addTestFile(&$groupTest, $file) {
		if (file_exists($file . self::$_testExtension)) {
			$file .= self::$_testExtension;
		} elseif (file_exists($file . self::$_groupExtension)) {
			$file .= self::$_groupExtension;
		}
		$groupTest->addTestFile($file);
	}

/**
 * Returns a list of test cases found in the current valid test case path
 *
 * @access public
 * @static
 */
	public static function &getTestCaseList() {
		$return = self::_getTestCaseList(self::_getTestsPath());
		return $return;
	}

/**
 * Builds the list of test cases from a given directory
 *
 * @param string $directory Directory to get test case list from.
 * @static
 */
	protected static function &_getTestCaseList($directory = '.') {
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
		$return = self::_getRecursiveFileList($directory, 'self::_isTestCaseFile');
		return $return;
	}

/**
 * Returns a list of group tests found in the current valid test case path
 *
 * @access public
 * @static
 */
	public static function &getGroupTestList() {
		$return = self::_getTestGroupList(self::_getTestsPath('groups'));
		return $return;
	}

/**
 * Returns a list of group test files from a given directory
 *
 * @param string $directory The directory to get group test files from.
 * @static
 */
	protected static function &_getTestGroupFileList($directory = '.') {
		$return = self::_getRecursiveFileList($directory, 'self::_isTestGroupFile');
		return $return;
	}

/**
 * Returns a list of group test files from a given directory
 *
 * @param string $directory The directory to get group tests from.
 * @static
 */
	protected static function &_getTestGroupList($directory = '.') {
		$fileList = self::_getTestGroupFileList($directory);
		$groupTests = array();

		foreach ($fileList as $groupTestFile) {
			$groupTests[$groupTestFile] = str_replace(self::$_groupExtension, '', basename($groupTestFile));
		}
		sort($groupTests);
		return $groupTests;
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
 * Tests if a file has the correct group test extension
 *
 * @param string $file
 * @return boolean Whether $file is a group
 * @static
 */
	protected static function _isTestGroupFile($file) {
		return static::_hasExpectedExtension($file, static::$_groupExtension);
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
 * Returns the given path to the test files depending on a given type of tests (cases, group, ..)
 *
 * @param string $type either 'cases' or 'groups'
 * @return string The path tests are located on
 * @static
 */
	protected static function _getTestsPath($type = 'cases') {
		if (!empty(self::$appTest)) {
			if ($type == 'cases') {
				$result = APP_TEST_CASES;
			} else if ($type == 'groups') {
				$result = APP_TEST_GROUPS;
			}
		} else if (!empty(self::$pluginTest)) {
			$_pluginBasePath = APP . 'plugins/' . self::$pluginTest . '/tests';
			$pluginPath = App::pluginPath(self::$pluginTest);
			if (file_exists($pluginPath . DS . 'tests')) {
				$_pluginBasePath = $pluginPath . DS . 'tests';
			}
			$result = $_pluginBasePath . DS . $type;
		} else {
			if ($type == 'cases') {
				$result = CORE_TEST_CASES;
			} else if ($type == 'groups') {
				$result = CORE_TEST_GROUPS;
			}
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
		if ($type == 'test' || $type == 'case') {
			return self::$_testExtension;
		}
		return self::$_groupExtension;
	}

/**
 * Get the container testSuite instance for this runner or creates a new one
 *
 * @param string $name The name for the container test suite
 * @return PHPUnit_Framework_TestSuite container test suite
 */
	protected function getTestSuite($name = '') {
		if (!empty($this->_testSuite)) {
			return $this->_testSuite;
		}
		return $this->_testSuite = new PHPUnit_Framework_TestSuite($name);
	}

}

?>