<?php
/**
 * ControllerTask Test Case
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ClassRegistry');
App::import('View', 'Helper', false);
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'project.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'controller.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'test.php';

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

Mock::generatePartial(
	'ProjectTask', 'ControllerMockProjectTask',
	array('in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest', 'getPrefix')
);

Mock::generate('TestTask', 'ControllerMockTestTask');

$imported = App::import('Model', 'Article');
$imported = $imported || App::import('Model', 'Comment');
$imported = $imported || App::import('Model', 'Tag');

if (!$imported) {
	define('ARTICLE_MODEL_CREATED', true);
	App::import('Core', 'Model');

	class Article extends Model {
		var $name = 'Article';
		var $hasMany = array('Comment');
		var $hasAndBelongsToMany = array('Tag');
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
 * @access public
 */
	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * startTest method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestControllerTaskMockShellDispatcher();
		$this->Task =& new MockControllerTask($this->Dispatcher);
		$this->Task->name = 'ControllerTask';
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = App::path('shells');
		$this->Task->Template =& new TemplateTask($this->Task->Dispatch);
		$this->Task->Template->params['theme'] = 'default';
		$this->Task->Model =& new ControllerMockModelTask($this->Task->Dispatch);
		$this->Task->Project =& new ControllerMockProjectTask($this->Task->Dispatch);
		$this->Task->Test =& new ControllerMockTestTask();
	}

/**
 * endTest method
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
 * @access public
 */
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
 * @access public
 */
	function testGetName() {
		$this->Task->interactive = true;
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
 * @access public
 */
	function testDoHelpers() {
		$this->Task->setReturnValue('in', 'n');
		$result = $this->Task->doHelpers();
		$this->assertEqual($result, array());

		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', ' Javascript, Ajax, CustomOne  ');
		$result = $this->Task->doHelpers();
		$expected = array('Javascript', 'Ajax', 'CustomOne');
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(3, 'in', 'y');
		$this->Task->setReturnValueAt(4, 'in', ' Javascript, Ajax, CustomOne, , ');
		$result = $this->Task->doHelpers();
		$expected = array('Javascript', 'Ajax', 'CustomOne');
		$this->assertEqual($result, $expected);
	}

/**
 * test component interactions
 *
 * @return void
 * @access public
 */
	function testDoComponents() {
		$this->Task->setReturnValue('in', 'n');
		$result = $this->Task->doComponents();
		$this->assertEqual($result, array());

		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', ' RequestHandler, Security  ');
		$result = $this->Task->doComponents();
		$expected = array('RequestHandler', 'Security');
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(3, 'in', 'y');
		$this->Task->setReturnValueAt(4, 'in', ' RequestHandler, Security, , ');
		$result = $this->Task->doComponents();
		$expected = array('RequestHandler', 'Security');
		$this->assertEqual($result, $expected);
	}

/**
 * test Confirming controller user interaction
 *
 * @return void
 * @access public
 */
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
 * @access public
 */
	function testBake() {
		$helpers = array('Ajax', 'Time');
		$components = array('Acl', 'Auth');
		$this->Task->setReturnValue('createFile', true);

		$result = $this->Task->bake('Articles', '--actions--', $helpers, $components);
		$this->assertPattern('/class ArticlesController extends AppController/', $result);
		$this->assertPattern('/\$components \= array\(\'Acl\', \'Auth\'\)/', $result);
		$this->assertPattern('/\$helpers \= array\(\'Ajax\', \'Time\'\)/', $result);
		$this->assertPattern('/\-\-actions\-\-/', $result);

		$result = $this->Task->bake('Articles', 'scaffold', $helpers, $components);
		$this->assertPattern('/class ArticlesController extends AppController/', $result);
		$this->assertPattern('/var \$scaffold/', $result);
		$this->assertNoPattern('/helpers/', $result);
		$this->assertNoPattern('/components/', $result);

		$result = $this->Task->bake('Articles', '--actions--', array(), array());
		$this->assertPattern('/class ArticlesController extends AppController/', $result);
		$this->assertNoPattern('/components/', $result);
		$this->assertNoPattern('/helpers/', $result);
		$this->assertPattern('/\-\-actions\-\-/', $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 * @access public
 */
	function testBakeWithPlugin() {
		$this->Task->plugin = 'ControllerTest';
		$helpers = array('Ajax', 'Time');
		$components = array('Acl', 'Auth');
		$uses = array('Comment', 'User');

		$path = APP . 'plugins' . DS . 'controller_test' . DS . 'controllers' . DS . 'articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array($path, '*'));
		$this->Task->bake('Articles', '--actions--', array(), array(), array());

		$this->Task->plugin = 'controllerTest';
		$path = APP . 'plugins' . DS . 'controller_test' . DS . 'controllers' . DS . 'articles_controller.php';
		$this->Task->expectAt(1, 'createFile', array(
			$path, new PatternExpectation('/ArticlesController extends ControllerTestAppController/')));
		$this->Task->bake('Articles', '--actions--', array(), array(), array());

		$this->assertEqual($this->Task->Template->templateVars['plugin'], 'ControllerTest');
	}

/**
 * test that bakeActions is creating the correct controller Code. (Using sessions)
 *
 * @return void
 * @access public
 */
	function testBakeActionsUsingSessions() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Testing bakeActions requires Article, Comment & Tag Model to be undefined. %s');
		if ($skip) {
			return;
		}
		$result = $this->Task->bakeActions('Articles', null, true);

		$this->assertTrue(strpos($result, 'function index() {') !== false);
		$this->assertTrue(strpos($result, '$this->Article->recursive = 0;') !== false);
		$this->assertTrue(strpos($result, "\$this->set('articles', \$this->paginate());") !== false);

		$this->assertTrue(strpos($result, 'function view($id = null)') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('Invalid article', true));") !== false);
		$this->assertTrue(strpos($result, "\$this->set('article', \$this->Article->read(null, \$id)") !== false);

		$this->assertTrue(strpos($result, 'function add()') !== false);
		$this->assertTrue(strpos($result, 'if (!empty($this->data))') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->save($this->data))') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('The article has been saved', true));") !== false);

		$this->assertTrue(strpos($result, 'function edit($id = null)') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('The article could not be saved. Please, try again.', true));") !== false);

		$this->assertTrue(strpos($result, 'function delete($id = null)') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->delete($id))') !== false);
		$this->assertTrue(strpos($result, "\$this->Session->setFlash(__('Article deleted', true));") !== false);

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
 * @access public
 */
	function testBakeActionsWithNoSessions() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Testing bakeActions requires Article, Tag, Comment Models to be undefined. %s');
		if ($skip) {
			return;
		}
		$result = $this->Task->bakeActions('Articles', null, false);

		$this->assertTrue(strpos($result, 'function index() {') !== false);
		$this->assertTrue(strpos($result, '$this->Article->recursive = 0;') !== false);
		$this->assertTrue(strpos($result, "\$this->set('articles', \$this->paginate());") !== false);

		$this->assertTrue(strpos($result, 'function view($id = null)') !== false);
		$this->assertTrue(strpos($result, "\$this->flash(__('Invalid article', true), array('action' => 'index'))") !== false);
		$this->assertTrue(strpos($result, "\$this->set('article', \$this->Article->read(null, \$id)") !== false);

		$this->assertTrue(strpos($result, 'function add()') !== false);
		$this->assertTrue(strpos($result, 'if (!empty($this->data))') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->save($this->data))') !== false);
		$this->assertTrue(strpos($result, "\$this->flash(__('The article has been saved.', true), array('action' => 'index'))") !== false);

		$this->assertTrue(strpos($result, 'function edit($id = null)') !== false);
		$this->assertTrue(strpos($result, "\$this->Article->Tag->find('list')") !== false);
		$this->assertTrue(strpos($result, "\$this->set(compact('tags'))") !== false);

		$this->assertTrue(strpos($result, 'function delete($id = null)') !== false);
		$this->assertTrue(strpos($result, 'if ($this->Article->delete($id))') !== false);
		$this->assertTrue(strpos($result, "\$this->flash(__('Article deleted', true), array('action' => 'index'))") !== false);
	}

/**
 * test baking a test
 *
 * @return void
 * @access public
 */
	function testBakeTest() {
		$this->Task->plugin = 'ControllerTest';
		$this->Task->connection = 'test_suite';
		$this->Task->interactive = false;

		$this->Task->Test->expectOnce('bake', array('Controller', 'Articles'));
		$this->Task->bakeTest('Articles');

		$this->assertEqual($this->Task->plugin, $this->Task->Test->plugin);
		$this->assertEqual($this->Task->connection, $this->Task->Test->connection);
		$this->assertEqual($this->Task->interactive, $this->Task->Test->interactive);
	}

/**
 * test Interactive mode.
 *
 * @return void
 * @access public
 */
	function testInteractive() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path';
		$this->Task->setReturnValue('in', '1');
		$this->Task->setReturnValueAt(1, 'in', 'y'); // build interactive
		$this->Task->setReturnValueAt(2, 'in', 'n'); // build no scaffolds
		$this->Task->setReturnValueAt(3, 'in', 'y'); // build normal methods
		$this->Task->setReturnValueAt(4, 'in', 'n'); // build admin methods
		$this->Task->setReturnValueAt(5, 'in', 'n'); // helpers?
		$this->Task->setReturnValueAt(6, 'in', 'n'); // components?
		$this->Task->setReturnValueAt(7, 'in', 'y'); // use sessions
		$this->Task->setReturnValueAt(8, 'in', 'y'); // looks good

		$this->Task->execute();

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class ArticlesController/')));
	}

/**
 * test Interactive mode.
 *
 * @return void
 * @access public
 */
	function testInteractiveAdminMethodsNotInteractive() {
		$this->Task->connection = 'test_suite';
		$this->Task->interactive = true;
		$this->Task->path = '/my/path';
		$this->Task->setReturnValue('in', '1');
		$this->Task->setReturnValueAt(1, 'in', 'y'); // build interactive
		$this->Task->setReturnValueAt(2, 'in', 'n'); // build no scaffolds
		$this->Task->setReturnValueAt(3, 'in', 'y'); // build normal methods
		$this->Task->setReturnValueAt(4, 'in', 'y'); // build admin methods
		$this->Task->setReturnValueAt(5, 'in', 'n'); // helpers?
		$this->Task->setReturnValueAt(6, 'in', 'n'); // components?
		$this->Task->setReturnValueAt(7, 'in', 'y'); // use sessions
		$this->Task->setReturnValueAt(8, 'in', 'y'); // looks good
		$this->Task->setReturnValue('createFile', true);
		$this->Task->Project->setReturnValue('getPrefix', 'admin_');

		$result = $this->Task->execute();
		$this->assertPattern('/admin_index/', $result);

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class ArticlesController/')));
	}

/**
 * test that execute runs all when the first arg == all
 *
 * @return void
 * @access public
 */
	function testExecuteIntoAll() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Execute into all could not be run as an Article, Tag or Comment model was already loaded. %s');
		if ($skip) {
			return;
		}
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');

		$this->Task->setReturnValue('createFile', true);
		$this->Task->setReturnValue('_checkUnitTest', true);
		$this->Task->Test->expectCallCount('bake', 1);

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class ArticlesController/')));

		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos` works.
 *
 * @return void
 * @access public
 */
	function testExecuteWithController() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Execute with scaffold param requires no Article, Tag or Comment model to be defined. %s');
		if ($skip) {
			return;
		}
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('Articles');

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array(
			$filename, new PatternExpectation('/\$scaffold/')
		));

		$this->Task->execute();
	}

/**
 * test that both plural and singular forms work for controller baking.
 *
 * @return void
 * @access public
 */
	function testExecuteWithControllerNameVariations() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Execute with scaffold param requires no Article, Tag or Comment model to be defined. %s');
		if ($skip) {
			return;
		}
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('Articles');

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array(
			$filename, new PatternExpectation('/\$scaffold/')
		));

		$this->Task->execute();

		$this->Task->args = array('Article');
		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(1, 'createFile', array(
			$filename, new PatternExpectation('/class ArticlesController/')
		));
		$this->Task->execute();

		$this->Task->args = array('article');
		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(2, 'createFile', array(
			$filename, new PatternExpectation('/class ArticlesController/')
		));

		$this->Task->args = array('articles');
		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(3, 'createFile', array(
			$filename, new PatternExpectation('/class ArticlesController/')
		));
		$this->Task->execute();

		$this->Task->args = array('Articles');
		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(4, 'createFile', array(
			$filename, new PatternExpectation('/class ArticlesController/')
		));
		$this->Task->execute();
		$this->Task->execute();
	}

/**
 * test that `cake bake controller foo scaffold` works.
 *
 * @return void
 * @access public
 */
	function testExecuteWithPublicParam() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Execute with scaffold param requires no Article, Tag or Comment model to be defined. %s');
		if ($skip) {
			return;
		}
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('Articles', 'public');

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array(
			$filename, new NoPatternExpectation('/var \$scaffold/')
		));

		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos both` works.
 *
 * @return void
 * @access public
 */
	function testExecuteWithControllerAndBoth() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Execute with scaffold param requires no Article, Tag or Comment model to be defined. %s');
		if ($skip) {
			return;
		}
		$this->Task->Project->setReturnValue('getPrefix', 'admin_');
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('Articles', 'public', 'admin');

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array(
			$filename, new PatternExpectation('/admin_index/')
		));

		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos admin` works.
 *
 * @return void
 * @access public
 */
	function testExecuteWithControllerAndAdmin() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Execute with scaffold param requires no Article, Tag or Comment model to be defined. %s');
		if ($skip) {
			return;
		}
		$this->Task->Project->setReturnValue('getPrefix', 'admin_');
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('Articles', 'admin');

		$filename = '/my/path/articles_controller.php';
		$this->Task->expectAt(0, 'createFile', array(
			$filename, new PatternExpectation('/admin_index/')
		));

		$this->Task->execute();
	}
}
