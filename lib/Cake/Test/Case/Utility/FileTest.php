<?php
/**
 * FileTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');

/**
 * FileTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class FileTest extends CakeTestCase {

/**
 * File property
 *
 * @var mixed null
 */
	public $File = null;

/**
 * setup the test case
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$file = __FILE__;
		$this->File = new File($file);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		$this->File->close();
		unset($this->File);
	}

/**
 * testBasic method
 *
 * @return void
 */
	public function testBasic() {
		$file = __FILE__;

		$result = $this->File->pwd();
		$expecting = $file;
		$this->assertEquals($expecting, $result);

		$result = $this->File->name;
		$expecting = basename(__FILE__);
		$this->assertEquals($expecting, $result);

		$result = $this->File->info();
		$expecting = array(
			'dirname' => dirname(__FILE__), 'basename' => basename(__FILE__),
			'extension' => 'php', 'filename' =>'FileTest', 'filesize' => filesize($file)
		);
		$this->assertEquals($expecting, $result);

		$result = $this->File->ext();
		$expecting = 'php';
		$this->assertEquals($expecting, $result);

		$result = $this->File->name();
		$expecting = 'FileTest';
		$this->assertEquals($expecting, $result);

		$result = $this->File->md5();
		$expecting = md5_file($file);
		$this->assertEquals($expecting, $result);

		$result = $this->File->md5(true);
		$expecting = md5_file($file);
		$this->assertEquals($expecting, $result);

		$result = $this->File->size();
		$expecting = filesize($file);
		$this->assertEquals($expecting, $result);

		$result = $this->File->owner();
		$expecting = fileowner($file);
		$this->assertEquals($expecting, $result);

		$result = $this->File->group();
		$expecting = filegroup($file);
		$this->assertEquals($expecting, $result);

		$result = $this->File->Folder();
		$this->assertInstanceOf('Folder', $result);

		$this->skipIf(DIRECTORY_SEPARATOR === '\\', 'File permissions tests not supported on Windows.');

		$result = $this->File->perms();
		$expecting = decoct(0644 & ~umask());
		$this->assertEquals($expecting, $result);
	}

/**
 * testRead method
 *
 * @return void
 */
	public function testRead() {
		$file = __FILE__;
		$this->File = new File($file);

		$result = $this->File->read();
		$expecting = file_get_contents(__FILE__);
		$this->assertEquals($expecting, $result);
		$this->assertTrue(!is_resource($this->File->handle));

		$this->File->lock = true;
		$result = $this->File->read();
		$expecting = file_get_contents(__FILE__);
		$this->assertEquals($result, trim($expecting));
		$this->File->lock = null;

		$data = $expecting;
		$expecting = substr($data, 0, 3);
		$result = $this->File->read(3);
		$this->assertEquals($expecting, $result);
		$this->assertTrue(is_resource($this->File->handle));

		$expecting = substr($data, 3, 3);
		$result = $this->File->read(3);
		$this->assertEquals($expecting, $result);
	}

/**
 * testOffset method
 *
 * @return void
 */
	public function testOffset() {
		$this->File->close();

		$result = $this->File->offset();
		$this->assertFalse($result);

		$this->assertFalse(is_resource($this->File->handle));
		$success = $this->File->offset(0);
		$this->assertTrue($success);
		$this->assertTrue(is_resource($this->File->handle));

		$result = $this->File->offset();
		$expecting = 0;
		$this->assertSame($result, $expecting);

		$data = file_get_contents(__FILE__);
		$success = $this->File->offset(5);
		$expecting = substr($data, 5, 3);
		$result = $this->File->read(3);
		$this->assertTrue($success);
		$this->assertEquals($expecting, $result);

		$result = $this->File->offset();
		$expecting = 5+3;
		$this->assertSame($result, $expecting);
	}

/**
 * testOpen method
 *
 * @return void
 */
	public function testOpen() {
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

/**
 * testClose method
 *
 * @return void
 */
	public function testClose() {
		$this->File->handle = null;
		$this->assertFalse(is_resource($this->File->handle));
		$this->assertTrue($this->File->close());
		$this->assertFalse(is_resource($this->File->handle));

		$this->File->handle = fopen(__FILE__, 'r');
		$this->assertTrue(is_resource($this->File->handle));
		$this->assertTrue($this->File->close());
		$this->assertFalse(is_resource($this->File->handle));
	}

/**
 * testCreate method
 *
 * @return void
 */
	public function testCreate() {
		$tmpFile = TMP.'tests'.DS.'cakephp.file.test.tmp';
		$File = new File($tmpFile, true, 0777);
		$this->assertTrue($File->exists());
	}

/**
 * testOpeningNonExistantFileCreatesIt method
 *
 * @return void
 */
	public function testOpeningNonExistantFileCreatesIt() {
		$someFile = new File(TMP . 'some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertEquals('', $someFile->read());
		$someFile->close();
		$someFile->delete();
	}

/**
 * testPrepare method
 *
 * @return void
 */
	public function testPrepare() {
		$string = "some\nvery\ncool\r\nteststring here\n\n\nfor\r\r\n\n\r\n\nhere";
		if (DS == '\\') {
			$expected = "some\r\nvery\r\ncool\r\nteststring here\r\n\r\n\r\n";
			$expected .= "for\r\n\r\n\r\n\r\n\r\nhere";
		} else {
			$expected = "some\nvery\ncool\nteststring here\n\n\nfor\n\n\n\n\nhere";
		}
		$this->assertSame(File::prepare($string), $expected);

		$expected = "some\r\nvery\r\ncool\r\nteststring here\r\n\r\n\r\n";
		$expected .= "for\r\n\r\n\r\n\r\n\r\nhere";
		$this->assertSame(File::prepare($string, true), $expected);
	}

/**
 * testReadable method
 *
 * @return void
 */
	public function testReadable() {
		$someFile = new File(TMP . 'some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertTrue($someFile->readable());
		$someFile->close();
		$someFile->delete();
	}

/**
 * testWritable method
 *
 * @return void
 */
	public function testWritable() {
		$someFile = new File(TMP . 'some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertTrue($someFile->writable());
		$someFile->close();
		$someFile->delete();
	}

/**
 * testExecutable method
 *
 * @return void
 */
	public function testExecutable() {
		$someFile = new File(TMP . 'some_file.txt', false);
		$this->assertTrue($someFile->open());
		$this->assertFalse($someFile->executable());
		$someFile->close();
		$someFile->delete();
	}

/**
 * testLastAccess method
 *
 * @return void
 */
	public function testLastAccess() {
		$ts = time();
		$someFile = new File(TMP . 'some_file.txt', false);
		$this->assertFalse($someFile->lastAccess());
		$this->assertTrue($someFile->open());
		$this->assertTrue($someFile->lastAccess() >= $ts);
		$someFile->close();
		$someFile->delete();
	}

/**
 * testLastChange method
 *
 * @return void
 */
	public function testLastChange() {
		$ts = time();
		$someFile = new File(TMP . 'some_file.txt', false);
		$this->assertFalse($someFile->lastChange());
		$this->assertTrue($someFile->open('r+'));
		$this->assertTrue($someFile->lastChange() >= $ts);
		$someFile->write('something');
		$this->assertTrue($someFile->lastChange() >= $ts);
		$someFile->close();
		$someFile->delete();
	}

/**
 * testWrite method
 *
 * @return void
 */
	public function testWrite() {
		if (!$tmpFile = $this->_getTmpFile()) {
			return false;
		};
		if (file_exists($tmpFile)) {
			unlink($tmpFile);
		}

		$TmpFile = new File($tmpFile);
		$this->assertFalse(file_exists($tmpFile));
		$this->assertFalse(is_resource($TmpFile->handle));

		$testData = array('CakePHP\'s', ' test suite', ' was here ...', '');
		foreach ($testData as $data) {
			$r = $TmpFile->write($data);
			$this->assertTrue($r);
			$this->assertTrue(file_exists($tmpFile));
			$this->assertEquals($data, file_get_contents($tmpFile));
			$this->assertTrue(is_resource($TmpFile->handle));
			$TmpFile->close();

		}
		unlink($tmpFile);
	}

/**
 * testAppend method
 *
 * @return void
 */
	public function testAppend() {
		if (!$tmpFile = $this->_getTmpFile()) {
			return false;
		};
		if (file_exists($tmpFile)) {
			unlink($tmpFile);
		}

		$TmpFile = new File($tmpFile);
		$this->assertFalse(file_exists($tmpFile));

		$fragments = array('CakePHP\'s', ' test suite', ' was here ...', '');
		$data = null;
		foreach ($fragments as $fragment) {
			$r = $TmpFile->append($fragment);
			$this->assertTrue($r);
			$this->assertTrue(file_exists($tmpFile));
			$data = $data.$fragment;
			$this->assertEquals($data, file_get_contents($tmpFile));
			$TmpFile->close();
		}
	}

/**
 * testDelete method
 *
 * @return void
 */
	public function testDelete() {
		if (!$tmpFile = $this->_getTmpFile()) {
			return false;
		}

		if (!file_exists($tmpFile)) {
			touch($tmpFile);
		}
		$TmpFile = new File($tmpFile);
		$this->assertTrue(file_exists($tmpFile));
		$result = $TmpFile->delete();
		$this->assertTrue($result);
		$this->assertFalse(file_exists($tmpFile));

		$TmpFile = new File('/this/does/not/exist');
		$result = $TmpFile->delete();
		$this->assertFalse($result);
	}

/**
 * Windows has issues unlinking files if there are
 * active filehandles open.
 *
 * @return void
 */
	function testDeleteAfterRead() {
		if (!$tmpFile = $this->_getTmpFile()) {
			return false;
		}
		if (!file_exists($tmpFile)) {
			touch($tmpFile);
		}
		$File =& new File($tmpFile);
		$File->read();
		$this->assertTrue($File->delete());
	}

/**
 * testCopy method
 *
 * @return void
 */
	public function testCopy() {
		$dest = TMP . 'tests' . DS . 'cakephp.file.test.tmp';
		$file = __FILE__;
		$this->File = new File($file);
		$result = $this->File->copy($dest);
		$this->assertTrue($result);

		$result = $this->File->copy($dest, true);
		$this->assertTrue($result);

		$result = $this->File->copy($dest, false);
		$this->assertFalse($result);

		$this->File->close();
		unlink($dest);

		$TmpFile = new File('/this/does/not/exist');
		$result = $TmpFile->copy($dest);
		$this->assertFalse($result);

		$TmpFile->close();
	}

/**
 * getTmpFile method
 *
 * @param bool $paintSkip
 * @return void
 */
	function _getTmpFile($paintSkip = true) {
		$tmpFile = TMP . 'tests' . DS . 'cakephp.file.test.tmp';
		if (is_writable(dirname($tmpFile)) && (!file_exists($tmpFile) || is_writable($tmpFile))) {
			return $tmpFile;
		};

		if ($paintSkip) {
			$trace = debug_backtrace();
			$caller = $trace[0]['function'];
			$shortPath = dirname($tmpFile);

			$message = __d('cake_dev', '[FileTest] Skipping %s because "%s" not writeable!', $caller, $shortPath);
			$this->markTestSkipped($message);
		}
		return false;
	}
}
