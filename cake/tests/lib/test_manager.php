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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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
	var $usersAppTest = false;

	function TestManager() {
		$this->_installSimpleTest();
		if (isset($_GET['app'])) {
			$this->usersAppTest = true;
		}
	}

	function _installSimpleTest() {
		vendor('simpletest'.DS.'unit_tester', 'simpletest'.DS.'web_tester', 'simpletest'.DS.'mock_objects');
		require_once(LIB_TESTS . 'cake_web_test_case.php');
		require_once(LIB_TESTS . 'cake_test_case.php');
	}

	function runAllTests(&$reporter) {
		$manager =& new TestManager();

		if (!empty($manager->usersAppTest)) {
			$testCasePath = APP_TEST_CASES;
		} else {
			$testCasePath = CORE_TEST_CASES;
		}
		$testCases =& $manager->_getTestFileList($testCasePath);
		$test =& new GroupTest('All Core Tests');

		if (isset($_GET['app'])) {
			$test =& new GroupTest('All App Tests');
		} else {
			$test =& new GroupTest('All Core Tests');
		}

		foreach ($testCases as $testCase) {
			$test->addTestFile($testCase);
		}
		$test->run($reporter);
	}

	function runTestCase($testCaseFile, &$reporter) {
		$manager =& new TestManager();

		if (!empty($manager->usersAppTest)) {
			$testCaseFileWithPath = APP_TEST_CASES . DIRECTORY_SEPARATOR . $testCaseFile;
		} else {
			$testCaseFileWithPath = CORE_TEST_CASES . DIRECTORY_SEPARATOR . $testCaseFile;
		}
		if (! file_exists($testCaseFileWithPath)) {
			trigger_error("Test case {$testCaseFile} cannot be found", E_USER_ERROR);
		}
		$test =& new GroupTest("Individual test case: " . $testCaseFile);
		$test->addTestFile($testCaseFileWithPath);
		$test->run($reporter);
	}

	function runGroupTest($groupTestName, $groupTestDirectory, &$reporter) {
		$manager =& new TestManager();
		$filePath = $groupTestDirectory . DIRECTORY_SEPARATOR .
		strtolower($groupTestName) . $manager->_groupExtension;

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
		$test->run($reporter);
	}

	function addTestCasesFromDirectory(&$groupTest, $directory = '.') {
		$manager =& new TestManager();
		$testCases =& $manager->_getTestFileList($directory);
		foreach ($testCases as $testCase) {
			$groupTest->addTestFile($testCase);
		}
	}

	function addTestFile(&$groupTest, $file) {
		$manager =& new TestManager();
		$groupTest->addTestFile($file.'.test.php');
	}

	function &getTestCaseList($directory = '.') {
		$manager =& new TestManager();
		$return = $manager->_getTestCaseList($directory);
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

	function &getGroupTestList($directory = '.') {
		$manager =& new TestManager();
		$return = $manager->_getTestGroupList($directory);
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
		$dh = opendir($directory);
		if (! is_resource($dh)) {
			trigger_error("Couldn't open {$directory}", E_USER_ERROR);
		}

		$fileList = array();
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
}
/**
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class CliTestManager extends TestManager {

	function &getGroupTestList($directory = '.') {
		$manager =& new CliTestManager();
		$groupTests =& $manager->_getTestGroupList($directory);
		$buffer = "Available Group Test:\n";

		foreach ($groupTests as $groupTest) {
			$buffer .= "  " . $groupTest . "\n";
		}
		return $buffer . "\n";
	}

	function &getTestCaseList($directory = '.') {
		$manager =& new CliTestManager();
		$testCases =& $manager->_getTestCaseList($directory);
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
		$this->_url = $_SERVER['PHP_SELF'];
	}

	function getBaseURL() {
		return $this->_url;
	}

	function &getGroupTestList($directory = '.') {
		$manager =& new TextTestManager();
		$groupTests =& $manager->_getTestGroupList($directory);

		if (1 > count($groupTests)) {
      $noGroups = "No test groups set up!\n";
      return $noGroups;
		}
		$buffer = "Available test groups:\n";
		$buffer .=  $manager->getBaseURL() . "?group=all All tests<\n";

		foreach ($groupTests as $groupTest) {
			$buffer .= "<li><a href='" . $manager->getBaseURL() . "?group={$groupTest}'>" . $groupTest . "&output=txt"."</a></li>\n";
		}
		return $buffer . "</ul>\n";
	}

	function &getTestCaseList($directory = '.') {
		$manager =& new TextTestManager();
		$testCases =& $manager->_getTestCaseList($directory);

		if (1 > count($testCases)) {
			$noTestCases = "No test cases set up!";
			return $noTestCases;
		}
		$buffer = "Available test cases:\n";

		foreach ($testCases as $testCaseFile => $testCase) {
			$buffer .= $_SERVER['SERVER_NAME']. $manager->getBaseURL()."?case=" . $testCase . "&output=txt"."\n";
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
class HtmlTestManager extends TestManager {
	var $_url;

	function HtmlTestManager() {
		$this->_url = $_SERVER['PHP_SELF'];
	}

	function getBaseURL() {
		return $this->_url;
	}

	function &getGroupTestList($directory = '.') {
		$userApp = '';
		if (isset($_GET['app'])) {
			$userApp = '&amp;app=true';
		}
		$manager =& new HtmlTestManager();
		$groupTests =& $manager->_getTestGroupList($directory);

		if (1 > count($groupTests)) {
			$noGroupTests = "<h3>No test cases set up!</h3>";
			return $noGroupTests;
		}

		if (isset($_GET['app'])) {
			$buffer = "<h3>Available App Test Groups:</h3>\n<ul>";
		} else {
			$buffer = "<h3>Available Core Test Groups:</h3>\n<ul>";
		}
		$buffer .= "<li><a href='" . $manager->getBaseURL() . "?group=all$userApp'>All tests</a></li>\n";

		foreach ($groupTests as $groupTest) {
			$buffer .= "<li><a href='" . $manager->getBaseURL() . "?group={$groupTest}" . "{$userApp}'>" . $groupTest . "</a></li>\n";
		}
		$buffer  .=  "</ul>\n";
		return $buffer;
	}

	function &getTestCaseList($directory = '.') {
		$userApp = '';
		if (isset($_GET['app'])) {
			$userApp = '&amp;app=true';
		}
		$manager =& new HtmlTestManager();
		$testCases =& $manager->_getTestCaseList($directory);

		if (1 > count($testCases)) {
			$noTestCases = "<h3>No test cases set up!</h3>";
			return $noTestCases;
		}
		if (isset($_GET['app'])) {
			$buffer = "<h3>Available App Test Cases:</h3>\n<ul>";
		} else {
			$buffer = "<h3>Available Core Test Cases:</h3>\n<ul>";
		}
		foreach ($testCases as $testCaseFile => $testCase) {
			$buffer .= "<li><a href='" . $manager->getBaseURL() . "?case=" . urlencode($testCase) . $userApp ."'>" . $testCase . "</a></li>\n";
		}
		$buffer  .=  "</ul>\n";
		return $buffer;
	}
}
?>