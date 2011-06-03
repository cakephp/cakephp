<?php
/**
 * Connection Manager tests
 *
 *
 * PHP versions 4 and 5
 *
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
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
		$this->ConnectionManager =& ConnectionManager::getInstance();
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

		$this->expectError(new PatternExpectation('/Non-existent data source/i'));

		$source = ConnectionManager::getDataSource('non_existent_source');
		$this->assertEqual($source, null);

	}

/**
 * testGetPluginDataSource method
 *
 * @access public
 * @return void
 */
	function testGetPluginDataSource() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));

		$name = 'test_source';
		$config = array('datasource' => 'TestPlugin.TestSource');
		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('TestSource'));
		$this->assertEqual($connection->configKeyName, $name);
		$this->assertEqual($connection->config, $config);

		App::build();
	}

/**
 * testGetPluginDataSourceAndPluginDriver method
 *
 * @access public
 * @return void
 */
	function testGetPluginDataSourceAndPluginDriver() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));

		$name = 'test_plugin_source_and_driver';
		$config = array('datasource' => 'TestPlugin.TestSource', 'driver' => 'TestPlugin.TestDriver');

		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('TestSource'));
		$this->assertTrue(class_exists('TestDriver'));
		$this->assertEqual($connection->configKeyName, $name);
		$this->assertEqual($connection->config, $config);

		App::build();
	}

/**
 * testGetLocalDataSourceAndPluginDriver method
 *
 * @access public
 * @return void
 */
	function testGetLocalDataSourceAndPluginDriver() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));

		$name = 'test_local_source_and_plugin_driver';
		$config = array('datasource' => 'dbo', 'driver' => 'TestPlugin.DboDummy');

		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('DboSource'));
		$this->assertTrue(class_exists('DboDummy'));
		$this->assertEqual($connection->configKeyName, $name);

		App::build();
	}

/**
 * testGetPluginDataSourceAndLocalDriver method
 *
 * @access public
 * @return void
 */
	function testGetPluginDataSourceAndLocalDriver() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'datasources' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS . 'datasources' . DS)
		));

		$name = 'test_plugin_source_and_local_driver';
		$config = array('datasource' => 'TestPlugin.TestSource', 'driver' => 'local_driver');

		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('TestSource'));
		$this->assertTrue(class_exists('TestLocalDriver'));
		$this->assertEqual($connection->configKeyName, $name);
		$this->assertEqual($connection->config, $config);
		App::build();
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

		$source =& new StdClass();
		$result = ConnectionManager::getSourceName($source);
		$this->assertEqual($result, null);
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
		$this->expectError(new PatternExpectation('/Unable to import DataSource class/i'));

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

/**
 * testConnectionData method
 *
 * @access public
 * @return void
 */
	function testConnectionData() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'datasources' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS . 'datasources' . DS)
		));

		$expected = array(
		    'filename' => 'test2_source',
		    'classname' => 'Test2Source',
		    'parent' => '',
		    'plugin' => ''
		);

		ConnectionManager::create('connection1', array('datasource' => 'Test2'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEqual($expected, $connections['connection1']);

		ConnectionManager::create('connection2', array('datasource' => 'Test2Source'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEqual($expected, $connections['connection2']);

		ConnectionManager::create('connection3', array('datasource' => 'TestPlugin.Test'));
		$connections = ConnectionManager::enumConnectionObjects();
		$expected['filename'] = 'test_source';
		$expected['classname'] = 'TestSource';
		$expected['plugin'] = 'TestPlugin';
		$this->assertEqual($expected, $connections['connection3']);

		ConnectionManager::create('connection4', array('datasource' => 'TestPlugin.TestSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEqual($expected, $connections['connection4']);

		ConnectionManager::create('connection5', array('datasource' => 'Test2Other'));
		$connections = ConnectionManager::enumConnectionObjects();
		$expected['filename'] = 'test2_other_source';
		$expected['classname'] = 'Test2OtherSource';
		$expected['plugin'] = '';
		$this->assertEqual($expected, $connections['connection5']);

		ConnectionManager::create('connection6', array('datasource' => 'Test2OtherSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEqual($expected, $connections['connection6']);

		ConnectionManager::create('connection7', array('datasource' => 'TestPlugin.TestOther'));
		$connections = ConnectionManager::enumConnectionObjects();
		$expected['filename'] = 'test_other_source';
		$expected['classname'] = 'TestOtherSource';
		$expected['plugin'] = 'TestPlugin';
		$this->assertEqual($expected, $connections['connection7']);

		ConnectionManager::create('connection8', array('datasource' => 'TestPlugin.TestOtherSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEqual($expected, $connections['connection8']);
	}
}
