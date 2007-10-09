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
		$this->File =& new File(__FILE__);
	
		$result = $this->File->read();
		$expecting = file_get_contents(__FILE__);
		$this->assertEqual($result, $expecting);
		
		// $expecting = substr($expecting, 0, 3);
		// $result = $this->File->read(3);
		// $this->assertEqual($result, $expecting);
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
		
		$InvalidFile =& new File('invalid-file.invalid-ext');
		$expecting =& new PatternExpectation('/could not open/i');
 		$this->expectError($expecting);
		$InvalidFile->open();
		
		$this->File->close();
	}

	function testClose() {
		$this->File->handle = null;
		$this->assertFalse($this->File->opened());
		$this->assertTrue($this->File->close());
		$this->assertFalse($this->File->opened());

		$this->File->handle = fopen(__FILE__, 'r');
		$this->assertTrue($this->File->opened());
		$this->assertTrue($this->File->close());
		$this->assertFalse($this->File->opened());
	}

	function testOpened() {
		$this->File->handle = null;
		$this->assertFalse($this->File->opened());

		$this->File->handle = fopen(__FILE__, 'r');
		$this->assertTrue($this->File->opened());
		
		$this->File->close();
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
	
		$testData = array('CakePHP\'s test suite was here ...', '');
		foreach ($testData as $data) {
			$r = $TmpFile->write($data);

			$this->assertTrue($r);
			$this->assertTrue(file_exists($tmpFile));
			$this->assertEqual($data, file_get_contents($tmpFile));

		}
		unlink($tmpFile);
	}

	function testAppend() {
		// TODO: Test the append function
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
		$TmpFile->delete();
		$this->assertFalse(file_exists($tmpFile));
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
}
?>