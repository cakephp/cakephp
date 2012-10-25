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
	public $fixtures = array('core.user', 'core.uuid', 'core.apple');

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
 * test Index introspection.
 *
 * @return void
 */
	public function testIndex2() {
		$this->Dbo->cacheSources = false;

		$name = 'test_index_tbl';
		$table = $this->Dbo->fullTableName($name, false, false);

		// no primary key
		$this->Dbo->rawQuery("CREATE TABLE $table (name text)");
		$expected = array();
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");

		// alias of ROWID
		$this->Dbo->rawQuery("CREATE TABLE $table (id integer primary key, name text)");
		$expected = array('PRIMARY' => array('column' => 'id', 'unique' => 1));
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");

		// not an alias of ROWID
		$this->Dbo->rawQuery("CREATE TABLE $table (id int primary key, name text)");
		$expected = array('PRIMARY' => array('column' => 'id', 'unique' => 1));
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");

		// composite primary key
		$this->Dbo->rawQuery("CREATE TABLE $table (id1 integer, id2 integer,  name text, primary key(id1, id2))");
		$expected = array('PRIMARY' => array('column' => array('id1', 'id2'), 'unique' => 1));
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");

		// unique column
		$this->Dbo->rawQuery("CREATE TABLE $table (id integer primary key, name text unique)");
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				'name' => array('column' => 'name', 'unique' => 1)   // consistent with MySql
		);
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");

		// unique column pair
		$this->Dbo->rawQuery("CREATE TABLE $table (id integer primary key, x integer, y integer, UNIQUE(x, y))");
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				'x' => array('column' => array('x', 'y'), 'unique' => 1) // consistent with MySql
		);
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");

		// named single column index (not unique)
		$indexName = 'index_for_name';
		$this->Dbo->rawQuery("CREATE TABLE $table (id integer primary key, name text)");
		$this->Dbo->rawQuery("CREATE INDEX $indexName ON $name (name)");
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				$indexName => array('column' => 'name', 'unique' => 0));
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");

		// named multi column index (not unique)
		$indexName = 'index_for_x_and_y';
		$this->Dbo->rawQuery("CREATE TABLE $table (id integer primary key, x integer, y integer)");
		$this->Dbo->rawQuery("CREATE INDEX $indexName ON $name (x, y)");
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				$indexName => array('column' => array('x', 'y'), 'unique' => 0));
		$result = $this->Dbo->index($table);
		$this->assertEqual($result, $expected);
		$this->Dbo->rawQuery("DROP TABLE $table");
	}

/**
 *
 *
 * autoincrement can only be declared on the primary key
 *     cannot use: CREATE TABLE my_table (id int(11) AUTOINCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));
 *       must use: CREATE TABLE my_table (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2));
 *
 * non-unique indexes cannot be declared in CREATE TABE statement
 *     cannot use: CREATE TABLE my_table (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2), KEY pointless_bool (bool))
 *       must use: CREATE TABLE my_table (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2))
 *                 CREATE INDEX pointless_bool on my_table (bool);
 *
 * @return void
 */
	public function testIndexDetection() {
		$this->Dbo->cacheSources = false;

		$name = $this->Dbo->fullTableName('simple');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2));');
		$expected = array('PRIMARY' => array('column' => 'id', 'unique' => 1));
		$result = $this->Dbo->index('simple', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);

		$name = $this->Dbo->fullTableName('simple');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int, bool tinyint(1), small_int tinyint(2), primary key(id));');
		$expected = array('PRIMARY' => array('column' => 'id', 'unique' => 1));
		$result = $this->Dbo->index('simple', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);

		$name = $this->Dbo->fullTableName('with_a_key');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2));');
		$this->Dbo->rawQuery('CREATE INDEX pointless_bool on with_a_key (bool);');
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				'pointless_bool' => array('column' => 'bool', 'unique' => 0),
		);
		$result = $this->Dbo->index('with_a_key', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);

		$name = $this->Dbo->fullTableName('with_two_keys');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2));');
		$this->Dbo->rawQuery('CREATE INDEX pointless_bool on with_two_keys (bool);');
		$this->Dbo->rawQuery('CREATE INDEX pointless_small_int on with_two_keys (small_int);');
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				'pointless_bool' => array('column' => 'bool', 'unique' => 0),
				'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
		);
		$result = $this->Dbo->index('with_two_keys', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);

		$name = $this->Dbo->fullTableName('with_compound_keys');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2));');
		$this->Dbo->rawQuery('CREATE INDEX pointless_bool on with_compound_keys (bool);');
		$this->Dbo->rawQuery('CREATE INDEX pointless_small_int on with_compound_keys (small_int);');
		$this->Dbo->rawQuery('CREATE INDEX one_way on with_compound_keys (bool, small_int);');
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				'pointless_bool' => array('column' => 'bool', 'unique' => 0),
				'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
				'one_way' => array('column' => array('bool', 'small_int'), 'unique' => 0),
		);
		$result = $this->Dbo->index('with_compound_keys', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);

		$name = $this->Dbo->fullTableName('with_multiple_compound_keys');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id integer primary key AUTOINCREMENT, bool tinyint(1), small_int tinyint(2));');
		$this->Dbo->rawQuery('CREATE INDEX pointless_bool on with_multiple_compound_keys (bool);');
		$this->Dbo->rawQuery('CREATE INDEX pointless_small_int on with_multiple_compound_keys (small_int);');
		$this->Dbo->rawQuery('CREATE INDEX one_way on with_multiple_compound_keys (bool, small_int);');
		$this->Dbo->rawQuery('CREATE INDEX other_way on with_multiple_compound_keys (small_int, bool);');
		$expected = array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				'pointless_bool' => array('column' => 'bool', 'unique' => 0),
				'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
				'one_way' => array('column' => array('bool', 'small_int'), 'unique' => 0),
				'other_way' => array('column' => array('small_int', 'bool'), 'unique' => 0),
		);
		$result = $this->Dbo->index('with_multiple_compound_keys', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that cached table descriptions are saved under the sanitized key name
 *
 */
	public function testCacheKeyName() {
		Configure::write('Cache.disable', false);
		Cache::set(array('duration' => '+1 days'));

		$dbName = 'db' . rand();
		$dbName .= (DS === '\\') ? '$(%&).db' : '$(*%&).db';
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
 * test testCreateSchemaWithIndexes()
 *
 * SQLite does not support named table constraints.  Say you want to 'DROP INDEX x_y_is_unique;'
 *   cannot use:
 *     create table aaa (id integer primary key autoincrement, x integer, y integer, constraint x_y_is_unique unique (x, y));
 *   must use:
 *     create table aaa (id integer primary key autoincrement, x integer, y integer);
 *     create index x_y_is_unique on aaa (x, y);
 *
 * @return void
 */
	public function testCreateSchemaWithIndexes() {
		$this->Dbo->cacheSources = false;
		$tableName = 'test_drop_schema';

		$schema = new CakeSchema();
		$schema->tables[$tableName] = array(
				'id' => array('type' => 'integer', 'null' => false, 'key' => 'primary'),
				'x' => array('type' => 'integer', 'null' => false),
				'data' => array('type' => 'text', 'null' => false),
				'indexes' => array(
						'x_is_unique' => array('column' => 'x', 'unique' => 1),
						'data_is_fast' => array('column' => 'data', 'unique => 0')
				)
		);

		$sql = $this->Dbo->createSchema($schema);
		$this->Dbo->rawQuery($sql);
		$fields = $this->Dbo->describe($tableName);
		$this->assertEqual($fields['id'], array('type' => 'integer', 'null' => false, 'length' => null, 'default' => null, 'key' => 'primary'));
		$this->assertEqual($fields['x'], array('type' => 'integer', 'null' => false, 'length' => null, 'default' => null, 'key' => 'unique'));
		$this->assertEqual($fields['data'], array('type' => 'text', 'null' => false, 'length' => null, 'default' => null, 'key' => 'index'));

		$this->Dbo->rawQuery('DROP INDEX x_is_unique;');
		$fields = $this->Dbo->describe($tableName);
		$this->assertEqual($fields['x'], array('type' => 'integer', 'null' => false, 'length' => null, 'default' => null));

		$this->Dbo->rawQuery('DROP INDEX data_is_fast;');
		$fields = $this->Dbo->describe($tableName);
		$this->assertEqual($fields['data'], array('type' => 'text', 'null' => false, 'length' => null, 'default' => null));

		$this->Dbo->rawQuery("DROP TABLE $tableName");



		$schema = new CakeSchema();
		$schema->tables[$tableName] = array(
				'id' => array('type' => 'integer', 'null' => false),
				'x' => array('type' => 'integer', 'null' => false),
				'data' => array('type' => 'text', 'null' => false),
				'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'x_is_unique' => array('column' => 'x', 'unique' => 1),
						'data_is_fast' => array('column' => 'data', 'unique => 0')
				)
		);

		$sql = $this->Dbo->createSchema($schema);
		$this->Dbo->rawQuery($sql);
		$fields = $this->Dbo->describe($tableName);
		$this->assertEqual($fields['id'], array('type' => 'integer', 'null' => false, 'length' => null, 'default' => null, 'key' => 'primary'));
		$this->assertEqual($fields['x'], array('type' => 'integer', 'null' => false, 'length' => null, 'default' => null, 'key' => 'unique'));
		$this->assertEqual($fields['data'], array('type' => 'text', 'null' => false, 'length' => null, 'default' => null, 'key' => 'index'));

		$this->Dbo->rawQuery('DROP INDEX x_is_unique;');
		$fields = $this->Dbo->describe($tableName);
		$this->assertEqual($fields['x'], array('type' => 'integer', 'null' => false, 'length' => null, 'default' => null));

		$this->Dbo->rawQuery('DROP INDEX data_is_fast;');
		$fields = $this->Dbo->describe($tableName);
		$this->assertEqual($fields['data'], array('type' => 'text', 'null' => false, 'length' => null, 'default' => null));

		$this->Dbo->rawQuery("DROP TABLE $tableName");
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
				'length' => null
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
 * test testDescribeApple() and normal results.
 *
 * @return void
 */
	public function testDescribeApple() {
		$this->loadFixtures('Apple');
		$this->Dbo->cacheSources = false;

		$model = new Apple();
		$fields_by_model = $this->Dbo->describe($model);
		$fields_by_name = $this->Dbo->describe($model->useTable);
		$this->assertIdentical($fields_by_model, $fields_by_name);

		$fields = $fields_by_model;
		$this->assertEqual($fields['id'], array('type' => 'integer', 'null' => false, 'default' => '', 'length' => null, 'key' => 'primary'));
		$this->assertEqual($fields['apple_id'], array('type' => 'integer', 'null' => true, 'default' => null, 'length' => null));
		$this->assertEqual($fields['color'], array('type' => 'string', 'null' => false, 'default' => '', 'length' => 40));
		$this->assertEqual($fields['name'], array('type' => 'string', 'null' => false, 'default' => '', 'length' => 40));
		$this->assertEqual($fields['created'], array('type' => 'datetime', 'null' => true, 'default' => null, 'length' => null));
		$this->assertEqual($fields['date'], array('type' => 'date', 'null' => true, 'default' => null, 'length' => null));
		$this->assertEqual($fields['modified'], array('type' => 'datetime', 'null' => true, 'default' => null, 'length' => null));
		$this->assertEqual($fields['mytime'], array('type' => 'time', 'null' => true, 'default' => null, 'length' => null));
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
 * test that describe 'key' value is consistent with MySql
 *
 * @return void
 */
	public function testDescribeWithIndexes() {
		$this->Dbo->cacheSources = false;

		$name = $this->Dbo->fullTableName('with_multiple_compound_keys');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int primary key, bool tinyint(1), small_int tinyint(2), unique_int integer unique);');
		$this->Dbo->rawQuery('CREATE INDEX pointless_bool on with_multiple_compound_keys (bool);');
		$this->Dbo->rawQuery('CREATE INDEX one_way on with_multiple_compound_keys (bool, small_int);');

		$fields = $this->Dbo->describe('with_multiple_compound_keys');
		$this->assertEqual($fields['id']['key'], 'primary');
		$this->assertEqual($fields['unique_int']['key'], 'unique');
		$this->assertEqual($fields['bool']['key'], 'index');
		$this->assertTrue(empty($fields['small_int']['key']));

		$this->Dbo->rawQuery('CREATE INDEX other_way on with_multiple_compound_keys (small_int, bool);');

		$fields = $this->Dbo->describe('with_multiple_compound_keys');
		$this->assertEqual($fields['bool']['key'], 'index');
		$this->assertEqual($fields['small_int']['key'], 'index');

		$this->Dbo->rawQuery('CREATE UNIQUE INDEX pointless_small_int on with_multiple_compound_keys (small_int);');

		$fields = $this->Dbo->describe('with_multiple_compound_keys');
		$this->assertEqual($fields['bool']['key'], 'index');
		$this->assertEqual($fields['small_int']['key'], 'unique');

		$this->Dbo->rawQuery("DROP TABLE $name");
	}

/**
 * Test virtualFields with functions.
 *
 * @return void
 */
	public function testVirtualFieldWithFunction() {
		$this->loadFixtures('User');
		$User = ClassRegistry::init('User');
		$User->virtualFields = array('name' => 'SUBSTR(User.user, 5, LENGTH(User.user) - 4)');

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

/**
 * Test nested transaction
 *
 * @return void
 */
	public function testNestedTransaction() {
		$this->skipIf($this->Dbo->nestedTransactionSupported() === false, 'The Sqlite version do not support nested transaction');

		$this->loadFixtures('User');
		$model = new User();
		$model->hasOne = $model->hasMany = $model->belongsTo = $model->hasAndBelongsToMany = array();
		$model->cacheQueries = false;
		$this->Dbo->cacheMethods = false;

		$this->assertTrue($this->Dbo->begin());
		$this->assertNotEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->begin());
		$this->assertTrue($model->delete(1));
		$this->assertEmpty($model->read(null, 1));
		$this->assertTrue($this->Dbo->rollback());
		$this->assertNotEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->begin());
		$this->assertTrue($model->delete(1));
		$this->assertEmpty($model->read(null, 1));
		$this->assertTrue($this->Dbo->commit());
		$this->assertEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->rollback());
		$this->assertNotEmpty($model->read(null, 1));
	}

}
