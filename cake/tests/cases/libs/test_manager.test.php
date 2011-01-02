<?php
/**
 * TestManagerTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class TestTestManager extends TestManager {

	public function setTestSuite($testSuite) {
		$this->_testSuite = $testSuite;
	}
}

/**
 * TestManagerTest class
 *
 * @package       cake.tests.cases.libs
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
		parent::setUp();
		$this->_countFiles = 0;
		$this->TestManager = new TestTestManager();
		$this->testSuiteStub = $this->getMock('CakeTestSuite');

		$this->testSuiteStub
			->expects($this->any())
			->method('addTestFile')
			->will($this->returnCallback(array(&$this, '_countIncludedTestFiles')));

		$this->testSuiteStub
			->expects($this->any())
			->method('addTestSuite')
			->will($this->returnCallback(array(&$this, '_countIncludedTestFiles')));

		$this->TestManager->setTestSuite($this->testSuiteStub);
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

	protected function _getAllTestFiles($directory = CORE_TEST_CASES, $type = 'test') {
		$folder = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
		$extension = str_replace('.', '\.', $this->TestManager->getExtension($type));
		$out = new RegexIterator($folder, '#^.+'.$extension.'$#i', RecursiveRegexIterator::GET_MATCH);

		$files = array();
		foreach ($out as $testFile) {
			$files[] = $testFile[0];
		}
		return $files;
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
		$file = 'libs/test_manager.test.php';
		$result = $this->TestManager->runTestCase($file, $this->Reporter, true);
		$this->assertEquals(1, $this->_countFiles);
		$this->assertInstanceOf('PHPUnit_Framework_TestResult', $result);
	}

}
