<?php
/**
 * FolderTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * FolderTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class FolderTest extends CakeTestCase {

	protected static $_tmp = array();

/**
 * Save the directory names in TMP and make sure default directories exist
 *
 * @return void
 */
	public static function setUpBeforeClass() {
		$dirs = array('cache', 'logs', 'sessions', 'tests');
		foreach ($dirs as $dir) {
			new Folder(TMP . $dir, true);
		}

		foreach (scandir(TMP) as $file) {
			if (is_dir(TMP . $file) && !in_array($file, array('.', '..'))) {
				self::$_tmp[] = $file;
			}
		}
	}

/**
 * setUp clearstatcache() to flush file descriptors.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		clearstatcache();
	}

/**
 * Restore the TMP directory to its original state.
 *
 * @return void
 */
	public function tearDown() {
		$exclude = array_merge(self::$_tmp, array('.', '..'));
		foreach (scandir(TMP) as $dir) {
			if (is_dir(TMP . $dir) && !in_array($dir, $exclude)) {
				$iterator = new RecursiveDirectoryIterator(TMP . $dir);
				foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
					if ($file->isFile() || $file->isLink()) {
						unlink($file->getPathname());
					} elseif ($file->isDir() && !in_array($file->getFilename(), array('.', '..'))) {
						rmdir($file->getPathname());
					}
				}
				rmdir(TMP . $dir);
			}
		}
		parent::tearDown();
	}

/**
 * testBasic method
 *
 * @return void
 */
	public function testBasic() {
		$path = dirname(__FILE__);
		$Folder = new Folder($path);

		$result = $Folder->pwd();
		$this->assertEquals($path, $result);

		$result = Folder::addPathElement($path, 'test');
		$expected = $path . DS . 'test';
		$this->assertEquals($expected, $result);

		$result = $Folder->cd(ROOT);
		$expected = ROOT;
		$this->assertEquals($expected, $result);

		$result = $Folder->cd(ROOT . DS . 'non-existent');
		$this->assertFalse($result);
	}

/**
 * testInPath method
 *
 * @return void
 */
	public function testInPath() {
		$path = dirname(dirname(__FILE__));
		$inside = dirname($path) . DS;

		$Folder = new Folder($path);

		$result = $Folder->pwd();
		$this->assertEquals($path, $result);

		$result = Folder::isSlashTerm($inside);
		$this->assertTrue($result);

		$result = $Folder->realpath('Test/');
		$this->assertEquals($path . DS . 'Test' . DS, $result);

		$result = $Folder->inPath('Test' . DS);
		$this->assertTrue($result);

		$result = $Folder->inPath(DS . 'non-existing' . $inside);
		$this->assertFalse($result);

		$result = $Folder->inPath($path . DS . 'Model', true);
		$this->assertTrue($result);
	}

/**
 * test creation of single and multiple paths.
 *
 * @return void
 */
	public function testCreation() {
		$Folder = new Folder(TMP . 'tests');
		$result = $Folder->create(TMP . 'tests' . DS . 'first' . DS . 'second' . DS . 'third');
		$this->assertTrue($result);

		rmdir(TMP . 'tests' . DS . 'first' . DS . 'second' . DS . 'third');
		rmdir(TMP . 'tests' . DS . 'first' . DS . 'second');
		rmdir(TMP . 'tests' . DS . 'first');

		$Folder = new Folder(TMP . 'tests');
		$result = $Folder->create(TMP . 'tests' . DS . 'first');
		$this->assertTrue($result);
		rmdir(TMP . 'tests' . DS . 'first');
	}

/**
 * test that creation of folders with trailing ds works
 *
 * @return void
 */
	public function testCreateWithTrailingDs() {
		$Folder = new Folder(TMP);
		$path = TMP . 'tests' . DS . 'trailing' . DS . 'dir' . DS;
		$result = $Folder->create($path);
		$this->assertTrue($result);

		$this->assertTrue(is_dir($path), 'Folder was not made');

		$Folder = new Folder(TMP . 'tests' . DS . 'trailing');
		$this->assertTrue($Folder->delete());
	}

/**
 * test recursive directory create failure.
 *
 * @return void
 */
	public function testRecursiveCreateFailure() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Cant perform operations using permissions on windows.');

		$path = TMP . 'tests' . DS . 'one';
		mkdir($path);
		chmod($path, '0444');

		try {
			$Folder = new Folder($path);
			$result = $Folder->create($path . DS . 'two' . DS . 'three');
			$this->assertFalse($result);
		} catch (PHPUnit_Framework_Error $e) {
			$this->assertTrue(true);
		}

		chmod($path, '0777');
		rmdir($path);
	}

/**
 * testOperations method
 *
 * @return void
 */
	public function testOperations() {
		$path = CAKE . 'Console' . DS . 'Templates' . DS . 'skel';
		$Folder = new Folder($path);

		$result = is_dir($Folder->pwd());
		$this->assertTrue($result);

		$new = TMP . 'test_folder_new';
		$result = $Folder->create($new);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $Folder->copy($copy);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $Folder->copy($copy);
		$this->assertTrue($result);

		$copy = TMP . 'test_folder_copy';
		$result = $Folder->chmod($copy, 0755, false);
		$this->assertTrue($result);

		$result = $Folder->cd($copy);
		$this->assertTrue((bool)$result);

		$mv = TMP . 'test_folder_mv';
		$result = $Folder->move($mv);
		$this->assertTrue($result);

		$mv = TMP . 'test_folder_mv_2';
		$result = $Folder->move($mv);
		$this->assertTrue($result);

		$result = $Folder->delete($new);
		$this->assertTrue($result);

		$result = $Folder->delete($mv);
		$this->assertTrue($result);

		$result = $Folder->delete($mv);
		$this->assertTrue($result);

		$new = APP . 'index.php';
		$result = $Folder->create($new);
		$this->assertFalse($result);

		$expected = $new . ' is a file';
		$result = $Folder->errors();
		$this->assertEquals($expected, $result[0]);

		$new = TMP . 'test_folder_new';
		$result = $Folder->create($new);
		$this->assertTrue($result);

		$result = $Folder->cd($new);
		$this->assertTrue((bool)$result);

		$result = $Folder->delete();
		$this->assertTrue($result);

		$Folder = new Folder('non-existent');
		$result = $Folder->pwd();
		$this->assertNull($result);
	}

/**
 * testChmod method
 *
 * @return void
 */
	public function testChmod() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Folder permissions tests not supported on Windows.');

		$path = TMP;
		$Folder = new Folder($path);

		$subdir = 'test_folder_new';
		$new = TMP . $subdir;

		$this->assertTrue($Folder->create($new));
		$this->assertTrue($Folder->create($new . DS . 'test1'));
		$this->assertTrue($Folder->create($new . DS . 'test2'));

		$filePath = $new . DS . 'test1.php';
		$File = new File($filePath);
		$this->assertTrue($File->create());

		$filePath = $new . DS . 'skip_me.php';
		$File = new File($filePath);
		$this->assertTrue($File->create());

		$this->assertTrue($Folder->chmod($new, 0755, true));
		$perms = substr(sprintf('%o', fileperms($new . DS . 'test2')), -4);
		$this->assertEquals('0755', $perms);

		$this->assertTrue($Folder->chmod($new, 0744, true, array('skip_me.php', 'test2')));

		$perms = substr(sprintf('%o', fileperms($new . DS . 'test2')), -4);
		$this->assertEquals('0755', $perms);

		$perms = substr(sprintf('%o', fileperms($new . DS . 'test1')), -4);
		$this->assertEquals('0744', $perms);

		$Folder->delete($new);
	}

/**
 * testRealPathForWebroot method
 *
 * @return void
 */
	public function testRealPathForWebroot() {
		$Folder = new Folder('files/');
		$this->assertEquals(realpath('files/'), $Folder->path);
	}

/**
 * testZeroAsDirectory method
 *
 * @return void
 */
	public function testZeroAsDirectory() {
		$Folder = new Folder(TMP);
		$new = TMP . '0';
		$this->assertTrue($Folder->create($new));

		$result = $Folder->read(true, true);
		$expected = array('0', 'cache', 'logs', 'sessions', 'tests');
		$this->assertEquals($expected, $result[0]);

		$result = $Folder->read(true, array('logs'));
		$expected = array('0', 'cache', 'sessions', 'tests');
		$this->assertEquals($expected, $result[0]);

		$result = $Folder->delete($new);
		$this->assertTrue($result);
	}

/**
 * test Adding path elements to a path
 *
 * @return void
 */
	public function testAddPathElement() {
		$result = Folder::addPathElement(DS . 'some' . DS . 'dir', 'another_path');
		$this->assertEquals(DS . 'some' . DS . 'dir' . DS . 'another_path', $result);

		$result = Folder::addPathElement(DS . 'some' . DS . 'dir' . DS, 'another_path');
		$this->assertEquals(DS . 'some' . DS . 'dir' . DS . 'another_path', $result);
	}

/**
 * testFolderRead method
 *
 * @return void
 */
	public function testFolderRead() {
		$Folder = new Folder(TMP);

		$expected = array('cache', 'logs', 'sessions', 'tests');
		$result = $Folder->read(true, true);
		$this->assertEquals($expected, $result[0]);

		$Folder->path = TMP . 'non-existent';
		$expected = array(array(), array());
		$result = $Folder->read(true, true);
		$this->assertEquals($expected, $result);
	}

/**
 * testFolderReadWithHiddenFiles method
 *
 * @return void
 */
	public function testFolderReadWithHiddenFiles() {
		$this->skipIf(!is_writable(TMP), 'Cant test Folder::read with hidden files unless the tmp folder is writable.');

		$Folder = new Folder(TMP . 'folder_tree_hidden', true, 0777);
		mkdir($Folder->path . DS . '.svn');
		mkdir($Folder->path . DS . 'some_folder');
		touch($Folder->path . DS . 'not_hidden.txt');
		touch($Folder->path . DS . '.hidden.txt');

		$expected = array(
			array('some_folder'),
			array('not_hidden.txt'),
		);
		$result = $Folder->read(true, true);
		$this->assertEquals($expected, $result);

		$expected = array(
			array(
				'.svn',
				'some_folder'
			),
			array(
				'.hidden.txt',
				'not_hidden.txt'
			),
		);
		$result = $Folder->read(true);
		$this->assertEquals($expected, $result);
	}

/**
 * testFolderTree method
 *
 * @return void
 */
	public function testFolderTree() {
		$Folder = new Folder();
		$expected = array(
			array(
				CAKE . 'Config',
				CAKE . 'Config' . DS . 'unicode',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding'
			),
			array(
				CAKE . 'Config' . DS . 'config.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0080_00ff.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0100_017f.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0180_024F.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0250_02af.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0370_03ff.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0400_04ff.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0500_052f.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '0530_058f.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '1e00_1eff.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '1f00_1fff.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '2100_214f.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '2150_218f.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '2460_24ff.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '2c00_2c5f.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '2c60_2c7f.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . '2c80_2cff.php',
				CAKE . 'Config' . DS . 'unicode' . DS . 'casefolding' . DS . 'ff00_ffef.php'
			)
		);

		$result = $Folder->tree(CAKE . 'Config', false);
		$this->assertSame(array(), array_diff($expected[0], $result[0]));
		$this->assertSame(array(), array_diff($result[0], $expected[0]));

		$result = $Folder->tree(CAKE . 'Config', false, 'dir');
		$this->assertSame(array(), array_diff($expected[0], $result));
		$this->assertSame(array(), array_diff($expected[0], $result));

		$result = $Folder->tree(CAKE . 'Config', false, 'files');
		$this->assertSame(array(), array_diff($expected[1], $result));
		$this->assertSame(array(), array_diff($expected[1], $result));
	}

/**
 * testFolderTreeWithHiddenFiles method
 *
 * @return void
 */
	public function testFolderTreeWithHiddenFiles() {
		$this->skipIf(!is_writable(TMP), 'Can\'t test Folder::tree with hidden files unless the tmp folder is writable.');

		$Folder = new Folder(TMP . 'folder_tree_hidden', true, 0777);
		mkdir($Folder->path . DS . '.svn', 0777, true);
		touch($Folder->path . DS . '.svn' . DS . 'InHiddenFolder.php');
		mkdir($Folder->path . DS . '.svn' . DS . 'inhiddenfolder');
		touch($Folder->path . DS . '.svn' . DS . 'inhiddenfolder' . DS . 'NestedInHiddenFolder.php');
		touch($Folder->path . DS . 'not_hidden.txt');
		touch($Folder->path . DS . '.hidden.txt');
		mkdir($Folder->path . DS . 'visible_folder' . DS . '.git', 0777, true);

		$expected = array(
			array(
				$Folder->path,
				$Folder->path . DS . 'visible_folder',
			),
			array(
				$Folder->path . DS . 'not_hidden.txt',
			),
		);

		$result = $Folder->tree(null, true);
		$this->assertEquals($expected, $result);

		$result = $Folder->tree(null, array('.'));
		$this->assertEquals($expected, $result);

		$expected = array(
			array(
				$Folder->path,
				$Folder->path . DS . 'visible_folder',
				$Folder->path . DS . 'visible_folder' . DS . '.git',
				$Folder->path . DS . '.svn',
				$Folder->path . DS . '.svn' . DS . 'inhiddenfolder',
			),
			array(
				$Folder->path . DS . 'not_hidden.txt',
				$Folder->path . DS . '.hidden.txt',
				$Folder->path . DS . '.svn' . DS . 'inhiddenfolder' . DS . 'NestedInHiddenFolder.php',
				$Folder->path . DS . '.svn' . DS . 'InHiddenFolder.php',
			),
		);

		$result = $Folder->tree(null, false);
		sort($result[0]);
		sort($expected[0]);
		sort($result[1]);
		sort($expected[1]);
		$this->assertEquals($expected, $result);

		$Folder->delete();
	}

/**
 * testWindowsPath method
 *
 * @return void
 */
	public function testWindowsPath() {
		$this->assertFalse(Folder::isWindowsPath('0:\\cake\\is\\awesome'));
		$this->assertTrue(Folder::isWindowsPath('C:\\cake\\is\\awesome'));
		$this->assertTrue(Folder::isWindowsPath('d:\\cake\\is\\awesome'));
		$this->assertTrue(Folder::isWindowsPath('\\\\vmware-host\\Shared Folders\\file'));
	}

/**
 * testIsAbsolute method
 *
 * @return void
 */
	public function testIsAbsolute() {
		$this->assertFalse(Folder::isAbsolute('path/to/file'));
		$this->assertFalse(Folder::isAbsolute('cake/'));
		$this->assertFalse(Folder::isAbsolute('path\\to\\file'));
		$this->assertFalse(Folder::isAbsolute('0:\\path\\to\\file'));
		$this->assertFalse(Folder::isAbsolute('\\path/to/file'));
		$this->assertFalse(Folder::isAbsolute('\\path\\to\\file'));

		$this->assertTrue(Folder::isAbsolute('/usr/local'));
		$this->assertTrue(Folder::isAbsolute('//path/to/file'));
		$this->assertTrue(Folder::isAbsolute('C:\\cake'));
		$this->assertTrue(Folder::isAbsolute('C:\\path\\to\\file'));
		$this->assertTrue(Folder::isAbsolute('d:\\path\\to\\file'));
		$this->assertTrue(Folder::isAbsolute('\\\\vmware-host\\Shared Folders\\file'));
	}

/**
 * testIsSlashTerm method
 *
 * @return void
 */
	public function testIsSlashTerm() {
		$this->assertFalse(Folder::isSlashTerm('cake'));

		$this->assertTrue(Folder::isSlashTerm('C:\\cake\\'));
		$this->assertTrue(Folder::isSlashTerm('/usr/local/'));
	}

/**
 * testStatic method
 *
 * @return void
 */
	public function testSlashTerm() {
		$result = Folder::slashTerm('/path/to/file');
		$this->assertEquals('/path/to/file/', $result);
	}

/**
 * testNormalizePath method
 *
 * @return void
 */
	public function testNormalizePath() {
		$path = '/path/to/file';
		$result = Folder::normalizePath($path);
		$this->assertEquals('/', $result);

		$path = '\\path\\\to\\\file';
		$result = Folder::normalizePath($path);
		$this->assertEquals('/', $result);

		$path = 'C:\\path\\to\\file';
		$result = Folder::normalizePath($path);
		$this->assertEquals('\\', $result);
	}

/**
 * correctSlashFor method
 *
 * @return void
 */
	public function testCorrectSlashFor() {
		$path = '/path/to/file';
		$result = Folder::correctSlashFor($path);
		$this->assertEquals('/', $result);

		$path = '\\path\\to\\file';
		$result = Folder::correctSlashFor($path);
		$this->assertEquals('/', $result);

		$path = 'C:\\path\to\\file';
		$result = Folder::correctSlashFor($path);
		$this->assertEquals('\\', $result);
	}

/**
 * testInCakePath method
 *
 * @return void
 */
	public function testInCakePath() {
		$Folder = new Folder();
		$Folder->cd(ROOT);
		$path = 'C:\\path\\to\\file';
		$result = $Folder->inCakePath($path);
		$this->assertFalse($result);

		$path = ROOT;
		$Folder->cd(ROOT);
		$result = $Folder->inCakePath($path);
		$this->assertFalse($result);

		$path = DS . 'lib' . DS . 'Cake' . DS . 'Config';
		$Folder->cd(ROOT . DS . 'lib' . DS . 'Cake' . DS . 'Config');
		$result = $Folder->inCakePath($path);
		$this->assertTrue($result);
	}

/**
 * testFind method
 *
 * @return void
 */
	public function testFind() {
		$Folder = new Folder();
		$Folder->cd(CAKE . 'Config');
		$result = $Folder->find();
		$expected = array('config.php');
		$this->assertSame(array_diff($expected, $result), array());
		$this->assertSame(array_diff($expected, $result), array());

		$result = $Folder->find('.*', true);
		$expected = array('cacert.pem', 'config.php', 'routes.php');
		$this->assertSame($expected, $result);

		$result = $Folder->find('.*\.php');
		$expected = array('config.php');
		$this->assertSame(array_diff($expected, $result), array());
		$this->assertSame(array_diff($expected, $result), array());

		$result = $Folder->find('.*\.php', true);
		$expected = array('config.php', 'routes.php');
		$this->assertSame($expected, $result);

		$result = $Folder->find('.*ig\.php');
		$expected = array('config.php');
		$this->assertSame($expected, $result);

		$result = $Folder->find('config\.php');
		$expected = array('config.php');
		$this->assertSame($expected, $result);

		$Folder->cd(TMP);
		$File = new File($Folder->pwd() . DS . 'paths.php', true);
		$Folder->create($Folder->pwd() . DS . 'testme');
		$Folder->cd('testme');
		$result = $Folder->find('paths\.php');
		$expected = array();
		$this->assertSame($expected, $result);

		$Folder->cd($Folder->pwd() . '/..');
		$result = $Folder->find('paths\.php');
		$expected = array('paths.php');
		$this->assertSame($expected, $result);

		$Folder->cd(TMP);
		$Folder->delete($Folder->pwd() . DS . 'testme');
		$File->delete();
	}

/**
 * testFindRecursive method
 *
 * @return void
 */
	public function testFindRecursive() {
		$Folder = new Folder();
		$Folder->cd(CAKE);
		$result = $Folder->findRecursive('(config|paths)\.php');
		$expected = array(
			CAKE . 'Config' . DS . 'config.php'
		);
		$this->assertSame(array_diff($expected, $result), array());
		$this->assertSame(array_diff($expected, $result), array());

		$result = $Folder->findRecursive('(config|paths)\.php', true);
		$expected = array(
			CAKE . 'Config' . DS . 'config.php'
		);
		$this->assertSame($expected, $result);

		$Folder->cd(TMP);
		$Folder->create($Folder->pwd() . DS . 'testme');
		$Folder->cd('testme');
		$File = new File($Folder->pwd() . DS . 'paths.php');
		$File->create();
		$Folder->cd(TMP . 'sessions');
		$result = $Folder->findRecursive('paths\.php');
		$expected = array();
		$this->assertSame($expected, $result);

		$Folder->cd(TMP . 'testme');
		$File = new File($Folder->pwd() . DS . 'my.php');
		$File->create();
		$Folder->cd($Folder->pwd() . '/../..');

		$result = $Folder->findRecursive('(paths|my)\.php');
		$expected = array(
			TMP . 'testme' . DS . 'my.php',
			TMP . 'testme' . DS . 'paths.php'
		);
		$this->assertSame(array_diff($expected, $result), array());
		$this->assertSame(array_diff($expected, $result), array());

		$result = $Folder->findRecursive('(paths|my)\.php', true);
		$expected = array(
			TMP . 'testme' . DS . 'my.php',
			TMP . 'testme' . DS . 'paths.php'
		);
		$this->assertSame($expected, $result);

		$Folder->cd(CAKE . 'Config');
		$Folder->cd(TMP);
		$Folder->delete($Folder->pwd() . DS . 'testme');
		$File->delete();
	}

/**
 * testConstructWithNonExistentPath method
 *
 * @return void
 */
	public function testConstructWithNonExistentPath() {
		$Folder = new Folder(TMP . 'config_non_existent', true);
		$this->assertTrue(is_dir(TMP . 'config_non_existent'));
		$Folder->cd(TMP);
		$Folder->delete($Folder->pwd() . 'config_non_existent');
	}

/**
 * testDirSize method
 *
 * @return void
 */
	public function testDirSize() {
		$Folder = new Folder(TMP . 'config_non_existent', true);
		$this->assertEquals(0, $Folder->dirSize());

		$File = new File($Folder->pwd() . DS . 'my.php', true, 0777);
		$File->create();
		$File->write('something here');
		$File->close();
		$this->assertEquals(14, $Folder->dirSize());

		$Folder->cd(TMP);
		$Folder->delete($Folder->pwd() . 'config_non_existent');
	}

/**
 * test that errors and messages can be resetted
 *
 * @return void
 */
	public function testReset() {
		$path = TMP . 'folder_delete_test';
		mkdir($path);
		$folder = $path . DS . 'sub';
		mkdir($folder);
		$file = $folder . DS . 'file';
		touch($file);

		chmod($folder, 0555);
		chmod($file, 0444);

		$Folder = new Folder($folder);
		$return = $Folder->delete();
		$this->assertFalse($return);

		$messages = $Folder->messages();
		$errors = $Folder->errors();
		$expected = array(
			$file . ' NOT removed',
			$folder . ' NOT removed',
		);
		sort($expected);
		sort($errors);
		$this->assertEmpty($messages);
		$this->assertEquals($expected, $errors);

		chmod($file, 0644);
		chmod($folder, 0755);

		$return = $Folder->delete();
		$this->assertTrue($return);

		$messages = $Folder->messages();
		$errors = $Folder->errors();
		$expected = array(
			$file . ' removed',
			$folder . ' removed',
		);
		sort($expected);
		sort($messages);
		$this->assertEmpty($errors);
		$this->assertEquals($expected, $messages);
	}

/**
 * testDelete method
 *
 * @return void
 */
	public function testDelete() {
		$path = TMP . 'folder_delete_test';
		mkdir($path);
		touch($path . DS . 'file_1');
		mkdir($path . DS . 'level_1_1');
		touch($path . DS . 'level_1_1' . DS . 'file_1_1');
		mkdir($path . DS . 'level_1_1' . DS . 'level_2_1');
		touch($path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_1');
		touch($path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_2');
		mkdir($path . DS . 'level_1_1' . DS . 'level_2_2');

		$Folder = new Folder($path, true);
		$return = $Folder->delete();
		$this->assertTrue($return);

		$messages = $Folder->messages();
		$errors = $Folder->errors();
		$this->assertEquals(array(), $errors);

		$expected = array(
			$path . DS . 'file_1 removed',
			$path . DS . 'level_1_1' . DS . 'file_1_1 removed',
			$path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_1 removed',
			$path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_2 removed',
			$path . DS . 'level_1_1' . DS . 'level_2_1 removed',
			$path . DS . 'level_1_1' . DS . 'level_2_2 removed',
			$path . DS . 'level_1_1 removed',
			$path . ' removed'
		);
		sort($expected);
		sort($messages);
		$this->assertEquals($expected, $messages);
	}

/**
 * testCopy method
 *
 * Verify that subdirectories existing in both destination and source directory
 * are merged recursively.
 *
 * @return void
 */
	public function testCopy() {
		extract($this->_setupFilesystem());

		$Folder = new Folder($folderOne);
		$result = $Folder->copy($folderThree);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

		$Folder = new Folder($folderTwo);
		$result = $Folder->copy($folderThree);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * testCopyWithMerge method
 *
 * Verify that subdirectories existing in both destination and source directory
 * are merged recursively.
 *
 * @return void
 */
	public function testCopyWithMerge() {
		extract($this->_setupFilesystem());

		$Folder = new Folder($folderOne);
		$result = $Folder->copy($folderThree);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

		$Folder = new Folder($folderTwo);
		$result = $Folder->copy(array('to' => $folderThree, 'scheme' => Folder::MERGE));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * testCopyWithSkip method
 *
 * Verify that directories and files are copied recursively
 * even if the destination directory already exists.
 * Subdirectories existing in both destination and source directory
 * are skipped and not merged or overwritten.
 *
 * @return void
 */
	public function testCopyWithSkip() {
		extract($this->_setupFilesystem());

		$Folder = new Folder($folderOne);
		$result = $Folder->copy(array('to' => $folderTwo, 'scheme' => Folder::SKIP));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));

		$Folder = new Folder($folderTwo);
		$Folder->delete();

		$Folder = new Folder($folderOne);
		$result = $Folder->copy(array('to' => $folderTwo, 'scheme' => Folder::SKIP));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));

		$Folder = new Folder($folderTwo);
		$Folder->delete();

		new Folder($folderTwo, true);
		new Folder($folderTwo . DS . 'folderB', true);
		file_put_contents($folderTwo . DS . 'file2.php', 'touched');
		file_put_contents($folderTwo . DS . 'folderB' . DS . 'fileB.php', 'untouched');

		$Folder = new Folder($folderTwo);
		$result = $Folder->copy(array('to' => $folderThree, 'scheme' => Folder::SKIP));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
		$this->assertEquals('touched', file_get_contents($folderThree . DS . 'file2.php'));
		$this->assertEquals('untouched', file_get_contents($folderThree . DS . 'folderB' . DS . 'fileB.php'));

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * testCopyWithOverwrite
 *
 * Verify that subdirectories existing in both destination and source directory
 * are overwritten/replaced recursively.
 *
 * @return void
 */
	public function testCopyWithOverwrite() {
		extract($this->_setupFilesystem());

		$Folder = new Folder($folderOne);
		$result = $Folder->copy(array('to' => $folderThree, 'scheme' => Folder::OVERWRITE));

		$this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

		$Folder = new Folder($folderTwo);
		$result = $Folder->copy(array('to' => $folderThree, 'scheme' => Folder::OVERWRITE));
		$this->assertTrue($result);

		$this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

		$Folder = new Folder($folderOne);
		unlink($fileOneA);
		$result = $Folder->copy(array('to' => $folderThree, 'scheme' => Folder::OVERWRITE));
		$this->assertTrue($result);

		$this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
		$this->assertTrue(!file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
		$this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * Setup filesystem for copy tests
 * $path: folder_test/
 * - folder1/file1.php
 * - folder1/folderA/fileA.php
 * - folder2/file2.php
 * - folder2/folderB/fileB.php
 * - folder3/
 *
 * @return array Filenames to extract in the test methods
 */
	protected function _setupFilesystem() {
		$path = TMP . 'folder_test';

		$folderOne = $path . DS . 'folder1';
		$folderOneA = $folderOne . DS . 'folderA';
		$folderTwo = $path . DS . 'folder2';
		$folderTwoB = $folderTwo . DS . 'folderB';
		$folderThree = $path . DS . 'folder3';

		$fileOne = $folderOne . DS . 'file1.php';
		$fileTwo = $folderTwo . DS . 'file2.php';
		$fileOneA = $folderOneA . DS . 'fileA.php';
		$fileTwoB = $folderTwoB . DS . 'fileB.php';

		new Folder($path, true);
		new Folder($folderOne, true);
		new Folder($folderOneA, true);
		new Folder($folderTwo, true);
		new Folder($folderTwoB, true);
		new Folder($folderThree, true);
		touch($fileOne);
		touch($fileTwo);
		touch($fileOneA);
		touch($fileTwoB);

		return compact(
			'path',
			'folderOne', 'folderOneA', 'folderTwo', 'folderTwoB', 'folderThree',
			'fileOne', 'fileOneA', 'fileTwo', 'fileTwoB');
	}

/**
 * testMove method
 *
 * Verify that directories and files are moved recursively
 * even if the destination directory already exists.
 * Subdirectories existing in both destination and source directory
 * are merged recursively.
 *
 * @return void
 */
	public function testMove() {
		extract($this->_setupFilesystem());

		$Folder = new Folder($folderOne);
		$result = $Folder->move($folderTwo);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertTrue(is_dir($folderTwo . DS . 'folderB'));
		$this->assertTrue(file_exists($folderTwo . DS . 'folderB' . DS . 'fileB.php'));
		$this->assertFalse(file_exists($fileOne));
		$this->assertTrue(file_exists($folderTwo . DS . 'folderA'));
		$this->assertFalse(file_exists($folderOneA));
		$this->assertFalse(file_exists($fileOneA));

		$Folder = new Folder($folderTwo);
		$Folder->delete();

		new Folder($folderOne, true);
		new Folder($folderOneA, true);
		touch($fileOne);
		touch($fileOneA);

		$Folder = new Folder($folderOne);
		$result = $Folder->move($folderTwo);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertTrue(is_dir($folderTwo . DS . 'folderA'));
		$this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));
		$this->assertFalse(file_exists($fileOne));
		$this->assertFalse(file_exists($folderOneA));
		$this->assertFalse(file_exists($fileOneA));

		$Folder = new Folder($folderTwo);
		$Folder->delete();

		new Folder($folderOne, true);
		new Folder($folderOneA, true);
		new Folder($folderTwo, true);
		new Folder($folderTwoB, true);
		touch($fileOne);
		touch($fileOneA);
		new Folder($folderOne . DS . 'folderB', true);
		touch($folderOne . DS . 'folderB' . DS . 'fileB.php');
		file_put_contents($folderTwoB . DS . 'fileB.php', 'untouched');

		$Folder = new Folder($folderOne);
		$result = $Folder->move($folderTwo);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertEquals('', file_get_contents($folderTwoB . DS . 'fileB.php'));
		$this->assertFalse(file_exists($fileOne));
		$this->assertFalse(file_exists($folderOneA));
		$this->assertFalse(file_exists($fileOneA));

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * testMoveWithSkip method
 *
 * Verify that directories and files are moved recursively
 * even if the destination directory already exists.
 * Subdirectories existing in both destination and source directory
 * are skipped and not merged or overwritten.
 *
 * @return void
 */
	public function testMoveWithSkip() {
		extract($this->_setupFilesystem());

		$Folder = new Folder($folderOne);
		$result = $Folder->move(array('to' => $folderTwo, 'scheme' => Folder::SKIP));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertTrue(is_dir($folderTwo . DS . 'folderB'));
		$this->assertTrue(file_exists($folderTwoB . DS . 'fileB.php'));
		$this->assertFalse(file_exists($fileOne));
		$this->assertFalse(file_exists($folderOneA));
		$this->assertFalse(file_exists($fileOneA));

		$Folder = new Folder($folderTwo);
		$Folder->delete();

		new Folder($folderOne, true);
		new Folder($folderOneA, true);
		new Folder($folderTwo, true);
		touch($fileOne);
		touch($fileOneA);

		$Folder = new Folder($folderOne);
		$result = $Folder->move(array('to' => $folderTwo, 'scheme' => Folder::SKIP));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertTrue(is_dir($folderTwo . DS . 'folderA'));
		$this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));
		$this->assertFalse(file_exists($fileOne));
		$this->assertFalse(file_exists($folderOneA));
		$this->assertFalse(file_exists($fileOneA));

		$Folder = new Folder($folderTwo);
		$Folder->delete();

		new Folder($folderOne, true);
		new Folder($folderOneA, true);
		new Folder($folderTwo, true);
		new Folder($folderTwoB, true);
		touch($fileOne);
		touch($fileOneA);
		file_put_contents($folderTwoB . DS . 'fileB.php', 'untouched');

		$Folder = new Folder($folderOne);
		$result = $Folder->move(array('to' => $folderTwo, 'scheme' => Folder::SKIP));
		$this->assertTrue($result);
		$this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
		$this->assertEquals('untouched', file_get_contents($folderTwoB . DS . 'fileB.php'));
		$this->assertFalse(file_exists($fileOne));
		$this->assertFalse(file_exists($folderOneA));
		$this->assertFalse(file_exists($fileOneA));

		$Folder = new Folder($path);
		$Folder->delete();
	}

}
