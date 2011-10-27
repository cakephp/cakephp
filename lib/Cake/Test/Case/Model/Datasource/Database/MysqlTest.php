<?php
/**
 * DboMysqlTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Mysql', 'Model/Datasource/Database');
App::uses('CakeSchema', 'Model');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';

/**
 * DboMysqlTest class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class MysqlTest extends CakeTestCase {
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
	public $fixtures = array(
		'core.apple', 'core.article', 'core.articles_tag', 'core.attachment', 'core.comment',
		'core.sample', 'core.tag', 'core.user', 'core.post', 'core.author', 'core.data_test',
		'core.binary_test'
	);

/**
 * The Dbo instance to be tested
 *
 * @var DboSource
 */
	public $Dbo = null;

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function setUp() {
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!($this->Dbo instanceof Mysql)) {
			$this->markTestSkipped('The MySQL extension is not available.');
		}
		$this->_debug = Configure::read('debug');
		Configure::write('debug', 1);
		$this->model = ClassRegistry::init('MysqlTestModel');
	}

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function tearDown() {
		unset($this->model);
		ClassRegistry::flush();
		Configure::write('debug', $this->_debug);
	}

/**
 * Test Dbo value method
 *
 * @group quoting
 */
	public function testQuoting() {
		$result = $this->Dbo->fields($this->model);
		$expected = array(
			'`MysqlTestModel`.`id`',
			'`MysqlTestModel`.`client_id`',
			'`MysqlTestModel`.`name`',
			'`MysqlTestModel`.`login`',
			'`MysqlTestModel`.`passwd`',
			'`MysqlTestModel`.`addr_1`',
			'`MysqlTestModel`.`addr_2`',
			'`MysqlTestModel`.`zip_code`',
			'`MysqlTestModel`.`city`',
			'`MysqlTestModel`.`country`',
			'`MysqlTestModel`.`phone`',
			'`MysqlTestModel`.`fax`',
			'`MysqlTestModel`.`url`',
			'`MysqlTestModel`.`email`',
			'`MysqlTestModel`.`comments`',
			'`MysqlTestModel`.`last_login`',
			'`MysqlTestModel`.`created`',
			'`MysqlTestModel`.`updated`'
		);
		$this->assertEqual($expected, $result);

		$expected = 1.2;
		$result = $this->Dbo->value(1.2, 'float');
		$this->assertEqual($expected, $result);

		$expected = "'1,2'";
		$result = $this->Dbo->value('1,2', 'float');
		$this->assertEqual($expected, $result);

		$expected = "'4713e29446'";
		$result = $this->Dbo->value('4713e29446');

		$this->assertEqual($expected, $result);

		$expected = 'NULL';
		$result = $this->Dbo->value('', 'integer');
		$this->assertEqual($expected, $result);

		$expected = "'0'";
		$result = $this->Dbo->value('', 'boolean');
		$this->assertEqual($expected, $result);

		$expected = 10010001;
		$result = $this->Dbo->value(10010001);
		$this->assertEqual($expected, $result);

		$expected = "'00010010001'";
		$result = $this->Dbo->value('00010010001');
		$this->assertEqual($expected, $result);
	}

/**
 * test that localized floats don't cause trouble.
 *
 * @group quoting
 * @return void
 */
	public function testLocalizedFloats() {
		$this->skipIf(DS === '\\', 'The locale is not supported in Windows and affect the others tests.');

		$restore = setlocale(LC_ALL, null);
		setlocale(LC_ALL, 'de_DE');

		$result = $this->Dbo->value(3.141593);
		$this->assertEquals('3.141593', $result);

		$result = $this->db->value(3.141593, 'float');
		$this->assertEquals('3.141593', $result);

		$result = $this->db->value(1234567.11, 'float');
		$this->assertEquals('1234567.11', $result);

		$result = $this->db->value(123456.45464748, 'float');
		$this->assertContains('123456.454647', $result);

		$result = $this->db->value(0.987654321, 'float');
		$this->assertEquals('0.987654321', (string)$result);

		$result = $this->db->value(2.2E-54, 'float');
		$this->assertEquals('2.2E-54', (string)$result);

		$result = $this->db->value(2.2E-54);
		$this->assertEquals('2.2E-54', (string)$result);

		setlocale(LC_ALL, $restore);
	}

/**
 * test that scientific notations are working correctly
 *
 * @return void
 */
	function testScientificNotation() {
		$result = $this->db->value(2.2E-54, 'float');
		$this->assertEqual('2.2E-54', (string)$result);

		$result = $this->db->value(2.2E-54);
		$this->assertEqual('2.2E-54', (string)$result);
	}

/**
 * testTinyintCasting method
 *
 *
 * @return void
 */
	public function testTinyintCasting() {
		$this->Dbo->cacheSources = false;
		$tableName = 'tinyint_' . uniqid();
		$this->Dbo->rawQuery('CREATE TABLE ' . $this->Dbo->fullTableName($tableName) . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));');

		$this->model = new CakeTestModel(array(
			'name' => 'Tinyint', 'table' => $tableName, 'ds' => 'test'
		));

		$result = $this->model->schema();
		$this->assertEqual($result['bool']['type'], 'boolean');
		$this->assertEqual($result['small_int']['type'], 'integer');

		$this->assertTrue((bool)$this->model->save(array('bool' => 5, 'small_int' => 5)));
		$result = $this->model->find('first');
		$this->assertIdentical($result['Tinyint']['bool'], true);
		$this->assertIdentical($result['Tinyint']['small_int'], '5');
		$this->model->deleteAll(true);

		$this->assertTrue((bool)$this->model->save(array('bool' => 0, 'small_int' => 100)));
		$result = $this->model->find('first');
		$this->assertIdentical($result['Tinyint']['bool'], false);
		$this->assertIdentical($result['Tinyint']['small_int'], '100');
		$this->model->deleteAll(true);

		$this->assertTrue((bool)$this->model->save(array('bool' => true, 'small_int' => 0)));
		$result = $this->model->find('first');
		$this->assertIdentical($result['Tinyint']['bool'], true);
		$this->assertIdentical($result['Tinyint']['small_int'], '0');
		$this->model->deleteAll(true);

		$this->Dbo->rawQuery('DROP TABLE ' . $this->Dbo->fullTableName($tableName));
	}

/**
 * testIndexDetection method
 *
 * @group indices
 * @return void
 */
	public function testIndexDetection() {
		$this->Dbo->cacheSources = false;

		$name = $this->Dbo->fullTableName('simple');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));');
		$expected = array('PRIMARY' => array('column' => 'id', 'unique' => 1));
		$result = $this->Dbo->index('simple', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEqual($expected, $result);


		$name = $this->Dbo->fullTableName('with_a_key');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
		);
		$result = $this->Dbo->index('with_a_key', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEqual($expected, $result);

		$name = $this->Dbo->fullTableName('with_two_keys');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
		);
		$result = $this->Dbo->index('with_two_keys', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEqual($expected, $result);

		$name = $this->Dbo->fullTableName('with_compound_keys');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ), KEY `one_way` ( `bool`, `small_int` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
			'one_way' => array('column' => array('bool', 'small_int'), 'unique' => 0),
		);
		$result = $this->Dbo->index('with_compound_keys', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEqual($expected, $result);

		$name = $this->Dbo->fullTableName('with_multiple_compound_keys');
		$this->Dbo->rawQuery('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ), KEY `one_way` ( `bool`, `small_int` ), KEY `other_way` ( `small_int`, `bool` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
			'one_way' => array('column' => array('bool', 'small_int'), 'unique' => 0),
			'other_way' => array('column' => array('small_int', 'bool'), 'unique' => 0),
		);
		$result = $this->Dbo->index('with_multiple_compound_keys', false);
		$this->Dbo->rawQuery('DROP TABLE ' . $name);
		$this->assertEqual($expected, $result);
	}

/**
 * testBuildColumn method
 *
 * @return void
 */
	public function testBuildColumn() {
		$restore = $this->Dbo->columns;
		$this->Dbo->columns = array('varchar(255)' => 1);
		$data = array(
			'name' => 'testName',
			'type' => 'varchar(255)',
			'default',
			'null' => true,
			'key',
			'comment' => 'test'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`testName`  DEFAULT NULL COMMENT \'test\'';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'testName',
			'type' => 'varchar(255)',
			'default',
			'null' => true,
			'key',
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`testName`  CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL';
		$this->assertEqual($expected, $result);
		$this->Dbo->columns = $restore;
	}

/**
 * MySQL 4.x returns index data in a different format,
 * Using a mock ensure that MySQL 4.x output is properly parsed.
 *
 * @group indices
 * @return void
 */
	public function testIndexOnMySQL4Output() {
		$name = $this->Dbo->fullTableName('simple');

		$mockDbo = $this->getMock('Mysql', array('connect', '_execute', 'getVersion'));
		$columnData = array(
			array('0' => array(
				'Table' => 'with_compound_keys',
				'Non_unique' => '0',
				'Key_name' => 'PRIMARY',
				'Seq_in_index' => '1',
				'Column_name' => 'id',
				'Collation' => 'A',
				'Cardinality' => '0',
				'Sub_part' => NULL,
				'Packed' => NULL,
				'Null' => '',
				'Index_type' => 'BTREE',
				'Comment' => ''
			)),
			array('0' => array(
				'Table' => 'with_compound_keys',
				'Non_unique' => '1',
				'Key_name' => 'pointless_bool',
				'Seq_in_index' => '1',
				'Column_name' => 'bool',
				'Collation' => 'A',
				'Cardinality' => NULL,
				'Sub_part' => NULL,
				'Packed' => NULL,
				'Null' => 'YES',
				'Index_type' => 'BTREE',
				'Comment' => ''
			)),
			array('0' => array(
				'Table' => 'with_compound_keys',
				'Non_unique' => '1',
				'Key_name' => 'pointless_small_int',
				'Seq_in_index' => '1',
				'Column_name' => 'small_int',
				'Collation' => 'A',
				'Cardinality' => NULL,
				'Sub_part' => NULL,
				'Packed' => NULL,
				'Null' => 'YES',
				'Index_type' => 'BTREE',
				'Comment' => ''
			)),
			array('0' => array(
				'Table' => 'with_compound_keys',
				'Non_unique' => '1',
				'Key_name' => 'one_way',
				'Seq_in_index' => '1',
				'Column_name' => 'bool',
				'Collation' => 'A',
				'Cardinality' => NULL,
				'Sub_part' => NULL,
				'Packed' => NULL,
				'Null' => 'YES',
				'Index_type' => 'BTREE',
				'Comment' => ''
			)),
			array('0' => array(
				'Table' => 'with_compound_keys',
				'Non_unique' => '1',
				'Key_name' => 'one_way',
				'Seq_in_index' => '2',
				'Column_name' => 'small_int',
				'Collation' => 'A',
				'Cardinality' => NULL,
				'Sub_part' => NULL,
				'Packed' => NULL,
				'Null' => 'YES',
				'Index_type' => 'BTREE',
				'Comment' => ''
			))
		);

		$mockDbo->expects($this->once())->method('getVersion')->will($this->returnValue('4.1'));
		$resultMock = $this->getMock('PDOStatement', array('fetch'));
		$mockDbo->expects($this->once())
			->method('_execute')
			->with('SHOW INDEX FROM ' . $name)
			->will($this->returnValue($resultMock));

		foreach ($columnData as $i => $data) {
			$resultMock->expects($this->at($i))->method('fetch')->will($this->returnValue((object) $data));
		}

		$result = $mockDbo->index($name, false);
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
			'one_way' => array('column' => array('bool', 'small_int'), 'unique' => 0),
		);
		$this->assertEqual($expected, $result);
	}

/**
 * testColumn method
 *
 * @return void
 */
	public function testColumn() {
		$result = $this->Dbo->column('varchar(50)');
		$expected = 'string';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('text');
		$expected = 'text';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('int(11)');
		$expected = 'integer';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('int(11) unsigned');
		$expected = 'integer';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('tinyint(1)');
		$expected = 'boolean';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('boolean');
		$expected = 'boolean';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('float');
		$expected = 'float';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('float unsigned');
		$expected = 'float';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('double unsigned');
		$expected = 'float';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->column('decimal(14,7) unsigned');
		$expected = 'float';
		$this->assertEqual($expected, $result);
	}

/**
 * testAlterSchemaIndexes method
 *
 * @group indices
 * @return void
 */
	public function testAlterSchemaIndexes() {
		$this->Dbo->cacheSources = $this->Dbo->testing = false;
		$table = $this->Dbo->fullTableName('altertest');

		$schema1 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'group1' => array('type' => 'integer', 'null' => true),
				'group2' => array('type' => 'integer', 'null' => true)
		)));
		$result = $this->Dbo->createSchema($schema1);
		$this->assertContains('`id` int(11) DEFAULT 0 NOT NULL,', $result);
		$this->assertContains('`name` varchar(50) NOT NULL,', $result);
		$this->assertContains('`group1` int(11) DEFAULT NULL', $result);
		$this->assertContains('`group2` int(11) DEFAULT NULL', $result);

		//Test that the string is syntactically correct
		$query = $this->Dbo->getConnection()->prepare($result);
		$this->assertEquals($result, $query->queryString);

		$schema2 = new CakeSchema(array(
			'name' => 'AlterTest2',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'group1' => array('type' => 'integer', 'null' => true),
				'group2' => array('type' => 'integer', 'null' => true),
				'indexes' => array(
					'name_idx' => array('column' => 'name', 'unique' => 0),
					'group_idx' => array('column' => 'group1', 'unique' => 0),
					'compound_idx' => array('column' => array('group1', 'group2'), 'unique' => 0),
					'PRIMARY' => array('column' => 'id', 'unique' => 1))
		)));

		$result = $this->Dbo->alterSchema($schema2->compare($schema1));
		$this->assertContains("ALTER TABLE $table", $result);
		$this->assertContains('ADD KEY name_idx (`name`),', $result);
		$this->assertContains('ADD KEY group_idx (`group1`),', $result);
		$this->assertContains('ADD KEY compound_idx (`group1`, `group2`),', $result);
		$this->assertContains('ADD PRIMARY KEY  (`id`);', $result);

		//Test that the string is syntactically correct
		$query = $this->Dbo->getConnection()->prepare($result);
		$this->assertEquals($result, $query->queryString);

		// Change three indexes, delete one and add another one
		$schema3 = new CakeSchema(array(
			'name' => 'AlterTest3',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'group1' => array('type' => 'integer', 'null' => true),
				'group2' => array('type' => 'integer', 'null' => true),
				'indexes' => array(
					'name_idx' => array('column' => 'name', 'unique' => 1),
					'group_idx' => array('column' => 'group2', 'unique' => 0),
					'compound_idx' => array('column' => array('group2', 'group1'), 'unique' => 0),
					'id_name_idx' => array('column' => array('id', 'name'), 'unique' => 0))
		)));

		$result = $this->Dbo->alterSchema($schema3->compare($schema2));
		$this->assertContains("ALTER TABLE $table", $result);
		$this->assertContains('DROP PRIMARY KEY,', $result);
		$this->assertContains('DROP KEY name_idx,', $result);
		$this->assertContains('DROP KEY group_idx,', $result);
		$this->assertContains('DROP KEY compound_idx,', $result);
		$this->assertContains('ADD KEY id_name_idx (`id`, `name`),', $result);
		$this->assertContains('ADD UNIQUE KEY name_idx (`name`),', $result);
		$this->assertContains('ADD KEY group_idx (`group2`),', $result);
		$this->assertContains('ADD KEY compound_idx (`group2`, `group1`);', $result);

		$query = $this->Dbo->getConnection()->prepare($result);
		$this->assertEquals($result, $query->queryString);

		// Compare us to ourself.
		$this->assertEqual($schema3->compare($schema3), array());

		// Drop the indexes
		$result = $this->Dbo->alterSchema($schema1->compare($schema3));

		$this->assertContains("ALTER TABLE $table", $result);
		$this->assertContains('DROP KEY name_idx,', $result);
		$this->assertContains('DROP KEY group_idx,', $result);
		$this->assertContains('DROP KEY compound_idx,', $result);
		$this->assertContains('DROP KEY id_name_idx;', $result);

		$query = $this->Dbo->getConnection()->prepare($result);
		$this->assertEquals($result, $query->queryString);
	}

/**
 * test saving and retrieval of blobs
 *
 * @return void
 */
	public function testBlobSaving() {
		$this->loadFixtures('BinaryTest');
		$this->Dbo->cacheSources = false;
		$data = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'webroot' . DS . 'img' . DS . 'cake.power.gif');

		$model = new CakeTestModel(array('name' => 'BinaryTest', 'ds' => 'test'));
		$model->save(compact('data'));

		$result = $model->find('first');
		$this->assertEqual($result['BinaryTest']['data'], $data);
	}

/**
 * test altering the table settings with schema.
 *
 * @return void
 */
	public function testAlteringTableParameters() {
		$this->Dbo->cacheSources = $this->Dbo->testing = false;

		$schema1 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'tableParameters' => array(
					'charset' => 'latin1',
					'collate' => 'latin1_general_ci',
					'engine' => 'MyISAM'
				)
			)
		));
		$this->Dbo->rawQuery($this->Dbo->createSchema($schema1));
		$schema2 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'tableParameters' => array(
					'charset' => 'utf8',
					'collate' => 'utf8_general_ci',
					'engine' => 'InnoDB'
				)
			)
		));
		$result = $this->Dbo->alterSchema($schema2->compare($schema1));
		$this->assertContains('DEFAULT CHARSET=utf8', $result);
		$this->assertContains('ENGINE=InnoDB', $result);
		$this->assertContains('COLLATE=utf8_general_ci', $result);

		$this->Dbo->rawQuery($result);
		$result = $this->Dbo->listDetailedSources($this->Dbo->fullTableName('altertest', false));
		$this->assertEqual($result['Collation'], 'utf8_general_ci');
		$this->assertEqual($result['Engine'], 'InnoDB');
		$this->assertEqual($result['charset'], 'utf8');

		$this->Dbo->rawQuery($this->Dbo->dropSchema($schema1));
	}

/**
 * test alterSchema on two tables.
 *
 * @return void
 */
	public function testAlteringTwoTables() {
		$schema1 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
			),
			'other_table' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
			)
		));
		$schema2 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'field_two' => array('type' => 'string', 'null' => false, 'length' => 50),
			),
			'other_table' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'field_two' => array('type' => 'string', 'null' => false, 'length' => 50),
			)
		));
		$result = $this->Dbo->alterSchema($schema2->compare($schema1));
		$this->assertEqual(2, substr_count($result, 'field_two'), 'Too many fields');
	}

/**
 * testReadTableParameters method
 *
 * @return void
 */
	public function testReadTableParameters() {
		$this->Dbo->cacheSources = $this->Dbo->testing = false;
		$tableName = 'tinyint_' . uniqid();
		$table = $this->Dbo->fullTableName($tableName);
		$this->Dbo->rawQuery('CREATE TABLE ' . $table . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');
		$result = $this->Dbo->readTableParameters($this->Dbo->fullTableName($tableName, false));
		$this->Dbo->rawQuery('DROP TABLE ' . $table);
		$expected = array(
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci',
			'engine' => 'InnoDB');
		$this->assertEqual($expected, $result);

		$table = $this->Dbo->fullTableName($tableName);
		$this->Dbo->rawQuery('CREATE TABLE ' . $table . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=cp1250 COLLATE=cp1250_general_ci;');
		$result = $this->Dbo->readTableParameters($this->Dbo->fullTableName($tableName, false));
		$this->Dbo->rawQuery('DROP TABLE ' . $table);
		$expected = array(
			'charset' => 'cp1250',
			'collate' => 'cp1250_general_ci',
			'engine' => 'MyISAM');
		$this->assertEqual($expected, $result);
	}

/**
 * testBuildTableParameters method
 *
 * @return void
 */
	public function testBuildTableParameters() {
		$this->Dbo->cacheSources = $this->Dbo->testing = false;
		$data = array(
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci',
			'engine' => 'InnoDB');
		$result = $this->Dbo->buildTableParameters($data);
		$expected = array(
			'DEFAULT CHARSET=utf8',
			'COLLATE=utf8_unicode_ci',
			'ENGINE=InnoDB');
		$this->assertEqual($expected, $result);
	}

/**
 * testBuildTableParameters method
 *
 * @return void
 */
	public function testGetCharsetName() {
		$this->Dbo->cacheSources = $this->Dbo->testing = false;
		$result = $this->Dbo->getCharsetName('utf8_unicode_ci');
		$this->assertEqual($result, 'utf8');
		$result = $this->Dbo->getCharsetName('cp1250_general_ci');
		$this->assertEqual($result, 'cp1250');
	}

/**
 * test that changing the virtualFieldSeparator allows for __ fields.
 *
 * @return void
 */
	public function testVirtualFieldSeparators() {
		$this->loadFixtures('BinaryTest');
		$model = new CakeTestModel(array('table' => 'binary_tests', 'ds' => 'test', 'name' => 'BinaryTest'));
		$model->virtualFields = array(
			'other__field' => 'SUM(id)'
		);

		$this->Dbo->virtualFieldSeparator = '_$_';
		$result = $this->Dbo->fields($model, null, array('data', 'other__field'));

		$expected = array('`BinaryTest`.`data`', '(SUM(id)) AS  `BinaryTest_$_other__field`');
		$this->assertEqual($expected, $result);
	}

/**
 * Test describe() on a fixture.
 *
 * @return void
 */
	public function testDescribe() {
		$this->loadFixtures('Apple');

		$model = new Apple();
		$result = $this->Dbo->describe($model);

		$this->assertTrue(isset($result['id']));
		$this->assertTrue(isset($result['color']));

		$result = $this->Dbo->describe($model->useTable);

		$this->assertTrue(isset($result['id']));
		$this->assertTrue(isset($result['color']));
	}

/**
 * test that a describe() gets additional fieldParameters
 *
 * @return void
 */
	public function testDescribeGettingFieldParameters() {
		$schema = new CakeSchema(array(
			'connection' => 'test',
			'testdescribes' => array(
				'id' => array('type' => 'integer', 'key' => 'primary'),
				'stringy' => array(
					'type' => 'string',
					'null' => true,
					'charset' => 'cp1250',
					'collate' => 'cp1250_general_ci',
				),
				'other_col' => array(
					'type' => 'string',
					'null' => false,
					'charset' => 'latin1',
					'comment' => 'Test Comment'
				)
			)
		));


		$this->Dbo->execute($this->Dbo->createSchema($schema));
		$model = new CakeTestModel(array('table' => 'testdescribes', 'name' => 'Testdescribes'));
		$result = $model->getDataSource()->describe($model);
		$this->Dbo->execute($this->Dbo->dropSchema($schema));

		$this->assertEqual($result['stringy']['collate'], 'cp1250_general_ci');
		$this->assertEqual($result['stringy']['charset'], 'cp1250');
		$this->assertEqual($result['other_col']['comment'], 'Test Comment');
	}

/**
 * Tests that listSources method sends the correct query and parses the result accordingly
 * @return void
 */
	public function testListSources() {
		$db = $this->getMock('Mysql', array('connect', '_execute'));
		$queryResult = $this->getMock('PDOStatement');
		$db->expects($this->once())
			->method('_execute')
			->with('SHOW TABLES FROM `cake`')
			->will($this->returnValue($queryResult));
		$queryResult->expects($this->at(0))
			->method('fetch')
			->will($this->returnValue(array('cake_table')));
		$queryResult->expects($this->at(1))
			->method('fetch')
			->will($this->returnValue(array('another_table')));
		$queryResult->expects($this->at(2))
			->method('fetch')
			->will($this->returnValue(null));

		$tables = $db->listSources();
		$this->assertEqual($tables, array('cake_table', 'another_table'));
	}

/**
 * test that listDetailedSources with a named table that doesn't exist.
 *
 * @return void
 */
	public function testListDetailedSourcesNamed() {
		$this->loadFixtures('Apple');

		$result = $this->Dbo->listDetailedSources('imaginary');
		$this->assertEquals(array(), $result, 'Should be empty when table does not exist.');

		$result = $this->Dbo->listDetailedSources();
		$tableName = $this->Dbo->fullTableName('apples', false);
		$this->assertTrue(isset($result[$tableName]), 'Key should exist');
	}

/**
 * Tests that getVersion method sends the correct query for getting the mysql version
 * @return void
 */
	public function testGetVersion() {
		$version = $this->Dbo->getVersion();
		$this->assertTrue(is_string($version));
	}

/**
 * Tests that getVersion method sends the correct query for getting the client encoding
 * @return void
 */
	public function testGetEncoding() {
		$db = $this->getMock('Mysql', array('connect', '_execute'));
		$queryResult = $this->getMock('PDOStatement');

		$db->expects($this->once())
			->method('_execute')
			->with('SHOW VARIABLES LIKE ?', array('character_set_client'))
			->will($this->returnValue($queryResult));
		$result = new StdClass;
		$result->Value = 'utf-8';
		$queryResult->expects($this->once())
			->method('fetchObject')
			->will($this->returnValue($result));

		$encoding = $db->getEncoding();
		$this->assertEqual('utf-8', $encoding);
	}

/**
 * testFieldDoubleEscaping method
 *
 * @return void
 */
	public function testFieldDoubleEscaping() {
		$test = $this->getMock('Mysql', array('connect', '_execute', 'execute'));
		$this->Model = $this->getMock('Article2', array('getDataSource'));
		$this->Model->alias = 'Article';
		$this->Model->expects($this->any())
			->method('getDataSource')
			->will($this->returnValue($test));

		$this->assertEqual($this->Model->escapeField(), '`Article`.`id`');
		$result = $test->fields($this->Model, null, $this->Model->escapeField());
		$this->assertEqual($result, array('`Article`.`id`'));

		$test->expects($this->at(0))->method('execute')
			->with('SELECT `Article`.`id` FROM `articles` AS `Article`   WHERE 1 = 1');

		$result = $test->read($this->Model, array(
			'fields' => $this->Model->escapeField(),
			'conditions' => null,
			'recursive' => -1
		));

		$test->startQuote = '[';
		$test->endQuote = ']';
		$this->assertEqual($this->Model->escapeField(), '[Article].[id]');

		$result = $test->fields($this->Model, null, $this->Model->escapeField());
		$this->assertEqual($result, array('[Article].[id]'));

		$test->expects($this->at(0))->method('execute')
			->with('SELECT [Article].[id] FROM [' . $test->fullTableName('articles', false) . '] AS [Article]   WHERE 1 = 1');
		$result = $test->read($this->Model, array(
			'fields' => $this->Model->escapeField(),
			'conditions' => null,
			'recursive' => -1
		));
	}

/**
 * testGenerateAssociationQuerySelfJoin method
 *
 * @return void
 */
	public function testGenerateAssociationQuerySelfJoin() {
		$this->Dbo = $this->getMock('Mysql', array('connect', '_execute', 'execute'));
		$this->startTime = microtime(true);
		$this->Model = new Article2();
		$this->_buildRelatedModels($this->Model);
		$this->_buildRelatedModels($this->Model->Category2);
		$this->Model->Category2->ChildCat = new Category2();
		$this->Model->Category2->ParentCat = new Category2();

		$queryData = array();

		foreach ($this->Model->Category2->associations() as $type) {
			foreach ($this->Model->Category2->{$type} as $assoc => $assocData) {
				$linkModel = $this->Model->Category2->{$assoc};
				$external = isset($assocData['external']);

				if ($this->Model->Category2->alias == $linkModel->alias && $type != 'hasAndBelongsToMany' && $type != 'hasMany') {
					$result = $this->Dbo->generateAssociationQuery($this->Model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null);
					$this->assertFalse(empty($result));
				} else {
					if ($this->Model->Category2->useDbConfig == $linkModel->useDbConfig) {
						$result = $this->Dbo->generateAssociationQuery($this->Model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null);
						$this->assertFalse(empty($result));
					}
				}
			}
		}

		$query = $this->Dbo->generateAssociationQuery($this->Model->Category2, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+(.+)FROM(.+)`Category2`\.`group_id`\s+=\s+`Group`\.`id`\)\s+LEFT JOIN(.+)WHERE\s+1 = 1\s*$/', $query);

		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4Parent');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$_queryData = $queryData;
		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$expected = array(
			'conditions' => array(),
			'fields' => array(
				'`TestModel4`.`id`',
				'`TestModel4`.`name`',
				'`TestModel4`.`created`',
				'`TestModel4`.`updated`',
				'`TestModel4Parent`.`id`',
				'`TestModel4Parent`.`name`',
				'`TestModel4Parent`.`created`',
				'`TestModel4Parent`.`updated`'
			),
			'joins' => array(
				array(
					'table' => '`test_model4`',
					'alias' => 'TestModel4Parent',
					'type' => 'LEFT',
					'conditions' => '`TestModel4`.`parent_id` = `TestModel4Parent`.`id`'
				)
			),
			'order' => array(),
			'limit' => array(),
			'offset' => array(),
			'group' => array(),
			'callbacks' => null
		);
		$queryData['joins'][0]['table'] = $this->Dbo->fullTableName($queryData['joins'][0]['table']);
		$this->assertEqual($queryData, $expected);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model4` AS `TestModel4Parent`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+1 = 1\s+$/', $result);

		$params['assocData']['type'] = 'INNER';
		$this->Model->belongsTo['TestModel4Parent']['type'] = 'INNER';
		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $_queryData, $params['external'], $resultSet);
		$this->assertTrue($result);
		$this->assertEqual($_queryData['joins'][0]['type'], 'INNER');
	}

/**
 * buildRelatedModels method
 *
 * @param mixed $model
 * @return void
 */
	function _buildRelatedModels($model) {
		foreach ($model->associations() as $type) {
			foreach ($model->{$type} as $assoc => $assocData) {
				if (is_string($assocData)) {
					$className = $assocData;
				} elseif (isset($assocData['className'])) {
					$className = $assocData['className'];
				}
				$model->$className = new $className();
				$model->$className->schema();
			}
		}
	}

/**
 * &_prepareAssociationQuery method
 *
 * @param mixed $model
 * @param mixed $queryData
 * @param mixed $binding
 * @return void
 */
	function &_prepareAssociationQuery($model, &$queryData, $binding) {
		$type = $binding['type'];
		$assoc = $binding['model'];
		$assocData = $model->{$type}[$assoc];
		$className = $assocData['className'];

		$linkModel = $model->{$className};
		$external = isset($assocData['external']);
		$reflection = new ReflectionMethod($this->Dbo, '_scrubQueryData');
		$reflection->setAccessible(true);
		$queryData = $reflection->invokeArgs($this->Dbo, array($queryData));

		$result = array_merge(array('linkModel' => &$linkModel), compact('type', 'assoc', 'assocData', 'external'));
		return $result;
	}

/**
 * testGenerateInnerJoinAssociationQuery method
 *
 * @return void
 */
	public function testGenerateInnerJoinAssociationQuery() {
		$test = $this->getMock('Mysql', array('connect', '_execute', 'execute'));
		$this->Model = $this->getMock('TestModel9', array('getDataSource'));
		$this->Model->expects($this->any())
			->method('getDataSource')
			->will($this->returnValue($test));

		$this->Model->TestModel8 = $this->getMock('TestModel8', array('getDataSource'));
		$this->Model->TestModel8->expects($this->any())
			->method('getDataSource')
			->will($this->returnValue($test));

		$test->expects($this->at(0))->method('execute')
			->with($this->stringContains('`TestModel9` LEFT JOIN `test_model8`'));

		$test->expects($this->at(1))->method('execute')
			->with($this->stringContains('TestModel9` INNER JOIN `test_model8`'));

		$test->read($this->Model, array('recursive' => 1));
		$this->Model->belongsTo['TestModel8']['type'] = 'INNER';
		$test->read($this->Model, array('recursive' => 1));
	}

/**
 * testGenerateAssociationQuerySelfJoinWithConditionsInHasOneBinding method
 *
 * @return void
 */
	public function testGenerateAssociationQuerySelfJoinWithConditionsInHasOneBinding() {
		$this->Model = new TestModel8();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel9');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
		$_queryData = $queryData;
		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`, `TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model8` AS `TestModel8`\s+LEFT JOIN\s+`test_model9` AS `TestModel9`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel9`\.`name` != \'mariano\'\s+AND\s+`TestModel9`.`test_model8_id` = `TestModel8`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQuerySelfJoinWithConditionsInBelongsToBinding method
 *
 * @return void
 */
	public function testGenerateAssociationQuerySelfJoinWithConditionsInBelongsToBinding() {
		$this->Model = new TestModel9();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel8');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`, `TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model9` AS `TestModel9`\s+LEFT JOIN\s+`test_model8` AS `TestModel8`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel8`\.`name` != \'larry\'\s+AND\s+`TestModel9`.`test_model8_id` = `TestModel8`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQuerySelfJoinWithConditions method
 *
 * @return void
 */
	public function testGenerateAssociationQuerySelfJoinWithConditions() {
		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4Parent');
		$queryData = array('conditions' => array('TestModel4Parent.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model4` AS `TestModel4Parent`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?`TestModel4Parent`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);

		$this->Featured2 = new Featured2();
		$this->Featured2->schema();

		$this->Featured2->bindModel(array(
			'belongsTo' => array(
				'ArticleFeatured2' => array(
					'conditions' => 'ArticleFeatured2.published = \'Y\'',
					'fields' => 'id, title, user_id, published'
				)
			)
		));

		$this->_buildRelatedModels($this->Featured2);

		$binding = array('type' => 'belongsTo', 'model' => 'ArticleFeatured2');
		$queryData = array('conditions' => array());
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Featured2, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Featured2, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);
		$result = $this->Dbo->generateAssociationQuery($this->Featured2, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern(
			'/^SELECT\s+`Featured2`\.`id`, `Featured2`\.`article_id`, `Featured2`\.`category_id`, `Featured2`\.`name`,\s+'.
			'`ArticleFeatured2`\.`id`, `ArticleFeatured2`\.`title`, `ArticleFeatured2`\.`user_id`, `ArticleFeatured2`\.`published`\s+' .
			'FROM\s+`featured2` AS `Featured2`\s+LEFT JOIN\s+`article_featured` AS `ArticleFeatured2`' .
			'\s+ON\s+\(`ArticleFeatured2`.`published` = \'Y\'\s+AND\s+`Featured2`\.`article_featured2_id` = `ArticleFeatured2`\.`id`\)' .
			'\s+WHERE\s+1\s+=\s+1\s*$/',
			$result
		);
	}

/**
 * testGenerateAssociationQueryHasOne method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasOne() {
		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel5');

		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->Dbo->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model5` AS `TestModel5` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+/', $result);
		$this->assertPattern('/`test_model5` AS `TestModel5`\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasOneWithConditions method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasOneWithConditions() {
		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel5');

		$queryData = array('conditions' => array('TestModel5.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model5` AS `TestModel5`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id`\s+=\s+`TestModel4`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*`TestModel5`.`name`\s+!=\s+\'mariano\'\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryBelongsTo method
 *
 * @return void
 */
	public function testGenerateAssociationQueryBelongsTo() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type'=>'belongsTo', 'model'=>'TestModel4');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->Dbo->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+`test_model4` AS `TestModel4`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryBelongsToWithConditions method
 *
 * @return void
 */
	public function testGenerateAssociationQueryBelongsToWithConditions() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4');
		$queryData = array('conditions' => array('TestModel5.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->Dbo->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+`test_model4` AS `TestModel4`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+`TestModel5`.`name` != \'mariano\'\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasMany method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasMany() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+`TestModel6`.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasManyWithLimit method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasManyWithLimit() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$this->Model->hasMany['TestModel6']['limit'] = 2;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern(
			'/^SELECT\s+' .
			'`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+'.
			'FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+' .
			'`TestModel6`.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)\s*'.
			'LIMIT \d*'.
			'\s*$/', $result
		);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern(
			'/^SELECT\s+'.
			'`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+'.
			'FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+'.
			'(?:\()?\s*1 = 1\s*(?:\))?'.
			'\s*$/', $result
		);
	}

/**
 * testGenerateAssociationQueryHasManyWithConditions method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasManyWithConditions() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('conditions' => array('TestModel5.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?`TestModel5`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasManyWithOffsetAndLimit method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasManyWithOffsetAndLimit() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasMany['TestModel6'];

		$this->Model->hasMany['TestModel6']['offset'] = 2;
		$this->Model->hasMany['TestModel6']['limit'] = 5;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
		$this->assertPattern('/\s+LIMIT 2,\s*5\s*$/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasMany['TestModel6'] = $__backup;
	}

/**
 * testGenerateAssociationQueryHasManyWithPageAndLimit method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasManyWithPageAndLimit() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasMany['TestModel6'];

		$this->Model->hasMany['TestModel6']['page'] = 2;
		$this->Model->hasMany['TestModel6']['limit'] = 5;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
		$this->assertPattern('/\s+LIMIT 5,\s*5\s*$/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasMany['TestModel6'] = $__backup;
	}

/**
 * testGenerateAssociationQueryHasManyWithFields method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasManyWithFields() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`, `TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`name`', '`TestModel5`.`created`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasMany['TestModel6']['fields'] = array('name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->Model->hasMany['TestModel6']['fields']);

		$this->Model->hasMany['TestModel6']['fields'] = array('id', 'name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->Model->hasMany['TestModel6']['fields']);

		$this->Model->hasMany['TestModel6']['fields'] = array('test_model5_id', 'name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`test_model5_id`, `TestModel6`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->Model->hasMany['TestModel6']['fields']);
	}

/**
 * test generateAssociationQuery with a hasMany and an aggregate function.
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasManyAndAggregateFunction() {
		$this->Model = new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('MIN(`TestModel5`.`test_model4_id`)'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
		$this->Model->recursive = 0;

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, $params['type'], $params['assoc'], $params['assocData'], $queryData, false, $resultSet);
		$this->assertPattern('/^SELECT\s+MIN\(`TestModel5`\.`test_model4_id`\)\s+FROM/', $result);
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToMany method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasAndBelongsToMany() {
		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasAndBelongsToMany', 'model' => 'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = $this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$assocTable = $this->Dbo->fullTableName($this->Model->TestModel4TestModel7, false);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7` AS `TestModel7`\s+JOIN\s+`' . $assocTable . '`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}\s+AND/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToManyWithConditions method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasAndBelongsToManyWithConditions() {
		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array('conditions' => array('TestModel4.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = $this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?`TestModel4`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToManyWithOffsetAndLimit method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasAndBelongsToManyWithOffsetAndLimit() {
		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasAndBelongsToMany['TestModel7'];

		$this->Model->hasAndBelongsToMany['TestModel7']['offset'] = 2;
		$this->Model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}\s+/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 2,\s*5\s*$/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasAndBelongsToMany['TestModel7'] = $__backup;
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToManyWithPageAndLimit method
 *
 * @return void
 */
	public function testGenerateAssociationQueryHasAndBelongsToManyWithPageAndLimit() {
		$this->Model = new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasAndBelongsToMany['TestModel7'];

		$this->Model->hasAndBelongsToMany['TestModel7']['page'] = 2;
		$this->Model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 5,\s*5\s*$/', $result);

		$result = $this->Dbo->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasAndBelongsToMany['TestModel7'] = $__backup;
	}

/**
 * testSelectDistict method
 *
 * @return void
 */
	public function testSelectDistict() {
		$this->Model = new TestModel4();
		$result = $this->Dbo->fields($this->Model, 'Vendor', "DISTINCT Vendor.id, Vendor.name");
		$expected = array('DISTINCT `Vendor`.`id`', '`Vendor`.`name`');
		$this->assertEqual($expected, $result);
	}

/**
 * testStringConditionsParsing method
 *
 * @return void
 */
	public function testStringConditionsParsing() {
		$result = $this->Dbo->conditions("ProjectBid.project_id = Project.id");
		$expected = " WHERE `ProjectBid`.`project_id` = `Project`.`id`";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("Candy.name LIKE 'a' AND HardCandy.name LIKE 'c'");
		$expected = " WHERE `Candy`.`name` LIKE 'a' AND `HardCandy`.`name` LIKE 'c'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("HardCandy.name LIKE 'a' AND Candy.name LIKE 'c'");
		$expected = " WHERE `HardCandy`.`name` LIKE 'a' AND `Candy`.`name` LIKE 'c'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("Post.title = '1.1'");
		$expected = " WHERE `Post`.`title` = '1.1'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("User.id != 0 AND User.user LIKE '%arr%'");
		$expected = " WHERE `User`.`id` != 0 AND `User`.`user` LIKE '%arr%'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("SUM(Post.comments_count) > 500");
		$expected = " WHERE SUM(`Post`.`comments_count`) > 500";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE (`Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("score BETWEEN 90.1 AND 95.7");
		$expected = " WHERE score BETWEEN 90.1 AND 95.7";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('score' => array(2=>1, 2, 10)));
		$expected = " WHERE score IN (1, 2, 10)";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("Aro.rght = Aro.lft + 1.1");
		$expected = " WHERE `Aro`.`rght` = `Aro`.`lft` + 1.1";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE (`Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('Sportstaette.sportstaette LIKE "%ru%" AND Sportstaette.sportstaettenart_id = 2');
		$expected = ' WHERE `Sportstaette`.`sportstaette` LIKE "%ru%" AND `Sportstaette`.`sportstaettenart_id` = 2';
		$this->assertPattern('/\s*WHERE\s+`Sportstaette`\.`sportstaette`\s+LIKE\s+"%ru%"\s+AND\s+`Sports/', $result);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('Sportstaette.sportstaettenart_id = 2 AND Sportstaette.sportstaette LIKE "%ru%"');
		$expected = ' WHERE `Sportstaette`.`sportstaettenart_id` = 2 AND `Sportstaette`.`sportstaette` LIKE "%ru%"';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('SUM(Post.comments_count) > 500 AND NOT Post.title IS NULL AND NOT Post.extended_title IS NULL');
		$expected = ' WHERE SUM(`Post`.`comments_count`) > 500 AND NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('NOT Post.title IS NULL AND NOT Post.extended_title IS NULL AND SUM(Post.comments_count) > 500');
		$expected = ' WHERE NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL AND SUM(`Post`.`comments_count`) > 500';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('NOT Post.extended_title IS NULL AND NOT Post.title IS NULL AND Post.title != "" AND SPOON(SUM(Post.comments_count) + 1.1) > 500');
		$expected = ' WHERE NOT `Post`.`extended_title` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title` != "" AND SPOON(SUM(`Post`.`comments_count`) + 1.1) > 500';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('NOT Post.title_extended IS NULL AND NOT Post.title IS NULL AND Post.title_extended != Post.title');
		$expected = ' WHERE NOT `Post`.`title_extended` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title_extended` != `Post`.`title`';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("Comment.id = 'a'");
		$expected = " WHERE `Comment`.`id` = 'a'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("lower(Article.title) LIKE 'a%'");
		$expected = " WHERE lower(`Article`.`title`) LIKE 'a%'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('((MATCH(Video.title) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(Video.description) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(Video.tags) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))');
		$expected = ' WHERE ((MATCH(`Video`.`title`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(`Video`.`description`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(`Video`.`tags`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('DATEDIFF(NOW(),Article.published) < 1 && Article.live=1');
		$expected = " WHERE DATEDIFF(NOW(),`Article`.`published`) < 1 && `Article`.`live`=1";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('file = "index.html"');
		$expected = ' WHERE file = "index.html"';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions("file = 'index.html'");
		$expected = " WHERE file = 'index.html'";
		$this->assertEqual($expected, $result);

		$letter = $letter = 'd.a';
		$conditions = array('Company.name like ' => $letter . '%');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `Company`.`name` like 'd.a%'";
		$this->assertEqual($expected, $result);

		$conditions = array('Artist.name' => 'JUDY and MARY');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `Artist`.`name` = 'JUDY and MARY'";
		$this->assertEqual($expected, $result);

		$conditions = array('Artist.name' => 'JUDY AND MARY');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `Artist`.`name` = 'JUDY AND MARY'";
		$this->assertEqual($expected, $result);

		$conditions = array('Company.name similar to ' => 'a word');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `Company`.`name` similar to 'a word'";
		$this->assertEqual($result, $expected);
	}

/**
 * testQuotesInStringConditions method
 *
 * @return void
 */
	public function testQuotesInStringConditions() {
		$result = $this->Dbo->conditions('Member.email = \'mariano@cricava.com\'');
		$expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\'';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('Member.email = "mariano@cricava.com"');
		$expected = ' WHERE `Member`.`email` = "mariano@cricava.com"';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions('Member.email = \'mariano@cricava.com\' AND Member.user LIKE \'mariano.iglesias%\'');
		$expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\' AND `Member`.`user` LIKE \'mariano.iglesias%\'';
		$this->assertEqual($expected, $result);


		$result = $this->Dbo->conditions('Member.email = "mariano@cricava.com" AND Member.user LIKE "mariano.iglesias%"');
		$expected = ' WHERE `Member`.`email` = "mariano@cricava.com" AND `Member`.`user` LIKE "mariano.iglesias%"';
		$this->assertEqual($expected, $result);
	}

/**
 * testParenthesisInStringConditions method
 *
 * @return void
 */
	public function testParenthesisInStringConditions() {
		$result = $this->Dbo->conditions('Member.name = \'(lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \')lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'va(lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'va)lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'va(lu)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'va(lu)e\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'(mariano)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'(mariano)iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'(mariano) iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'(mariano word) iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'(mariano.iglesias)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias) CakePHP\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

		$result = $this->Dbo->conditions('Member.name = \'(mariano.iglesias) CakePHP\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
	}

/**
 * testParenthesisInArrayConditions method
 *
 * @return void
 */
	public function testParenthesisInArrayConditions() {
		$result = $this->Dbo->conditions(array('Member.name' => '(lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => ')lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => 'va(lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => 'va)lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => 'va(lu)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => 'va(lu)e'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => '(mariano)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => '(mariano)iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => '(mariano) iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => '(mariano word) iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => '(mariano.iglesias)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => 'Mariano Iglesias (mariano.iglesias)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => 'Mariano Iglesias (mariano.iglesias) CakePHP'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

		$result = $this->Dbo->conditions(array('Member.name' => '(mariano.iglesias) CakePHP'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
	}

/**
 * testArrayConditionsParsing method
 *
 * @return void
 */
	public function testArrayConditionsParsing() {
		$this->loadFixtures('Post', 'Author');
		$result = $this->Dbo->conditions(array('Stereo.type' => 'in dash speakers'));
		$this->assertPattern("/^\s+WHERE\s+`Stereo`.`type`\s+=\s+'in dash speakers'/", $result);

		$result = $this->Dbo->conditions(array('Candy.name LIKE' => 'a', 'HardCandy.name LIKE' => 'c'));
		$this->assertPattern("/^\s+WHERE\s+`Candy`.`name` LIKE\s+'a'\s+AND\s+`HardCandy`.`name`\s+LIKE\s+'c'/", $result);

		$result = $this->Dbo->conditions(array('HardCandy.name LIKE' => 'a', 'Candy.name LIKE' => 'c'));
		$expected = " WHERE `HardCandy`.`name` LIKE 'a' AND `Candy`.`name` LIKE 'c'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('HardCandy.name LIKE' => 'a%', 'Candy.name LIKE' => '%c%'));
		$expected = " WHERE `HardCandy`.`name` LIKE 'a%' AND `Candy`.`name` LIKE '%c%'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('HardCandy.name LIKE' => 'to be or%', 'Candy.name LIKE' => '%not to be%'));
		$expected = " WHERE `HardCandy`.`name` LIKE 'to be or%' AND `Candy`.`name` LIKE '%not to be%'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('score BETWEEN ? AND ?' => array(90.1, 95.7)));
		$expected = " WHERE `score` BETWEEN 90.1 AND 95.7";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Post.title' => 1.1));
		$expected = " WHERE `Post`.`title` = 1.1";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Post.title' => 1.1), true, true, new Post());
		$expected = " WHERE `Post`.`title` = '1.1'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('SUM(Post.comments_count) >' => '500'));
		$expected = " WHERE SUM(`Post`.`comments_count`) > '500'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('MAX(Post.rating) >' => '50'));
		$expected = " WHERE MAX(`Post`.`rating`) > '50'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('lower(Article.title)' =>  'secrets'));
		$expected = " WHERE lower(`Article`.`title`) = 'secrets'";
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->conditions(array('title LIKE' => '%hello'));
		$expected = " WHERE `title` LIKE '%hello'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Post.name' => 'mad(g)ik'));
		$expected = " WHERE `Post`.`name` = 'mad(g)ik'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('score' => array(1, 2, 10)));
		$expected = " WHERE score IN (1, 2, 10)";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('score' => array()));
		$expected = " WHERE `score` IS NULL";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('score !=' => array()));
		$expected = " WHERE `score` IS NOT NULL";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('score !=' => '20'));
		$expected = " WHERE `score` != '20'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('score >' => '20'));
		$expected = " WHERE `score` > '20'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('client_id >' => '20'), true, true, new TestModel());
		$expected = " WHERE `client_id` > 20";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('OR' => array(
			array('User.user' => 'mariano'),
			array('User.user' => 'nate')
		)));

		$expected = " WHERE ((`User`.`user` = 'mariano') OR (`User`.`user` = 'nate'))";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('or' => array(
			'score BETWEEN ? AND ?' => array('4', '5'), 'rating >' => '20'
		)));
		$expected = " WHERE ((`score` BETWEEN '4' AND '5') OR (`rating` > '20'))";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('or' => array(
			'score BETWEEN ? AND ?' => array('4', '5'), array('score >' => '20')
		)));
		$expected = " WHERE ((`score` BETWEEN '4' AND '5') OR (`score` > '20'))";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('and' => array(
			'score BETWEEN ? AND ?' => array('4', '5'), array('score >' => '20')
		)));
		$expected = " WHERE ((`score` BETWEEN '4' AND '5') AND (`score` > '20'))";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(
			'published' => 1, 'or' => array('score >' => '2', array('score >' => '20'))
		));
		$expected = " WHERE `published` = 1 AND ((`score` > '2') OR (`score` > '20'))";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(array('Project.removed' => false)));
		$expected = " WHERE `Project`.`removed` = '0'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(array('Project.removed' => true)));
		$expected = " WHERE `Project`.`removed` = '1'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(array('Project.removed' => null)));
		$expected = " WHERE `Project`.`removed` IS NULL";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(array('Project.removed !=' => null)));
		$expected = " WHERE `Project`.`removed` IS NOT NULL";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('(Usergroup.permissions) & 4' => 4));
		$expected = " WHERE (`Usergroup`.`permissions`) & 4 = 4";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('((Usergroup.permissions) & 4)' => 4));
		$expected = " WHERE ((`Usergroup`.`permissions`) & 4) = 4";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Post.modified >=' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)'));
		$expected = " WHERE `Post`.`modified` >= 'DATE_SUB(NOW(), INTERVAL 7 DAY)'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Post.modified >= DATE_SUB(NOW(), INTERVAL 7 DAY)'));
		$expected = " WHERE `Post`.`modified` >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(
			'NOT' => array('Course.id' => null, 'Course.vet' => 'N', 'level_of_education_id' => array(912,999)),
			'Enrollment.yearcompleted >' => '0')
		);
		$this->assertPattern('/^\s*WHERE\s+\(NOT\s+\(`Course`\.`id` IS NULL\)\s+AND NOT\s+\(`Course`\.`vet`\s+=\s+\'N\'\)\s+AND NOT\s+\(level_of_education_id IN \(912, 999\)\)\)\s+AND\s+`Enrollment`\.`yearcompleted`\s+>\s+\'0\'\s*$/', $result);

		$result = $this->Dbo->conditions(array('id <>' => '8'));
		$this->assertPattern('/^\s*WHERE\s+`id`\s+<>\s+\'8\'\s*$/', $result);

		$result = $this->Dbo->conditions(array('TestModel.field =' => 'gribe$@()lu'));
		$expected = " WHERE `TestModel`.`field` = 'gribe$@()lu'";
		$this->assertEqual($expected, $result);

		$conditions['NOT'] = array('Listing.expiration BETWEEN ? AND ?' => array("1", "100"));
		$conditions[0]['OR'] = array(
			"Listing.title LIKE" => "%term%",
			"Listing.description LIKE" => "%term%"
		);
		$conditions[1]['OR'] = array(
			"Listing.title LIKE" => "%term_2%",
			"Listing.description LIKE" => "%term_2%"
		);
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE NOT (`Listing`.`expiration` BETWEEN '1' AND '100') AND" .
		" ((`Listing`.`title` LIKE '%term%') OR (`Listing`.`description` LIKE '%term%')) AND" .
		" ((`Listing`.`title` LIKE '%term_2%') OR (`Listing`.`description` LIKE '%term_2%'))";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('MD5(CONCAT(Reg.email,Reg.id))' => 'blah'));
		$expected = " WHERE MD5(CONCAT(`Reg`.`email`,`Reg`.`id`)) = 'blah'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(
			'MD5(CONCAT(Reg.email,Reg.id))' => array('blah', 'blahblah')
		));
		$expected = " WHERE MD5(CONCAT(`Reg`.`email`,`Reg`.`id`)) IN ('blah', 'blahblah')";
		$this->assertEqual($expected, $result);

		$conditions = array('id' => array(2, 5, 6, 9, 12, 45, 78, 43, 76));
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE id IN (2, 5, 6, 9, 12, 45, 78, 43, 76)";
		$this->assertEqual($expected, $result);

		$conditions = array('title' => 'user(s)');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `title` = 'user(s)'";
		$this->assertEqual($expected, $result);

		$conditions = array('title' => 'user(s) data');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `title` = 'user(s) data'";
		$this->assertEqual($expected, $result);

		$conditions = array('title' => 'user(s,arg) data');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `title` = 'user(s,arg) data'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array("Book.book_name" => 'Java(TM)'));
		$expected = " WHERE `Book`.`book_name` = 'Java(TM)'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array("Book.book_name" => 'Java(TM) '));
		$expected = " WHERE `Book`.`book_name` = 'Java(TM) '";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array("Book.id" => 0));
		$expected = " WHERE `Book`.`id` = 0";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array("Book.id" => null));
		$expected = " WHERE `Book`.`id` IS NULL";
		$this->assertEqual($expected, $result);

		$conditions = array('MysqlModel.id' => '');
		$result = $this->Dbo->conditions($conditions, true, true, $this->model);
		$expected = " WHERE `MysqlModel`.`id` IS NULL";
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->conditions(array('Listing.beds >=' => 0));
		$expected = " WHERE `Listing`.`beds` >= 0";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(
			'ASCII(SUBSTRING(keyword, 1, 1)) BETWEEN ? AND ?' => array(65, 90)
		));
		$expected = ' WHERE ASCII(SUBSTRING(keyword, 1, 1)) BETWEEN 65 AND 90';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('or' => array(
			'? BETWEEN Model.field1 AND Model.field2' => '2009-03-04'
		)));
		$expected = " WHERE '2009-03-04' BETWEEN Model.field1 AND Model.field2";
		$this->assertEqual($expected, $result);
	}

/**
 * testArrayConditionsParsingComplexKeys method
 *
 * @return void
 */
	public function testArrayConditionsParsingComplexKeys() {
		$result = $this->Dbo->conditions(array(
			'CAST(Book.created AS DATE)' => '2008-08-02'
		));
		$expected = " WHERE CAST(`Book`.`created` AS DATE) = '2008-08-02'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(
			'CAST(Book.created AS DATE) <=' => '2008-08-02'
		));
		$expected = " WHERE CAST(`Book`.`created` AS DATE) <= '2008-08-02'";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array(
			'(Stats.clicks * 100) / Stats.views >' => 50
		));
		$expected = " WHERE (`Stats`.`clicks` * 100) / `Stats`.`views` > 50";
		$this->assertEqual($expected, $result);
	}

/**
 * testMixedConditionsParsing method
 *
 * @return void
 */
	public function testMixedConditionsParsing() {
		$conditions[] = 'User.first_name = \'Firstname\'';
		$conditions[] = array('User.last_name' => 'Lastname');
		$result = $this->Dbo->conditions($conditions);
		$expected = " WHERE `User`.`first_name` = 'Firstname' AND `User`.`last_name` = 'Lastname'";
		$this->assertEqual($expected, $result);

		$conditions = array(
			'Thread.project_id' => 5,
			'Thread.buyer_id' => 14,
			'1=1 GROUP BY Thread.project_id'
		);
		$result = $this->Dbo->conditions($conditions);
		$this->assertPattern('/^\s*WHERE\s+`Thread`.`project_id`\s*=\s*5\s+AND\s+`Thread`.`buyer_id`\s*=\s*14\s+AND\s+1\s*=\s*1\s+GROUP BY `Thread`.`project_id`$/', $result);
	}

/**
 * testConditionsOptionalArguments method
 *
 * @return void
 */
	public function testConditionsOptionalArguments() {
		$result = $this->Dbo->conditions( array('Member.name' => 'Mariano'), true, false);
		$this->assertPattern('/^\s*`Member`.`name`\s*=\s*\'Mariano\'\s*$/', $result);

		$result = $this->Dbo->conditions( array(), true, false);
		$this->assertPattern('/^\s*1\s*=\s*1\s*$/', $result);
	}

/**
 * testConditionsWithModel
 *
 * @return void
 */
	public function testConditionsWithModel() {
		$this->Model = new Article2();

		$result = $this->Dbo->conditions(array('Article2.viewed >=' => 0), true, true, $this->Model);
		$expected = " WHERE `Article2`.`viewed` >= 0";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Article2.viewed >=' => '0'), true, true, $this->Model);
		$expected = " WHERE `Article2`.`viewed` >= 0";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Article2.viewed >=' => '1'), true, true, $this->Model);
		$expected = " WHERE `Article2`.`viewed` >= 1";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Article2.rate_sum BETWEEN ? AND ?' => array(0, 10)), true, true, $this->Model);
		$expected = " WHERE `Article2`.`rate_sum` BETWEEN 0 AND 10";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Article2.rate_sum BETWEEN ? AND ?' => array('0', '10')), true, true, $this->Model);
		$expected = " WHERE `Article2`.`rate_sum` BETWEEN 0 AND 10";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->conditions(array('Article2.rate_sum BETWEEN ? AND ?' => array('1', '10')), true, true, $this->Model);
		$expected = " WHERE `Article2`.`rate_sum` BETWEEN 1 AND 10";
		$this->assertEqual($expected, $result);
	}

/**
 * testFieldParsing method
 *
 * @return void
 */
	public function testFieldParsing() {
		$this->Model = new TestModel();
		$result = $this->Dbo->fields($this->Model, 'Vendor', "Vendor.id, COUNT(Model.vendor_id) AS `Vendor`.`count`");
		$expected = array('`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, 'Vendor', "`Vendor`.`id`, COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`");
		$expected = array('`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, 'Post', "CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name, Node.created");
		$expected = array("CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", "`Node`.`created`");
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, 'round( (3.55441 * fooField), 3 ) AS test');
		$this->assertEqual($result, array('round( (3.55441 * fooField), 3 ) AS test'));

		$result = $this->Dbo->fields($this->Model, null, 'ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating');
		$this->assertEqual($result, array('ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating'));

		$result = $this->Dbo->fields($this->Model, null, 'ROUND(Rating.rate_total / Rating.rate_count,2) AS rating');
		$this->assertEqual($result, array('ROUND(Rating.rate_total / Rating.rate_count,2) AS rating'));

		$result = $this->Dbo->fields($this->Model, 'Post', "Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name");
		$expected = array("`Node`.`created`", "CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name");
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, 'Post', "2.2,COUNT(*), SUM(Something.else) as sum, Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name,Post.title,Post.1,1.1");
		$expected = array(
			'2.2', 'COUNT(*)', 'SUM(`Something`.`else`) as sum', '`Node`.`created`',
			"CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", '`Post`.`title`', '`Post`.`1`', '1.1'
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, "(`Provider`.`star_total` / `Provider`.`total_ratings`) as `rating`");
		$expected = array("(`Provider`.`star_total` / `Provider`.`total_ratings`) as `rating`");
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, 'Post');
		$expected = array(
			'`Post`.`id`', '`Post`.`client_id`', '`Post`.`name`', '`Post`.`login`',
			'`Post`.`passwd`', '`Post`.`addr_1`', '`Post`.`addr_2`', '`Post`.`zip_code`',
			'`Post`.`city`', '`Post`.`country`', '`Post`.`phone`', '`Post`.`fax`',
			'`Post`.`url`', '`Post`.`email`', '`Post`.`comments`', '`Post`.`last_login`',
			'`Post`.`created`', '`Post`.`updated`'
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, 'Other');
		$expected = array(
			'`Other`.`id`', '`Other`.`client_id`', '`Other`.`name`', '`Other`.`login`',
			'`Other`.`passwd`', '`Other`.`addr_1`', '`Other`.`addr_2`', '`Other`.`zip_code`',
			'`Other`.`city`', '`Other`.`country`', '`Other`.`phone`', '`Other`.`fax`',
			'`Other`.`url`', '`Other`.`email`', '`Other`.`comments`', '`Other`.`last_login`',
			'`Other`.`created`', '`Other`.`updated`'
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, array(), false);
		$expected = array('id', 'client_id', 'name', 'login', 'passwd', 'addr_1', 'addr_2', 'zip_code', 'city', 'country', 'phone', 'fax', 'url', 'email', 'comments', 'last_login', 'created', 'updated');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, 'COUNT(*)');
		$expected = array('COUNT(*)');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, 'SUM(Thread.unread_buyer) AS ' . $this->Dbo->name('sum_unread_buyer'));
		$expected = array('SUM(`Thread`.`unread_buyer`) AS `sum_unread_buyer`');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, 'name, count(*)');
		$expected = array('`TestModel`.`name`', 'count(*)');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, 'count(*), name');
		$expected = array('count(*)', '`TestModel`.`name`');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields(
			$this->Model, null, 'field1, field2, field3, count(*), name'
		);
		$expected = array(
			'`TestModel`.`field1`', '`TestModel`.`field2`',
			'`TestModel`.`field3`', 'count(*)', '`TestModel`.`name`'
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, array('dayofyear(now())'));
		$expected = array('dayofyear(now())');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, array('MAX(Model.field) As Max'));
		$expected = array('MAX(`Model`.`field`) As Max');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, array('Model.field AS AnotherName'));
		$expected = array('`Model`.`field` AS `AnotherName`');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, array('field AS AnotherName'));
		$expected = array('`field` AS `AnotherName`');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, null, array(
			'TestModel.field AS AnotherName'
		));
		$expected = array('`TestModel`.`field` AS `AnotherName`');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->Model, 'Foo', array(
			'id', 'title', '(user_count + discussion_count + post_count) AS score'
		));
		$expected = array(
			'`Foo`.`id`',
			'`Foo`.`title`',
			'(user_count + discussion_count + post_count) AS score'
		);
		$this->assertEqual($expected, $result);
	}

/**
 * test that fields() will accept objects made from DboSource::expression
 *
 * @return void
 */
	public function testFieldsWithExpression() {
		$this->Model = new TestModel;
		$expression = $this->Dbo->expression("CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col");
		$result = $this->Dbo->fields($this->Model, null, array("id", $expression));
		$expected = array(
			'`TestModel`.`id`',
			"CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col"
		);
		$this->assertEqual($expected, $result);
	}

/**
 * testRenderStatement method
 *
 * @return void
 */
	public function testRenderStatement() {
		$result = $this->Dbo->renderStatement('select', array(
			'fields' => 'id', 'table' => 'table', 'conditions' => 'WHERE 1=1',
			'alias' => '', 'joins' => '', 'order' => '', 'limit' => '', 'group' => ''
		));
		$this->assertPattern('/^\s*SELECT\s+id\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->Dbo->renderStatement('update', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => ''));
		$this->assertPattern('/^\s*UPDATE\s+table\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->Dbo->renderStatement('update', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => ''));
		$this->assertPattern('/^\s*UPDATE\s+table\s+AS\s+alias\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->Dbo->renderStatement('delete', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => ''));
		$this->assertPattern('/^\s*DELETE\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->Dbo->renderStatement('delete', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => ''));
		$this->assertPattern('/^\s*DELETE\s+alias\s+FROM\s+table\s+AS\s+alias\s+WHERE\s+1=1\s*$/', $result);
	}

/**
 * testSchema method
 *
 * @return void
 */
	public function testSchema() {
		$Schema = new CakeSchema();
		$Schema->tables = array('table' => array(), 'anotherTable' => array());

		$result = $this->Dbo->dropSchema($Schema, 'non_existing');
		$this->assertTrue(empty($result));

		$result = $this->Dbo->dropSchema($Schema, 'table');
		$this->assertPattern('/^\s*DROP TABLE IF EXISTS\s+' . $this->Dbo->fullTableName('table') . ';\s*$/s', $result);
	}

/**
 * testDropSchemaNoSchema method
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testDropSchemaNoSchema() {
		$result = $this->Dbo->dropSchema(null);
	}

/**
 * testOrderParsing method
 *
 * @return void
 */
	public function testOrderParsing() {
		$result = $this->Dbo->order("ADDTIME(Event.time_begin, '-06:00:00') ASC");
		$expected = " ORDER BY ADDTIME(`Event`.`time_begin`, '-06:00:00') ASC";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->order("title, id");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->Dbo->order("title desc, id desc");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->Dbo->order(array("title desc, id desc"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->Dbo->order(array("title", "id"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->Dbo->order(array(array('title'), array('id')));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->Dbo->order(array("Post.title" => 'asc', "Post.id" => 'desc'));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->Dbo->order(array(array("Post.title" => 'asc', "Post.id" => 'desc')));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->Dbo->order(array("title"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->Dbo->order(array(array("title")));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->Dbo->order("Dealer.id = 7 desc, Dealer.id = 3 desc, Dealer.title asc");
		$expected = " ORDER BY `Dealer`.`id` = 7 desc, `Dealer`.`id` = 3 desc, `Dealer`.`title` asc";
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->order(array("Page.name" => "='test' DESC"));
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*='test'\s+DESC\s*$/", $result);

		$result = $this->Dbo->order("Page.name = 'view' DESC");
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*=\s*'view'\s+DESC\s*$/", $result);

		$result = $this->Dbo->order("(Post.views)");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\)\s+ASC\s*$/", $result);

		$result = $this->Dbo->order("(Post.views)*Post.views");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\)\*`Post`\.`views`\s+ASC\s*$/", $result);

		$result = $this->Dbo->order("(Post.views) * Post.views");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\) \* `Post`\.`views`\s+ASC\s*$/", $result);

		$result = $this->Dbo->order("(Model.field1 + Model.field2) * Model.field3");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Model`\.`field1` \+ `Model`\.`field2`\) \* `Model`\.`field3`\s+ASC\s*$/", $result);

		$result = $this->Dbo->order("Model.name+0 ASC");
		$this->assertPattern("/^\s*ORDER BY\s+`Model`\.`name`\+0\s+ASC\s*$/", $result);

		$result = $this->Dbo->order("Anuncio.destaque & 2 DESC");
		$expected = ' ORDER BY `Anuncio`.`destaque` & 2 DESC';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->order("3963.191 * id");
		$expected = ' ORDER BY 3963.191 * id ASC';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->order(array('Property.sale_price IS NULL'));
		$expected = ' ORDER BY `Property`.`sale_price` IS NULL ASC';
		$this->assertEqual($expected, $result);
	}

/**
 * testComplexSortExpression method
 *
 * @return void
 */
	public function testComplexSortExpression() {
		$result = $this->Dbo->order(array('(Model.field > 100) DESC', 'Model.field ASC'));
		$this->assertPattern("/^\s*ORDER BY\s+\(`Model`\.`field`\s+>\s+100\)\s+DESC,\s+`Model`\.`field`\s+ASC\s*$/", $result);
	}

/**
 * testCalculations method
 *
 * @return void
 */
	public function testCalculations() {
		$this->Model = new TestModel();
		$result = $this->Dbo->calculate($this->Model, 'count');
		$this->assertEqual($result, 'COUNT(*) AS `count`');

		$result = $this->Dbo->calculate($this->Model, 'count', array('id'));
		$this->assertEqual($result, 'COUNT(`id`) AS `count`');

		$result = $this->Dbo->calculate(
			$this->Model,
			'count',
			array($this->Dbo->expression('DISTINCT id'))
		);
		$this->assertEqual($result, 'COUNT(DISTINCT id) AS `count`');

		$result = $this->Dbo->calculate($this->Model, 'count', array('id', 'id_count'));
		$this->assertEqual($result, 'COUNT(`id`) AS `id_count`');

		$result = $this->Dbo->calculate($this->Model, 'count', array('Model.id', 'id_count'));
		$this->assertEqual($result, 'COUNT(`Model`.`id`) AS `id_count`');

		$result = $this->Dbo->calculate($this->Model, 'max', array('id'));
		$this->assertEqual($result, 'MAX(`id`) AS `id`');

		$result = $this->Dbo->calculate($this->Model, 'max', array('Model.id', 'id'));
		$this->assertEqual($result, 'MAX(`Model`.`id`) AS `id`');

		$result = $this->Dbo->calculate($this->Model, 'max', array('`Model`.`id`', 'id'));
		$this->assertEqual($result, 'MAX(`Model`.`id`) AS `id`');

		$result = $this->Dbo->calculate($this->Model, 'min', array('`Model`.`id`', 'id'));
		$this->assertEqual($result, 'MIN(`Model`.`id`) AS `id`');

		$result = $this->Dbo->calculate($this->Model, 'min', 'left');
		$this->assertEqual($result, 'MIN(`left`) AS `left`');
	}

/**
 * testLength method
 *
 * @return void
 */
	public function testLength() {
		$result = $this->Dbo->length('varchar(255)');
		$expected = 255;
		$this->assertIdentical($expected, $result);

		$result = $this->Dbo->length('int(11)');
		$expected = 11;
		$this->assertIdentical($expected, $result);

		$result = $this->Dbo->length('float(5,3)');
		$expected = '5,3';
		$this->assertIdentical($expected, $result);

		$result = $this->Dbo->length('decimal(5,2)');
		$expected = '5,2';
		$this->assertIdentical($expected, $result);

		$result = $this->Dbo->length("enum('test','me','now')");
		$expected = 4;
		$this->assertIdentical($expected, $result);

		$result = $this->Dbo->length("set('a','b','cd')");
		$expected = 2;
		$this->assertIdentical($expected, $result);

		$result = $this->Dbo->length(false);
		$this->assertTrue($result === null);

		$result = $this->Dbo->length('datetime');
		$expected = null;
		$this->assertIdentical($expected, $result);

		$result = $this->Dbo->length('text');
		$expected = null;
		$this->assertIdentical($expected, $result);
	}

/**
 * testBuildIndex method
 *
 * @return void
 */
	public function testBuildIndex() {
		$data = array(
			'PRIMARY' => array('column' => 'id')
		);
		$result = $this->Dbo->buildIndex($data);
		$expected = array('PRIMARY KEY  (`id`)');
		$this->assertIdentical($expected, $result);

		$data = array(
			'MyIndex' => array('column' => 'id', 'unique' => true)
		);
		$result = $this->Dbo->buildIndex($data);
		$expected = array('UNIQUE KEY `MyIndex` (`id`)');
		$this->assertEqual($expected, $result);

		$data = array(
			'MyIndex' => array('column' => array('id', 'name'), 'unique' => true)
		);
		$result = $this->Dbo->buildIndex($data);
		$expected = array('UNIQUE KEY `MyIndex` (`id`, `name`)');
		$this->assertEqual($expected, $result);
	}

/**
 * testBuildColumn method
 *
 * @return void
 */
	public function testBuildColumn2() {
		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 255,
			'default',
			'null' => true,
			'key'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`testName` varchar(255) DEFAULT NULL';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
		);
		$restore = $this->Dbo->columns;

		$this->Dbo->columns = array('integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'), );
		$result = $this->Dbo->buildColumn($data);
		$expected = '`int_field` int(11) NOT NULL';
		$this->assertEqual($expected, $result);

		$this->Dbo->fieldParameters['param'] = array(
			'value' => 'COLLATE',
			'quote' => false,
			'join' => ' ',
			'column' => 'Collate',
			'position' => 'beforeDefault',
			'options' => array('GOOD', 'OK')
		);
		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
			'param' => 'BAD'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`int_field` int(11) NOT NULL';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
			'param' => 'GOOD'
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`int_field` int(11) COLLATE GOOD NOT NULL';
		$this->assertEqual($expected, $result);

		$this->Dbo->columns = $restore;

		$data = array(
			'name' => 'created',
			'type' => 'timestamp',
			'default' => 'current_timestamp',
			'null' => false,
 		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'created',
			'type' => 'timestamp',
			'default' => 'CURRENT_TIMESTAMP',
			'null' => true,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'modified',
			'type' => 'timestamp',
			'null' => true,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`modified` timestamp NULL';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'modified',
			'type' => 'timestamp',
			'default' => null,
			'null' => true,
		);
		$result = $this->Dbo->buildColumn($data);
		$expected = '`modified` timestamp NULL';
		$this->assertEqual($expected, $result);
	}

/**
 * testBuildColumnBadType method
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testBuildColumnBadType() {
		$data = array(
			'name' => 'testName',
			'type' => 'varchar(255)',
			'default',
			'null' => true,
			'key'
		);
		$this->Dbo->buildColumn($data);
	}

/**
 * test hasAny()
 *
 * @return void
 */
	public function testHasAny() {
		$this->Dbo = $this->getMock('Mysql', array('connect', '_execute', 'execute', 'value'));
		$this->Model = $this->getMock('TestModel', array('getDataSource'));
		$this->Model->expects($this->any())
			->method('getDataSource')
			->will($this->returnValue($this->Dbo));

		$this->Dbo->expects($this->at(0))->method('value')
			->with('harry')
			->will($this->returnValue("'harry'"));

		$this->Dbo->expects($this->at(1))->method('execute')
			->with('SELECT COUNT(`TestModel`.`id`) AS count FROM `test_models` AS `TestModel` WHERE `TestModel`.`name` = \'harry\'');
		$this->Dbo->expects($this->at(2))->method('execute')
			->with('SELECT COUNT(`TestModel`.`id`) AS count FROM `test_models` AS `TestModel` WHERE 1 = 1');

		$this->Dbo->hasAny($this->Model, array('TestModel.name' => 'harry'));
		$this->Dbo->hasAny($this->Model, array());

	}

/**
 * test fields generating usable virtual fields to use in query
 *
 * @return void
 */
	public function testVirtualFields() {
		$this->loadFixtures('Article', 'Comment', 'Tag');
		$this->Dbo->virtualFieldSeparator = '__';
		$Article = ClassRegistry::init('Article');
		$commentsTable = $this->Dbo->fullTableName('comments', false);
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $commentsTable .
				' WHERE Article.id = ' . $commentsTable . '.article_id'
		);
		$result = $this->Dbo->fields($Article);
		$expected = array(
			'`Article`.`id`',
			'`Article`.`user_id`',
			'`Article`.`title`',
			'`Article`.`body`',
			'`Article`.`published`',
			'`Article`.`created`',
			'`Article`.`updated`',
			'(NOW()) AS  `Article__this_moment`',
			'(1 + 1) AS  `Article__two`',
			"(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS  `Article__comment_count`"
		);

		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('this_moment', 'title'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('Article.title', 'Article.this_moment'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('Article.this_moment', 'Article.title'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('Article.*'));
		$expected = array(
			'`Article`.*',
			'(NOW()) AS  `Article__this_moment`',
			'(1 + 1) AS  `Article__two`',
			"(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS  `Article__comment_count`"
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('*'));
		$expected = array(
			'*',
			'(NOW()) AS  `Article__this_moment`',
			'(1 + 1) AS  `Article__two`',
			"(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS  `Article__comment_count`"
		);
		$this->assertEqual($expected, $result);
	}

/**
 * test conditions to generate query conditions for virtual fields
 *
 * @return void
 */
	public function testVirtualFieldsInConditions() {
		$Article = ClassRegistry::init('Article');
		$commentsTable = $this->Dbo->fullTableName('comments', false);

		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $commentsTable .
				' WHERE Article.id = ' . $commentsTable . '.article_id'
		);
		$conditions = array('two' => 2);
		$result = $this->Dbo->conditions($conditions, true, false, $Article);
		$expected = '(1 + 1) = 2';
		$this->assertEqual($expected, $result);

		$conditions = array('this_moment BETWEEN ? AND ?' => array(1,2));
		$expected = 'NOW() BETWEEN 1 AND 2';
		$result = $this->Dbo->conditions($conditions, true, false, $Article);
		$this->assertEqual($expected, $result);

		$conditions = array('comment_count >' => 5);
		$expected = "(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) > 5";
		$result = $this->Dbo->conditions($conditions, true, false, $Article);
		$this->assertEqual($expected, $result);

		$conditions = array('NOT' => array('two' => 2));
		$result = $this->Dbo->conditions($conditions, true, false, $Article);
		$expected = 'NOT ((1 + 1) = 2)';
		$this->assertEqual($expected, $result);
	}

/**
 * test that virtualFields with complex functions and aliases work.
 *
 * @return void
 */
	public function testConditionsWithComplexVirtualFields() {
		$Article = ClassRegistry::init('Article', 'Comment', 'Tag');
		$Article->virtualFields = array(
			'distance' => 'ACOS(SIN(20 * PI() / 180)
					* SIN(Article.latitude * PI() / 180)
					+ COS(20 * PI() / 180)
					* COS(Article.latitude * PI() / 180)
					* COS((50 - Article.longitude) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
		);
		$conditions = array('distance >=' => 20);
		$result = $this->Dbo->conditions($conditions, true, true, $Article);

		$this->assertPattern('/\) >= 20/', $result);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]latitude[`\'"]/', $result);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]longitude[`\'"]/', $result);
	}

/**
 * test calculate to generate claculate statements on virtual fields
 *
 * @return void
 */
	public function testVirtualFieldsInCalculate() {
		$Article = ClassRegistry::init('Article');
		$commentsTable = $this->Dbo->fullTableName('comments', false);
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $commentsTable .
				' WHERE Article.id = ' . $commentsTable . '.article_id'
		);

		$result = $this->Dbo->calculate($Article, 'count', array('this_moment'));
		$expected = 'COUNT(NOW()) AS `count`';
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->calculate($Article, 'max', array('comment_count'));
		$expected = "MAX(SELECT COUNT(*) FROM $commentsTable WHERE `Article`.`id` = `$commentsTable`.`article_id`) AS `comment_count`";
		$this->assertEqual($expected, $result);
	}

/**
 * test reading virtual fields containing newlines when recursive > 0
 *
 * @return void
 */
	public function testReadVirtualFieldsWithNewLines() {
		$Article = new Article();
		$Article->recursive = 1;
		$Article->virtualFields = array(
			'test' => '
			User.id + User.id
			'
		);
		$result = $this->Dbo->fields($Article, null, array());
		$result = $this->Dbo->fields($Article, $Article->alias, $result);
		$this->assertPattern('/[`\"]User[`\"]\.[`\"]id[`\"] \+ [`\"]User[`\"]\.[`\"]id[`\"]/', $result[7]);
	}

/**
 * test group to generate GROUP BY statements on virtual fields
 *
 * @return void
 */
	public function testVirtualFieldsInGroup() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_year' => 'YEAR(Article.created)'
		);

		$result = $this->Dbo->group('this_year', $Article);

		$expected = " GROUP BY (YEAR(`Article`.`created`))";
		$this->assertEqual($expected, $result);
	}

/**
 * test that virtualFields with complex functions and aliases work.
 *
 * @return void
 */
	public function testFieldsWithComplexVirtualFields() {
		$Article = new Article();
		$Article->virtualFields = array(
			'distance' => 'ACOS(SIN(20 * PI() / 180)
					* SIN(Article.latitude * PI() / 180)
					+ COS(20 * PI() / 180)
					* COS(Article.latitude * PI() / 180)
					* COS((50 - Article.longitude) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
		);

		$fields = array('id', 'distance');
		$result = $this->Dbo->fields($Article, null, $fields);
		$qs = $this->Dbo->startQuote;
		$qe = $this->Dbo->endQuote;

		$this->assertEqual($result[0], "{$qs}Article{$qe}.{$qs}id{$qe}");
		$this->assertPattern('/Article__distance/', $result[1]);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]latitude[`\'"]/', $result[1]);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]longitude[`\'"]/', $result[1]);
	}

/**
 * test that execute runs queries.
 *
 * @return void
 */
	public function testExecute() {
		$query = 'SELECT * FROM ' . $this->Dbo->fullTableName('articles') . ' WHERE 1 = 1';
		$this->Dbo->took = null;
		$this->Dbo->affected = null;
		$result = $this->Dbo->execute($query, array('log' => false));
		$this->assertNotNull($result, 'No query performed! %s');
		$this->assertNull($this->Dbo->took, 'Stats were set %s');
		$this->assertNull($this->Dbo->affected, 'Stats were set %s');

		$result = $this->Dbo->execute($query);
		$this->assertNotNull($result, 'No query performed! %s');
		$this->assertNotNull($this->Dbo->took, 'Stats were not set %s');
		$this->assertNotNull($this->Dbo->affected, 'Stats were not set %s');
	}

/**
 * test a full example of using virtual fields
 *
 * @return void
 */
	public function testVirtualFieldsFetch() {
		$this->loadFixtures('Article', 'Comment');

		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->Dbo->fullTableName('comments') .
				' WHERE Article.id = ' . $this->Dbo->fullTableName('comments') . '.article_id'
		);

		$conditions = array('comment_count >' => 2);
		$query = 'SELECT ' . join(',',$this->Dbo->fields($Article, null, array('id', 'comment_count'))) .
				' FROM ' .  $this->Dbo->fullTableName($Article) . ' Article ' . $this->Dbo->conditions($conditions, true, true, $Article);
		$result = $this->Dbo->fetchAll($query);
		$expected = array(array(
			'Article' => array('id' => 1, 'comment_count' => 4)
		));
		$this->assertEqual($expected, $result);
	}

/**
 * test reading complex virtualFields with subqueries.
 *
 * @return void
 */
	public function testVirtualFieldsComplexRead() {
		$this->loadFixtures('DataTest', 'Article', 'Comment', 'User', 'Tag', 'ArticlesTag');

		$Article = ClassRegistry::init('Article');
		$commentTable = $this->Dbo->fullTableName('comments');
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'comment_count' => 'SELECT COUNT(*) FROM ' . $commentTable .
				' AS Comment WHERE Article.id = Comment.article_id'
		);
		$result = $Article->find('all');
		$this->assertTrue(count($result) > 0);
		$this->assertTrue($result[0]['Article']['comment_count'] > 0);

		$DataTest = ClassRegistry::init('DataTest');
		$DataTest->virtualFields = array(
			'complicated' => 'ACOS(SIN(20 * PI() / 180)
				* SIN(DataTest.float * PI() / 180)
				+ COS(20 * PI() / 180)
				* COS(DataTest.count * PI() / 180)
				* COS((50 - DataTest.float) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
		);
		$result = $DataTest->find('all');
		$this->assertTrue(count($result) > 0);
		$this->assertTrue($result[0]['DataTest']['complicated'] > 0);
	}

/**
 * testIntrospectType method
 *
 * @return void
 */
	public function testIntrospectType() {
		$this->assertEqual($this->Dbo->introspectType(0), 'integer');
		$this->assertEqual($this->Dbo->introspectType(2), 'integer');
		$this->assertEqual($this->Dbo->introspectType('2'), 'string');
		$this->assertEqual($this->Dbo->introspectType('2.2'), 'string');
		$this->assertEqual($this->Dbo->introspectType(2.2), 'float');
		$this->assertEqual($this->Dbo->introspectType('stringme'), 'string');
		$this->assertEqual($this->Dbo->introspectType('0stringme'), 'string');

		$data = array(2.2);
		$this->assertEqual($this->Dbo->introspectType($data), 'float');

		$data = array('2.2');
		$this->assertEqual($this->Dbo->introspectType($data), 'float');

		$data = array(2);
		$this->assertEqual($this->Dbo->introspectType($data), 'integer');

		$data = array('2');
		$this->assertEqual($this->Dbo->introspectType($data), 'integer');

		$data = array('string');
		$this->assertEqual($this->Dbo->introspectType($data), 'string');

		$data = array(2.2, '2.2');
		$this->assertEqual($this->Dbo->introspectType($data), 'float');

		$data = array(2, '2');
		$this->assertEqual($this->Dbo->introspectType($data), 'integer');

		$data = array('string one', 'string two');
		$this->assertEqual($this->Dbo->introspectType($data), 'string');

		$data = array('2.2', 3);
		$this->assertEqual($this->Dbo->introspectType($data), 'integer');

		$data = array('2.2', '0stringme');
		$this->assertEqual($this->Dbo->introspectType($data), 'string');

		$data = array(2.2, 3);
		$this->assertEqual($this->Dbo->introspectType($data), 'integer');

		$data = array(2.2, '0stringme');
		$this->assertEqual($this->Dbo->introspectType($data), 'string');

		$data = array(2, 'stringme');
		$this->assertEqual($this->Dbo->introspectType($data), 'string');

		$data = array(2, '2.2', 'stringgme');
		$this->assertEqual($this->Dbo->introspectType($data), 'string');

		$data = array(2, '2.2');
		$this->assertEqual($this->Dbo->introspectType($data), 'integer');

		$data = array(2, 2.2);
		$this->assertEqual($this->Dbo->introspectType($data), 'integer');


		// NULL
		$result = $this->Dbo->value(null, 'boolean');
		$this->assertEqual($result, 'NULL');

		// EMPTY STRING
		$result = $this->Dbo->value('', 'boolean');
		$this->assertEqual($result, "'0'");


		// BOOLEAN
		$result = $this->Dbo->value('true', 'boolean');
		$this->assertEqual($result, "'1'");

		$result = $this->Dbo->value('false', 'boolean');
		$this->assertEqual($result, "'1'");

		$result = $this->Dbo->value(true, 'boolean');
		$this->assertEqual($result, "'1'");

		$result = $this->Dbo->value(false, 'boolean');
		$this->assertEqual($result, "'0'");

		$result = $this->Dbo->value(1, 'boolean');
		$this->assertEqual($result, "'1'");

		$result = $this->Dbo->value(0, 'boolean');
		$this->assertEqual($result, "'0'");

		$result = $this->Dbo->value('abc', 'boolean');
		$this->assertEqual($result, "'1'");

		$result = $this->Dbo->value(1.234, 'boolean');
		$this->assertEqual($result, "'1'");

		$result = $this->Dbo->value('1.234e05', 'boolean');
		$this->assertEqual($result, "'1'");

		// NUMBERS
		$result = $this->Dbo->value(123, 'integer');
		$this->assertEqual($result, 123);

		$result = $this->Dbo->value('123', 'integer');
		$this->assertEqual($result, '123');

		$result = $this->Dbo->value('0123', 'integer');
		$this->assertEqual($result, "'0123'");

		$result = $this->Dbo->value('0x123ABC', 'integer');
		$this->assertEqual($result, "'0x123ABC'");

		$result = $this->Dbo->value('0x123', 'integer');
		$this->assertEqual($result, "'0x123'");

		$result = $this->Dbo->value(1.234, 'float');
		$this->assertEqual($result, 1.234);

		$result = $this->Dbo->value('1.234', 'float');
		$this->assertEqual($result, '1.234');

		$result = $this->Dbo->value(' 1.234 ', 'float');
		$this->assertEqual($result, "' 1.234 '");

		$result = $this->Dbo->value('1.234e05', 'float');
		$this->assertEqual($result, "'1.234e05'");

		$result = $this->Dbo->value('1.234e+5', 'float');
		$this->assertEqual($result, "'1.234e+5'");

		$result = $this->Dbo->value('1,234', 'float');
		$this->assertEqual($result, "'1,234'");

		$result = $this->Dbo->value('FFF', 'integer');
		$this->assertEqual($result, "'FFF'");

		$result = $this->Dbo->value('abc', 'integer');
		$this->assertEqual($result, "'abc'");

		// STRINGS
		$result = $this->Dbo->value('123', 'string');
		$this->assertEqual($result, "'123'");

		$result = $this->Dbo->value(123, 'string');
		$this->assertEqual($result, "'123'");

		$result = $this->Dbo->value(1.234, 'string');
		$this->assertEqual($result, "'1.234'");

		$result = $this->Dbo->value('abc', 'string');
		$this->assertEqual($result, "'abc'");

		$result = $this->Dbo->value(' abc ', 'string');
		$this->assertEqual($result, "' abc '");

		$result = $this->Dbo->value('a bc', 'string');
		$this->assertEqual($result, "'a bc'");
	}

/**
 * testRealQueries method
 *
 * @return void
 */
	public function testRealQueries() {
		$this->loadFixtures('Apple', 'Article', 'User', 'Comment', 'Tag', 'Sample', 'ArticlesTag');

		$Apple = ClassRegistry::init('Apple');
		$Article = ClassRegistry::init('Article');

		$result = $this->Dbo->rawQuery('SELECT color, name FROM ' . $this->Dbo->fullTableName('apples'));
		$this->assertTrue(!empty($result));

		$result = $this->Dbo->fetchRow($result);
		$expected = array($this->Dbo->fullTableName('apples', false) => array(
			'color' => 'Red 1',
			'name' => 'Red Apple 1'
		));
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fetchAll('SELECT name FROM ' . $this->Dbo->fullTableName('apples') . ' ORDER BY id');
		$expected = array(
			array($this->Dbo->fullTableName('apples', false) => array('name' => 'Red Apple 1')),
			array($this->Dbo->fullTableName('apples', false) => array('name' => 'Bright Red Apple')),
			array($this->Dbo->fullTableName('apples', false) => array('name' => 'green blue')),
			array($this->Dbo->fullTableName('apples', false) => array('name' => 'Test Name')),
			array($this->Dbo->fullTableName('apples', false) => array('name' => 'Blue Green')),
			array($this->Dbo->fullTableName('apples', false) => array('name' => 'My new apple')),
			array($this->Dbo->fullTableName('apples', false) => array('name' => 'Some odd color'))
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->field($this->Dbo->fullTableName('apples', false), 'SELECT color, name FROM ' . $this->Dbo->fullTableName('apples') . ' ORDER BY id');
		$expected = array(
			'color' => 'Red 1',
			'name' => 'Red Apple 1'
		);
		$this->assertEqual($expected, $result);

		$Apple->unbindModel(array(), false);
		$result = $this->Dbo->read($Apple, array(
			'fields' => array($Apple->escapeField('name')),
			'conditions' => null,
			'recursive' => -1
		));
		$expected = array(
			array('Apple' => array('name' => 'Red Apple 1')),
			array('Apple' => array('name' => 'Bright Red Apple')),
			array('Apple' => array('name' => 'green blue')),
			array('Apple' => array('name' => 'Test Name')),
			array('Apple' => array('name' => 'Blue Green')),
			array('Apple' => array('name' => 'My new apple')),
			array('Apple' => array('name' => 'Some odd color'))
		);
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->read($Article, array(
			'fields' => array('id', 'user_id', 'title'),
			'conditions' => null,
			'recursive' => 1
		));

		$this->assertTrue(Set::matches('/Article[id=1]', $result));
		$this->assertTrue(Set::matches('/Comment[id=1]', $result));
		$this->assertTrue(Set::matches('/Comment[id=2]', $result));
		$this->assertFalse(Set::matches('/Comment[id=10]', $result));
	}

/**
 * @expectedException MissingConnectionException
 * @return void
 */
	public function testExceptionOnBrokenConnection() {
		$dbo = new Mysql(array(
			'driver' => 'mysql',
			'host' => 'imaginary_host',
			'login' => 'mark',
			'password' => 'inyurdatabase',
			'database' => 'imaginary'
		));
	}
}
