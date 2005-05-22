<?php

uses ('test', 'dbo_factory');

class DboTest extends TestCase {
	var $abc;

	// constructor of the test suite
	function DboTest ($name) {
		$this->TestCase($name);
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp() {
		$this->abc = DboFactory::make('test');
		$this->createTemporaryTable();
	}

	// called after the test functions are executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function tearDown() {
		if (!$this->abc) return false;

		$this->dropTemporaryTable();
	}

	function createTemporaryTable () {
		if (!$this->abc) return false;

		if ($this->abc->config['driver'] == 'postgres')
			$sql = 'CREATE TABLE __test (id serial NOT NULL, body CHARACTER VARYING(255))';
		else
			$sql = 'CREATE TABLE __test (id INT UNSIGNED PRIMARY KEY, body VARCHAR(255))';

		return $this->abc->query($sql);
	}

	function dropTemporaryTable () {
		if (!$this->abc) return false;

		return $this->abc->query("DROP TABLE __test");
	}

	function testHasImplementation () {
		if (!$this->abc) return false;

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

		foreach ($functions as $function) {
			$this->assertTrue(method_exists($this->abc, $function));
		}
	}

	function testConnectivity () {
		if (!$this->abc) return false;

		$this->assertTrue($this->abc->connected);
	}

	function testFields () {
		if (!$this->abc) return false;

		$fields = $this->abc->fields('__test');
		$this->assertEquals(count($fields), 2, 'equals');
	}

	function testTables () {
		if (!$this->abc) return false;

		$this->assertTrue(in_array('__test', $this->abc->tables()));
	}
}

?>