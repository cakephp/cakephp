<?php
/**
 * DboSqliteTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Sqlite', 'Model/Datasource/Database');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';

/**
 * DboSqliteTestDb class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class DboSqliteTestDb extends Sqlite {

/**
 * simulated property
 *
 * @var array
 */
	public $simulated = array();

/**
 * execute method
 *
 * @param mixed $sql
 * @return void
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$this->simulated[] = $sql;
		return null;
	}

/**
 * getLastQuery method
 *
 * @return void
 */
	public function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}

}

/**
 * DboSqliteTest class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class SqliteTest extends CakeTestCase {

/**
 * Do not automatically load fixtures for each test, they will be loaded manually using CakeTestCase::loadFixtures
 *
 * @var boolean
 */
	public $autoFixtures = false;

/**
 * Fixtures
 *
 * @var object
 */
	public $fixtures = array('core.user', 'core.uuid');

/**
 * Actual DB connection used in testing
 *
 * @var DboSource
 */
	public $Dbo = null;

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Cache.disable', true);
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!$this->Dbo instanceof Sqlite) {
			$this->markTestSkipped('The Sqlite extension is not available.');
		}
	}

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function tearDown() {
		parent::tearDown();
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
 * @return void
 */
	public function testIndex() {
		$name = $this->Dbo->fullTableName('with_a_key', false, false);
		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" int(11) PRIMARY KEY, "bool" int(1), "small_char" varchar(50), "description" varchar(40) );');
		$this->Dbo->query('CREATE INDEX pointless_bool ON ' . $name . '("bool")');
		$this->Dbo->query('CREATE UNIQUE INDEX char_index ON ' . $name . '("small_char")');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'char_index' => array('column' => 'small_char', 'unique' => 1),

		);
		$result = $this->Dbo->index($name);
		$this->assertEquals($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);

		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" int(11) PRIMARY KEY, "bool" int(1), "small_char" varchar(50), "description" varchar(40) );');
		$this->Dbo->query('CREATE UNIQUE INDEX multi_col ON ' . $name . '("small_char", "bool")');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'multi_col' => array('column' => array('small_char', 'bool'), 'unique' => 1),
		);
		$result = $this->Dbo->index($name);
		$this->assertEquals($expected, $result);
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
		$db = new Sqlite(array_merge($this->Dbo->config, array('database' => TMP . $dbName)));
		$this->assertTrue(file_exists(TMP . $dbName));

		$db->execute("CREATE TABLE test_list (id VARCHAR(255));");

		$db->cacheSources = true;
		$this->assertEquals(array('test_list'), $db->listSources());
		$db->cacheSources = false;

		$fileName = '_' . preg_replace('/[^A-Za-z0-9_\-+]/', '_', TMP . $dbName) . '_list';

		$result = Cache::read($fileName, '_cake_model_');
		$this->assertEquals(array('test_list'), $result);

		Cache::delete($fileName, '_cake_model_');
		Configure::write('Cache.disable', true);
	}

/**
 * test building columns with SQLite
 *
 * @return void
 */
	public function testBuildColumn() {
		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"int_field" integer NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'name',
			'type' => 'string',
			'length' => 20,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"name" varchar(20) NOT NULL';
		$this->assertEquals($expected, $result);

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
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 20,
			'default' => 'test-value',
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" varchar(20) DEFAULT \'test-value\' NOT NULL';
		$this->assertEquals($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'integer',
			'length' => 10,
			'default' => 10,
			'null' => false,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '"testName" integer(10) DEFAULT 10 NOT NULL';
		$this->assertEquals($expected, $result);

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
		$this->assertEquals($expected, $result);
	}

/**
 * test describe() and normal results.
 *
 * @return void
 */
	public function testDescribe() {
		$this->loadFixtures('User');
		$Model = new Model(array('name' => 'User', 'ds' => 'test', 'table' => 'users'));

		$this->Dbo->cacheSources = true;
		Configure::write('Cache.disable', false);

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
				'null' => true,
				'default' => null
			),
			'password' => array(
				'type' => 'string',
				'length' => 255,
				'null' => true,
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
		$this->assertEquals($expected, $result);

		$result = $this->Dbo->describe($Model->useTable);
		$this->assertEquals($expected, $result);

		$result = Cache::read('test_users', '_cake_model_');
		$this->assertEquals($expected, $result);
	}

/**
 * test that describe does not corrupt UUID primary keys
 *
 * @return void
 */
	public function testDescribeWithUuidPrimaryKey() {
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
		$this->assertEquals($expected, $result['id']);
		$this->Dbo->query('DROP TABLE ' . $tableName);

		$tableName = 'uuid_tests';
		$this->Dbo->query("CREATE TABLE {$tableName} (id CHAR(36) PRIMARY KEY, name VARCHAR, created DATETIME, modified DATETIME)");
		$Model = new Model(array('name' => 'UuidTest', 'ds' => 'test', 'table' => 'uuid_tests'));
		$result = $this->Dbo->describe($Model);
		$expected = array(
			'type' => 'string',
			'length' => 36,
			'null' => false,
			'default' => null,
			'key' => 'primary',
		);
		$this->assertEquals($expected, $result['id']);
		$this->Dbo->query('DROP TABLE ' . $tableName);
	}

/**
 * Test virtualFields with functions.
 *
 * @return void
 */
	public function testVirtualFieldWithFunction() {
		$this->loadFixtures('User');
		$User = ClassRegistry::init('User');
		$User->virtualFields = array('name' => 'SUBSTR(User.user, 5)');

		$result = $User->find('first', array(
			'conditions' => array('User.user' => 'garrett')
		));
		$this->assertEquals('ett', $result['User']['name']);
	}

/**
 * Test that records can be inserted with uuid primary keys, and
 * that the primary key is not blank
 *
 * @return void
 */
	public function testUuidPrimaryKeyInsertion() {
		$this->loadFixtures('Uuid');
		$Model = ClassRegistry::init('Uuid');

		$data = array(
			'title' => 'A uuid should work',
			'count' => 10
		);
		$Model->create($data);
		$this->assertTrue((bool)$Model->save());
		$result = $Model->read();

		$this->assertEquals($data['title'], $result['Uuid']['title']);
		$this->assertTrue(Validation::uuid($result['Uuid']['id']), 'Not a uuid');
	}

}
