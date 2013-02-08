<?php
/**
 * CakeTestFixture file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('DboSource', 'Model/Datasource');
App::uses('Model', 'Model');
App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * CakeTestFixtureTestFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureTestFixture extends CakeTestFixture {

/**
 * Name property
 *
 * @var string
 */
	public $name = 'FixtureTest';

/**
 * Table property
 *
 * @var string
 */
	public $table = 'fixture_tests';

/**
 * Fields array
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => '255'),
		'created' => array('type' => 'datetime')
	);

/**
 * Records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'),
		array('name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'),
		array('name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00')
	);
}

/**
 * StringFieldsTestFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 * @subpackage    cake.cake.tests.cases.libs
 */
class StringsTestFixture extends CakeTestFixture {

/**
 * Name property
 *
 * @var string
 */
	public $name = 'Strings';

/**
 * Table property
 *
 * @var string
 */
	public $table = 'strings';

/**
 * Fields array
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => '255'),
		'email' => array('type' => 'string', 'length' => '255'),
		'age' => array('type' => 'integer', 'default' => 10)
	);

/**
 * Records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Mark Doe', 'email' => 'mark.doe@email.com'),
		array('name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20),
		array('email' => 'jane.doe@email.com', 'name' => 'Jane Doe', 'age' => 30)
	);
}


/**
 * CakeTestFixtureImportFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureImportFixture extends CakeTestFixture {

/**
 * Name property
 *
 * @var string
 */
	public $name = 'ImportFixture';

/**
 * Import property
 *
 * @var mixed
 */
	public $import = array('table' => 'fixture_tests', 'connection' => 'fixture_test_suite');
}

/**
 * CakeTestFixtureDefaultImportFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureDefaultImportFixture extends CakeTestFixture {

/**
 * Name property
 *
 * @var string
 */
	public $name = 'ImportFixture';
}

/**
 * FixtureImportTestModel class
 *
 * @package       Cake.Test.Case.TestSuite
 * @package       Cake.Test.Case.TestSuite
 */
class FixtureImportTestModel extends Model {

	public $name = 'FixtureImport';

	public $useTable = 'fixture_tests';

	public $useDbConfig = 'test';

}

class FixturePrefixTest extends Model {

	public $name = 'FixturePrefix';

	public $useTable = '_tests';

	public $tablePrefix = 'fixture';

	public $useDbConfig = 'test';
}


/**
 * Test case for CakeTestFixture
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$methods = array_diff(get_class_methods('DboSource'), array('enabled'));
		$methods[] = 'connect';

		$this->criticDb = $this->getMock('DboSource', $methods);
		$this->criticDb->fullDebug = true;
		$this->db = ConnectionManager::getDataSource('test');
		$this->_backupConfig = $this->db->config;
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->criticDb);
		$this->db->config = $this->_backupConfig;
	}

/**
 * testInit
 *
 * @return void
 */
	public function testInit() {
		$Fixture = new CakeTestFixtureTestFixture();
		unset($Fixture->table);
		$Fixture->init();
		$this->assertEquals('fixture_tests', $Fixture->table);
		$this->assertEquals('id', $Fixture->primaryKey);

		$Fixture = new CakeTestFixtureTestFixture();
		$Fixture->primaryKey = 'my_random_key';
		$Fixture->init();
		$this->assertEquals('my_random_key', $Fixture->primaryKey);
	}

/**
 * test that init() correctly sets the fixture table when the connection
 * or model have prefixes defined.
 *
 * @return void
 */
	public function testInitDbPrefix() {
		$this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
		$db = ConnectionManager::getDataSource('test');
		$Source = new CakeTestFixtureTestFixture();
		$Source->drop($db);
		$Source->create($db);
		$Source->insert($db);

		$Fixture = new CakeTestFixtureTestFixture();
		$expected = array('id', 'name', 'created');
		$this->assertEquals($expected, array_keys($Fixture->fields));

		$config = $db->config;
		$config['prefix'] = 'fixture_test_suite_';
		ConnectionManager::create('fixture_test_suite', $config);

		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('table' => 'fixture_tests', 'connection' => 'test', 'records' => true);
		$Fixture->init();
		$this->assertEquals(count($Fixture->records), count($Source->records));
		$Fixture->create(ConnectionManager::getDataSource('fixture_test_suite'));

		$Fixture = new CakeTestFixtureImportFixture();
		$Fixture->fields = $Fixture->records = $Fixture->table = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'test');
		$Fixture->init();
		$this->assertEquals(array('id', 'name', 'created'), array_keys($Fixture->fields));
		$this->assertEquals('fixture_tests', $Fixture->table);

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Fixture->drop(ConnectionManager::getDataSource('fixture_test_suite'));
		$Source->drop($db);
	}

/**
 * test that fixtures don't duplicate the test db prefix.
 *
 * @return void
 */
	public function testInitDbPrefixDuplication() {
		$this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
		$db = ConnectionManager::getDataSource('test');
		$backPrefix = $db->config['prefix'];
		$db->config['prefix'] = 'cake_fixture_test_';
		ConnectionManager::create('fixture_test_suite', $db->config);
		$newDb = ConnectionManager::getDataSource('fixture_test_suite');
		$newDb->config['prefix'] = 'cake_fixture_test_';

		$Source = new CakeTestFixtureTestFixture();
		$Source->create($db);
		$Source->insert($db);

		$Fixture = new CakeTestFixtureImportFixture();
		$Fixture->fields = $Fixture->records = $Fixture->table = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'test');

		$Fixture->init();
		$this->assertEquals(array('id', 'name', 'created'), array_keys($Fixture->fields));
		$this->assertEquals('fixture_tests', $Fixture->table);

		$Source->drop($db);
		$db->config['prefix'] = $backPrefix;
	}

/**
 * test init with a model that has a tablePrefix declared.
 *
 * @return void
 */
	public function testInitModelTablePrefix() {
		$this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
		$this->skipIf(!empty($this->db->config['prefix']), 'Cannot run this test, you have a database connection prefix.');

		$Source = new CakeTestFixtureTestFixture();
		$Source->create($this->db);
		$Source->insert($this->db);

		$Fixture = new CakeTestFixtureTestFixture();
		unset($Fixture->table);
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('model' => 'FixturePrefixTest', 'connection' => 'test', 'records' => false);
		$Fixture->init();
		$this->assertEquals('fixture_tests', $Fixture->table);

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Source->drop($this->db);
	}

/**
 * testImport
 *
 * @return void
 */
	public function testImport() {
		$testSuiteDb = ConnectionManager::getDataSource('test');
		$testSuiteConfig = $testSuiteDb->config;
		ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, array('prefix' => 'new_' . $testSuiteConfig['prefix'])));
		$newTestSuiteDb = ConnectionManager::getDataSource('new_test_suite');

		$Source = new CakeTestFixtureTestFixture();
		$Source->create($newTestSuiteDb);
		$Source->insert($newTestSuiteDb);

		$Fixture = new CakeTestFixtureDefaultImportFixture();
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'new_test_suite');
		$Fixture->init();
		$this->assertEquals(array('id', 'name', 'created'), array_keys($Fixture->fields));

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Source->drop($newTestSuiteDb);
	}

/**
 * test that importing with records works. Make sure to try with postgres as its
 * handling of aliases is a workaround at best.
 *
 * @return void
 */
	public function testImportWithRecords() {
		$testSuiteDb = ConnectionManager::getDataSource('test');
		$testSuiteConfig = $testSuiteDb->config;
		ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, array('prefix' => 'new_' . $testSuiteConfig['prefix'])));
		$newTestSuiteDb = ConnectionManager::getDataSource('new_test_suite');

		$Source = new CakeTestFixtureTestFixture();
		$Source->create($newTestSuiteDb);
		$Source->insert($newTestSuiteDb);

		$Fixture = new CakeTestFixtureDefaultImportFixture();
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array(
			'model' => 'FixtureImportTestModel', 'connection' => 'new_test_suite', 'records' => true
		);
		$Fixture->init();
		$this->assertEquals(array('id', 'name', 'created'), array_keys($Fixture->fields));
		$this->assertFalse(empty($Fixture->records[0]), 'No records loaded on importing fixture.');
		$this->assertTrue(isset($Fixture->records[0]['name']), 'No name loaded for first record');

		$Source->drop($newTestSuiteDb);
	}

/**
 * test create method
 *
 * @return void
 */
	public function testCreate() {
		$Fixture = new CakeTestFixtureTestFixture();
		$this->criticDb->expects($this->atLeastOnce())->method('execute');
		$this->criticDb->expects($this->atLeastOnce())->method('createSchema');
		$return = $Fixture->create($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);

		unset($Fixture->fields);
		$return = $Fixture->create($this->criticDb);
		$this->assertFalse($return);
	}

/**
 * test the insert method
 *
 * @return void
 */
	public function testInsert() {
		$Fixture = new CakeTestFixtureTestFixture();
		$this->criticDb->expects($this->atLeastOnce())
			->method('insertMulti')
			->will($this->returnCallback(array($this, 'insertCallback')));

		$return = $Fixture->insert($this->criticDb);
		$this->assertTrue(!empty($this->insertMulti));
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);
		$this->assertEquals('fixture_tests', $this->insertMulti['table']);
		$this->assertEquals(array('name', 'created'), $this->insertMulti['fields']);
		$expected = array(
			array('Gandalf', '2009-04-28 19:20:00'),
			array('Captain Picard', '2009-04-28 19:20:00'),
			array('Chewbacca', '2009-04-28 19:20:00')
		);
		$this->assertEquals($expected, $this->insertMulti['values']);
	}

/**
 * Helper function to be used as callback and store the parameters of an insertMulti call
 *
 * @param string $table
 * @param string $fields
 * @param string $values
 * @return boolean true
 */
	public function insertCallback($table, $fields, $values) {
		$this->insertMulti['table'] = $table;
		$this->insertMulti['fields'] = $fields;
		$this->insertMulti['values'] = $values;
		return true;
	}

/**
 * test the insert method
 *
 * @return void
 */
	public function testInsertStrings() {
		$Fixture = new StringsTestFixture();
		$this->criticDb->expects($this->atLeastOnce())
			->method('insertMulti')
			->will($this->returnCallback(array($this, 'insertCallback')));

		$return = $Fixture->insert($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);
		$this->assertEquals('strings', $this->insertMulti['table']);
		$this->assertEquals(array('email', 'name', 'age'), $this->insertMulti['fields']);
		$expected = array(
			array('Mark Doe', 'mark.doe@email.com', null),
			array('John Doe', 'john.doe@email.com', 20),
			array('Jane Doe', 'jane.doe@email.com', 30),
		);
		$this->assertEquals($expected, $this->insertMulti['values']);
	}

/**
 * Test the drop method
 *
 * @return void
 */
	public function testDrop() {
		$Fixture = new CakeTestFixtureTestFixture();
		$this->criticDb->expects($this->at(1))->method('execute')->will($this->returnValue(true));
		$this->criticDb->expects($this->at(3))->method('execute')->will($this->returnValue(false));
		$this->criticDb->expects($this->exactly(2))->method('dropSchema');

		$return = $Fixture->drop($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);

		$return = $Fixture->drop($this->criticDb);
		$this->assertTrue($return);

		unset($Fixture->fields);
		$return = $Fixture->drop($this->criticDb);
		$this->assertFalse($return);
	}

/**
 * Test the truncate method.
 *
 * @return void
 */
	public function testTruncate() {
		$Fixture = new CakeTestFixtureTestFixture();
		$this->criticDb->expects($this->atLeastOnce())->method('truncate');
		$Fixture->truncate($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
	}
}
