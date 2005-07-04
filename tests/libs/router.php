<?php

uses ('router');

class RouterTest extends UnitTestCase
{
	var $router;

	// constructor of the test suite
	function RouterTest()
	{
		$this->UnitTestCase('Router test');
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp()
	{
		$this->router = new Router();
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown()
	{
		unset($this->router);
	}


	function _testConnect()
	{
		$tests = array(
		'/' => array('controller'=>'Foo', 'action'=>'bar'),
		'/foo/baz' => array('controller'=>'Foo', 'action'=>'baz'),
		'/foo/*' => array('controller'=>'Foo', 'action'=>'dodo'),
		'/foobar' => array('controller'=>'Foobar', 'action'=>'bar'),
		);

		foreach ($tests as $route=>$data)
		$this->router->connect ($route, $data);
	}


	function testParse ()
	{

		$this->_testConnect();

		$tests = array(
		'' => array('controller'=>'Foo', 'action'=>'bar'),
		'/' => array('controller'=>'Foo', 'action'=>'bar'),
		'/foo/baz/' => array('controller'=>'Foo', 'action'=>'baz'),
		'/foo/foo+bar' => array('pass'=>array('foo+bar'), 'controller'=>'Foo', 'action'=>'dodo'),
		'/foobar/' => array('controller'=>'Foobar', 'action'=>'bar'),
		'/foo/bar/baz' => array('controller'=>'Foo', 'action'=>'dodo', 'pass'=>array('bar', 'baz')),
		'/one/two/three/' => array('controller'=>'one', 'action'=>'two', 'pass'=>array('three')),
		'/ruburb' => array('controller'=>'ruburb','action'=>null),
		'???' => array()
		);

		foreach ($tests as $test=>$expected)
		{
			$tested = $this->router->parse($test);
			$this->assertEqual($tested, $expected);
		}
	}
}

?>