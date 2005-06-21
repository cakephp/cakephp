<?php

uses('folder');

class FolderTest extends UnitTestCase
{
	var $folder;
	var $testDir;

	// constructor of the test suite
	function FolderTest()
	{
		$this->UnitTestCase('Folder test');
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp()
	{
		$this->testDir = ROOT.'tmp'.DS.'tests';

		touch($this->testDir.DS.'.htaccess');
		chmod($this->testDir.DS.'.htaccess', 0777);
		if (!is_dir($this->testDir.DS.'dir1'))
		{
			mkdir($this->testDir.DS.'dir1', 0777);
		}
		touch($this->testDir.DS.'dir1'.DS.'test1.php');
		chmod($this->testDir.DS.'dir1'.DS.'test1.php', 0777);

		if (!is_dir($this->testDir.DS.'dir2'))
		{
			mkdir($this->testDir.DS.'dir2', 0777);
		}
		touch($this->testDir.DS.'dir2'.DS.'test2.php');
		chmod($this->testDir.DS.'dir2'.DS.'test2.php', 0777);


		$this->folder = new Folder($this->testDir);
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown()
	{
		unlink($this->testDir.DS.'.htaccess');
		unlink($this->testDir.DS.'dir1'.DS.'test1.php');
		unlink($this->testDir.DS.'dir2'.DS.'test2.php');

		rmdir($this->testDir.DS.'dir1');
		rmdir($this->testDir.DS.'dir2');

		unset($this->folder);
	}


	function testLs()
	{
		$result = $this->folder->ls();
		$expected = array (
		array('.svn', 'dir1', 'dir2'),
		array('.htaccess')
		);

		$this->assertEqual($result, $expected, "List directories and files from test dir");
	}

	function testPwd()
	{
		$result = $this->folder->pwd();

		$this->assertEqual($result, $this->testDir, 'Print current path (test dir)');
	}

	function testCd()
	{
		$this->folder->cd($this->testDir);
		$result = $this->folder->pwd();
		$this->assertEqual($result, $this->testDir, 'Change directory to the actual one');

		//THIS ONE IS HACKED... why do i need to give a full path to this method?
		$this->folder->cd($this->testDir.DS.'dir1');
		$result = $this->folder->pwd();

		$this->assertEqual($result, Folder::addPathElement($this->testDir, 'dir1'), 'Change directory to dir1');
	}

	function testFindRecursive()
	{
		$result = $this->folder->findRecursive('.*\.php');
		$expected = array(Folder::addPathElement($this->folder->pwd().DS.'dir1', 'test1.php'), Folder::addPathElement($this->folder->pwd().DS.'dir2', 'test2.php'));

		$this->assertEqual($result, $expected, 'Find .php files');
	}

	function testIsWindowsPath()
	{
		$result = Folder::isWindowsPath('C:\foo');
		$expected = true;
		$this->assertEqual($result, $expected);

		$result = Folder::isWindowsPath('/foo/bar');
		$expected = false;
		$this->assertEqual($result, $expected);
	}

	function testIsAbsolute()
	{
		$result = Folder::isAbsolute('foo/bar');
		$expected = false;
		$this->assertEqual($result, $expected);

		$result = Folder::isAbsolute('c:\foo\bar');
		$expected = true;
		$this->assertEqual($result, $expected);
	}

	function testAddPathElement()
	{
		$result = Folder::addPathElement('c:\foo', 'bar');
		$expected = 'c:\foo\bar';
		$this->assertEqual($result, $expected);

		$result = Folder::addPathElement('C:\foo\bar\\', 'baz');
		$expected = 'C:\foo\bar\baz';
		$this->assertEqual($result, $expected);

		$result = Folder::addPathElement('/foo/bar/', 'baz');
		$expected = '/foo/bar/baz';
		$this->assertEqual($result, $expected);
	}

	function testIsSlashTerm()
	{
		$result = Folder::isSlashTerm('/foo/bar/');
		$this->assertEqual($result, true);

		$result = Folder::isSlashTerm('/foo/bar');
		$this->assertEqual($result, false);
	}

	function testCorrectSlashFor()
	{
		$result = Folder::correctSlashFor('/foo/bar/');
		$this->assertEqual($result, '/');

		$result = Folder::correctSlashFor('C:\foo\bar');
		$this->assertEqual($result, '\\');
	}

	function testSlashTerm()
	{
		$result = Folder::slashTerm('/foo/bar/');
		$this->assertEqual($result, '/foo/bar/');

		$result = Folder::slashTerm('/foo/bar');
		$this->assertEqual($result, '/foo/bar/');

		$result = Folder::slashTerm('C:\foo\bar');
		$this->assertEqual($result, 'C:\foo\bar\\');
	}
}

?>