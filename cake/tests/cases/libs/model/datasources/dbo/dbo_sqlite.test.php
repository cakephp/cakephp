<?php
/**
 * DboSqliteTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('Model', 'DataSource', 'DboSource', 'DboSqlite'));

/**
 * DboSqliteTestDb class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class DboSqliteTestDb extends DboSqlite {

/**
 * simulated property
 *
 * @var array
 * @access public
 */
	public $simulated = array();

/**
 * execute method
 *
 * @param mixed $sql
 * @access protected
 * @return void
 */
	function _execute($sql, $params = array()) {
		$this->simulated[] = $sql;
		return null;
	}

/**
 * getLastQuery method
 *
 * @access public
 * @return void
 */
	function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}
}

/**
 * DboSqliteTest class
 *
 * @package       cake.tests.cases.libs.model.datasources.dbo
 */
class DboSqliteTest extends CakeTestCase {

/**
 * Do not automatically load fixtures for each test, they will be loaded manually using CakeTestCase::loadFixtures
 *
 * @var boolean
 * @access public
 */
	public $autoFixtures = false;

/**
 * Fixtures
 *
 * @var object
 * @access public
 */
	public $fixtures = array('core.user');

/**
 * Actual DB connection used in testing
 *
 * @var DboSource
 * @access public
 */
	public $Dbo = null;

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function setUp() {
		Configure::write('Cache.disable', true);
		$this->Dbo = ConnectionManager::getDataSource('test');
		if ($this->Dbo->config['driver'] !== 'sqlite') {
			$this->markTestSkipped('The Sqlite extension is not available.');
		}
	}

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function tearDown() {
		Configure::write('Cache.disable', false);
	}

/**
 * Tests that SELECT queries from DboSqlite::listSources() are not cached
 *
 */
	public function testTableListCacheDisabling() {
		$this->assertFalse(in_array('foo_test', $this->Dbo->listSources()));

		$this->Dbo->query('CREATE TABLE foo_test (test VARCHAR(255))');
		$this->assertTrue(in_array('foo_test', $this->Dbo->listSources()));

		$this->Dbo->cacheSources = false;
		$this->Dbo->query('DROP TABLE foo_test');
		$this->assertFalse(in_array('foo_test', $this->Dbo->listSources()));
	}

/**
 * test Index introspection.
 *
 * @access public
 * @return void
 */
	function testIndex() {
		$name = $this->Dbo->fullTableName('with_a_key');
		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" int(11) PRIMARY KEY, "bool" int(1), "small_char" varchar(50), "description" varchar(40) );');
		$this->Dbo->query('CREATE INDEX pointless_bool ON ' . $name . '("bool")');
		$this->Dbo->query('CREATE UNIQUE INDEX char_index ON ' . $name . '("small_char")');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'char_index' => array('column' => 'small_char', 'unique' => 1),

		);
		$result = $this->Dbo->index($name);
		$this->assertEqual($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);

		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" int(11) PRIMARY KEY, "bool" int(1), "small_char" varchar(50), "description" varchar(40) );');
		$this->Dbo->query('CREATE UNIQUE INDEX multi_col ON ' . $name . '("small_char", "bool")');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'multi_col' => array('column' => array('small_char', 'bool'), 'unique' => 1),
		);
		$result = $this->Dbo->index($name);
		$this->assertEqual($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);
	}

/**
 * Tests that cached table descriptions are saved under the sanitized key name
 *
 */
	public function testCacheKeyName() {
		Configure::write('Cache.disable', false);

		$dbName = 'db' . rand() . '$(*%&).db';
		$this->assertFalse(file_exists(TMP . $dbName));

		$config = $this->Dbo->config;
		$db = new DboSqlite(array_merge($this->Dbo->config, array('database' => TMP . $dbName)));
		$this->assertTrue(file_exists(TMP . $dbName));

		$db->execute("CREATE TABLE test_list (id VARCHAR(255));");

		$db->cacheSources = true;
		$this->assertEqual($db->listSources(), array('test_list'));
		$db->cacheSources = false;

		$fileName = '_' . preg_replace('/[^A-Za-z0-9_\-+]/', '_', TMP . $dbName) . '_list';

		$result = Cache::read($fileName, '_cake_model_');
		$this->assertEqual($result, array('test_list'));

		Cache::delete($fileName, '_cake_model_');
		Configure::write('Cache.disable', true);
	}

/**
 * test building columns with SQLite
 *
 * @return void
 */
	function testBuildColumn() {
		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"int_field" integer NOT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'name',
			'type' => 'string',
			'length' => 20,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"name" varchar(20) NOT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 20,
			'default' => null,
			'null' => true,
			'collate' => 'NOCASE'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" varchar(20) DEFAULT NULL COLLATE NOCASE';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 20,
			'default' => 'test-value',
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" varchar(20) DEFAULT \'test-value\' NOT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'testName',
			'type' => 'integer',
			'length' => 10,
			'default' => 10,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" integer(10) DEFAULT 10 NOT NULL';
		$this->assertEqual($result, $expected);
		
		$data = array(
			'name' => 'testName',
			'type' => 'integer',
			'length' => 10,
			'default' => 10,
			'null' => false,
			'collate' => 'BADVALUE'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" integer(10) DEFAULT 10 NOT NULL';
		$this->assertEqual($result, $expected);
	}

/**
 * test describe() and normal results.
 *
 * @return void
 */
	function testDescribe() {
		$this->loadFixtures('User');
		$Model = new Model(array('name' => 'User', 'ds' => 'test', 'table' => 'users'));
		$result = $this->Dbo->describe($Model);
		$expected = array(
			'id' => array(
				'type' => 'integer',
				'key' => 'primary',
				'null' => false,
				'default' => null,
				'length' => 11
			),
			'user' => array(
				'type' => 'string',
				'length' => 255,
				'null' => false,
				'default' => null
			),
			'password' => array(
				'type' => 'string',
				'length' => 255,
				'null' => false,
				'default' => null
			),
			'created' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
			),
			'updated' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * test that describe does not corrupt UUID primary keys
 *
 * @return void
 */
	function testDescribeWithUuidPrimaryKey() {
		$tableName = 'uuid_tests';
		$this->Dbo->query("CREATE TABLE {$tableName} (id VARCHAR(36) PRIMARY KEY, name VARCHAR, created DATETIME, modified DATETIME)");
		$Model = new Model(array('name' => 'UuidTest', 'ds' => 'test', 'table' => 'uuid_tests'));
		$result = $this->Dbo->describe($Model);
		$expected = array(
			'type' => 'string',
			'length' => 36,
			'null' => false,
			'default' => null,
			'key' => 'primary',
		);
		$this->assertEqual($result['id'], $expected);
		$this->Dbo->query('DROP TABLE ' . $tableName);
	}
}
