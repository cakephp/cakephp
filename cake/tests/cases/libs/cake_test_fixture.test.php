<?php
/**
 * CakeTestFixture file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Datasource', 'DboSource', false);

/**
 * CakeTestFixtureTestFixture class
 *
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs
 */
class CakeTestFixtureTestFixture extends CakeTestFixture {

/**
 * name Property
 *
 * @var string
 */
	var $name = 'FixtureTest';

/**
 * table property
 *
 * @var string
 */
	var $table = 'fixture_tests';

/**
 * Fields array
 *
 * @var array
 */
	var $fields = array(
		'id' => array('type' => 'integer',  'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => '255'),
		'created' => array('type' => 'datetime')
	);

/**
 * Records property
 *
 * @var array
 */
	var $records = array(
		array('name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'),
		array('name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'),
		array('name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00')
	);
}

/**
 * CakeTestFixtureImportFixture class
 *
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs
 */
class CakeTestFixtureImportFixture extends CakeTestFixture {

/**
 * Name property
 *
 * @var string
 */
	var $name = 'ImportFixture';

/**
 * Import property
 *
 * @var mixed
 */
	var $import = array('table' => 'fixture_tests', 'connection' => 'test_suite');
}

/**
 * CakeTestFixtureDefaultImportFixture class
 *
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs
 */
class CakeTestFixtureDefaultImportFixture extends CakeTestFixture {

/**
 * Name property
 *
 * @var string
 */
	var $name = 'ImportFixture';
}

/**
 * FixtureImportTestModel class
 *
 * @package       default
 * @subpackage    cake.cake.tests.cases.libs.
 */
class FixtureImportTestModel extends Model {
	var $name = 'FixtureImport';
	var $useTable = 'fixture_tests';
	var $useDbConfig = 'test_suite';
}

class FixturePrefixTest extends Model {
	var $name = 'FixturePrefix';
	var $useTable = '_tests';
	var $tablePrefix = 'fixture';
	var $useDbConfig = 'test_suite';
}

Mock::generate('DboSource', 'FixtureMockDboSource');

/**
 * Test case for CakeTestFixture
 *
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs
 */
class CakeTestFixtureTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->criticDb =& new FixtureMockDboSource();
		$this->criticDb->fullDebug = true;
	}

/**
 * tearDown
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->criticDb);
	}

/**
 * testInit
 *
 * @access public
 * @return void
 */
	function testInit() {
		$Fixture =& new CakeTestFixtureTestFixture();
		unset($Fixture->table);
		$Fixture->init();
		$this->assertEqual($Fixture->table, 'fixture_tests');
		$this->assertEqual($Fixture->primaryKey, 'id');

		$Fixture =& new CakeTestFixtureTestFixture();
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
		$this->_initDb();
		$Source =& new CakeTestFixtureTestFixture();
		$Source->create($this->db);
		$Source->insert($this->db);

		$Fixture =& new CakeTestFixtureImportFixture();
		$expected = array('id', 'name', 'created');
		$this->assertEqual(array_keys($Fixture->fields), $expected);

		$db =& ConnectionManager::getDataSource('test_suite');
		$config = $db->config;
		$config['prefix'] = 'fixture_test_suite_';
		ConnectionManager::create('fixture_test_suite', $config);

		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('table' => 'fixture_tests', 'connection' => 'test_suite', 'records' => true);
		$Fixture->init();
		$this->assertEqual(count($Fixture->records), count($Source->records));

		$Fixture =& new CakeTestFixtureImportFixture();
		$Fixture->fields = $Fixture->records = $Fixture->table = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'test_suite');
		$Fixture->init();
		$this->assertEqual(array_keys($Fixture->fields), array('id', 'name', 'created'));
		$this->assertEqual($Fixture->table, 'fixture_tests');
		
		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Source->drop($this->db);
	}

/**
 * test that fixtures don't duplicate the test db prefix.
 *
 * @return void
 */
	function testInitDbPrefixDuplication() {
		$this->_initDb();
		$backPrefix = $this->db->config['prefix'];
		$this->db->config['prefix'] = 'cake_fixture_test_';

		$Source =& new CakeTestFixtureTestFixture();
		$Source->create($this->db);
		$Source->insert($this->db);

		$Fixture =& new CakeTestFixtureImportFixture();
		$Fixture->fields = $Fixture->records = $Fixture->table = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel', 'connection' => 'test_suite');

		$Fixture->init();
		$this->assertEqual(array_keys($Fixture->fields), array('id', 'name', 'created'));
		$this->assertEqual($Fixture->table, 'fixture_tests');

		$Source->drop($this->db);
		$this->db->config['prefix'] = $backPrefix;
	}

/**
 * test init with a model that has a tablePrefix declared.
 *
 * @return void
 */
	function testInitModelTablePrefix() {
		$this->_initDb();
		$hasPrefix = !empty($this->db->config['prefix']);
		if ($this->skipIf($hasPrefix, 'Cannot run this test, you have a database connection prefix.')) {
			return;
		}
		$Source =& new CakeTestFixtureTestFixture();
		$Source->create($this->db);
		$Source->insert($this->db);

		$Fixture =& new CakeTestFixtureImportFixture();
		unset($Fixture->table);
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('model' => 'FixturePrefixTest', 'connection' => 'test_suite', 'records' => false);
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
		$this->_initDb();

		$defaultDb =& ConnectionManager::getDataSource('default');
		$testSuiteDb =& ConnectionManager::getDataSource('test_suite');
		$defaultConfig = $defaultDb->config;
		$testSuiteConfig = $testSuiteDb->config;
		ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, array('prefix' => 'new_' . $testSuiteConfig['prefix'])));
		$newTestSuiteDb =& ConnectionManager::getDataSource('new_test_suite');

		$Source =& new CakeTestFixtureTestFixture();
		$Source->create($newTestSuiteDb);
		$Source->insert($newTestSuiteDb);

		$defaultDb->config = $newTestSuiteDb->config;

		$Fixture =& new CakeTestFixtureDefaultImportFixture();
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
		$this->_initDb();

		$defaultDb =& ConnectionManager::getDataSource('default');
		$testSuiteDb =& ConnectionManager::getDataSource('test_suite');
		$defaultConfig = $defaultDb->config;
		$testSuiteConfig = $testSuiteDb->config;
		ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, array('prefix' => 'new_' . $testSuiteConfig['prefix'])));
		$newTestSuiteDb =& ConnectionManager::getDataSource('new_test_suite');

		$Source =& new CakeTestFixtureTestFixture();
		$Source->create($newTestSuiteDb);
		$Source->insert($newTestSuiteDb);

		$defaultDb->config = $newTestSuiteDb->config;

		$Fixture =& new CakeTestFixtureDefaultImportFixture();
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
		$Fixture =& new CakeTestFixtureTestFixture();
		$this->criticDb->expectAtLeastOnce('execute');
		$this->criticDb->expectAtLeastOnce('createSchema');
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
		$Fixture =& new CakeTestFixtureTestFixture();
		$this->criticDb->setReturnValue('insertMulti', true);
		$this->criticDb->expectAtLeastOnce('insertMulti');

		$return = $Fixture->insert($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);
	}

/**
 * Test the drop method
 *
 * @access public
 * @return void
 */
	function testDrop() {
		$Fixture =& new CakeTestFixtureTestFixture();
		$this->criticDb->setReturnValueAt(0, 'execute', true);
		$this->criticDb->expectAtLeastOnce('execute');
		$this->criticDb->expectAtLeastOnce('dropSchema');

		$return = $Fixture->drop($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
		$this->assertTrue($return);

		$this->criticDb->setReturnValueAt(1, 'execute', false);
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
		$Fixture =& new CakeTestFixtureTestFixture();
		$this->criticDb->expectAtLeastOnce('truncate');
		$Fixture->truncate($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
	}
}
