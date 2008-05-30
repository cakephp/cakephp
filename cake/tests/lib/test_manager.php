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
 * @subpackage		cake.cake.tests.lib
 * @since			CakePHP(tm) v 1.2.0.4433
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
define ('CORE_TEST_CASES', dirname(dirname(__FILE__)) . DS . 'cases');
define ('CORE_TEST_GROUPS', dirname(dirname(__FILE__)) . DS . 'groups');
define ('APP_TEST_CASES', APP . 'tests' .DS. 'cases');
define ('APP_TEST_GROUPS', APP . 'tests' .DS. 'groups');
/**
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class TestManager {
	var $_testExtension = '.test.php';
	var $_groupExtension = '.group.php';
	var $appTest = false;
	var $pluginTest = false;

	function TestManager() {
		$this->_installSimpleTest();
		if (isset($_GET['app'])) {
			$this->appTest = true;
		}
		if (isset($_GET['plugin'])) {
			$this->pluginTest = $_GET['plugin'];
		}
	}

	function _installSimpleTest() {
		App::import('Vendor', array('simpletest'.DS.'unit_tester', 'simpletest'.DS.'mock_objects', 'simpletest'.DS.'web_tester'));
		require_once(CAKE_TESTS_LIB . 'cake_web_test_case.php');
		require_once(CAKE_TESTS_LIB . 'cake_test_case.php');
	}

	function runAllTests(&$reporter) {
		$manager =& new TestManager();

		$testCases =& $manager->_getTestFileList($manager->_getTestsPath());

		if ($manager->appTest) {
			$test =& new GroupTest('All App Tests');
		} else if ($manager->pluginTest) {
			$test =& new GroupTest('All ' . Inflector::humanize($manager->pluginTest) . ' Plugin Tests');
		} else {
			$test =& new GroupTest('All Core Tests');
		}

		foreach ($testCases as $testCase) {
			$test->addTestFile($testCase);
		}
		return $test->run($reporter);
	}

	function runTestCase($testCaseFile, &$reporter) {
		$manager =& new TestManager();

		$testCaseFileWithPath = $manager->_getTestsPath() . DS . $testCaseFile;
		if (! file_exists($testCaseFileWithPath)) {
			trigger_error("Test case {$testCaseFile} cannot be found", E_USER_ERROR);
		}
		$test =& new GroupTest("Individual test case: " . $testCaseFile);
		$test->addTestFile($testCaseFileWithPath);
		return $test->run($reporter);
	}

	function runGroupTest($groupTestName, &$reporter) {
		$manager =& new TestManager();
		$filePath = $manager->_getTestsPath('groups') . DS . strtolower($groupTestName) . $manager->_groupExtension;

		if (! file_exists($filePath)) {
			trigger_error("Group test {$groupTestName} cannot be found at {$filePath}", E_USER_ERROR);
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

	function addTestCasesFromDirectory(&$groupTest, $directory = '.') {
		$manager =& new TestManager();
		$testCases =& $manager->_getTestFileList($directory);
		foreach ($testCases as $testCase) {
			$groupTest->addTestFile($testCase);
		}
	}

	function addTestFile(&$groupTest, $file, $isGroupTest = false) {
		$manager =& new TestManager();
		
		if (!$isGroupTest) {
			$file .= '.test.php';
		} else {
			$file .= '.group.php';
		}
		$groupTest->addTestFile($file);
	}

	function &getTestCaseList() {
		$manager =& new TestManager();
		$return = $manager->_getTestCaseList($manager->_getTestsPath());
		return $return;
	}

	function &_getTestCaseList($directory = '.') {
		$fileList =& $this->_getTestFileList($directory);
		$testCases = array();
		foreach ($fileList as $testCaseFile) {
			$testCases[$testCaseFile] = str_replace($directory . DS, '', $testCaseFile);
		}
		return $testCases;
	}

	function &_getTestFileList($directory = '.') {
		$return = $this->_getRecursiveFileList($directory, array(&$this, '_isTestCaseFile'));
		return $return;
	}

	function &getGroupTestList() {
		$manager =& new TestManager();
		$return = $manager->_getTestGroupList($manager->_getTestsPath('groups'));
		return $return;
	}

	function &_getTestGroupFileList($directory = '.') {
		$return = $this->_getRecursiveFileList($directory, array(&$this, '_isTestGroupFile'));
		return $return;
	}

	function &_getTestGroupList($directory = '.') {
		$fileList =& $this->_getTestGroupFileList($directory);
		$groupTests = array();

		foreach ($fileList as $groupTestFile) {
			$groupTests[$groupTestFile] = str_replace($this->_groupExtension, '', basename($groupTestFile));
		}
		sort($groupTests);
		return $groupTests;
	}

	function &_getGroupTestClassNames($groupTestFile) {
		$file = implode("\n", file($groupTestFile));
		preg_match("~lass\s+?(.*)\s+?extends GroupTest~", $file, $matches);
		if (! empty($matches)) {
			unset($matches[0]);
			return $matches;
		} else {
			return array();
		}
	}

	function &_getRecursiveFileList($directory = '.', $fileTestFunction) {
		$fileList = array();
		if (!is_dir($directory)) {
			return $fileList;
		}
		$dh = opendir($directory);
		if (! is_resource($dh)) {
			trigger_error("Couldn't open {$directory}", E_USER_ERROR);
		}

		while ($file = readdir($dh)) {
			$filePath = $directory . DIRECTORY_SEPARATOR . $file;
			if (0 === strpos($file, '.')) {
				continue;
			}

			if (is_dir($filePath)) {
				$fileList = array_merge($fileList, $this->_getRecursiveFileList($filePath, $fileTestFunction));
			}
			if ($fileTestFunction[0]->$fileTestFunction[1]($file)) {
				$fileList[] = $filePath;
			}
		}
		closedir($dh);
		return $fileList;
	}

	function _isTestCaseFile($file) {
		return $this->_hasExpectedExtension($file, $this->_testExtension);
	}

	function _isTestGroupFile($file) {
		return $this->_hasExpectedExtension($file, $this->_groupExtension);
	}

	function _hasExpectedExtension($file, $extension) {
		return $extension == strtolower(substr($file, (0 - strlen($extension))));
	}

	function _getTestsPath($type = 'cases') {
		if (!empty($this->appTest)) {
			if ($type == 'cases') {
				$result = APP_TEST_CASES;
			} else if ($type == 'groups') {
				$result = APP_TEST_GROUPS;
			}
		} else if (!empty($this->pluginTest)) {
			$_pluginBasePath = APP . 'plugins' . DS . $this->pluginTest . DS . 'tests';
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
}
/**
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class CliTestManager extends TestManager {

	function &getGroupTestList() {
		$manager =& new CliTestManager();
		$groupTests =& $manager->_getTestGroupList($manager->_getTestsPath('groups'));
		$buffer = "Available Group Test:\n";

		foreach ($groupTests as $groupTest) {
			$buffer .= "  " . $groupTest . "\n";
		}
		return $buffer . "\n";
	}

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
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class TextTestManager extends TestManager {
	var $_url;

	function TextTestManager() {
		parent::TestManager();
		$this->_url = $_SERVER['PHP_SELF'];
	}

	function getBaseURL() {
		return $this->_url;
	}

	function &getGroupTestList() {
		$manager =& new TextTestManager();
		$groupTests =& $manager->_getTestGroupList($manager->_getTestsPath('groups'));

		$buffer = "Core Test Groups:\n";
		$urlExtra = null;
		if ($manager->appTest) {
			$buffer = "App Test Groups:\n";
			$urlExtra = '&app=true';
		} else if ($manager->pluginTest) {
			$buffer = Inflector::humanize($manager->pluginTest) . " Test Groups:\n";
			$urlExtra = '&plugin=' . $manager->pluginTest;
		}

		$buffer .=  "All tests\n" . $_SERVER['SERVER_NAME'] . $manager->getBaseURL() . "?group=all&output=txt{$urlExtra}\n";

		foreach ((array)$groupTests as $groupTest) {
			$buffer .= $_SERVER['SERVER_NAME']. $manager->getBaseURL()."?group=" . $groupTest . "&output=txt{$urlExtra}"."\n";
		}

		return $buffer;
	}

	function &getTestCaseList() {
		$manager =& new TextTestManager();
		$testCases =& $manager->_getTestCaseList($manager->_getTestsPath());

		$buffer = "Core Test Cases:\n";
		$urlExtra = null;
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
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class HtmlTestManager extends TestManager {
	var $_url;

	function HtmlTestManager() {
		parent::TestManager();
		$this->_url = $_SERVER['PHP_SELF'];
	}

	function getBaseURL() {
		return $this->_url;
	}

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

		foreach ((array)$groupTests as $groupTest) {
			$buffer .= "<li><a href='" . $manager->getBaseURL() . "?group={$groupTest}" . "{$urlExtra}'>" . $groupTest . "</a></li>\n";
		}
		$buffer  .=  "</ul>\n";
		return $buffer;
	}

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
			$buffer .= "<li><a href='" . $manager->getBaseURL() . "?case=" . urlencode($testCase) . $urlExtra ."'>" . $testCase . "</a></li>\n";
		}
		$buffer  .=  "</ul>\n";
		return $buffer;
	}
}
if (function_exists('caketestsgetreporter')) {
	echo "You need a new test.php. \n";
	echo "Try this one: " . CONSOLE_LIBS . "templates" . DS . "skel" . DS . "webroot" . DS . "test.php";
	exit();
} else {
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
				echo "<p><a href='" . RUN_TEST_LINK . $show . "'>Run more tests</a> | <a href='" . RUN_TEST_LINK . $query . "&show_passes=1'>Show Passes</a> | \n";
			break;
		}
	}

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
				echo " <a href='" . RUN_TEST_LINK . $query . "'>Analyze Code Coverage</a></p>\n";
			break;
		}
	}

	function CakePHPTestCaseList() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				echo HtmlTestManager::getTestCaseList();
			break;
			case CAKE_TEST_OUTPUT_TEXT:
			default:
				echo TextTestManager::getTestCaseList();
			break;
		}
	}

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

	function CakePHPTestHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
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

	function CakePHPTestSuiteHeader() {
		switch (CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				$groups = $_SERVER['PHP_SELF'].'?show=groups';
				$cases = $_SERVER['PHP_SELF'].'?show=cases';
				$plugins = Configure::listObjects('plugin');
				include CAKE_TESTS_LIB . 'content.php';
			break;
		}
	}

	function CakePHPTestSuiteFooter() {
		switch ( CAKE_TEST_OUTPUT) {
			case CAKE_TEST_OUTPUT_HTML:
				$baseUrl = BASE;
				include CAKE_TESTS_LIB . 'footer.php';
			break;
		}
	}
}
?>