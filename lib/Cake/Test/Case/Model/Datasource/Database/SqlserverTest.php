<?php
/**
 * SqlserverTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Sqlserver', 'Model/Datasource/Database');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';

/**
 * SqlserverTestDb class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class SqlserverTestDb extends Sqlserver {

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
 * @param mixed $params
 * @param mixed $prepareOptions
 * @return mixed
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$this->simulated[] = $sql;
		return empty($this->executeResultsStack) ? null : array_pop($this->executeResultsStack);
	}

/**
 * fetchAll method
 *
 * @param mixed $sql
 * @return void
 */
	protected function _matchRecords(Model $model, $conditions = null) {
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
 * SqlserverTestModel class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class SqlserverTestModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'SqlserverTestModel'
 */
	public $name = 'SqlserverTestModel';

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
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'),
		'client_id' => array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'login' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'passwd' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_1' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_2' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
		'zip_code' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'city' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'country' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'phone' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'fax' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'url' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'comments' => array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
		'last_login' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'SqlserverClientTestModel' => array(
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

}

/**
 * SqlserverClientTestModel class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class SqlserverClientTestModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'SqlserverAssociatedTestModel'
 */
	public $name = 'SqlserverClientTestModel';

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
 * SqlserverTestResultIterator class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class SqlserverTestResultIterator extends ArrayIterator {

/**
 * closeCursor method
 *
 * @return void
 */
	public function closeCursor() {
	}

/**
 * fetch method
 *
 * @return void
 */
	public function fetch() {
		if (!$this->valid()) {
			return null;
		}
		$current = $this->current();
		$this->next();
		return $current;
	}

}

/**
 * SqlserverTest class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class SqlserverTest extends CakeTestCase {

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
	public $fixtures = array('core.user', 'core.category', 'core.author', 'core.post');

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function setUp() {
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!($this->Dbo instanceof Sqlserver)) {
			$this->markTestSkipped('Please configure the test datasource to use SQL Server.');
		}
		$this->db = new SqlserverTestDb($this->Dbo->config);
		$this->model = new SqlserverTestModel();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Dbo);
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
		$this->assertSame($expected, $result);

		$expected = "'1,2'";
		$result = $this->db->value('1,2', 'float');
		$this->assertSame($expected, $result);

		$expected = 'NULL';
		$result = $this->db->value('', 'integer');
		$this->assertSame($expected, $result);

		$expected = 'NULL';
		$result = $this->db->value('', 'float');
		$this->assertSame($expected, $result);

		$expected = "''";
		$result = $this->db->value('', 'binary');
		$this->assertSame($expected, $result);
	}

/**
 * testFields method
 *
 * @return void
 */
	public function testFields() {
		$fields = array(
			'[SqlserverTestModel].[id] AS [SqlserverTestModel__id]',
			'[SqlserverTestModel].[client_id] AS [SqlserverTestModel__client_id]',
			'[SqlserverTestModel].[name] AS [SqlserverTestModel__name]',
			'[SqlserverTestModel].[login] AS [SqlserverTestModel__login]',
			'[SqlserverTestModel].[passwd] AS [SqlserverTestModel__passwd]',
			'[SqlserverTestModel].[addr_1] AS [SqlserverTestModel__addr_1]',
			'[SqlserverTestModel].[addr_2] AS [SqlserverTestModel__addr_2]',
			'[SqlserverTestModel].[zip_code] AS [SqlserverTestModel__zip_code]',
			'[SqlserverTestModel].[city] AS [SqlserverTestModel__city]',
			'[SqlserverTestModel].[country] AS [SqlserverTestModel__country]',
			'[SqlserverTestModel].[phone] AS [SqlserverTestModel__phone]',
			'[SqlserverTestModel].[fax] AS [SqlserverTestModel__fax]',
			'[SqlserverTestModel].[url] AS [SqlserverTestModel__url]',
			'[SqlserverTestModel].[email] AS [SqlserverTestModel__email]',
			'[SqlserverTestModel].[comments] AS [SqlserverTestModel__comments]',
			'CONVERT(VARCHAR(20), [SqlserverTestModel].[last_login], 20) AS [SqlserverTestModel__last_login]',
			'[SqlserverTestModel].[created] AS [SqlserverTestModel__created]',
			'CONVERT(VARCHAR(20), [SqlserverTestModel].[updated], 20) AS [SqlserverTestModel__updated]'
		);

		$result = $this->db->fields($this->model);
		$expected = $fields;
		$this->assertEquals($expected, $result);

		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, 'SqlserverTestModel.*');
		$expected = $fields;
		$this->assertEquals($expected, $result);

		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, array('*', 'AnotherModel.id', 'AnotherModel.name'));
		$expected = array_merge($fields, array(
			'[AnotherModel].[id] AS [AnotherModel__id]',
			'[AnotherModel].[name] AS [AnotherModel__name]'));
		$this->assertEquals($expected, $result);

		$this->db->clearFieldMappings();
		$result = $this->db->fields($this->model, null, array('*', 'SqlserverClientTestModel.*'));
		$expected = array_merge($fields, array(
			'[SqlserverClientTestModel].[id] AS [SqlserverClientTestModel__id]',
			'[SqlserverClientTestModel].[name] AS [SqlserverClientTestModel__name]',
			'[SqlserverClientTestModel].[email] AS [SqlserverClientTestModel__email]',
			'CONVERT(VARCHAR(20), [SqlserverClientTestModel].[created], 20) AS [SqlserverClientTestModel__created]',
			'CONVERT(VARCHAR(20), [SqlserverClientTestModel].[updated], 20) AS [SqlserverClientTestModel__updated]'));
		$this->assertEquals($expected, $result);
	}

/**
 * testDistinctFields method
 *
 * @return void
 */
	public function testDistinctFields() {
		$result = $this->db->fields($this->model, null, array('DISTINCT Car.country_code'));
		$expected = array('DISTINCT [Car].[country_code] AS [Car__country_code]');
		$this->assertEquals($expected, $result);

		$result = $this->db->fields($this->model, null, 'DISTINCT Car.country_code');
		$expected = array('DISTINCT [Car].[country_code] AS [Car__country_code]');
		$this->assertEquals($expected, $result);
	}

/**
 * testDistinctWithLimit method
 *
 * @return void
 */
	public function testDistinctWithLimit() {
		$this->db->read($this->model, array(
			'fields' => array('DISTINCT SqlserverTestModel.city', 'SqlserverTestModel.country'),
			'limit' => 5
		));
		$result = $this->db->getLastQuery();
		$this->assertRegExp('/^SELECT DISTINCT TOP 5/', $result);
	}

/**
 * testDescribe method
 *
 * @return void
 */
	public function testDescribe() {
		$SqlserverTableDescription = new SqlserverTestResultIterator(array(
			(object)array(
				'Default' => '((0))',
				'Field' => 'count',
				'Key' => 0,
				'Length' => '4',
				'Null' => 'NO',
				'Type' => 'integer'
			),
			(object)array(
				'Default' => '',
				'Field' => 'body',
				'Key' => 0,
				'Length' => '-1',
				'Null' => 'YES',
				'Type' => 'nvarchar'
			),
			(object)array(
				'Default' => '',
				'Field' => 'published',
				'Key' => 0,
				'Type' => 'datetime2',
				'Length' => 8,
				'Null' => 'YES',
				'Size' => ''
			),
			(object)array(
				'Default' => '',
				'Field' => 'id',
				'Key' => 1,
				'Type' => 'nchar',
				'Length' => 72,
				'Null' => 'NO',
				'Size' => ''
			),
			(object)array(
				'Default' => null,
				'Field' => 'parent_id',
				'Key' => '0',
				'Type' => 'bigint',
				'Length' => 8,
				'Null' => 'YES',
				'Size' => '0',
			),
		));
		$this->db->executeResultsStack = array($SqlserverTableDescription);
		$dummyModel = $this->model;
		$result = $this->db->describe($dummyModel);
		$expected = array(
			'count' => array(
				'type' => 'integer',
				'null' => false,
				'default' => '0',
				'length' => 4
			),
			'body' => array(
				'type' => 'text',
				'null' => true,
				'default' => null,
				'length' => null
			),
			'published' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => '',
				'length' => null
			),
			'id' => array(
				'type' => 'string',
				'null' => false,
				'default' => '',
				'length' => 36,
				'key' => 'primary'
			),
			'parent_id' => array(
				'type' => 'biginteger',
				'null' => true,
				'default' => null,
				'length' => 8,
			),
		);
		$this->assertEquals($expected, $result);
		$this->assertSame($expected['parent_id'], $result['parent_id']);
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
		$this->assertEquals($expected, $result);

		$column = array('name' => 'client_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '11');
		$result = $this->db->buildColumn($column);
		$expected = '[client_id] int DEFAULT 0 NOT NULL';
		$this->assertEquals($expected, $result);

		$column = array('name' => 'client_id', 'type' => 'integer', 'null' => true);
		$result = $this->db->buildColumn($column);
		$expected = '[client_id] int NULL';
		$this->assertEquals($expected, $result);

		// 'name' => 'type' format for columns
		$column = array('type' => 'integer', 'name' => 'client_id');
		$result = $this->db->buildColumn($column);
		$expected = '[client_id] int NULL';
		$this->assertEquals($expected, $result);

		$column = array('type' => 'string', 'name' => 'name');
		$result = $this->db->buildColumn($column);
		$expected = '[name] nvarchar(255) NULL';
		$this->assertEquals($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] nvarchar(255) DEFAULT \'\' NOT NULL';
		$this->assertEquals($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => false, 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] nvarchar(255) NOT NULL';
		$this->assertEquals($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => false, 'default' => null, 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] nvarchar(255) NOT NULL';
		$this->assertEquals($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => null, 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] nvarchar(255) NULL';
		$this->assertEquals($expected, $result);

		$column = array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => '', 'length' => '255');
		$result = $this->db->buildColumn($column);
		$expected = '[name] nvarchar(255) DEFAULT \'\'';
		$this->assertEquals($expected, $result);

		$column = array('name' => 'body', 'type' => 'text');
		$result = $this->db->buildColumn($column);
		$expected = '[body] nvarchar(MAX)';
		$this->assertEquals($expected, $result);

		$column = array(
			'name' => 'checked',
			'type' => 'boolean',
			'length' => 10,
			'default' => '1'
		);
		$result = $this->db->buildColumn($column);
		$expected = "[checked] bit DEFAULT '1'";
		$this->assertEquals($expected, $result);

		$column = array(
			'name' => 'huge',
			'type' => 'biginteger',
		);
		$result = $this->db->buildColumn($column);
		$expected = "[huge] bigint";
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$indexes = array('client_id' => array('column' => 'client_id'));
		$result = $this->db->buildIndex($indexes, 'items');
		$this->assertSame(array(), $result);

		$indexes = array('client_id' => array('column' => array('client_id', 'period_id'), 'unique' => 1));
		$result = $this->db->buildIndex($indexes, 'items');
		$expected = array('ALTER TABLE items ADD CONSTRAINT client_id UNIQUE([client_id], [period_id]);');
		$this->assertEquals($expected, $result);
	}

/**
 * testUpdateAllSyntax method
 *
 * @return void
 */
	public function testUpdateAllSyntax() {
		$fields = array('SqlserverTestModel.client_id' => '[SqlserverTestModel].[client_id] + 1');
		$conditions = array('SqlserverTestModel.updated <' => date('2009-01-01 00:00:00'));
		$this->db->update($this->model, $fields, null, $conditions);

		$result = $this->db->getLastQuery();
		$this->assertNotRegExp('/SqlserverTestModel/', $result);
		$this->assertRegExp('/^UPDATE \[sqlserver_test_models\]/', $result);
		$this->assertRegExp('/SET \[client_id\] = \[client_id\] \+ 1/', $result);
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
		$this->assertEquals('id', $result);

		unset($schema['id']['key']);
		$this->db->describe = $schema;
		$result = $this->db->getPrimaryKey($this->model);
		$this->assertNull($result);
	}

/**
 * SQL server < 11 doesn't have proper limit/offset support, test that our hack works.
 *
 * @return void
 */
	public function testLimitOffsetHack() {
		$this->loadFixtures('Author', 'Post', 'User');
		$query = array(
			'limit' => 2,
			'page' => 1,
			'order' => 'User.user ASC',
		);
		$User = ClassRegistry::init('User');
		$results = $User->find('all', $query);

		$this->assertEquals(2, count($results));
		$this->assertEquals('garrett', $results[0]['User']['user']);
		$this->assertEquals('larry', $results[1]['User']['user']);

		$query = array(
			'limit' => 2,
			'page' => 2,
			'order' => 'User.user ASC',
		);
		$User = ClassRegistry::init('User');
		$results = $User->find('all', $query);

		$this->assertEquals(2, count($results));
		$this->assertFalse(isset($results[0][0]));
		$this->assertEquals('mariano', $results[0]['User']['user']);
		$this->assertEquals('nate', $results[1]['User']['user']);
	}

/**
 * Test that the return of stored procedures is honoured
 *
 * @return void
 */
	public function testStoredProcedureReturn() {
		$sql = <<<SQL
CREATE PROCEDURE cake_test_procedure
AS
BEGIN
RETURN 2;
END
SQL;
		$this->Dbo->execute($sql);

		$sql = <<<SQL
DECLARE @return_value int
EXEC @return_value = [cake_test_procedure]
SELECT 'value' = @return_value
SQL;
		$query = $this->Dbo->execute($sql);
		$this->Dbo->execute('DROP PROC cake_test_procedure');

		$result = $query->fetch();
		$this->assertEquals(2, $result['value']);
	}

}
