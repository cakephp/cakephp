<?php
/**
 * TestManagerTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

class TestTestManager extends TestManager {

	public function setTestSuite($testSuite) {
		$this->_testSuite = $testSuite;
	}
}

/**
 * TestManagerTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class TestManagerTest extends CakeTestCase {

/**
 * Number of times the funcion PHPUnit_Framework_TestSuite::addTestFile() has been called
 *
 * @var integer
 */
	protected $_countFiles = 0;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$this->_countFiles = 0;
		$this->TestManager = new TestTestManager();
		$testSuiteStub = $this->getMock('PHPUnit_Framework_TestSuite');
		$testSuiteStub
			->expects($this->any())
			->method('addTestFile')
			->will($this->returnCallback(array(&$this, '_countIncludedTestFiles')));
		$this->TestManager->setTestSuite($testSuiteStub);
		$this->Reporter = $this->getMock('CakeHtmlReporter');
	}

/**
 * Helper method to count the number of times the
 * function PHPUnit_Framework_TestSuite::addTestFile() has been called
 * @return void
 */
	public function _countIncludedTestFiles() {
		$this->_countFiles++;
	}

/**
 * testRunAllTests method
 *
 * @return void
 */
	public function testRunAllTests() {
		$folder = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CORE_TEST_CASES));
		$extension = str_replace('.', '\.', $this->TestManager->getExtension('test'));
		$out = new RegexIterator($folder, '#^.+'.$extension.'$#i', RecursiveRegexIterator::GET_MATCH);

		$files = array();
		foreach ($out as $testFile) {
			$files[] = $testFile[0];
		}

		$result = $this->TestManager->runAllTests($this->Reporter, true);

		$this->assertEqual(count($files), $this->_countFiles);
		$this->assertType('PHPUnit_Framework_TestResult', $result);
	}

/**
* Tests that trying to run an unexistent file throws an exception
* @expectedException InvalidArgumentException
*/
	public function testRunUnexistentCase() {
		$file = md5(time());
		$result = $this->TestManager->runTestCase($file, $this->Reporter);
	}

/**
 * testRunTestCase method
 *
 * @return void
 */
	public function testRunTestCase() {
		$file = str_replace(CORE_TEST_CASES, '', __FILE__);
		$result = $this->TestManager->runTestCase($file, $this->Reporter, true);
		$this->assertEqual(1, $this->_countFiles);
		$this->assertType('PHPUnit_Framework_TestResult', $result);
	}

/**
 * testRunGroupTest method
 *
 * @return void
 */
	public function testRunGroupTest() {
	}

/**
 * testAddTestCasesFromDirectory method
 *
 * @return void
 */
	public function testAddTestCasesFromDirectory() {
	}

/**
 * testAddTestFile method
 *
 * @return void
 */
	public function testAddTestFile() {
	}

/**
 * testGetTestCaseList method
 *
 * @return void
 */
	public function testGetTestCaseList() {
	}

/**
 * testGetGroupTestList method
 *
 * @return void
 */
	public function testGetGroupTestList() {
	}
}
?>