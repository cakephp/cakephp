<?php
/**
 * CakeTestFixture file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('DboSource', 'Model/Datasource');
App::uses('Model', 'Model');
App::uses('CakeTestFixture', 'TestSuite/Fixture');

/**
 * CakeTestFixtureTestFixture class
 *
 * @package       cake.tests.cases.libs
 */
class CakeTestFixtureTestFixture extends CakeTestFixture {

/**
 * name Property
 *
 * @var string
 */
	public $name = 'FixtureTest';

/**
 * table property
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
		'id' => array('type' => 'integer',  'key' => 'primary'),
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
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs
 */
class StringsTestFixture extends CakeTestFixture {

/**
 * name Property
 *
 * @var string
 */
	var $name = 'Strings';

/**
 * table property
 *
 * @var string
 */
	var $table = 'strings';

/**
 * Fields array
 *
 * @var array
 */
	var $fields = array(
		'id' => array('type' => 'integer',  'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => '255'),
		'email' => array('type' => 'string', 'length' => '255'),
        'age' => array('type' => 'integer', 'default' => 10)
	);

/**
 * Records property
 *
 * @var array
 */
	var $records = array(
		array('name' => 'Mark Doe', 'email' => 'mark.doe@email.com'),
        array('name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20),
        array('email' => 'jane.doe@email.com', 'name' => 'Jane Doe', 'age' => 30)
	);
}


/**
 * CakeTestFixtureImportFixture class
 *
 * @package       cake.tests.cases.libs
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
 * @package       cake.tests.cases.libs
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
 * @package       default
 * @package       cake.tests.cases.libs.
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
 * @package       cake.tests.cases.libs
 */
class CakeTestFixtureTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->criticDb = $this->getMock('DboSource');
		$this->criticDb->fullDebug = true;
		$this->db = ConnectionManager::getDataSource('test');
		$this->_backupConfig = $this->db->config;
	}

/**
 * tearDown
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->criticDb);
		$this->db->config = $this->_backupConfig;
	}

/**
 * testInit
 *
 * @access public
 * @return void
 */
	function testInit() {
		$Fixture = new CakeTestFixtureTestFixture();
		unset($Fixture->table);
		$Fixture->init();
		$this->assertEqual($Fixture->table, 'fixture_tests');
		$this->assertEqual($Fixture->primaryKey, 'id');

		$Fixture = new CakeTestFixtureTestFixture();
		$Fixture->primaryKey = 'my_random_key';
		$Fixture->init();
		$this->assertEqual($Fixture->primaryKey, 'my_random_key');
	}


/**
 * test that init() correctly sets the fixture table when the connection or model have prefixes defined.
 *
 * @return void
 */
	function testInitDbPrefix() {
		$db = ConnectionManager::getDataSource('test');
		$Source = new CakeTestFixtureTestFixture();
		$Source->drop($db);
		$Source->create($db);
		$Source->insert($db);

		$Fixture = new CakeTestFixtureTestFixture();
		$expected = array('id', 'name', 'created');
		$this->assertEqual(array_keys($Fixture->fields), $expected);

		$config = $db->config;
		$config['prefix'] = 'fixture_test_suite_';
		ConnectionManager::create('fixture_test_suite', $config);

		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('table' => 'fixture_tests', 'connection' => 'test', 'records' => true);
		$Fixture->init();
		$this->assertEqual(count($Fixture->records), count($Source->records));
		$Fixture->create(ConnectionManager::getDataSource('fixture_test_suite'));

		$Fixture = new CakeTestFixtureImportFixture();
		$Fixture->fields = $Fixture->records = $Fixture->table = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'test');
		$Fixture->init();
		$this->assertEqual(array_keys($Fixture->fields), array('id', 'name', 'created'));
		$this->assertEqual($Fixture->table, 'fixture_tests');

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
	function testInitDbPrefixDuplication() {
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
		$this->assertEqual(array_keys($Fixture->fields), array('id', 'name', 'created'));
		$this->assertEqual($Fixture->table, 'fixture_tests');

		$Source->drop($db);
		$db->config['prefix'] = $backPrefix;
	}

/**
 * test init with a model that has a tablePrefix declared.
 *
 * @return void
 */
	function testInitModelTablePrefix() {
		$hasPrefix = !empty($this->db->config['prefix']);
		if ($this->skipIf($hasPrefix, 'Cannot run this test, you have a database connection prefix.')) {
			return;
		}
		$Source = new CakeTestFixtureTestFixture();
		$Source->create($this->db);
		$Source->insert($this->db);

		$Fixture = new CakeTestFixtureTestFixture();
		unset($Fixture->table);
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('model' => 'FixturePrefixTest', 'connection' => 'test', 'records' => false);
		$Fixture->init();
		$this->assertEqual($Fixture->table, 'fixture_tests');

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Source->drop($this->db);
	}

/**
 * testImport
 *
 * @access public
 * @return void
 */
	function testImport() {
		$defaultDb = ConnectionManager::getDataSource('default');
		$testSuiteDb = ConnectionManager::getDataSource('test');
		$defaultConfig = $defaultDb->config;
		$testSuiteConfig = $testSuiteDb->config;
		ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, array('prefix' => 'new_' . $testSuiteConfig['prefix'])));
		$newTestSuiteDb = ConnectionManager::getDataSource('new_test_suite');

		$Source = new CakeTestFixtureTestFixture();
		$Source->create($newTestSuiteDb);
		$Source->insert($newTestSuiteDb);

		$defaultDb->config = $newTestSuiteDb->config;

		$Fixture = new CakeTestFixtureDefaultImportFixture();
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'new_test_suite');
		$Fixture->init();
		$this->assertEqual(array_keys($Fixture->fields), array('id', 'name', 'created'));

		$defaultDb->config = $defaultConfig;

		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Source->drop($newTestSuiteDb);
	}

/**
 * test that importing with records works.  Make sure to try with postgres as its
 * handling of aliases is a workaround at best.
 *
 * @return void
 */
	function testImportWithRecords() {

		$defaultDb = ConnectionManager::getDataSource('default');
		$testSuiteDb = ConnectionManager::getDataSource('test');
		$defaultConfig = $defaultDb->config;
		$testSuiteConfig = $testSuiteDb->config;
		ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, array('prefix' => 'new_' . $testSuiteConfig['prefix'])));
		$newTestSuiteDb = ConnectionManager::getDataSource('new_test_suite');

		$Source = new CakeTestFixtureTestFixture();
		$Source->create($newTestSuiteDb);
		$Source->insert($newTestSuiteDb);

		$defaultDb->config = $newTestSuiteDb->config;

		$Fixture = new CakeTestFixtureDefaultImportFixture();
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array(
			'model' => 'FixtureImportTestModel', 'connection' => 'new_test_suite', 'records' => true
		);
		$Fixture->init();
		$this->assertEqual(array_keys($Fixture->fields), array('id', 'name', 'created'));
		$this->assertFalse(empty($Fixture->records[0]), 'No records loaded on importing fixture.');
		$this->assertTrue(isset($Fixture->records[0]['name']), 'No name loaded for first record');

		$defaultDb->config = $defaultConfig;

		$Source->drop($newTestSuiteDb);
	}

/**
 * test create method
 *
 * @access public
 * @return void
 */
	function testCreate() {
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
 * @access public
 * @return void
 */
	function testInsert() {
		$Fixture = new CakeTestFixtureTestFixture();
		$this->criticDb->expects($this->atLeastOnce())
			->method('insertMulti')
			->will($this->returnCallback(array($this, '_insertCallback')));

		$return = $Fixture->insert($this->criticDb);
		$this->assertTrue(!empty($this->insertMulti));
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);
		$this->assertEqual('fixture_tests', $this->insertMulti['table']);
		$this->assertEqual(array('name', 'created'), $this->insertMulti['fields']);
		$expected = array(
			array('Gandalf', '2009-04-28 19:20:00'),
			array('Captain Picard', '2009-04-28 19:20:00'),
			array('Chewbacca', '2009-04-28 19:20:00')
		);
		$this->assertEqual($expected, $this->insertMulti['values']);
	}

/**
 * Helper function to be used as callback and store the parameters of an insertMulti call
 *
 * @param string $table 
 * @param string $fields 
 * @param string $values 
 * @return boolean true
 */
	function _insertCallback($table, $fields, $values) {
		$this->insertMulti['table'] = $table;
		$this->insertMulti['fields'] = $fields;
		$this->insertMulti['values'] = $values;
		return true;
	}

/**
 * test the insert method
 *
 * @access public
 * @return void
 */
	function testInsertStrings() {
		$Fixture = new StringsTestFixture();
		$this->criticDb->expects($this->atLeastOnce())
			->method('insertMulti')
			->will($this->returnCallback(array($this, '_insertCallback')));

		$return = $Fixture->insert($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);
		$this->assertEqual('strings', $this->insertMulti['table']);
		$this->assertEqual(array('email', 'name', 'age'), $this->insertMulti['fields']);
		$expected = array(
			array('Mark Doe', 'mark.doe@email.com', null),
			array('John Doe', 'john.doe@email.com', 20),
			array('Jane Doe', 'jane.doe@email.com', 30),
		);
		$this->assertEqual($expected, $this->insertMulti['values']);
	}

/**
 * Test the drop method
 *
 * @access public
 * @return void
 */
	function testDrop() {
		$Fixture = new CakeTestFixtureTestFixture();
		$this->criticDb->expects($this->at(1))->method('execute')->will($this->returnValue(true));
		$this->criticDb->expects($this->at(3))->method('execute')->will($this->returnValue(false));
		$this->criticDb->expects($this->exactly(2))->method('dropSchema');

		$return = $Fixture->drop($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);

		$return = $Fixture->drop($this->criticDb);
		$this->assertFalse($return);

		unset($Fixture->fields);
		$return = $Fixture->drop($this->criticDb);
		$this->assertFalse($return);
	}

/**
 * Test the truncate method.
 *
 * @access public
 * @return void
 */
	function testTruncate() {
		$Fixture = new CakeTestFixtureTestFixture();
		$this->criticDb->expects($this->atLeastOnce())->method('truncate');
		$Fixture->truncate($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
	}
}
