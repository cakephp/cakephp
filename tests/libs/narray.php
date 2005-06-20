<?PHP

uses('narray');

class NarrayTest extends UnitTestCase
{
	var $narray;

	// constructor of the test suite
	function NarrayTest()
	{
		$this->UnitTestCase('Narray test');
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp()
	{
		$this->narray = new Narray();
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown()
	{
		unset($this->narray);
	}


	function testInArray()
	{
		$a = array('foo'=>' bar ', 'i-am'=>'a');
		$b = array('foo'=>'bar ',  'i-am'=>'b');
		$c = array('foo'=>' bar',  'i-am'=>'c');
		$d = array('foo'=>'bar',   'i-am'=>'d');
		
		$n = new Narray(array($a, $b, $c, $d));

		$result = $n->findIn('foo', ' bar ');
		$expected = array(0=>$a);
		$this->assertEqual($result, $expected);

		$result = $n->findIn('foo', 'bar ');
		$expected = array(1=>$b);
		$this->assertEqual($result, $expected);

		$result = $n->findIn('foo', ' bar');
		$expected = array(2=>$c);
		$this->assertEqual($result, $expected);

		$result = $n->findIn('foo', 'bar');
		$expected = array(3=>$d);
		$this->assertEqual($result, $expected);
	}

}

?>