<?php

uses('dbo_factory');

class DboTest extends UnitTestCase
{
	var $dbo;

	// constructor of the test suite
	function DboTest()
	{
		$this->UnitTestCase('DBO test');
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp()
	{
		$this->dbo = DBO::getInstance('test');
		
		$this->createTemporaryTable();
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown()
	{
		if(!$this->dbo) return false;

		$this->dropTemporaryTable();
	}

	function createTemporaryTable()
	{
		if(!$this->dbo) return false;

		if($this->dbo->config['driver'] == 'postgres')
		$sql = 'CREATE TABLE __test(id serial NOT NULL, body CHARACTER VARYING(255))';
		else
		$sql = 'CREATE TABLE __test(id INT UNSIGNED PRIMARY KEY, body VARCHAR(255))';

		return $this->dbo->query($sql);
	}

	function dropTemporaryTable()
	{
		if(!$this->dbo) return false;

		return $this->dbo->query("DROP TABLE __test");
	}

	function testHasImplementation()
	{
		if(!$this->dbo) return false;

		$functions = array(
		'connect',
		'disconnect',
		'execute',
		'fetchRow',
		'tables',
		'fields',
		'prepare',
		'lastError',
		'lastAffected',
		'lastNumRows',
		'lastInsertId'
		);

		foreach($functions as $function)
		{
			$this->assertTrue(method_exists($this->dbo, $function));
		}
	}

	function testConnectivity()
	{
		if(!$this->dbo) return false;

		$this->assertTrue($this->dbo->connected);
	}

	function testFields()
	{
		if(!$this->dbo) return false;

		$fields = $this->dbo->fields('__test');
		$this->assertEqual(count($fields), 2, 'equals');
	}

	function testTables()
	{
		if(!$this->dbo) return false;

		$this->assertTrue(in_array('__test', $this->dbo->tables()));
	}
}

?>