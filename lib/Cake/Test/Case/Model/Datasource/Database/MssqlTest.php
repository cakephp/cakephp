<?php
/**
 * MssqlTest file
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

App::uses('Model', 'Model');
App::uses('Mssql', 'Model/Datasource/Database');

/**
 * MssqlTestDb class
 *
 * @package       cake.tests.cases.libs.model.datasources.dbo
 */
class MssqlTestDb extends Mssql {

/**
 * simulated property
 *
 * @var array
 */
	public $simulated = array();

/**
 * execute results stack
 *
 * @var array
 */
	public $executeResultsStack = array();

/**
 * execute method
 *
 * @param mixed $sql
 * @return mixed
 */
	protected function _execute($sql) {
		$this->simulated[] = $sql;
		return empty($this->executeResultsStack) ? null : array_pop($this->executeResultsStack);
	}

/**
 * fetchAll method
 *
 * @param mixed $sql
 * @return void
 */
	protected function _matchRecords($model, $conditions = null) {
		return $this->conditions(array('id' => array(1, 2)));
	}

/**
 * getLastQuery method
 *
 * @return string
 */
	public function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}

/**
 * getPrimaryKey method
 *
 * @param mixed $model
 * @return string
 */
	public function getPrimaryKey($model) {
		return parent::_getPrimaryKey($model);
	}

/**
 * clearFieldMappings method
 *
 * @return void
 */
	public function clearFieldMappings() {
		$this->_fieldMappings = array();
	}
	
/**
 * describe method
 *
 * @param object $model
 * @return void
 */
	public function describe($model) {
		return empty($this->describe) ? parent::describe($model) : $this->describe;
	}
}

/**
 * MssqlTestModel class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class MssqlTestModel extends Model {

/**
 * name property
 *
 * @var string 'MssqlTestModel'
 */
	public $name = 'MssqlTestModel';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * _schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'),
		'client_id'	=> array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
		'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'login'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'passwd'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_1'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_2'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
		'zip_code'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'city'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'country'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'phone'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'fax'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'url'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'comments'	=> array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
		'last_login'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
		'created'	=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'MssqlClientTestModel' => array(
			'foreignKey' => 'client_id'
		)
	);
/**
 * find method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @return void
 */
	public function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @return array
 */
	public function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}
}

/**
 * MssqlClientTestModel class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class MssqlClientTestModel extends Model {
/**
 * name property
 *
 * @var string 'MssqlAssociatedTestModel'
 */
	public $name = 'MssqlClientTestModel';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * _schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'),
		'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'created'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
		'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
}

/**
 * MssqlTestResultIterator class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class MssqlTestResultIterator extends ArrayIterator {
/**
 * closeCursor method
 *
 * @return void
 */
	public function closeCursor() {}
}

/**
 * MssqlTest class
 *
 * @package       cake.tests.cases.libs.model.datasources.dbo
 */
class MssqlTest extends CakeTestCase {

/**
 * The Dbo instance to be tested
 *
 * @var DboSource
 */
	public $db = null;

/**
 * autoFixtures property
 *
 * @var bool false
 */
	public $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.category');

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function setUp() {
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!($this->Dbo instanceof Mssql)) {
			$this->markTestSkipped('Please configure the test datasource to use SQL Server.');
		}
		$this->db = new MssqlTestDb($this->Dbo->config);
		$this->model = new MssqlTestModel();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Dbo);
		unset($this->db);
		unset($this->model);
	}

/**
 * testQuoting method
 *
 * @return void
 */
	public function testQuoting() {
		$expected = "1.2";
		$result = $this->db->value(1.2, 'float');
		$this->assertIdentical($expected, $result);

		$expected = "'1,2'";
		$result = $this->db->value('1,2', 'float');
		$this->assertIdentical($expected, $result);

		$expected = 'NULL';
		$result = $this->db->value('', 'integer');
		$this->assertIdentical($expected, $result);

		$expected = 'NULL';
		$result = $this->db->value('', 'float');
		$this->assertIdentical($expected, $result);

		$expected = 'NULL';
		$result = $this->db->value('', 'binary');
		$this->assertIdentical($expected, $result);
	}
/**
 * testFields method
 *
 * @return void
 */
	public function testFields() {
		$fields = array(
			'[MssqlTestModel].[id] AS [MssqlTestModel__0]',
			'[MssqlTestModel].[client_id] AS [MssqlTestModel__1]',
			'[MssqlTestModel].[name] AS [MssqlTestModel__2]',
			'[MssqlTestModel].[login] AS [MssqlTestModel__3]',
			'[MssqlTestModel].[passwd] AS [MssqlTestModel__4]',
			'[MssqlTestModel].[addr_1] AS [MssqlTestModel__5]',
			'[MssqlTestModel].[addr_2] AS [MssqlTestModel__6]',
			'[MssqlTestModel].[zip_code] AS [MssqlTestModel__7]',
			'[MssqlTestModel].[city] AS [MssqlTestModel__8]',
			'[MssqlTestModel].[country] AS [MssqlTestModel__9]',
			'[MssqlTestModel].[phone] AS [MssqlTestModel__10]',
			'[MssqlTestModel].[fax] AS [MssqlTestModel__11]',
			'[MssqlTestModel].[url] AS [MssqlTestModel__12]',
			'[MssqlTestModel].[email] AS [MssqlTestModel__13]',
			'[MssqlTestModel].[comments] AS [MssqlTestModel__14]',
			'CONVERT(VARCHAR(20), [MssqlTestModel].[last_login], 20) AS [MssqlTestModel__15]',
			'[MssqlTestModel].[created] AS [MssqlTestModel__16]',
			'CONVERT(VARCHAR(20), [MssqlTestModel].[updated], 20) AS [MssqlTestModel__17]'
		);

		$result = $this->db->fields($this->model);
		$expected = $fields;
		$this->assertEqual($expected, $result);

		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, 'MssqlTestModel.*');
		$expected = $fields;
		$this->assertEqual($expected, $result);

		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, array('*', 'AnotherModel.id', 'AnotherModel.name'));
		$expected = array_merge($fields, array(
			'[AnotherModel].[id] AS [AnotherModel__18]',
			'[AnotherModel].[name] AS [AnotherModel__19]'));
		$this->assertEqual($expected, $result);

		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, array('*', 'MssqlClientTestModel.*'));
		$expected = array_merge($fields, array(
			'[MssqlClientTestModel].[id] AS [MssqlClientTestModel__18]',
			'[MssqlClientTestModel].[name] AS [MssqlClientTestModel__19]',
			'[MssqlClientTestModel].[email] AS [MssqlClientTestModel__20]',
			'CONVERT(VARCHAR(20), [MssqlClientTestModel].[created], 20) AS [MssqlClientTestModel__21]',
			'CONVERT(VARCHAR(20), [MssqlClientTestModel].[updated], 20) AS [MssqlClientTestModel__22]'));
		$this->assertEqual($expected, $result);
	}

/**
 * testDistinctFields method
 *
 * @return void
 */
	public function testDistinctFields() {
		$result = $this->db->fields($this->model, null, array('DISTINCT Car.country_code'));
		$expected = array('DISTINCT [Car].[country_code] AS [Car__0]');
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($this->model, null, 'DISTINCT Car.country_code');
		$expected = array('DISTINCT [Car].[country_code] AS [Car__1]');
		$this->assertEqual($expected, $result);
	}

/**
 * testDistinctWithLimit method
 *
 * @return void
 */
	public function testDistinctWithLimit() {
		$this->db->read($this->model, array(
			'fields' => array('DISTINCT MssqlTestModel.city', 'MssqlTestModel.country'),
			'limit' => 5
		));
		$result = $this->db->getLastQuery();
		$this->assertPattern('/^SELECT DISTINCT TOP 5/', $result);
	}

/**
 * testDescribe method
 *
 * @return void
 */
	public function testDescribe() {
		$MssqlTableDescription = new MssqlTestResultIterator(array(
			(object) array(
				'Default' => '((0))',
				'Field' => 'count',
				'Key' => 0,
				'Length' => '4',
				'Null' => 'NO',
				'Type' => 'integer'
			)
		));
		$this->db->executeResultsStack = array($MssqlTableDescription);
		$dummyModel = $this->model;
		$result = $this->db->describe($dummyModel);
		$expected = array(
			'count' => array(
				'type' => 'integer',
				'null' => false,
				'default' => '0',
				'length' => 4
			)
		);
		$this->assertEqual($expected, $result);
	}
/**
 * testBuildColumn
 *
 * @return void
 */
	public function testBuildColumn() {
		$column = array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '8', 'key' => 'primary');
		$result = $this->db->buildColumn($column);
		$expected = '[id] int IDENTITY (1, 1) NOT NULL';
		$this->assertEqual($expected, $result);

		$column = array('name' => 'client_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '11');
		$result = $this->db->buildColumn($column);
		$expected = '[client_id] int DEFAULT 0 NOT NULL';
		$this->assertEqual($expected, $result);

		$column = array('name' => 'client_id', 'type' => 'integer', 'null' => true);
		$result = $this->db->buildColumn($column);
		$expected = '[client_id] int NULL';
		$this->assertEqual($expected, $result);

		// 'name' => 'type' format for columns
		$column = array('type' => 'integer', 'name' => 'client_id');
		$result = $this->db->buildColumn($column);
		$expected = '[client_id] int NULL';
		$this->assertEqual($expected, $result);

		$column = array('type' => 'string', 'name' => 'name');
		$result = $this->db->buildColumn($column);
		$expected = '[name] varchar(255) NULL';
		$this->assertEqual($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] varchar(255) DEFAULT \'\' NOT NULL';
		$this->assertEqual($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => false, 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] varchar(255) NOT NULL';
		$this->assertEqual($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => false, 'default' => null, 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] varchar(255) NOT NULL';
		$this->assertEqual($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => null, 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] varchar(255) NULL';
		$this->assertEqual($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => '', 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] varchar(255) DEFAULT \'\'';
		$this->assertEqual($expected, $result);
	}
/**
 * testBuildIndex method
 *
 * @return void
 */
	public function testBuildIndex() {
		$indexes = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'client_id' => array('column' => 'client_id', 'unique' => 1)
		);
		$result = $this->db->buildIndex($indexes, 'items');
		$expected = array(
			'PRIMARY KEY ([id])',
			'ALTER TABLE items ADD CONSTRAINT client_id UNIQUE([client_id]);'
		);
		$this->assertEqual($expected, $result);

		$indexes = array('client_id' => array('column' => 'client_id'));
		$result = $this->db->buildIndex($indexes, 'items');
		$this->assertEqual($result, array());

		$indexes = array('client_id' => array('column' => array('client_id', 'period_id'), 'unique' => 1));
		$result = $this->db->buildIndex($indexes, 'items');
		$expected = array('ALTER TABLE items ADD CONSTRAINT client_id UNIQUE([client_id], [period_id]);');
		$this->assertEqual($expected, $result);
	}
/**
 * testUpdateAllSyntax method
 *
 * @return void
 */
	public function testUpdateAllSyntax() {
		$fields = array('MssqlTestModel.client_id' => '[MssqlTestModel].[client_id] + 1');
		$conditions = array('MssqlTestModel.updated <' => date('2009-01-01 00:00:00'));
		$this->db->update($this->model, $fields, null, $conditions);

		$result = $this->db->getLastQuery();
		$this->assertNoPattern('/MssqlTestModel/', $result);
		$this->assertPattern('/^UPDATE \[mssql_test_models\]/', $result);
		$this->assertPattern('/SET \[client_id\] = \[client_id\] \+ 1/', $result);
	}

/**
 * testGetPrimaryKey method
 *
 * @return void
 */
	public function testGetPrimaryKey() {
		$schema = $this->model->schema();
		
		$this->db->describe = $schema;
		$result = $this->db->getPrimaryKey($this->model);
		$this->assertEqual($result, 'id');
		
		unset($schema['id']['key']);
		$this->db->describe = $schema;
		$result = $this->db->getPrimaryKey($this->model);
		$this->assertNull($result);
	}

/**
 * testInsertMulti
 *
 * @return void
 */
	public function testInsertMulti() {
		$this->db->describe = $this->model->schema();
		
		$fields = array('id', 'name', 'login');
		$values = array(
			array(1, 'Larry', 'PhpNut'),
			array(2, 'Renan', 'renan.saddam'));
		$this->db->simulated = array();
		$this->db->insertMulti($this->model, $fields, $values);
		$result = $this->db->simulated;
		$expected = array(
			'SET IDENTITY_INSERT [mssql_test_models] ON',
			"INSERT INTO [mssql_test_models] ([id], [name], [login]) VALUES (1, 'Larry', 'PhpNut')",
			"INSERT INTO [mssql_test_models] ([id], [name], [login]) VALUES (2, 'Renan', 'renan.saddam')",
			'SET IDENTITY_INSERT [mssql_test_models] OFF'
		);
		$this->assertEqual($expected, $result);

		$fields = array('name', 'login');
		$values = array(
			array('Larry', 'PhpNut'),
			array('Renan', 'renan.saddam'));
		$this->db->simulated = array();
		$this->db->insertMulti($this->model, $fields, $values);
		$result = $this->db->simulated;
		$expected = array(
			"INSERT INTO [mssql_test_models] ([name], [login]) VALUES ('Larry', 'PhpNut')",
			"INSERT INTO [mssql_test_models] ([name], [login]) VALUES ('Renan', 'renan.saddam')",
		);
		$this->assertEqual($expected, $result);
	}
}
