<?php
/* SVN FILE: $Id$ */
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.cake.libs.
 * @since         CakePHP v 1.2.0.4487
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'CakeTestCase');

if (!class_exists('AppController')) {
	require_once LIBS . 'controller' . DS . 'app_controller.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}

Mock::generate('CakeHtmlReporter');
Mock::generate('CakeTestCase', 'CakeDispatcherMockTestCase');

SimpleTest::ignore('SubjectCakeTestCase');
SimpleTest::ignore('CakeDispatcherMockTestCase');
/**
 * SubjectCakeTestCase
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class SubjectCakeTestCase extends CakeTestCase {
/**
 * Feed a Mocked Reporter to the subject case
 * prevents its pass/fails from affecting the real test
 *
 * @param string $reporter
 * @access public
 * @return void
 */
	function setReporter(&$reporter) {
		$this->_reporter = &$reporter;
	}
/**
 * testDummy method
 *
 * @return void
 * @access public
 */
	function testDummy() {
	}
}
/**
 * CakeTestCaseTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class CakeTestCaseTest extends CakeTestCase {
/**
 * setUp
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Case =& new SubjectCakeTestCase();
		$reporter =& new MockCakeHtmlReporter();
		$this->Case->setReporter($reporter);
		$this->Reporter = $reporter;
	}
/**
 * tearDown
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Case);
		unset($this->Reporter);
	}
/**
 * testAssertGoodTags
 *
 * @access public
 * @return void
 */
	function testAssertGoodTags() {
		$this->Reporter->expectAtLeastOnce('paintPass');
		$this->Reporter->expectNever('paintFail');

		$input = '<p>Text</p>';
		$pattern = array(
			'<p',
			'Text',
			'/p',
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));

		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));

		$pattern = array(
			'a' => array('class' => 'active', 'href' => '/test.html'),
			'My link',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));

		$input = "<a    href=\"/test.html\"\t\n\tclass=\"active\"\tid=\"primary\">\t<span>My link</span></a>";
		$pattern = array(
			'a' => array('id' => 'primary', 'href' => '/test.html', 'class' => 'active'),
			'<span',
			'My link',
			'/span',
			'/a'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));

		$input = '<p class="info"><a href="/test.html" class="active"><strong onClick="alert(\'hey\');">My link</strong></a></p>';
		$pattern = array(
			'p' => array('class' => 'info'),
			'a' => array('class' => 'active', 'href' => '/test.html' ),
			'strong' => array('onClick' => 'alert(\'hey\');'),
			'My link',
			'/strong',
			'/a',
			'/p'
		);
		$this->assertTrue($this->Case->assertTags($input, $pattern));
	}
/**
 * testBadAssertTags
 *
 * @access public
 * @return void
 */
	function testBadAssertTags() {
		$this->Reporter->expectAtLeastOnce('paintFail');
		$this->Reporter->expectNever('paintPass');

		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'a' => array('hRef' => '/test.html', 'clAss' => 'active'),
			'My link',
			'/a'
		);
		$this->assertFalse($this->Case->assertTags($input, $pattern));

		$input = '<a href="/test.html" class="active">My link</a>';
		$pattern = array(
			'<a' => array('href' => '/test.html', 'class' => 'active'),
			'My link',
			'/a'
		);
		$this->assertFalse($this->Case->assertTags($input, $pattern));
	}
/**
 * testBefore
 *
 * @access public
 * @return void
 */
	function testBefore() {
		$this->Case->before('testDummy');
		$this->assertFalse(isset($this->Case->db));

		$this->Case->fixtures = array('core.post');
		$this->Case->before('start');
		$this->assertTrue(isset($this->Case->db));
		$this->assertTrue(isset($this->Case->_fixtures['core.post']));
		$this->assertTrue(is_a($this->Case->_fixtures['core.post'], 'CakeTestFixture'));
		$this->assertEqual($this->Case->_fixtureClassMap['Post'], 'core.post');
	}
/**
 * testAfter
 *
 * @access public
 * @return void
 */
	function testAfter() {
		$this->Case->after('testDummy');
		$this->assertFalse($this->Case->__truncated);

		$this->Case->fixtures = array('core.post');
		$this->Case->before('start');
		$this->Case->start();
		$this->Case->after('testDummy');
		$this->assertTrue($this->Case->__truncated);
	}
/**
 * testLoadFixtures
 *
 * @access public
 * @return void
 */
	function testLoadFixtures() {
		$this->Case->fixtures = array('core.post');
		$this->Case->autoFixtures = false;
		$this->Case->before('start');
		$this->expectError();
		$this->Case->loadFixtures('Wrong!');
		$this->Case->end();
	}
/**
 * testGetTests Method
 *
 * @return void
 * @access public
 */
	function testGetTests() {
		$result = $this->Case->getTests();
		$this->assertEqual(array_slice($result, 0, 2), array('start', 'startCase'));
		$this->assertEqual(array_slice($result, -2), array('endCase', 'end'));
	}
/**
 * TestTestAction
 *
 * @access public
 * @return void
 **/
	function testTestAction() {
		$_back = array(
			'controller' => Configure::read('controllerPaths'),
			'view' => Configure::read('viewPaths'),
			'model' => Configure::read('modelPaths'),
			'plugin' => Configure::read('pluginPaths')
		);
		Configure::write('controllerPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'controllers' . DS));
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS));
		Configure::write('modelPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS));
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));

		$result = $this->Case->testAction('/tests_apps/index', array('return' => 'view'));
		$this->assertPattern('/This is the TestsAppsController index view/', $result);

		$result = $this->Case->testAction('/tests_apps/index', array('return' => 'contents'));
		$this->assertPattern('/This is the TestsAppsController index view/', $result);
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/<\/html>/', $result);

		$result = $this->Case->testAction('/tests_apps/some_method', array('return' => 'result'));
		$this->assertEqual($result, 5);

		$result = $this->Case->testAction('/tests_apps/set_action', array('return' => 'vars'));
		$this->assertEqual($result, array('var' => 'string'));

		$db =& ConnectionManager::getDataSource('test_suite');
		$fixture =& new PostFixture();
		$fixture->create($db);

		$result = $this->Case->testAction('/tests_apps_posts/add', array('return' => 'vars'));
		$this->assertTrue(array_key_exists('posts', $result));
		$this->assertEqual(count($result['posts']), 1);

		$result = $this->Case->testAction('/tests_apps_posts/url_var/var1:value1/var2:val2', array(
			'return' => 'vars',
			'method' => 'get',
		));
		$this->assertTrue(isset($result['params']['url']['url']));
		$this->assertTrue(isset($result['params']['url']['output']));
		$this->assertEqual(array_keys($result['params']['named']), array('var1', 'var2'));

		$result = $this->Case->testAction('/tests_apps_posts/url_var/gogo/val2', array(
			'return' => 'vars',
			'method' => 'get',
		));
		$this->assertEqual($result['params']['pass'], array('gogo', 'val2'));


		$result = $this->Case->testAction('/tests_apps_posts/url_var', array(
			'return' => 'vars',
			'method' => 'get',
			'data' => array(
				'red' => 'health',
				'blue' => 'mana'
			)
		));
		$this->assertTrue(isset($result['params']['url']['output']));
		$this->assertTrue(isset($result['params']['url']['red']));
		$this->assertTrue(isset($result['params']['url']['blue']));
		$this->assertTrue(isset($result['params']['url']['url']));

		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'return' => 'vars',
			'method' => 'post',
			'data' => array(
				'name' => 'is jonas',
				'pork' => 'and beans',
			)
		));
		$this->assertEqual(array_keys($result['data']), array('name', 'pork'));
		$fixture->drop($db);

		$db =& ConnectionManager::getDataSource('test_suite');
		$_backPrefix = $db->config['prefix'];
		$db->config['prefix'] = 'cake_testaction_test_suite_';

		$config = $db->config;
		$config['prefix'] = 'cake_testcase_test_';

		ConnectionManager::create('cake_test_case', $config);
		$db2 =& ConnectionManager::getDataSource('cake_test_case');

		$fixture =& new PostFixture($db2);
		$fixture->create($db2);
		$fixture->insert($db2);

		$result = $this->Case->testAction('/tests_apps_posts/fixtured', array(
			'return' => 'vars',
			'fixturize' => true,
			'connection' => 'cake_test_case',
		));
		$this->assertTrue(isset($result['posts']));
		$this->assertEqual(count($result['posts']), 3);
		$tables = $db2->listSources();
		$this->assertFalse(in_array('cake_testaction_test_suite_posts', $tables));

		$fixture->drop($db2);

		$db =& ConnectionManager::getDataSource('test_suite');

		//test that drop tables behaves as exepected with testAction
		$db =& ConnectionManager::getDataSource('test_suite');
		$_backPrefix = $db->config['prefix'];
		$db->config['prefix'] = 'cake_testaction_test_suite_';

		$config = $db->config;
		$config['prefix'] = 'cake_testcase_test_';

		ConnectionManager::create('cake_test_case', $config);
		$db =& ConnectionManager::getDataSource('cake_test_case');
		$fixture =& new PostFixture($db);
		$fixture->create($db);
		$fixture->insert($db);

		$this->Case->dropTables = false;
		$result = $this->Case->testAction('/tests_apps_posts/fixtured', array(
			'return' => 'vars',
			'fixturize' => true,
			'connection' => 'cake_test_case',
		));

		$tables = $db->listSources();
		$this->assertTrue(in_array('cake_testaction_test_suite_posts', $tables));

		$fixture->drop($db);
		$db =& ConnectionManager::getDataSource('test_suite');
		$db->config['prefix'] = $_backPrefix;
		$fixture->drop($db);


		Configure::write('modelPaths', $_back['model']);
		Configure::write('controllerPaths', $_back['controller']);
		Configure::write('viewPaths', $_back['view']);
		Configure::write('pluginPaths', $_back['plugin']);
	}
/**
 * testSkipIf
 *
 * @return void
 **/
	function testSkipIf() {
		$this->assertTrue($this->Case->skipIf(true));
		$this->assertFalse($this->Case->skipIf(false));
	}
/**
 * testTestDispatcher
 *
 * @access public
 * @return void
 */
	function testTestDispatcher() {
		$_back = array(
			'controller' => Configure::read('controllerPaths'),
			'view' => Configure::read('viewPaths'),
			'plugin' => Configure::read('pluginPaths')
		);
		Configure::write('controllerPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'controllers' . DS));
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS));
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));

		$Dispatcher =& new CakeTestDispatcher();
		$Case =& new CakeDispatcherMockTestCase();

		$Case->expectOnce('startController');
		$Case->expectOnce('endController');

		$Dispatcher->testCase($Case);
		$this->assertTrue(isset($Dispatcher->testCase));

		$return = $Dispatcher->dispatch('/tests_apps/index', array('autoRender' => 0, 'return' => 1, 'requested' => 1));

		Configure::write('controllerPaths', $_back['controller']);
		Configure::write('viewPaths', $_back['view']);
		Configure::write('pluginPaths', $_back['plugin']);
	}
}
?>