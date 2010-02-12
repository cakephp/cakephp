<?php

/**
 * Connection Manager tests
 *
 *
 * PHP versions 4 and 5
 *
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'ConnectionManager');

/**
 * ConnectionManagerTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.models
 */
class ConnectionManagerTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->ConnectionManager = ConnectionManager::getInstance();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->ConnectionManager);
	}

/**
 * testInstantiation method
 *
 * @access public
 * @return void
 */
	function testInstantiation() {
		$this->assertTrue(is_a($this->ConnectionManager, 'ConnectionManager'));
	}

/**
 * testEnumConnectionObjects method
 *
 * @access public
 * @return void
 */
	function testEnumConnectionObjects() {
		$sources = ConnectionManager::enumConnectionObjects();
		$this->assertTrue(count($sources) >= 1);

		$connections = array('default', 'test', 'test_suite');
		$this->assertTrue(count(array_intersect(array_keys($sources), $connections)) >= 1);
	}

/**
 * testGetDataSource method
 *
 * @access public
 * @return void
 */
	function testGetDataSource() {
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertTrue(count(array_keys($connections) >= 1));

		$source = ConnectionManager::getDataSource(key($connections));
		$this->assertTrue(is_object($source));

	}

/**
 * testSourceList method
 *
 * @access public
 * @return void
 */
	function testSourceList() {
		$sources = ConnectionManager::sourceList();
		$this->assertTrue(count($sources) >= 1);

		$connections = array('default', 'test', 'test_suite');
		$this->assertTrue(count(array_intersect($sources, $connections)) >= 1);
	}

/**
 * testGetSourceName method
 *
 * @access public
 * @return void
 */
	function testGetSourceName() {
		$connections = ConnectionManager::enumConnectionObjects();
		$name = key($connections);
		$source = ConnectionManager::getDataSource($name);
		$result = ConnectionManager::getSourceName($source);

		$this->assertEqual($result, $name);
	}

/**
 * testLoadDataSource method
 *
 * @access public
 * @return void
 */
	function testLoadDataSource() {
		$connections = array(
			array('classname' => 'DboMysql', 'filename' => 'dbo' . DS . 'dbo_mysql'),
			array('classname' => 'DboMysqli', 'filename' => 'dbo' . DS . 'dbo_mysqli'),
			array('classname' => 'DboMssql', 'filename' => 'dbo' . DS . 'dbo_mssql'),
			array('classname' => 'DboOracle', 'filename' => 'dbo' . DS . 'dbo_oracle'),
		);

		foreach ($connections as $connection) {
			$exists = class_exists($connection['classname']);
			$loaded = ConnectionManager::loadDataSource($connection);
			$this->assertEqual($loaded, !$exists, "%s Failed loading the {$connection['classname']} datasource");
		}

		$connection = array('classname' => 'NonExistentDataSource', 'filename' => 'non_existent');
		$this->expectError(new PatternExpectation('/Unable to load DataSource file/i'));

		$loaded = ConnectionManager::loadDataSource($connection);
		$this->assertEqual($loaded, null);
	}

/**
 * testCreateDataSource method
 *
 * @access public
 * @return void
 */
	function testCreateDataSourceWithIntegrationTests() {
		$name = 'test_created_connection';

		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertTrue(count(array_keys($connections) >= 1));

		$source = ConnectionManager::getDataSource(key($connections));
		$this->assertTrue(is_object($source));

		$config = $source->config;
		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(is_object($connection));
		$this->assertEqual($name, $connection->configKeyName);
		$this->assertEqual($name, ConnectionManager::getSourceName($connection));

		$source = ConnectionManager::create(null, array());
		$this->assertEqual($source, null);

		$source = ConnectionManager::create('another_test', array());
		$this->assertEqual($source, null);

		$config = array('classname' => 'DboMysql', 'filename' => 'dbo' . DS . 'dbo_mysql');
		$source = ConnectionManager::create(null, $config);
		$this->assertEqual($source, null);
	}
}
?>