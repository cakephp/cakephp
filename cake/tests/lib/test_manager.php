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
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
define('CORE_TEST_CASES', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'cases');
define('CORE_TEST_GROUPS', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'groups');
define('APP_TEST_CASES', TESTS . 'cases');
define('APP_TEST_GROUPS', TESTS . 'groups');
/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class TestManager {
	var $_testExtension = '.test.php';
	var $_groupExtension = '.group.php';
	var $appTest = false;
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
 * @param string $reporter
 * @return void
 * @access public
 */
	function runAllTests(&$reporter, $testing = false) {
		$manager =& new TestManager();

		$testCases =& $manager->_getTestFileList($manager->_getTestsPath());
		if ($manager->appTest) {
			$test =& new GroupTest('All App Tests');
		} else if ($manager->pluginTest) {
			$test =& new GroupTest('All ' . Inflector::humanize($manager->pluginTest) . ' Plugin Tests');
		} else {
			$test =& new GroupTest('All Core Tests');
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
 * @param string $testCaseFile
 * @param string $reporter
 * @return void
 * @access public
 */
	function runTestCase($testCaseFile, &$reporter, $testing = false) {
		$manager =& new TestManager();

		$testCaseFileWithPath = $manager->_getTestsPath() . DS . $testCaseFile;

		if (!file_exists($testCaseFileWithPath) || strpos($testCaseFileWithPath, '..')) {
			trigger_error(
				sprintf("Test case %s cannot be found", htmlentities($testCaseFile)),
				E_USER_ERROR
			);
			return false;
		}

		if ($testing) {
			return true;
		}

		$test =& new GroupTest("Individual test case: " . $testCaseFile);
		$test->addTestFile($testCaseFileWithPath);
		return $test->run($reporter);
	}
/**
 * Runs a specific group test file
 *
 * @param string $groupTestName
 * @param string $reporter
 * @return void
 * @access public
 */
	function runGroupTest($groupTestName, &$reporter) {
		$manager =& new TestManager();
		$filePath = $manager->_getTestsPath('groups') . DS . strtolower($groupTestName) . $manager->_groupExtension;

		if (!file_exists($filePath) || strpos($filePath, '..')) {
			trigger_error(
				sprintf("Group test %s cannot be found at %s", htmlentities($groupTestName), htmlentities($filePath)),
				E_USER_ERROR
			);
		}

		require_once $filePath;
		$test =& new GroupTest($groupTestName . ' group test');
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
 * @param string $groupTest
 * @param string $directory
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
 * @param string $groupTest
 * @param string $file
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
 * @access public
 */
	function &_getTestGroupFileList($directory = '.') {
		$return = $this->_getRecursiveFileList($directory, array(&$this, '_isTestGroupFile'));
		return $return;
	}
/**
 * Returns a list of group test files from a given directory
 *
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
 * @access public
 */
	function &_getGroupTestClassNames($groupTestFile) {
		$file = implode("\n", file($groupTestFile));
		preg_match("~lass\s+?(.*)\s+?extends GroupTest~", $file, $matches);
		if (!empty($matches)) {
			unset($matches[0]);
			return $matches;
		}
		return array();
	}
/**
 * Gets a recursive list of files from a given directory and matches then against
 * a given fileTestFunction, like isTestCaseFile()
 *
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
 * @param string $type
 * @return void
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
			$pluginPaths = Configure::read('pluginPaths');
			foreach ($pluginPaths as $path) {
				if (file_exists($path . $this->pluginTest . DS . 'tests')) {
					$_pluginBasePath = $path . $this->pluginTest . DS . 'tests';
					break;
				}
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
 * undocumented function
 *
 * @param string $type
 * @return void
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
	echo "Try this one: " . CONSOLE_LIBS . "templates" . DS . "skel" . DS . "webroot" . DS . "test.php";
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
					$Reporter =& new TextReporter();
					break;
			}
		}
		return $Reporter;
	}
/**
 * Provides the "Run More" links in the testsuite interface
 *
 * @return void
 * @access public
 */
	function CakePHPTestRunMore() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				if (isset($_GET['group'])) {
					if (isset($_GET['app'])) {
						$show = '?show=groups&amp;app=true';
					} else if (isset($_GET['plugin'])) {
						$show = '?show=groups&amp;plugin=' . $_GET['plugin'];
					} else {
						$show = '?show=groups';
					}
					$query = '?group='.$_GET['group'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				}
				if (isset($_GET['case'])) {
					if (isset($_GET['app'])) {
						$show = '?show=cases&amp;app=true';
					} else if (isset($_GET['plugin'])) {
						$show = '?show=cases&amp;plugin=' . $_GET['plugin'];
					} else {
						$show = '?show=cases';
					}
					$query = '?case='.$_GET['case'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				}
				ob_start();
				echo "<p><a href='" . RUN_TEST_LINK . $show . "'>Run more tests</a> | <a href='" . RUN_TEST_LINK . $query . "&show_passes=1'>Show Passes</a> | \n";

				break;
		}
	}
/**
 * Provides the links to analyzing code coverage
 *
 * @return void
 * @access public
 */
	function CakePHPTestAnalyzeCodeCoverage() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				if (isset($_GET['case'])) {
					$query = '?case='.$_GET['case'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				} else {
					$query = '?group='.$_GET['group'];
					if (isset($_GET['app'])) {
						$query .= '&amp;app=true';
					} elseif (isset($_GET['plugin'])) {
						$query .= '&amp;plugin=' . $_GET['plugin'];
					}
				}
				$query .= '&amp;code_coverage=true';
				ob_start();
				echo " <a href='" . RUN_TEST_LINK . $query . "'>Analyze Code Coverage</a></p>\n";

				break;
		}
	}
/**
 * Prints a list of test cases
 *
 * @return void
 * @access public
 */
	function CakePHPTestCaseList() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				echo HtmlTestManager::getTestCaseList();
				break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				echo TextTestManager::getTestCaseList();
				break;
		}
	}
/**
 * Prints a list of group tests
 *
 * @return void
 * @access public
 */
	function CakePHPTestGroupTestList() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				echo HtmlTestManager::getGroupTestList();
				break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				echo TextTestManager::getGroupTestList();
				break;
		}
	}
/**
 * Includes the Testsuite Header
 *
 * @return void
 * @access public
 */
	function CakePHPTestHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				if (!class_exists('dispatcher')) {
					require CAKE . 'dispatcher.php';
				}
				$dispatch =& new Dispatcher();
				$dispatch->baseUrl();
				define('BASE', $dispatch->webroot);
				$baseUrl = BASE;
				$characterSet = 'charset=utf-8';
				include CAKE_TESTS_LIB . 'header.php';

				break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				header('content-type: text/plain');
				break;
		}
	}
/**
 * Provides the left hand navigation for the testsuite
 *
 * @return void
 * @access public
 */
	function CakePHPTestSuiteHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				$groups = $_SERVER['PHP_SELF'].'?show=groups';
				$cases = $_SERVER['PHP_SELF'].'?show=cases';
				$plugins = Configure::listObjects('plugin');
				include CAKE_TESTS_LIB . 'content.php';
				break;
		}
	}
/**
 * Provides the testsuite footer text
 *
 * @return void
 * @access public
 */
	function CakePHPTestSuiteFooter() {
		switch ( CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				ob_start();
				$baseUrl = BASE;
				include CAKE_TESTS_LIB . 'footer.php';
				break;
		}
	}
}
?>