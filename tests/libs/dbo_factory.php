<?php

uses('dbo_factory');

class DboFactoryTest extends UnitTestCase
{
	var $dboFactory;

	// constructor of the test suite
	function DboFactoryTest()
	{
		$this->UnitTestCase('DBO Factory test');
	}

/*	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp()
	{
		$this->dboFactory = new DboFactory();
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown()
	{
		unset($this->dboFactory);
	}


	function testMake()
	{
		if(class_exists('DATABASE_CONFIG'))
		{

			$output = $this->dboFactory->make('test');
			$this->assertTrue(is_object($output), 'We create dbo factory object');

			$config = DATABASE_CONFIG::test();
			if(preg_match('#^(adodb)_.*$#i', $config['driver'], $res))
			{
				$desiredDriverName = $res[1];
			}
			else
			{
				$desiredDriverName = $config['driver'];
			}

			$desiredClassName = 'dbo_'.strtolower($desiredDriverName);
			$outputClassName  = is_object($output)?  strtolower(get_class($output)): false;

			$this->assertEqual($outputClassName, $desiredClassName, "Class name should be $desiredClassName - is $outputClassName");

			$this->assertTrue($output->connected, 'We are connected');
		}
	}

	// this test expect an E_USER_ERROR to occur during it's run
	// I've disabled it until I find a way to assert it happen
	//
	//	function testBadConfig() {
	//		$output = $this->dboFactory->make(null);
	//		$this->assertTrue($output === false);
	//	}*/
}

?>