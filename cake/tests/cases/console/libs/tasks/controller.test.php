<?php
/**
 * ControllerTask Test Case
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Shell');

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'controller.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';


Mock::generatePartial(
	'ShellDispatcher', 'TestControllerTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'ControllerTask', 'MockControllerTask',
	array('in', 'hr', 'out', 'err', 'createFile', '_stop', '_checkUnitTest')
);

Mock::generatePartial(
	'ModelTask', 'ControllerMockModelTask',
	array('in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest')
);

if (!class_exists('Article')) {
	define('ARTICLE_MODEL_CREATED', true);
	App::import('Core', 'Model');
	
	class Article extends Model {
		var $name = 'Article';
		var $hasMany = array('Comment');
		var $hasAndBelongToMany = array('Tag');
	}

}

/**
 * ControllerTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ControllerTaskTest extends CakeTestCase {
/**
 * fixtures
 *
 * @var array
 **/
	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestControllerTaskMockShellDispatcher();
		$this->Task =& new MockControllerTask($this->Dispatcher);
		$this->Task->Dispatch =& new $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = Configure::read('shellPaths');
		$this->Task->Template =& new TemplateTask($this->Task->Dispatch);
		$this->Task->Model =& new ControllerMockModelTask($this->Task->Dispatch);
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * test ListAll
 *
 * @return void
 **/
	function testListAll() {
		$this->Task->connection = 'test_suite';
		$this->Task->interactive = true;
		$this->Task->expectAt(1, 'out', array('1. Articles'));
		$this->Task->expectAt(2, 'out', array('2. ArticlesTags'));
		$this->Task->expectAt(3, 'out', array('3. Comments'));
		$this->Task->expectAt(4, 'out', array('4. Tags'));

		$expected = array('Articles', 'ArticlesTags', 'Comments', 'Tags');
		$result = $this->Task->listAll('test_suite');
		$this->assertEqual($result, $expected);

		$this->Task->expectAt(6, 'out', array('1. Articles'));
		$this->Task->expectAt(7, 'out', array('2. ArticlesTags'));
		$this->Task->expectAt(8, 'out', array('4. Comments'));
		$this->Task->expectAt(9, 'out', array('5. Tags'));

		$this->Task->interactive = false;
		$result = $this->Task->listAll();

		$expected = array('articles', 'articles_tags', 'comments', 'tags');
		$this->assertEqual($result, $expected);	
	}

/**
 * Test that getName interacts with the user and returns the controller name.
 *
 * @return void
 **/
	function testGetName() {
		$this->Task->setReturnValue('in', 1);

		$this->Task->setReturnValueAt(0, 'in', 'q');
		$this->Task->expectOnce('_stop');
		$this->Task->getName('test_suite');

		$this->Task->setReturnValueAt(1, 'in', 1);
		$result = $this->Task->getName('test_suite');
		$expected = 'Articles';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(2, 'in', 3);
		$result = $this->Task->getName('test_suite');
		$expected = 'Comments';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(3, 'in', 10);
		$result = $this->Task->getName('test_suite');
		$this->Task->expectOnce('err');
	}

/**
 * test helper interactions
 *
 * @return void
 **/
	function testDoHelpers() {
		$this->Task->setReturnValueAt(0, 'in', 'n');
		$result = $this->Task->doHelpers();
		$this->assertEqual($result, array());

		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', ' Javascript, Ajax, CustomOne  ');
		$result = $this->Task->doHelpers();
		$expected = array('Javascript', 'Ajax', 'CustomOne');
		$this->assertEqual($result, $expected);
	}

/**
 * test component interactions
 *
 * @return void
 **/
	function testDoComponents() {
		$this->Task->setReturnValueAt(0, 'in', 'n');
		$result = $this->Task->doComponents();
		$this->assertEqual($result, array());

		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', ' RequestHandler, Security  ');
		$result = $this->Task->doComponents();
		$expected = array('RequestHandler', 'Security');
		$this->assertEqual($result, $expected);
	}

/**
 * test Confirming controller user interaction
 *
 * @return void
 **/
	function testConfirmController() {
		$controller = 'Posts';
		$scaffold = false;
		$helpers = array('Ajax', 'Time');
		$components = array('Acl', 'Auth');
		$uses = array('Comment', 'User');

		$this->Task->expectAt(2, 'out', array("Controller Name:\n\t$controller"));
		$this->Task->expectAt(3, 'out', array("Helpers:\n\tAjax, Time"));
		$this->Task->expectAt(4, 'out', array("Components:\n\tAcl, Auth"));
		$this->Task->confirmController($controller, $scaffold, $helpers, $components);
	}

/**
 * test the bake method
 *
 * @return void
 **/
	function testBake() {
		$helpers = array('Ajax', 'Time');
		$components = array('Acl', 'Auth');
		$uses = array('Comment', 'User');
		$this->Task->setReturnValue('createFile', true);

		$result = $this->Task->bake('Articles', '--actions--', $helpers, $components, $uses);
		$this->assertPattern('/class ArticlesController extends AppController/', $result);
		$this->assertPattern('/\$components \= array\(\'Acl\', \'Auth\'\)/', $result);
		$this->assertPattern('/\$helpers \= array\(\'Html\', \'Form\', \'Ajax\', \'Time\'\)/', $result);
		$this->assertPattern('/\-\-actions\-\-/', $result);

		$result = $this->Task->bake('Articles', 'scaffold', $helpers, $components, $uses);
		$this->assertPattern('/class ArticlesController extends AppController/', $result);
		$this->assertPattern('/var \$scaffold/', $result);
		$this->assertNoPattern('/helpers/', $result);
		$this->assertNoPattern('/components/', $result);
	}

/**
 * test that bakeActions is creating the correct controller Code. (Using sessions)
 *
 * @return void
 **/
	function testBakeActionsUsingSessions() {
		$result = $this->Task->bakeActions('Articles', null, true);

		$this->assertTrue(strpos($result, 'function index() {') !== false);
		$this->assertTrue(strpos($result, '$this->Article->recursive = 0;') !== false);
		$this->assertTrue(strpos($result, "\$this->set('articles', \$this->paginate());") !== false);

		$this->assertTrue(strpos($result, 'function view($id = null)') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('Invalid Article', true))") !== false);
		$this->assertTrue(strpos($result, "\$this->set('article', \$this->Article->read(null, \$id)") !== false);

		$this->assertTrue(strpos($result, 'function add()') !== false);
		$this->assertTrue(strpos($result, 'if (!empty($this->data))') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->save($this->data))') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('The Article has been saved', true))") !== false);

		$this->assertTrue(strpos($result, 'function edit($id = null)') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('The Article could not be saved. Please, try again.', true));") !== false);

		$this->assertTrue(strpos($result, 'function delete($id = null)') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->del($id))') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('Article deleted', true))") !== false);


		$result = $this->Task->bakeActions('Articles', 'admin_', true);

		$this->assertTrue(strpos($result, 'function admin_index() {') !== false);
		$this->assertTrue(strpos($result, 'function admin_add()') !== false);
		$this->assertTrue(strpos($result, 'function admin_view($id = null)') !== false);
		$this->assertTrue(strpos($result, 'function admin_edit($id = null)') !== false);
		$this->assertTrue(strpos($result, 'function admin_delete($id = null)') !== false);
	}

/**
 * Test baking with Controller::flash() or no sessions.
 *
 * @return void
 **/
	function testBakeActionsWithNoSessions() {
		$result = $this->Task->bakeActions('Articles', null, false);

		$this->assertTrue(strpos($result, 'function index() {') !== false);
		$this->assertTrue(strpos($result, '$this->Article->recursive = 0;') !== false);
		$this->assertTrue(strpos($result, "\$this->set('articles', \$this->paginate());") !== false);

		$this->assertTrue(strpos($result, 'function view($id = null)') !== false);
		$this->assertTrue(strpos($result, "\$this->flash(__('Invalid Article', true), array('action'=>'index'))") !== false);
		$this->assertTrue(strpos($result, "\$this->set('article', \$this->Article->read(null, \$id)") !== false);

		$this->assertTrue(strpos($result, 'function add()') !== false);
		$this->assertTrue(strpos($result, 'if (!empty($this->data))') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->save($this->data))') !== false);
		$this->assertTrue(strpos($result, "\$this->flash(__('The Article has been saved.', true), array('action'=>'index'))") !== false);

		$this->assertTrue(strpos($result, 'function edit($id = null)') !== false);

		$this->assertTrue(strpos($result, 'function delete($id = null)') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->del($id))') !== false);
		$this->assertTrue(strpos($result, "\$this->flash(__('Article deleted', true), array('action'=>'index'))") !== false);
	}
/**
 * test that execute runs all when the first arg == all
 *
 * @return void
 **/
	function testExecuteIntoAll() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'), 'Execute into all could not be run as an Article model was already loaded');
		if ($skip) {
			return;
		}
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class ArticlesController/')));

		$this->Task->execute();
	}
}
?>