<?PHP

uses ('test', 'folder');

class FolderTest extends TestCase {
	var $abc;

	// constructor of the test suite
	function ControllerTest($name) {
		$this->TestCase($name);
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp() {
		$this->abc = new Folder ();
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown() {
		unset($this->abc);
	}


	function testLs () {
		$result = $this->abc->ls();
		$expected = array(array('.svn', 'css', 'files', 'img', 'js'),array('.htaccess', '500.html', 'index.php'));
		$this->assertEquals($result, $expected);
	}

	function testPwd () {
		$result = $this->abc->pwd();
		$expected = getcwd();
		$this->assertEquals($result, $expected);
	}

	function testCd () {
		$this->abc->cd(getcwd());
		$result = $this->abc->pwd();
		$this->assertEquals($result, getcwd());

		$this->abc->cd('css');
		$result = $this->abc->pwd();
		$this->assertEquals($result, Folder::addPathElement(getcwd(), 'css'));
	}

	function testFindRecursive () {
		$result = $this->abc->findRecursive('.*ex\.php');
		$expected = array(Folder::addPathElement($this->abc->pwd(), 'index.php'));
		$this->assertEquals($result, $expected);
	}

	function testIsWindowsPath() {
		$result = Folder::isWindowsPath('C:\foo');
		$expected = true;
		$this->assertEquals($result, $expected);

		$result = Folder::isWindowsPath('/foo/bar');
		$expected = false;
		$this->assertEquals($result, $expected);
	}

	function testIsAbsolute () {
		$result = Folder::isAbsolute('foo/bar');
		$expected = false;
		$this->assertEquals($result, $expected);

		$result = Folder::isAbsolute('c:\foo\bar');
		$expected = true;
		$this->assertEquals($result, $expected);
	}

	function testAddPathElement () {
		$result = Folder::addPathElement('c:\foo', 'bar');
		$expected = 'c:\foo\bar';
		$this->assertEquals($result, $expected);

		$result = Folder::addPathElement('C:\foo\bar\\', 'baz');
		$expected = 'C:\foo\bar\baz';
		$this->assertEquals($result, $expected);

		$result = Folder::addPathElement('/foo/bar/', 'baz');
		$expected = '/foo/bar/baz';
		$this->assertEquals($result, $expected);
	}

	function testIsSlashTerm () {
		$result = Folder::isSlashTerm('/foo/bar/');
		$this->assertEquals($result, true);

		$result = Folder::isSlashTerm('/foo/bar');
		$this->assertEquals($result, false);
	}

	function testCorrectSlashFor () {
		$result = Folder::correctSlashFor('/foo/bar/');
		$this->assertEquals($result, '/');

		$result = Folder::correctSlashFor('C:\foo\bar');
		$this->assertEquals($result, '\\');
	}

	function testSlashTerm () {
		$result = Folder::slashTerm('/foo/bar/');
		$this->assertEquals($result, '/foo/bar/');

		$result = Folder::slashTerm('/foo/bar');
		$this->assertEquals($result, '/foo/bar/');

		$result = Folder::slashTerm('C:\foo\bar');
		$this->assertEquals($result, 'C:\foo\bar\\');
	}


/*
	function test () {
		$result = $this->abc->();
		$expected = '';
		$this->assertEquals($result, $expected);
	}
*/
}

?>