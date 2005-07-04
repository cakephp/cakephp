<?php

uses('neat_array');

class NeatArrayTest extends UnitTestCase
{
	var $neatArray;

	// constructor of the test suite
	function NeatArrayTest()
	{
		$this->UnitTestCase('NeatArray test');
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp()
	{
		$this->neatArray = new NeatArray();
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown()
	{
		unset($this->neatArray);
	}


	function testInArray()
	{
		$a = array('foo'=>' bar ', 'i-am'=>'a');
		$b = array('foo'=>'bar ',  'i-am'=>'b');
		$c = array('foo'=>' bar',  'i-am'=>'c');
		$d = array('foo'=>'bar',   'i-am'=>'d');
		
		$n = new NeatArray(array($a, $b, $c, $d));

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