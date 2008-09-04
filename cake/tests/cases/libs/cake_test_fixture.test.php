<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake
 * @subpackage		cake.cake.tests.libs
 * @since			CakePHP(tm) v 1.2.0.4667
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'DboSource');

/**
 * CakeFixture Test Fixture
 *
 * @package cake
 * @subpackage cake.cake.tests.cases.libs
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
		'name' => array('type' => 'text', 'length' => '255'),
		'created' => array('type' => 'datetime'),
	);
/**
 * Records property
 *
 * @var array
 */
	var $records = array(
		array('name' => 'Gandalf'),
		array('name' => 'Captain Picard'),
		array('name' => 'Chewbacca')
	);
}


/**
 * Import Fixture Test Fixture
 *
 * @package cake
 * @subpackage cake.cake.tests.cases.libs
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
 * @var array
 */	
	var $import = array('table' => 'fixture_tests', 'connection' => 'test_suite');
}

/**
 * Fixture Test Case Model
 *
 * @package default
 * @subpackage cake.cake.tests.cases.libs.
 **/
class FixtureImportTestModel extends Model {
	var $name = 'FixtureImport';
	var $useTable = 'fixture_tests';
	var $useDbConfig = 'test_suite';
}

Mock::generate('DboSource', 'FixtureMockDboSource');

/**
 * Test case for CakeTestFixture
 *
 * @package    cake
 * @subpackage cake.cake.tests.cases.libs
 */
class CakeTestFixtureTest extends CakeTestCase {
	
	function setUp() {
		$this->criticDb =& new FixtureMockDboSource();
		$this->criticDb->fullDebug = true;
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
		$Fixture->fields = $Fixture->records = null;
		$Fixture->import = array('model' => 'FixtureImportTestModel');
		$Fixture->init();
		$this->assertEqual(array_keys($Fixture->fields), array('id', 'name', 'created'));

		//assert that model has been removed from registry, stops infinite loops.
		$keys = array_flip(ClassRegistry::keys());
		$this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

		$Source->drop($this->db);
	}
/**
 * test create method
 *
 * @return void
 **/
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
 * @return void
 **/
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
 * @return void
 **/
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
	}
/**
 * Test the truncate method.
 *
 * @return void
 **/
	function testTruncate() {
		$Fixture =& new CakeTestFixtureTestFixture();
		$this->criticDb->expectAtLeastOnce('truncate');
		$Fixture->truncate($this->criticDb);
		$this->assertTrue($this->criticDb->fullDebug);
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
}
?>