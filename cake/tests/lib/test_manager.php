<?php
/**
 * Short description for file.
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
			$this->pluginTest = $_GET['plugin'];
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
		$manager =& new TestManager();

		$testCases =& $manager->_getTestFileList($manager->_getTestsPath());
		if ($manager->appTest) {
			$test =& new TestSuite('All App Tests');
		} else if ($manager->pluginTest) {
			$test =& new TestSuite('All ' . Inflector::humanize($manager->pluginTest) . ' Plugin Tests');
		} else {
			$test =& new TestSuite('All Core Tests');
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
 * @return mixed
 * @access public
 */
	function runTestCase($testCaseFile, &$reporter, $testing = false) {
		$manager =& new TestManager();

		$testCaseFileWithPath = $manager->_getTestsPath() . DS . $testCaseFile;

		if (!file_exists($testCaseFileWithPath)) {
			trigger_error("Test case {$testCaseFile} cannot be found", E_USER_ERROR);
			return false;
		}

		if ($testing) {
			return true;
		}

		$test =& new TestSuite("Individual test case: " . $testCaseFile);
		$test->addTestFile($testCaseFileWithPath);
		return $test->run($reporter);
	}

/**
 * Runs a specific group test file
 *
 * @param string $groupTestName GroupTest that you want to run.
 * @param Object $reporter Reporter instance to use with the group test being run.
 * @return mixed
 * @access public
 */
	function runGroupTest($groupTestName, &$reporter) {
		$manager =& new TestManager();
		$filePath = $manager->_getTestsPath('groups') . DS . strtolower($groupTestName) . $manager->_groupExtension;

		if (!file_exists($filePath)) {
			trigger_error("Group test {$groupTestName} cannot be found at {$filePath}", E_USER_ERROR);
		}

		require_once $filePath;
		$test =& new TestSuite($groupTestName . ' group test');
		foreach ($manager->_getGroupTestClassNames($filePath) as $groupTest) {
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
 */
	function addTestFile(&$groupTest, $file) {
		$manager =& new TestManager();

		if (file_exists($file.'.test.php')) {
			$file .= '.test.php';
		} elseif (file_exists($file.'.group.php')) {
			$file .= '.group.php';
		}
		$groupTest->addTestFile($file);
	}

/**
 * Returns a list of test cases found in the current valid test case path
 *
 * @access public
 */
	function &getTestCaseList() {
		$manager =& new TestManager();
		$return = $manager->_getTestCaseList($manager->_getTestsPath());
		return $return;
	}

/**
 * Builds the list of test cases from a given directory
 *
 * @access public
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
 * @access public
 */
	function &_getTestFileList($directory = '.') {
		$return = $this->_getRecursiveFileList($directory, array(&$this, '_isTestCaseFile'));
		return $return;
	}

/**
 * Returns a list of group tests found in the current valid test case path
 *
 * @access public
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
 * @access public
 */
	function &_getTestGroupFileList($directory = '.') {
		$return = $this->_getRecursiveFileList($directory, array(&$this, '_isTestGroupFile'));
		return $return;
	}

/**
 * Returns a list of group test files from a given directory
 *
 * @param string $directory The directory to get group tests from.
 * @access public
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
 * @access public
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
 * @access public
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
 * @return void
 * @access public
 */
	function _isTestCaseFile($file) {
		return $this->_hasExpectedExtension($file, $this->_testExtension);
	}

/**
 * Tests if a file has the correct group test extension
 *
 * @param string $file
 * @return void
 * @access public
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
 * @access public
 */
	function _hasExpectedExtension($file, $extension) {
		return $extension == strtolower(substr($file, (0 - strlen($extension))));
	}

/**
 * Returns the given path to the test files depending on a given type of tests (cases, group, ..)
 *
 * @param string $type either 'cases' or 'groups'
 * @return string The path tests are located on
 * @access public
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
		$manager =& new TestManager();
		if ($type == 'test') {
			return $manager->_testExtension;
		}
		return $manager->_groupExtension;
	}
}

/**
 * The CliTestManager ensures that the list of available files are printed in the correct cli format
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class CliTestManager extends TestManager {

/**
 * Prints the list of group tests in a cli friendly format
 *
 * @access public
 */
	function &getGroupTestList() {
		$manager =& new CliTestManager();
		$groupTests =& $manager->_getTestGroupList($manager->_getTestsPath('groups'));
		$buffer = "Available Group Test:\n";

		foreach ($groupTests as $groupTest) {
			$buffer .= "  " . $groupTest . "\n";
		}
		return $buffer . "\n";
	}

/**
 * Prints the list of test cases in a cli friendly format
 *
 * @access public
 */
	function &getTestCaseList() {
		$manager =& new CliTestManager();
		$testCases =& $manager->_getTestCaseList($manager->_getTestsPath());
		$buffer = "Available Test Cases:\n";

		foreach ($testCases as $testCaseFile => $testCase) {
			$buffer .= "  " . $testCaseFile . "\n";
		}
		return $buffer . "\n";
	}
}

/**
 * The TextTestManager ensures that the list of available tests is printed as a list of urls in a text-friendly format
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class TextTestManager extends TestManager {
	var $_url;

/**
 * Constructor
 *
 * @return void
 * @access public
 */
	function TextTestManager() {
		parent::TestManager();
		$this->_url = $_SERVER['PHP_SELF'];
	}

/**
 * Returns the base url
 *
 * @return void
 * @access public
 */
	function getBaseURL() {
		return $this->_url;
	}

/**
 * Returns a list of available group tests in a text-friendly format
 *
 * @access public
 */
	function &getGroupTestList() {
		$manager =& new TextTestManager();
		$groupTests =& $manager->_getTestGroupList($manager->_getTestsPath('groups'));

		$buffer = "Core Test Groups:\n";
		$urlExtra = '';
		if ($manager->appTest) {
			$buffer = "App Test Groups:\n";
			$urlExtra = '&app=true';
		} else if ($manager->pluginTest) {
			$buffer = Inflector::humanize($manager->pluginTest) . " Test Groups:\n";
			$urlExtra = '&plugin=' . $manager->pluginTest;
		}

		$buffer .= "All tests\n" . $_SERVER['SERVER_NAME'] . $manager->getBaseURL() . "?group=all&output=txt{$urlExtra}\n";

		foreach ((array)$groupTests as $groupTest) {
			$buffer .= $_SERVER['SERVER_NAME']. $manager->getBaseURL()."?group=" . $groupTest . "&output=txt{$urlExtra}"."\n";
		}

		return $buffer;
	}

/**
 * Returns a list of available test cases in a text-friendly format
 *
 * @access public
 */
	function &getTestCaseList() {
		$manager =& new TextTestManager();
		$testCases =& $manager->_getTestCaseList($manager->_getTestsPath());

		$buffer = "Core Test Cases:\n";
		$urlExtra = '';
		if ($manager->appTest) {
			$buffer = "App Test Cases:\n";
			$urlExtra = '&app=true';
		} else if ($manager->pluginTest) {
			$buffer = Inflector::humanize($manager->pluginTest) . " Test Cases:\n";
			$urlExtra = '&plugin=' . $manager->pluginTest;
		}

		if (1 > count($testCases)) {
			$buffer .= "EMPTY";
			return $buffer;
		}

		foreach ($testCases as $testCaseFile => $testCase) {
			$buffer .= $_SERVER['SERVER_NAME']. $manager->getBaseURL()."?case=" . $testCase . "&output=txt"."\n";
		}

		$buffer .= "\n";
		return $buffer;
	}
}

/**
 * The HtmlTestManager provides the foundation for the web-based CakePHP testsuite.
 * It prints the different lists of tests and provides the interface for CodeCoverage, etc.
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class HtmlTestManager extends TestManager {
	var $_url;

/**
 * Constructor
 *
 * @return void
 * @access public
 */
	function HtmlTestManager() {
		parent::TestManager();
		$this->_url = $_SERVER['PHP_SELF'];
	}

/**
 * Returns the current base url
 *
 * @return void
 * @access public
 */
	function getBaseURL() {
		return $this->_url;
	}

/**
 * Prints the links to the available group tests
 *
 * @access public
 */
	function &getGroupTestList() {
		$urlExtra = '';
		$manager =& new HtmlTestManager();
		$groupTests =& $manager->_getTestGroupList($manager->_getTestsPath('groups'));

		$buffer = "<h3>Core Test Groups:</h3>\n<ul>";
		$urlExtra = null;
		if ($manager->appTest) {
			$buffer = "<h3>App Test Groups:</h3>\n<ul>";
			$urlExtra = '&app=true';
		} else if ($manager->pluginTest) {
			$buffer = "<h3>" . Inflector::humanize($manager->pluginTest) . " Test Groups:</h3>\n<ul>";
			$urlExtra = '&plugin=' . $manager->pluginTest;
		}

		$buffer .= "<li><a href='" . $manager->getBaseURL() . "?group=all$urlExtra'>All tests</a></li>\n";

		foreach ($groupTests as $groupTest) {
			$buffer .= "<li><a href='" . $manager->getBaseURL() . "?group={$groupTest}" . "{$urlExtra}'>" . $groupTest . "</a></li>\n";
		}
		$buffer .= "</ul>\n";
		return $buffer;
	}

/**
 * Prints the links to the available test cases
 *
 * @access public
 */
	function &getTestCaseList() {
		$urlExtra = '';
		$manager =& new HtmlTestManager();
		$testCases =& $manager->_getTestCaseList($manager->_getTestsPath());

		$buffer = "<h3>Core Test Cases:</h3>\n<ul>";
		$urlExtra = null;
		if ($manager->appTest) {
			$buffer = "<h3>App Test Cases:</h3>\n<ul>";
			$urlExtra = '&app=true';
		} else if ($manager->pluginTest) {
			$buffer = "<h3>" . Inflector::humanize($manager->pluginTest) . " Test Cases:</h3>\n<ul>";
			$urlExtra = '&plugin=' . $manager->pluginTest;
		}

		if (1 > count($testCases)) {
			$buffer .= "<strong>EMPTY</strong>";
			return $buffer;
		}

		foreach ($testCases as $testCaseFile => $testCase) {
			$title = explode(strpos($testCase, '\\') ? '\\' : '/', str_replace('.test.php', '', $testCase));
			$title[count($title) - 1] = Inflector::camelize($title[count($title) - 1]);
			$title = implode(' / ', $title);

				$buffer .= "<li><a href='" . $manager->getBaseURL() . "?case=" . urlencode($testCase) . $urlExtra ."'>" . $title . "</a></li>\n";
		}
		$buffer .= "</ul>\n";
		return $buffer;
	}
}

if (function_exists('caketestsgetreporter')) {
	echo "You need a new test.php. \n";
	echo "Try this one: " . dirname(CONSOLE_LIBS) . "templates" . DS . "skel" . DS . "webroot" . DS . "test.php";
	exit();
} else {

/**
 * Returns an object of the currently needed reporter
 *
 * @access public
 */
	function &CakeTestsGetReporter() {
		static $Reporter = NULL;
		if (!$Reporter) {
			switch (CAKE_TEST_OUTPUT) {
				case CAKE_TEST_OUTPUT_HTML:
					require_once CAKE_TESTS_LIB . 'cake_reporter.php';
					$Reporter =& new CakeHtmlReporter();
					break;
				default:
					require_once CAKE_TESTS_LIB . 'cake_text_reporter.php';
					$Reporter =& new CakeTextReporter();
					break;
			}
		}
		return $Reporter;
	}

}
?>