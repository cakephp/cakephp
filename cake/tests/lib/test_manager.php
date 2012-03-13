<?php
/**
 * TestManager for CakePHP Test suite.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
define('CORE_TEST_CASES', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'cases');
define('CORE_TEST_GROUPS', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'groups');
define('APP_TEST_CASES', TESTS . 'cases');
define('APP_TEST_GROUPS', TESTS . 'groups');

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
	var $_testExtension = '.test.php';

/**
 * Extension suffix for group test case files.
 *
 * @var string
 */
	var $_groupExtension = '.group.php';

/**
 * Is this test an AppTest?
 *
 * @var boolean
 */
	var $appTest = false;

/**
 * Is this test a plugin test?
 *
 * @var mixed boolean false or string name of the plugin being used.
 */
	var $pluginTest = false;

/**
 * Constructor for the TestManager class
 *
 * @return void
 * @access public
 */
	function TestManager() {
		$this->_installSimpleTest();
		if (isset($_GET['app'])) {
			$this->appTest = true;
		}
		if (isset($_GET['plugin'])) {
			$this->pluginTest = htmlentities($_GET['plugin']);
		}
	}

/**
 * Includes the required simpletest files in order for the testsuite to run
 *
 * @return void
 * @access public
 */
	function _installSimpleTest() {
		App::import('Vendor', array(
			'simpletest' . DS . 'unit_tester',
			'simpletest' . DS . 'mock_objects',
			'simpletest' . DS . 'web_tester'
		));
		require_once(CAKE_TESTS_LIB . 'cake_web_test_case.php');
		require_once(CAKE_TESTS_LIB . 'cake_test_case.php');
	}

/**
 * Runs all tests in the Application depending on the current appTest setting
 *
 * @param Object $reporter Reporter object for the tests being run.
 * @param boolean $testing Are tests supposed to be auto run.  Set to true to return testcase list.
 * @return mixed
 * @access public
 */
	function runAllTests(&$reporter, $testing = false) {
		$testCases =& $this->_getTestFileList($this->_getTestsPath());
		if ($this->appTest) {
			$test =& new TestSuite(__('All App Tests', true));
		} else if ($this->pluginTest) {
			$test =& new TestSuite(sprintf(__('All %s Plugin Tests', true), Inflector::humanize($this->pluginTest)));
		} else {
			$test =& new TestSuite(__('All Core Tests', true));
		}

		if ($testing) {
			return $testCases;
		}

		foreach ($testCases as $testCase) {
			$test->addTestFile($testCase);
		}

		return $test->run($reporter);
	}

/**
 * Runs a specific test case file
 *
 * @param string $testCaseFile Filename of the test to be run.
 * @param Object $reporter Reporter instance to attach to the test case.
 * @param boolean $testing Set to true if testing, otherwise test case will be run.
 * @return mixed Result of test case being run.
 * @access public
 */
	function runTestCase($testCaseFile, &$reporter, $testing = false) {
		$testCaseFileWithPath = $this->_getTestsPath() . DS . $testCaseFile;

		if (!file_exists($testCaseFileWithPath) || strpos($testCaseFileWithPath, '..')) {
			trigger_error(
				sprintf(__("Test case %s cannot be found", true), htmlentities($testCaseFile)),
				E_USER_ERROR
			);
			return false;
		}

		if ($testing) {
			return true;
		}

		$test =& new TestSuite(sprintf(__('Individual test case: %s', true), $testCaseFile));
		$test->addTestFile($testCaseFileWithPath);
		return $test->run($reporter);
	}

/**
 * Runs a specific group test file
 *
 * @param string $groupTestName GroupTest that you want to run.
 * @param Object $reporter Reporter instance to use with the group test being run.
 * @return mixed Results of group test being run.
 * @access public
 */
	function runGroupTest($groupTestName, &$reporter) {
		$filePath = $this->_getTestsPath('groups') . DS . strtolower($groupTestName) . $this->_groupExtension;

		if (!file_exists($filePath) || strpos($filePath, '..')) {
			trigger_error(sprintf(
					__("Group test %s cannot be found at %s", true), 
					htmlentities($groupTestName), 
					htmlentities($filePath)
				),
				E_USER_ERROR
			);
		}

		require_once $filePath;
		$test =& new TestSuite(sprintf(__('%s group test', true), $groupTestName));
		foreach ($this->_getGroupTestClassNames($filePath) as $groupTest) {
			$testCase = new $groupTest();
			$test->addTestCase($testCase);
			if (isset($testCase->label)) {
				$test->_label = $testCase->label;
			}
		}
		return $test->run($reporter);
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
	function addTestCasesFromDirectory(&$groupTest, $directory = '.') {
		$manager =& new TestManager();
		$testCases =& $manager->_getTestFileList($directory);
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
	function addTestFile(&$groupTest, $file) {
		$manager =& new TestManager();

		if (file_exists($file . $manager->_testExtension)) {
			$file .= $manager->_testExtension;
		} elseif (file_exists($file . $manager->_groupExtension)) {
			$file .= $manager->_groupExtension;
		}
		$groupTest->addTestFile($file);
	}

/**
 * Returns a list of test cases found in the current valid test case path
 *
 * @access public
 * @static
 */
	function &getTestCaseList() {
		$manager =& new TestManager();
		$return = $manager->_getTestCaseList($manager->_getTestsPath());
		return $return;
	}

/**
 * Builds the list of test cases from a given directory
 *
 * @param string $directory Directory to get test case list from.
 * @access protected
 */
	function &_getTestCaseList($directory = '.') {
		$fileList =& $this->_getTestFileList($directory);
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
 * @access protected
 */
	function &_getTestFileList($directory = '.') {
		$return = $this->_getRecursiveFileList($directory, array(&$this, '_isTestCaseFile'));
		return $return;
	}

/**
 * Returns a list of group tests found in the current valid test case path
 *
 * @access public
 * @static
 */
	function &getGroupTestList() {
		$manager =& new TestManager();
		$return = $manager->_getTestGroupList($manager->_getTestsPath('groups'));
		return $return;
	}

/**
 * Returns a list of group test files from a given directory
 *
 * @param string $directory The directory to get group test files from.
 * @access protected
 */
	function &_getTestGroupFileList($directory = '.') {
		$return = $this->_getRecursiveFileList($directory, array(&$this, '_isTestGroupFile'));
		return $return;
	}

/**
 * Returns a list of group test files from a given directory
 *
 * @param string $directory The directory to get group tests from.
 * @access protected
 */
	function &_getTestGroupList($directory = '.') {
		$fileList =& $this->_getTestGroupFileList($directory);
		$groupTests = array();

		foreach ($fileList as $groupTestFile) {
			$groupTests[$groupTestFile] = str_replace($this->_groupExtension, '', basename($groupTestFile));
		}
		sort($groupTests);
		return $groupTests;
	}

/**
 * Returns a list of class names from a group test file
 *
 * @param string $groupTestFile The groupTest file to scan for TestSuite classnames.
 * @access protected
 */
	function &_getGroupTestClassNames($groupTestFile) {
		$file = implode("\n", file($groupTestFile));
		preg_match("~lass\s+?(.*)\s+?extends TestSuite~", $file, $matches);
		if (!empty($matches)) {
			unset($matches[0]);
			return $matches;
		}
		$matches = array();
		return $matches;
	}

/**
 * Gets a recursive list of files from a given directory and matches then against
 * a given fileTestFunction, like isTestCaseFile()
 *
 * @param string $directory The directory to scan for files.
 * @param mixed $fileTestFunction
 * @access protected
 */
	function &_getRecursiveFileList($directory = '.', $fileTestFunction) {
		$fileList = array();
		if (!is_dir($directory)) {
			return $fileList;
		}

		$files = glob($directory . DS . '*');
		$files = $files ? $files : array();

		foreach ($files as $file) {
			if (is_dir($file)) {
				$fileList = array_merge($fileList, $this->_getRecursiveFileList($file, $fileTestFunction));
			} elseif ($fileTestFunction[0]->$fileTestFunction[1]($file)) {
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
 * @access protected
 */
	function _isTestCaseFile($file) {
		return $this->_hasExpectedExtension($file, $this->_testExtension);
	}

/**
 * Tests if a file has the correct group test extension
 *
 * @param string $file
 * @return boolean Whether $file is a group
 * @access protected
 */
	function _isTestGroupFile($file) {
		return $this->_hasExpectedExtension($file, $this->_groupExtension);
	}

/**
 * Check if a file has a specific extension
 *
 * @param string $file
 * @param string $extension
 * @return void
 * @access protected
 */
	function _hasExpectedExtension($file, $extension) {
		return $extension == strtolower(substr($file, (0 - strlen($extension))));
	}

/**
 * Returns the given path to the test files depending on a given type of tests (cases, group, ..)
 *
 * @param string $type either 'cases' or 'groups'
 * @return string The path tests are located on
 * @access protected
 */
	function _getTestsPath($type = 'cases') {
		if (!empty($this->appTest)) {
			if ($type == 'cases') {
				$result = APP_TEST_CASES;
			} else if ($type == 'groups') {
				$result = APP_TEST_GROUPS;
			}
		} else if (!empty($this->pluginTest)) {
			$_pluginBasePath = APP . 'plugins' . DS . $this->pluginTest . DS . 'tests';
			$pluginPath = App::pluginPath($this->pluginTest);
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
 * @access public
 */
	function getExtension($type = 'test') {
		if ($type == 'test' || $type == 'case') {
			return $this->_testExtension;
		}
		return $this->_groupExtension;
	}
}
