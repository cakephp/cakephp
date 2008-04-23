<?php
/* SVN FILE: $Id: http_socket.test.php 6563 2008-03-12 21:19:31Z phpnut $ */
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
 * @version			$Revision: 6563 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2008-03-12 22:19:31 +0100 (Wed, 12 Mar 2008) $
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'CodeCoverageManager');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class CodeCoverageManagerTest extends UnitTestCase {
/**
 * Test that invalid supplied files will raise an error; test if random library files can be analyzed without errors
 *
 */
	function testNoTestCaseSupplied() {
		CodeCoverageManager::start(substr(md5(microtime()), 0, 5), CakeTestsGetReporter());
		CodeCoverageManager::report(false);
		$this->assertError();

		CodeCoverageManager::start('libs/code_coverage_manager.test.php', CakeTestsGetReporter());
		CodeCoverageManager::report(false);
		$this->assertError();
		
		App::import('Core', 'Folder');
		$folder = new Folder();
		$folder->cd(ROOT.DS.LIBS);
		$contents = $folder->ls();
		function remove($var) {
 		    return ($var != 'code_coverage_manager.test.php');
		}
		$contents[1] = array_filter($contents[1], "remove");
		$keys = array_rand($contents[1], 5);

		foreach ($keys as $key) {
			CodeCoverageManager::start('libs'.DS.$contents[1][$key], CakeTestsGetReporter());
			CodeCoverageManager::report(false);
			$this->assertNoErrors();
		}
	}
	
	function testGetTestObjectFileNameFromTestCaseFile() {
		$manager = CodeCoverageManager::getInstance();

		$expected = $manager->_testObjectFileFromCaseFile('models/some_file.test.php', true);
		$this->assertIdentical(APP.'models'.DS.'some_file.php', $expected);
		
		$expected = $manager->_testObjectFileFromCaseFile('controllers/some_file.test.php', true);
		$this->assertIdentical(APP.'controllers'.DS.'some_file.php', $expected);
		
		$expected = $manager->_testObjectFileFromCaseFile('views/some_file.test.php', true);
		$this->assertIdentical(APP.'views'.DS.'some_file.php', $expected);
		
		$expected = $manager->_testObjectFileFromCaseFile('behaviors/some_file.test.php', true);
		$this->assertIdentical(APP.'models'.DS.'behaviors'.DS.'some_file.php', $expected);
		
		$expected = $manager->_testObjectFileFromCaseFile('components/some_file.test.php', true);
		$this->assertIdentical(APP.'controllers'.DS.'components'.DS.'some_file.php', $expected);
		
		$expected = $manager->_testObjectFileFromCaseFile('helpers/some_file.test.php', true);
		$this->assertIdentical(APP.'views'.DS.'helpers'.DS.'some_file.php', $expected);
	}
	
	function testGetExecutableLines() {
		$manager = CodeCoverageManager::getInstance();
		$code = <<<HTML
			\$manager = CodeCoverageManager::getInstance();
HTML;
		$result = $manager->_getExecutableLines($code);
		foreach ($result as $line) {
			$this->assertNotIdentical($line, '');
		}
		
		$code = <<<HTML
		function testGettestObjectFileNameFromTestCaseFileName() {
		function testGettestObjectFileNameFromTestCaseFileName() 
		{
		}
		// test comment here
		/* some comment here */
		/*
		*
		* multiline comment here
		*/
		<?php?>
		?>
		<?
HTML;
		$result = $manager->_getExecutableLines($code);
		foreach ($result as $line) {
			$this->assertIdentical(trim($line), '');
		}
	}
}
?>