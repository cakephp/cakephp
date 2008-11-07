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
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'TestManager');
class TestManagerTest extends CakeTestCase {
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->Sut =& new TestManager();
		$this->Reporter =& new CakeHtmlReporter();
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testRunAllTests() {
		$folder =& new Folder($this->Sut->_getTestsPath());
		$extension = str_replace('.', '\.', TestManager::getExtension('test'));
		$out = $folder->findRecursive('.*' . $extension);

		$reporter =& new CakeHtmlReporter();
		$list = TestManager::runAllTests($reporter, true);

		$this->assertEqual(count($out), count($list));
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testRunTestCase() {
		$file = md5(time());
		$result = $this->Sut->runTestCase($file, $this->Reporter);
		$this->assertError('Test case ' . $file . ' cannot be found');
		$this->assertFalse($result);

		$file = str_replace(CORE_TEST_CASES, '', __FILE__);
		$result = $this->Sut->runTestCase($file, $this->Reporter, true);
		$this->assertTrue($result);
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testRunGroupTest() {

	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testAddTestCasesFromDirectory() {

	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testAddTestFile() {

	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testGetTestCaseList() {

	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testGetGroupTestList() {

	}
}
?>