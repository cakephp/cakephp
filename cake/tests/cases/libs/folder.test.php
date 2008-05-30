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
App::import('Core', 'Folder');
App::import('Core', 'File');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class FolderTest extends UnitTestCase {
	var $Folder = null;

	function testBasic() {
		$path = dirname(__FILE__);
		$Folder =& new Folder($path);

		$result = $Folder->pwd();
		$this->assertEqual($result, $path);

		$result = $Folder->addPathElement($path, 'test');
		$expected = $path . DS . 'test';
		$this->assertEqual($result, $expected);

		$result = $Folder->cd(ROOT);
		$expected = ROOT;
		$this->assertEqual($result, $expected);
	}

	function testInPath() {
		$path = dirname(dirname(__FILE__));
		$inside = dirname($path) . DS;

		$Folder =& new Folder($path);

		$result = $Folder->pwd();
		$this->assertEqual($result, $path);

		$result = $Folder->isSlashTerm($inside);
		$this->assertTrue($result);

		$result = $Folder->realpath('tests/');
		$this->assertEqual($result, $path . DS .'tests' . DS);

		$result = $Folder->inPath('tests' . DS);
		$this->assertTrue($result);

		$result = $Folder->inPath(DS . 'non-existing' . $inside);
		$this->assertFalse($result);
	}

	function testOperations() {
		$path = TEST_CAKE_CORE_INCLUDE_PATH.'console'.DS.'libs'.DS.'templates'.DS.'skel';
		$Folder =& new Folder($path);

		$result = is_dir($Folder->pwd());
		$this->assertTrue($result);

		$new = TMP . 'test_folder_new';
		$result = $Folder->create($new);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $Folder->copy($copy);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $Folder->cp($copy);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $Folder->chmod($copy, 0755, false);
		$this->assertTrue($result);

		$result = $Folder->cd($copy);
		$this->assertTrue($result);

		$mv = TMP . 'test_folder_mv';
		$result = $Folder->move($mv);
		$this->assertTrue($result);

		$mv = TMP . 'test_folder_mv_2';
		$result = $Folder->mv($mv);
		$this->assertTrue($result);

		$result = $Folder->delete($new);
		$this->assertTrue($result);

		$result = $Folder->delete($mv);
		$this->assertTrue($result);

		$result = $Folder->rm($mv);
		$this->assertTrue($result);

		$new = TMP . 'test_folder_new';
		$result = $Folder->create($new);
		$this->assertTrue($result);

		$result = $Folder->cd($new);
		$this->assertTrue($result);

		$result = $Folder->delete();
		$this->assertTrue($result);

		$Folder =& new Folder('non-existent');
		$result = $Folder->pwd();
		$this->assertNull($result);
	}

	function testRealPathForWebroot() {
		$Folder = new Folder('files/');
		$this->assertEqual(realpath('files/'), $Folder->path);
	}

	function testZeroAsDirectory() {
		$Folder =& new Folder(TMP);
		$new = TMP . '0';
		$result = $Folder->create($new);
		$this->assertTrue($result);

		$result = $Folder->read(true, true);
		$expected = array(array('0', 'cache', 'logs', 'sessions', 'tests'), array());
		$this->assertEqual($expected, $result);

		$result = $Folder->read(true, array('.', '..', 'logs', '.svn'));
		$expected = array(array('0', 'cache', 'sessions', 'tests'), array());
		$this->assertEqual($expected, $result);

		$result = $Folder->delete($new);
		$this->assertTrue($result);
	}

	function testFolderRead() {
		$Folder =& new Folder(TMP);
		$expected = array('cache', 'logs', 'sessions', 'tests');
		$results = $Folder->read(true, true);
		$this->assertEqual($results[0], $expected);
	}

	function testFolderTree() {
		$Folder =& new Folder();
		$expected = array(
			array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'config',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding'
			),
			array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'config.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'paths.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0080_00ff.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0100_017f.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0180_024F.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0250_02af.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0370_03ff.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0400_04ff.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0500_052f.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '0530_058f.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '1e00_1eff.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '1f00_1fff.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '2100_214f.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '2150_218f.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '2460_24ff.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '2c00_2c5f.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '2c60_2c7f.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . '2c80_2cff.php',
				TEST_CAKE_CORE_INCLUDE_PATH . 'config' . DS . 'unicode' .  DS . 'casefolding' . DS . 'ff00_ffef.php'
			)
		);

		$results = $Folder->tree(TEST_CAKE_CORE_INCLUDE_PATH . 'config', false);
		$this->assertEqual($results, $expected);
	}

	function testWindowsPath(){
		$Folder =& new Folder();
		$this->assertTrue($Folder->isWindowsPath('C:\cake'));
		$this->assertTrue($Folder->isWindowsPath('c:\cake'));
	}

	function testIsAbsolute(){
		$Folder =& new Folder();
		$this->assertTrue($Folder->isAbsolute('C:\cake'));
		$this->assertTrue($Folder->isAbsolute('/usr/local'));
		$this->assertFalse($Folder->isAbsolute('cake/'));
	}

	function testIsSlashTerm(){
		$Folder =& new Folder();
		$this->assertTrue($Folder->isSlashTerm('C:\cake\\'));
		$this->assertTrue($Folder->isSlashTerm('/usr/local/'));
		$this->assertFalse($Folder->isSlashTerm('cake'));
	}

	function testStatic() {
		$result = Folder::slashTerm('/path/to/file');
		$this->assertEqual($result, '/path/to/file/');
	}

	function testNormalizePath() {
		$path = '/path/to/file';
		$result = Folder::normalizePath($path);
		$this->assertEqual($result, '/');

		$path = '\path\to\file';
		$result = Folder::normalizePath($path);
		$this->assertEqual($result, '/');

		$path = 'C:\path\to\file';
		$result = Folder::normalizePath($path);
		$this->assertEqual($result, '\\');
	}

	function correctSlashFor() {
		$path = '/path/to/file';
		$result = Folder::correctSlashFor($path);
		$this->assertEqual($result, '/');

		$path = '\path\to\file';
		$result = Folder::correctSlashFor($path);
		$this->assertEqual($result, '/');

		$path = 'C:\path\to\file';
		$result = Folder::correctSlashFor($path);
		$this->assertEqual($result, '\\');
	}

	function testInCakePath() {
		$Folder =& new Folder();
		$Folder->cd(ROOT);
		$path = 'C:\path\to\file';
		$result = $Folder->inCakePath($path);
		$this->assertFalse($result);

		$path = ROOT;
		$Folder->cd(ROOT);
		$result = $Folder->inCakePath($path);
		$this->assertFalse($result);

// WHY DOES THIS FAIL ??
		$path = DS.'cake'.DS.'config';
		$Folder->cd(ROOT.DS.'cake'.DS.'config');
		$result = $Folder->inCakePath($path);
		$this->assertTrue($result);
	}

	function testFind() {
		$folder =& new Folder();
		$folder->cd(TEST_CAKE_CORE_INCLUDE_PATH . 'config');
		$result = $folder->find();
		$expected = array('config.php', 'paths.php');
		$this->assertIdentical($result, $expected);

		$result = $folder->find('.*\.php');
		$expected = array('config.php', 'paths.php');
		$this->assertIdentical($result, $expected);

		$result = $folder->find('.*ig\.php');
		$expected = array('config.php');
		$this->assertIdentical($result, $expected);

		$result = $folder->find('paths\.php');
		$expected = array('paths.php');
		$this->assertIdentical($result, $expected);

		$folder->mkdir($folder->pwd().DS.'testme');
		$folder->cd('testme');
		$result = $folder->find('paths\.php');
		$expected = array();
		$this->assertIdentical($result, $expected);

		$folder->cd($folder->pwd().'/..');
		$result = $folder->find('paths\.php');
		$expected = array('paths.php');
		$this->assertIdentical($result, $expected);
		$folder->delete($folder->pwd().DS.'testme');
	}

	function testFindRecursive() {
		$folder =& new Folder();
		$folder->cd(TEST_CAKE_CORE_INCLUDE_PATH);
		$result = $folder->findRecursive('(config|paths)\.php');
		$expected = array(
			TEST_CAKE_CORE_INCLUDE_PATH.'config'.DS.'config.php',
			TEST_CAKE_CORE_INCLUDE_PATH.'config'.DS.'paths.php'
		);
		$this->assertIdentical($result, $expected);

		$folder->cd('config');
		$folder->mkdir($folder->pwd().DS.'testme');
		$folder->cd('testme');
		$result = $folder->findRecursive('paths\.php');
		$expected = array();
		$this->assertIdentical($result, $expected);

		$file =& new File($folder->pwd().DS.'my.php');
		$file->create();
		$folder->cd($folder->pwd().'/../..');

		$result = $folder->findRecursive('(paths|my)\.php');
		$expected = array(
			TEST_CAKE_CORE_INCLUDE_PATH.'config'.DS.'paths.php',
			TEST_CAKE_CORE_INCLUDE_PATH.'config'.DS.'testme'.DS.'my.php'
		);
		$this->assertIdentical($result, $expected);

		$folder->cd(TEST_CAKE_CORE_INCLUDE_PATH.'config');
		$folder->delete($folder->pwd().DS.'testme');
	}

	function testConstructWithNonExistantPath() {
		$folder =& new Folder(TEST_CAKE_CORE_INCLUDE_PATH.'config_non_existant', true);
		$this->assertTrue(is_dir(TEST_CAKE_CORE_INCLUDE_PATH.'config_non_existant'));
		$folder->cd(TEST_CAKE_CORE_INCLUDE_PATH);
		$folder->delete($folder->pwd().'config_non_existant');
	}

	function testDirSize() {
		$folder =& new Folder(TEST_CAKE_CORE_INCLUDE_PATH.'config_non_existant', true);
		$this->assertEqual($folder->dirSize(), 0);

		$file =& new File($folder->pwd().DS.'my.php', true, 0777);
		$file->create();
		$file->write('something here');
		$file->close();
		$this->assertEqual($folder->dirSize(), 14);

		$folder->cd(TEST_CAKE_CORE_INCLUDE_PATH);
		$folder->delete($folder->pwd().'config_non_existant');
	}
}
?>