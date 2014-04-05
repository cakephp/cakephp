<?php
/**
 * Toolbar facade tests.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('View', 'View');
App::uses('Controller', 'Controller');
App::uses('Helper', 'View');
App::uses('ToolbarHelper', 'DebugKit.View/Helper');
App::uses('ConnectionManager', 'Manager');

/**
 * Class MockBackendHelper
 *
 * @since         DebugKit 0.1
 */
class MockBackendHelper extends Helper {
}

/**
 * Class ToolbarHelperTestCase
 *
 */
class ToolbarHelperTestCase extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('core.post');

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$db = ConnectionManager::getDatasource('test');
		$db->fullDebug = true;

		Configure::write('Cache.disable', false);
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::parse('/');

		$this->Controller = new Controller(null);
		$this->View = new View($this->Controller);
		$this->Toolbar = new ToolbarHelper($this->View, array(
			'output' => 'MockBackendHelper',
			'cacheKey' => 'debug_kit_toolbar_test_case',
			'cacheConfig' => 'default'
		));
		$this->Toolbar->MockBackend = $this->getMock('Helper', array('testMethod'), array($this->View));

		$this->_viewPaths = App::path('views');
		App::build(array(
			'View' => array(
				CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'Test' . DS . 'test_app' . DS . 'View' . DS,
				APP . 'Plugin' . DS . 'DebugKit' . DS . 'View' . DS,
				CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'View' . DS
		)), true);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Cache::delete('debug_kit_toolbar_test_case', 'default');
		unset($this->Toolbar, $this->Controller);
	}

/**
 * test cache writing for views.
 *
 * @return void
 */
	public function testCacheWrite() {
		$result = $this->Toolbar->writeCache('test', array('stuff', 'to', 'cache'));
		$this->assertTrue($result);
	}

/**
 * Ensure that the cache writing only affects the
 * top most level of the history stack. As this is where the current request is stored.
 *
 * @return void
 */
	public function testOnlyWritingToFirstElement() {
		$values = array(
			array('test' => array('content' => array('first', 'values'))),
			array('test' => array('content' => array('second', 'values'))),
		);
		Cache::write('debug_kit_toolbar_test_case', $values, 'default');
		$this->Toolbar->writeCache('test', array('new', 'values'));

		$result = $this->Toolbar->readCache('test');
		$this->assertEquals($result, array('new', 'values'));

		$result = $this->Toolbar->readCache('test', 1);
		$this->assertEquals($result, array('second', 'values'));
	}

/**
 * test cache reading for views
 *
 * @return void
 */
	public function testCacheRead() {
		$result = $this->Toolbar->writeCache('test', array('stuff', 'to', 'cache'));
		$this->assertTrue($result, 'Cache write failed %s');

		$result = $this->Toolbar->readCache('test');
		$this->assertEquals($result, array('stuff', 'to', 'cache'), 'Cache value is wrong %s');

		$result = $this->Toolbar->writeCache('test', array('new', 'stuff'));
		$this->assertTrue($result, 'Cache write failed %s');

		$result = $this->Toolbar->readCache('test');
		$this->assertEquals($result, array('new', 'stuff'), 'Cache value is wrong %s');
	}

/**
 * Test that reading/writing doesn't work with no cache config.
 *
 * @return void
 */
	public function testNoCacheConfigPresent() {
		$this->Toolbar = new ToolbarHelper($this->View, array('output' => 'MockBackendHelper'));

		$result = $this->Toolbar->writeCache('test', array('stuff', 'to', 'cache'));
		$this->assertFalse($result, 'Writing to cache succeeded with no cache config %s');

		$result = $this->Toolbar->readCache('test');
		$this->assertFalse($result, 'Reading cache succeeded with no cache config %s');
	}

/**
 * ensure that getQueryLogs works and writes to the cache so the history panel will
 * work.
 *
 * @return void
 */
	public function testGetQueryLogs() {
		$model = new CakeTestModel(array('table' => 'posts', 'alias' => 'Post'));
		$model->find('all');
		$model->find('first');

		$result = $this->Toolbar->getQueryLogs($model->useDbConfig, array('cache' => false));
		$this->assertTrue(is_array($result));
		$this->assertTrue(count($result) >= 2, 'Should be more than 2 queries in the log %s');
		$this->assertTrue(isset($result['queries'][0]['actions']));

		$model->find('first');
		Cache::delete('debug_kit_toolbar_test_case', 'default');
		$result = $this->Toolbar->getQueryLogs($model->useDbConfig, array('cache' => true));

		$cached = $this->Toolbar->readCache('sql_log');
		$this->assertTrue(isset($cached[$model->useDbConfig]));
		$this->assertEquals($cached[$model->useDbConfig]['queries'][0], $result['queries'][0]);
	}

}
