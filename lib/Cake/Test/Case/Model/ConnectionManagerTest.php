<?php
/**
 * Connection Manager tests
 *
 * PHP 5
 *
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ConnectionManager', 'Model');

/**
 * ConnectionManagerTest
 *
 * @package       Cake.Test.Case.Model
 */
class ConnectionManagerTest extends CakeTestCase {

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload();
	}

/**
 * testEnumConnectionObjects method
 *
 * @return void
 */
	public function testEnumConnectionObjects() {
		$sources = ConnectionManager::enumConnectionObjects();
		$this->assertTrue(count($sources) >= 1);

		$connections = array('default', 'test', 'test');
		$this->assertTrue(count(array_intersect(array_keys($sources), $connections)) >= 1);
	}

/**
 * testGetDataSource method
 *
 * @return void
 */
	public function testGetDataSource() {
		App::build(array(
			'Model/Datasource' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS
			)
		));

		$name = 'test_get_datasource';
		$config = array('datasource' => 'Test2Source');

		$connection = ConnectionManager::create($name, $config);
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertTrue((bool)(count(array_keys($connections) >= 1)));

		$source = ConnectionManager::getDataSource('test_get_datasource');
		$this->assertTrue(is_object($source));
		ConnectionManager::drop('test_get_datasource');
	}

/**
 * testGetDataSourceException() method
 *
 * @return void
 * @expectedException MissingDatasourceConfigException
 */
	public function testGetDataSourceException() {
		ConnectionManager::getDataSource('non_existent_source');
	}

/**
 * testGetPluginDataSource method
 *
 * @return void
 */
	public function testGetPluginDataSource() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');
		$name = 'test_source';
		$config = array('datasource' => 'TestPlugin.TestSource');
		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('TestSource'));
		$this->assertEquals($connection->configKeyName, $name);
		$this->assertEquals($connection->config, $config);

		ConnectionManager::drop($name);
	}

/**
 * testGetPluginDataSourceAndPluginDriver method
 *
 * @return void
 */
	public function testGetPluginDataSourceAndPluginDriver() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');
		$name = 'test_plugin_source_and_driver';
		$config = array('datasource' => 'TestPlugin.Database/TestDriver');

		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('TestSource'));
		$this->assertTrue(class_exists('TestDriver'));
		$this->assertEquals($connection->configKeyName, $name);
		$this->assertEquals($connection->config, $config);

		ConnectionManager::drop($name);
	}

/**
 * testGetLocalDataSourceAndPluginDriver method
 *
 * @return void
 */
	public function testGetLocalDataSourceAndPluginDriver() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');
		$name = 'test_local_source_and_plugin_driver';
		$config = array('datasource' => 'TestPlugin.Database/DboDummy');

		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('DboSource'));
		$this->assertTrue(class_exists('DboDummy'));
		$this->assertEquals($connection->configKeyName, $name);

		ConnectionManager::drop($name);
	}

/**
 * testGetPluginDataSourceAndLocalDriver method
 *
 * @return void
 */
	public function testGetPluginDataSourceAndLocalDriver() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Model/Datasource/Database' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Database' . DS
			)
		));

		$name = 'test_plugin_source_and_local_driver';
		$config = array('datasource' => 'Database/TestLocalDriver');

		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(class_exists('TestSource'));
		$this->assertTrue(class_exists('TestLocalDriver'));
		$this->assertEquals($connection->configKeyName, $name);
		$this->assertEquals($connection->config, $config);
		ConnectionManager::drop($name);
	}

/**
 * testSourceList method
 *
 * @return void
 */
	public function testSourceList() {
		ConnectionManager::getDataSource('test');
		$sources = ConnectionManager::sourceList();
		$this->assertTrue(count($sources) >= 1);
		$this->assertTrue(in_array('test', array_keys($sources)));
	}

/**
 * testGetSourceName method
 *
 * @return void
 */
	public function testGetSourceName() {
		$connections = ConnectionManager::enumConnectionObjects();
		$source = ConnectionManager::getDataSource('test');
		$result = ConnectionManager::getSourceName($source);

		$this->assertEquals('test', $result);

		$source = new StdClass();
		$result = ConnectionManager::getSourceName($source);
		$this->assertNull($result);
	}

/**
 * testLoadDataSource method
 *
 * @return void
 */
	public function testLoadDataSource() {
		$connections = array(
			array('classname' => 'Mysql', 'filename' => 'Mysql', 'package' => 'Database'),
			array('classname' => 'Postgres', 'filename' => 'Postgres', 'package' => 'Database'),
			array('classname' => 'Sqlite', 'filename' => 'Sqlite', 'package' => 'Database'),
		);

		foreach ($connections as $connection) {
			$exists = class_exists($connection['classname']);
			$loaded = ConnectionManager::loadDataSource($connection);
			$this->assertEquals($loaded, !$exists, "Failed loading the {$connection['classname']} datasource");
		}
	}

/**
 * testLoadDataSourceException() method
 *
 * @return void
 * @expectedException MissingDatasourceException
 */
	public function testLoadDataSourceException() {
		$connection = array('classname' => 'NonExistentDataSource', 'filename' => 'non_existent');
		$loaded = ConnectionManager::loadDataSource($connection);
	}

/**
 * testCreateDataSource method
 *
 * @return void
 */
	public function testCreateDataSourceWithIntegrationTests() {
		$name = 'test_created_connection';

		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertTrue((bool)(count(array_keys($connections) >= 1)));

		$source = ConnectionManager::getDataSource('test');
		$this->assertTrue(is_object($source));

		$config = $source->config;
		$connection = ConnectionManager::create($name, $config);

		$this->assertTrue(is_object($connection));
		$this->assertEquals($name, $connection->configKeyName);
		$this->assertEquals($name, ConnectionManager::getSourceName($connection));

		$source = ConnectionManager::create(null, array());
		$this->assertEquals(null, $source);

		$source = ConnectionManager::create('another_test', array());
		$this->assertEquals(null, $source);

		$config = array('classname' => 'DboMysql', 'filename' => 'dbo' . DS . 'dbo_mysql');
		$source = ConnectionManager::create(null, $config);
		$this->assertEquals(null, $source);
	}

/**
 * testConnectionData method
 *
 * @return void
 */
	public function testConnectionData() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Model/Datasource' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS
			)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));
		$expected = array(
			'datasource' => 'Test2Source'
		);

		ConnectionManager::create('connection1', array('datasource' => 'Test2Source'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEquals($expected, $connections['connection1']);
		ConnectionManager::drop('connection1');

		ConnectionManager::create('connection2', array('datasource' => 'Test2Source'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEquals($expected, $connections['connection2']);
		ConnectionManager::drop('connection2');

		ConnectionManager::create('connection3', array('datasource' => 'TestPlugin.TestSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$expected['datasource'] = 'TestPlugin.TestSource';
		$this->assertEquals($expected, $connections['connection3']);
		ConnectionManager::drop('connection3');

		ConnectionManager::create('connection4', array('datasource' => 'TestPlugin.TestSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEquals($expected, $connections['connection4']);
		ConnectionManager::drop('connection4');

		ConnectionManager::create('connection5', array('datasource' => 'Test2OtherSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$expected['datasource'] = 'Test2OtherSource';
		$this->assertEquals($expected, $connections['connection5']);
		ConnectionManager::drop('connection5');

		ConnectionManager::create('connection6', array('datasource' => 'Test2OtherSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEquals($expected, $connections['connection6']);
		ConnectionManager::drop('connection6');

		ConnectionManager::create('connection7', array('datasource' => 'TestPlugin.TestOtherSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$expected['datasource'] = 'TestPlugin.TestOtherSource';
		$this->assertEquals($expected, $connections['connection7']);
		ConnectionManager::drop('connection7');

		ConnectionManager::create('connection8', array('datasource' => 'TestPlugin.TestOtherSource'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEquals($expected, $connections['connection8']);
		ConnectionManager::drop('connection8');
	}

/**
 * Tests that a connection configuration can be deleted in runtime
 *
 * @return void
 */
	public function testDrop() {
		App::build(array(
			'Model/Datasource' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS
			)
		));
		ConnectionManager::create('droppable', array('datasource' => 'Test2Source'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertEquals(array('datasource' => 'Test2Source'), $connections['droppable']);

		$this->assertTrue(ConnectionManager::drop('droppable'));
		$connections = ConnectionManager::enumConnectionObjects();
		$this->assertFalse(isset($connections['droppable']));
	}
}
