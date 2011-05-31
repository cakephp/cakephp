<?php
/**
 * TestManagerTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * TestManagerTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class TestManagerTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->TestManager =& new TestManager();
		$this->Reporter =& new CakeHtmlReporter();
	}

/**
 * testRunAllTests method
 *
 * @return void
 * @access public
 */
	function testRunAllTests() {
		$folder =& new Folder($this->TestManager->_getTestsPath());
		$extension = str_replace('.', '\.', $this->TestManager->getExtension('test'));
		$out = $folder->findRecursive('.*' . $extension);

		$reporter =& new CakeHtmlReporter();
		$list = $this->TestManager->runAllTests($reporter, true);

		$this->assertEqual(count($out), count($list));
	}

/**
 * testRunTestCase method
 *
 * @return void
 * @access public
 */
	function testRunTestCase() {
		$file = md5(time());
		$result = $this->TestManager->runTestCase($file, $this->Reporter);
		$this->assertError('Test case ' . $file . ' cannot be found');
		$this->assertFalse($result);

		$file = str_replace(CORE_TEST_CASES, '', __FILE__);
		$result = $this->TestManager->runTestCase($file, $this->Reporter, true);
		$this->assertTrue($result);
	}

/**
 * testRunGroupTest method
 *
 * @return void
 * @access public
 */
	function testRunGroupTest() {
	}

/**
 * testAddTestCasesFromDirectory method
 *
 * @return void
 * @access public
 */
	function testAddTestCasesFromDirectory() {
	}

/**
 * testAddTestFile method
 *
 * @return void
 * @access public
 */
	function testAddTestFile() {
	}

/**
 * testGetTestCaseList method
 *
 * @return void
 * @access public
 */
	function testGetTestCaseList() {
	}

/**
 * testGetGroupTestList method
 *
 * @return void
 * @access public
 */
	function testGetGroupTestList() {
	}
}
