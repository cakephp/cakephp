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
uses('file');

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class FileTest extends UnitTestCase {

	var $File = null;

	function testBasic() {
		$file = __FILE__;
		$this->File =& new File($file);

		$result = $this->File->pwd();
		$expecting = $file;
		$this->assertEqual($result, $expecting);

		$result = $this->File->name;
		$expecting = basename(__FILE__);
		$this->assertEqual($result, $expecting);

		$result = $this->File->info();
		$expecting = array('dirname'=> dirname(__FILE__), 'basename'=> basename(__FILE__), 'extension'=> 'php', 'filename'=>'file.test');
		$this->assertEqual($result, $expecting);

		$result = $this->File->ext();
		$expecting = 'php';
		$this->assertEqual($result, $expecting);

		$result = $this->File->name();
		$expecting = 'file.test';
		$this->assertEqual($result, $expecting);

		$result = $this->File->md5();
		$expecting = md5_file($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->md5(true);
		$expecting = md5_file($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->size();
		$expecting = filesize($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->owner();
		$expecting = fileowner($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->group();
		$expecting = filegroup($file);
		$this->assertEqual($result, $expecting);

		$result = $this->File->perms();
		$expecting = '0644';
		$this->assertEqual($result, $expecting);

		$result = $this->File->Folder();
		$this->assertIsA($result, 'Folder');

	}

	function testRead() {
		$result = $this->File->read();
		$expecting = file_get_contents(__FILE__);
		$this->assertEqual($result, $expecting);
		$this->assertTrue(!is_resource($this->File->handle));

		$data = $expecting;
		$expecting = substr($data, 0, 3);
		$result = $this->File->read(3);
		$this->assertEqual($result, $expecting);
		$this->assertTrue(is_resource($this->File->handle));

		$expecting = substr($data, 3, 3);
		$result = $this->File->read(3);
		$this->assertEqual($result, $expecting);
	}

	function testOffset() {
		$this->File->close();

		$result = $this->File->offset();
		$this->assertFalse($result);

		$this->assertFalse(is_resource($this->File->handle));
		$success = $this->File->offset(0);
		$this->assertTrue($success);
		$this->assertTrue(is_resource($this->File->handle));

		$result = $this->File->offset();
		$expecting = 0;
		$this->assertIdentical($result, $expecting);

		$data = file_get_contents(__FILE__);
		$success = $this->File->offset(5);
		$expecting = substr($data, 5, 3);
		$result = $this->File->read(3);
		$this->assertTrue($success);
		$this->assertEqual($result, $expecting);

		$result = $this->File->offset();
		$expecting = 5+3;
		$this->assertIdentical($result, $expecting);
	}

	function testOpen() {
		$this->File->handle = null;

		$r = $this->File->open();
		$this->assertTrue(is_resource($this->File->handle));
		$this->assertTrue($r);

		$handle = $this->File->handle;
		$r = $this->File->open();
		$this->assertTrue($r);
		$this->assertTrue($handle === $this->File->handle);
		$this->assertTrue(is_resource($this->File->handle));

		$r = $this->File->open('r', true);
		$this->assertTrue($r);
		$this->assertFalse($handle === $this->File->handle);
		$this->assertTrue(is_resource($this->File->handle));
	}

	function testClose() {
		$this->File->handle = null;
		$this->assertFalse(is_resource($this->File->handle));
		$this->assertTrue($this->File->close());
		$this->assertFalse(is_resource($this->File->handle));

		$this->File->handle = fopen(__FILE__, 'r');
		$this->assertTrue(is_resource($this->File->handle));
		$this->assertTrue($this->File->close());
		$this->assertFalse(is_resource($this->File->handle));
	}

	function testCreate() {
		$tmpFile = TMP.'tests'.DS.'cakephp.file.test.tmp';
		$File =& new File($tmpFile, true, 0777);
		$this->assertTrue($File->exists());
	}

	function testOpeningNonExistantFileCreatesIt() {
		$someFile =& new File('some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertEqual($someFile->read(), '');
		$someFile->close();
		$someFile->delete();
	}

	function testPrepare() {
		$string = "some\nvery\ncool\r\nteststring here\n\n\nfor\r\r\n\n\r\n\nhere";
		$expected = "some\nvery\ncool\nteststring here\n\n\nfor\n\n\n\n\nhere";
		$this->assertIdentical(File::prepare($string), $expected);

		$expected = "some\r\nvery\r\ncool\r\nteststring here\r\n\r\n\r\nfor\r\n\r\n\r\n\r\n\r\nhere";
		$this->assertIdentical(File::prepare($string, true), $expected);
	}

	function testReadable() {
		$someFile =& new File('some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertTrue($someFile->readable());
		$someFile->close();
		$someFile->delete();
	}

	function testWritable() {
		$someFile =& new File('some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertTrue($someFile->writable());
		$someFile->close();
		$someFile->delete();
	}

	function testExecutable() {
		$someFile =& new File('some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertFalse($someFile->executable());
		$someFile->close();
		$someFile->delete();
	}

	function testLastAccess() {
		$someFile =& new File('some_file.txt', false);
		$this->assertFalse($someFile->lastAccess());
		$this->assertTrue($someFile->open());
		$this->assertEqual($someFile->lastAccess(), time());
		$someFile->close();
		$someFile->delete();
	}

	function testLastChange() {
		$someFile =& new File('some_file.txt', false);
		$this->assertFalse($someFile->lastChange());
		$this->assertTrue($someFile->open('r+'));
		$this->assertEqual($someFile->lastChange(), time());
		$someFile->write('something');
		$this->assertEqual($someFile->lastChange(), time());
		$someFile->close();
		$someFile->delete();
	}

	function testWrite() {
		if (!$tmpFile = $this->_getTmpFile()) {
			return false;
		};
		if (file_exists($tmpFile)) {
			unlink($tmpFile);
		}

		$TmpFile =& new File($tmpFile);
		$this->assertFalse(file_exists($tmpFile));
		$this->assertFalse(is_resource($TmpFile->handle));

		$testData = array('CakePHP\'s', ' test suite', ' was here ...', '');
		foreach ($testData as $data) {
			$r = $TmpFile->write($data);
			$this->assertTrue($r);
			$this->assertTrue(file_exists($tmpFile));
			$this->assertEqual($data, file_get_contents($tmpFile));
			$this->assertTrue(is_resource($TmpFile->handle));
			$TmpFile->close();

		}
		unlink($tmpFile);
	}

	function testAppend() {
		if (!$tmpFile = $this->_getTmpFile()) {
			return false;
		};
		if (file_exists($tmpFile)) {
			unlink($tmpFile);
		}

		$TmpFile =& new File($tmpFile);
		$this->assertFalse(file_exists($tmpFile));

		$fragments = array('CakePHP\'s', ' test suite', ' was here ...', '');
		$data = null;
		foreach ($fragments as $fragment) {
			$r = $TmpFile->append($fragment);
			$this->assertTrue($r);
			$this->assertTrue(file_exists($tmpFile));
			$data = $data.$fragment;
			$this->assertEqual($data, file_get_contents($tmpFile));
			$TmpFile->close();
		}
	}

	function testDelete() {
		if (!$tmpFile = $this->_getTmpFile()) {
			return false;
		};

		if (!file_exists($tmpFile)) {
			touch($tmpFile);
		}
		$TmpFile =& new File($tmpFile);
		$this->assertTrue(file_exists($tmpFile));
		$result = $TmpFile->delete();
		$this->assertTrue($result);
		$this->assertFalse(file_exists($tmpFile));

		$TmpFile =& new File('/this/does/not/exist');
		$result = $TmpFile->delete();
		$this->assertFalse($result);
	}

	function _getTmpFile($paintSkip = true) {
		$tmpFile = TMP.'tests'.DS.'cakephp.file.test.tmp';
		if (is_writable(dirname($tmpFile)) && (!file_exists($tmpFile) || is_writable($tmpFile))) {
			return $tmpFile;
		};

		if ($paintSkip) {
			$caller = 'test';
			if (function_exists('debug_backtrace')) {
				$trace = debug_backtrace();
				$caller = $trace[1]['function'].'()';
			}
			$assertLine = new SimpleStackTrace(array(__FUNCTION__));
			$assertLine = $assertLine->traceMethod();
			$shortPath = substr($tmpFile, strlen(ROOT));

			$message = sprintf(__('[FileTest] Skipping %s because "%s" not writeable!', true), $caller, $shortPath).$assertLine;
			$this->_reporter->paintSkip($message);
		}
		return false;
	}

	function testGetFullPathIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getFullPath();
		$this->assertError();
	}

	function testGetNameIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getName();
		$this->assertError();
	}

	function testFilenameIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->filename();
		$this->assertError();
	}

	function testGetExtIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getExt();
		$this->assertError();
	}

	function testGetMd5IsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getMd5();
		$this->assertError();
	}

	function testGetSizeIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getSize();
		$this->assertError();
	}

	function testGetOwnerIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getOwner();
		$this->assertError();
	}

	function testGetGroupIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getGroup();
		$this->assertError();
	}

	function testGetChmodIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getChmod();
		$this->assertError();
	}

	function testGetFolderIsDeprecated() {
		$someFile =& new File('some_file.txt', false);
		$someFile->getFolder();
		$this->assertError();
	}
}
?>