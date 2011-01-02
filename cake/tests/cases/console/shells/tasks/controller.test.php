<?php
/**
 * ControllerTask Test Case
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ClassRegistry');
App::import('View', 'Helper', false);
App::import('Shell', 'Shell', false);
App::import('Shell', array(
	'tasks/project',
	'tasks/controller',
	'tasks/model',
	'tasks/template',
	'tasks/test'
));

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

$imported = App::import('Model', 'BakeArticle');
$imported = $imported || App::import('Model', 'BakeComment');
$imported = $imported || App::import('Model', 'BakeTag');

if (!$imported) {
	define('ARTICLE_MODEL_CREATED', true);
	App::import('Core', 'Model');

	class BakeArticle extends Model {
		public $name = 'BakeArticle';
		public $hasMany = array('BakeComment');
		public $hasAndBelongsToMany = array('BakeTag');
	}

}

/**
 * ControllerTaskTest class
 *
 * @package       cake.tests.cases.console.libs.tasks
 */
class ControllerTaskTest extends CakeTestCase {

/**
 * fixtures
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.bake_article', 'core.bake_articles_bake_tag', 'core.bake_comment', 'core.bake_tag');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ControllerTask', 
			array('in', 'out', 'err', 'hr', 'createFile', '_stop', '_checkUnitTest'),
			array($out, $out, $in)
		);
		$this->Task->name = 'Controller';
		$this->Task->Template = new TemplateTask($out, $out, $in);
		$this->Task->Template->params['theme'] = 'default';

		$this->Task->Model = $this->getMock('ModelTask', 
			array('in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest'), 
			array($out, $out, $in)
		);
		$this->Task->Project = $this->getMock('ProjectTask', 
			array('in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest', 'getPrefix'), 
			array($out, $out, $in)
		);
		$this->Task->Test = $this->getMock('TestTask', array(), array($out, $out, $in));
	}

/**
 * teardown method
 *
 * @return void
 */
	public function teardown() {
		unset($this->Task);
		ClassRegistry::flush();
	}

/**
 * test ListAll
 *
 * @return void
 */
	public function testListAll() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->interactive = true;
		$this->Task->expects($this->at(1))->method('out')->with('1. BakeArticles');
		$this->Task->expects($this->at(2))->method('out')->with('2. BakeArticlesBakeTags');
		$this->Task->expects($this->at(3))->method('out')->with('3. BakeComments');
		$this->Task->expects($this->at(4))->method('out')->with('4. BakeTags');

		$expected = array('BakeArticles', 'BakeArticlesBakeTags', 'BakeComments', 'BakeTags');
		$result = $this->Task->listAll('test');
		$this->assertEqual($result, $expected);

		$this->Task->interactive = false;
		$result = $this->Task->listAll();

		$expected = array('bake_articles', 'bake_articles_bake_tags', 'bake_comments', 'bake_tags');
		$this->assertEqual($result, $expected);
	}

/**
 * Test that getName interacts with the user and returns the controller name.
 *
 * @return void
 */
	public function testGetNameValidIndex() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}
		$this->Task->interactive = true;
		$this->Task->expects($this->any())->method('in')->will(
			$this->onConsecutiveCalls(3, 1)
		);
		
		$result = $this->Task->getName('test');
		$expected = 'BakeComments';
		$this->assertEqual($result, $expected);
	
		$result = $this->Task->getName('test');
		$expected = 'BakeArticles';
		$this->assertEqual($result, $expected);
	}

/**
 * test getting invalid indexes.
 *
 * @return void
 */
	function testGetNameInvalidIndex() {
		$this->Task->interactive = true;
		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls(50, 'q'));

		$this->Task->expects($this->once())->method('err');
		$this->Task->expects($this->once())->method('_stop');

		$this->Task->getName('test');
	}

/**
 * test helper interactions
 *
 * @return void
 */
	public function testDoHelpersNo() {
		$this->Task->expects($this->any())->method('in')->will($this->returnValue('n'));
		$result = $this->Task->doHelpers();
		$this->assertEqual($result, array());
	}

/**
 * test getting helper values
 *
 * @return void
 */
	function testDoHelpersTrailingSpace() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' Javascript, Ajax, CustomOne  '));
		$result = $this->Task->doHelpers();
		$expected = array('Javascript', 'Ajax', 'CustomOne');
		$this->assertEqual($result, $expected);
	}

/**
 * test doHelpers with extra commas
 *
 * @return void
 */
	function testDoHelpersTrailingCommas() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' Javascript, Ajax, CustomOne, , '));
		$result = $this->Task->doHelpers();
		$expected = array('Javascript', 'Ajax', 'CustomOne');
		$this->assertEqual($result, $expected);
	}

/**
 * test component interactions
 *
 * @return void
 */
	public function testDoComponentsNo() {
		$this->Task->expects($this->any())->method('in')->will($this->returnValue('n'));
		$result = $this->Task->doComponents();
		$this->assertEqual($result, array());
	}

/**
 * test components with spaces
 *
 * @return void
 */
	function testDoComponentsTrailingSpaces() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' RequestHandler, Security  '));

		$result = $this->Task->doComponents();
		$expected = array('RequestHandler', 'Security');
		$this->assertEqual($result, $expected);
	}

/**
 * test components with commas
 *
 * @return void
 */
	function testDoComponentsTrailingCommas() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue(' RequestHandler, Security, , '));

		$result = $this->Task->doComponents();
		$expected = array('RequestHandler', 'Security');
		$this->assertEqual($result, $expected);
	}

/**
 * test Confirming controller user interaction
 *
 * @return void
 */
	public function testConfirmController() {
		$controller = 'Posts';
		$scaffold = false;
		$helpers = array('Ajax', 'Time');
		$components = array('Acl', 'Auth');
		$uses = array('Comment', 'User');

		$this->Task->expects($this->at(4))->method('out')->with("Controller Name:\n\t$controller");
		$this->Task->expects($this->at(5))->method('out')->with("Helpers:\n\tAjax, Time");
		$this->Task->expects($this->at(6))->method('out')->with("Components:\n\tAcl, Auth");
		$this->Task->confirmController($controller, $scaffold, $helpers, $components);
	}

/**
 * test the bake method
 *
 * @return void
 */
	public function testBake() {
		$helpers = array('Ajax', 'Time');
		$components = array('Acl', 'Auth');
		$this->Task->expects($this->any())->method('createFile')->will($this->returnValue(true));

		$result = $this->Task->bake('Articles', '--actions--', $helpers, $components);
		$this->assertContains('class ArticlesController extends AppController', $result);
		$this->assertContains("\$components = array('Acl', 'Auth')", $result);
		$this->assertContains("\$helpers = array('Ajax', 'Time')", $result);
		$this->assertContains("--actions--", $result);

		$result = $this->Task->bake('Articles', 'scaffold', $helpers, $components);
		$this->assertContains("class ArticlesController extends AppController", $result);
		$this->assertContains("public \$scaffold", $result);
		$this->assertNotContains('helpers', $result);
		$this->assertNotContains('components', $result);

		$result = $this->Task->bake('Articles', '--actions--', array(), array());
		$this->assertContains('class ArticlesController extends AppController', $result);
		$this->assertNotContains('components', $result);
		$this->assertNotContains('helpers', $result);
		$this->assertContains('--actions--', $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeWithPlugin() {
		$this->Task->plugin = 'ControllerTest';
		$helpers = array('Ajax', 'Time');
		$components = array('Acl', 'Auth');
		$uses = array('Comment', 'User');

		$path = APP . 'plugins' . DS . 'controller_test' . DS . 'controllers' . DS . 'articles_controller.php';

		$this->Task->expects($this->at(1))->method('createFile')->with(
			$path,
			new PHPUnit_Framework_Constraint_IsAnything()
		);
		$this->Task->expects($this->at(3))->method('createFile')->with(
			$path,
			new PHPUnit_Framework_Constraint_PCREMatch('/ArticlesController extends ControllerTestAppController/')
		);
		
		$this->Task->bake('Articles', '--actions--', array(), array(), array());

		$this->Task->plugin = 'controllerTest';
		$path = APP . 'plugins' . DS . 'controller_test' . DS . 'controllers' . DS . 'articles_controller.php';
		$this->Task->bake('Articles', '--actions--', array(), array(), array());

		$this->assertEqual($this->Task->Template->templateVars['plugin'], 'ControllerTest');
	}

/**
 * test that bakeActions is creating the correct controller Code. (Using sessions)
 *
 * @return void
 */
	public function testBakeActionsUsingSessions() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Testing bakeActions requires Article, Comment & Tag Model to be undefined. %s');
		if ($skip) {
			return;
		}
		$result = $this->Task->bakeActions('BakeArticles', null, true);

		$this->assertContains('function index() {', $result);
		$this->assertContains('$this->BakeArticle->recursive = 0;', $result);
		$this->assertContains("\$this->set('bakeArticles', \$this->paginate());", $result);

		$this->assertContains('function view($id = null)', $result);
		$this->assertContains("throw new NotFoundException(__('Invalid bake article'));", $result);
		$this->assertContains("\$this->set('bakeArticle', \$this->BakeArticle->read(null, \$id)", $result);

		$this->assertContains('function add()', $result);
		$this->assertContains("if (\$this->request->is('post'))", $result);
		$this->assertContains('if ($this->BakeArticle->save($this->request->data))', $result);
		$this->assertContains("\$this->Session->setFlash(__('The bake article has been saved'));", $result);

		$this->assertContains('function edit($id = null)', $result);
		$this->assertContains("\$this->Session->setFlash(__('The bake article could not be saved. Please, try again.'));", $result);

		$this->assertContains('function delete($id = null)', $result);
		$this->assertContains('if ($this->BakeArticle->delete())', $result);
		$this->assertContains("\$this->Session->setFlash(__('Bake article deleted'));", $result);

		$result = $this->Task->bakeActions('BakeArticles', 'admin_', true);

		$this->assertContains('function admin_index() {', $result);
		$this->assertContains('function admin_add()', $result);
		$this->assertContains('function admin_view($id = null)', $result);
		$this->assertContains('function admin_edit($id = null)', $result);
		$this->assertContains('function admin_delete($id = null)', $result);
	}

/**
 * Test baking with Controller::flash() or no sessions.
 *
 * @return void
 */
	public function testBakeActionsWithNoSessions() {
		$skip = $this->skipIf(!defined('ARTICLE_MODEL_CREATED'),
			'Testing bakeActions requires Article, Tag, Comment Models to be undefined. %s');
		if ($skip) {
			return;
		}
		$result = $this->Task->bakeActions('BakeArticles', null, false);

		$this->assertContains('function index() {', $result);
		$this->assertContains('$this->BakeArticle->recursive = 0;', $result);
		$this->assertContains("\$this->set('bakeArticles', \$this->paginate());", $result);

		$this->assertContains('function view($id = null)', $result);
		$this->assertContains("throw new NotFoundException(__('Invalid bake article'));", $result);
		$this->assertContains("\$this->set('bakeArticle', \$this->BakeArticle->read(null, \$id)", $result);

		$this->assertContains('function add()', $result);
		$this->assertContains("if (\$this->request->is('post'))", $result);
		$this->assertContains('if ($this->BakeArticle->save($this->request->data))', $result);

		$this->assertContains("\$this->flash(__('The bake article has been saved.'), array('action' => 'index'))", $result);

		$this->assertContains('function edit($id = null)', $result);
		$this->assertContains("\$this->BakeArticle->BakeTag->find('list')", $result);
		$this->assertContains("\$this->set(compact('bakeTags'))", $result);

		$this->assertContains('function delete($id = null)', $result);
		$this->assertContains('if ($this->BakeArticle->delete())', $result);
		$this->assertContains("\$this->flash(__('Bake article deleted'), array('action' => 'index'))", $result);
	}

/**
 * test baking a test
 *
 * @return void
 */
	public function testBakeTest() {
		$this->Task->plugin = 'ControllerTest';
		$this->Task->connection = 'test';
		$this->Task->interactive = false;

		$this->Task->Test->expects($this->once())->method('bake')->with('Controller', 'BakeArticles');
		$this->Task->bakeTest('BakeArticles');

		$this->assertEqual($this->Task->plugin, $this->Task->Test->plugin);
		$this->assertEqual($this->Task->connection, $this->Task->Test->connection);
		$this->assertEqual($this->Task->interactive, $this->Task->Test->interactive);
	}

/**
 * test Interactive mode.
 *
 * @return void
 */
	public function testInteractive() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		
		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls(
				'1',
				'y', // build interactive
				'n', // build no scaffolds
				'y', // build normal methods
				'n', // build admin methods
				'n', // helpers?
				'n', // components?
				'y', // sessions ?
				'y' // looks good?
			));

		$filename = '/my/path/bake_articles_controller.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, 
			new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticlesController/')
		);
		$this->Task->execute();
	}

/**
 * test Interactive mode.
 *
 * @return void
 */
	function testInteractiveAdminMethodsNotInteractive() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->interactive = true;
		$this->Task->path = '/my/path/';

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls(
				'1',
				'y', // build interactive
				'n', // build no scaffolds
				'y', // build normal methods
				'y', // build admin methods
				'n', // helpers?
				'n', // components?
				'y', // sessions ?
				'y' // looks good?
			));

		$this->Task->Project->expects($this->any())
			->method('getPrefix')
			->will($this->returnValue('admin_'));

		$filename = '/my/path/bake_articles_controller.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, 
			new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticlesController/')
		)->will($this->returnValue(true));

		$result = $this->Task->execute();
		$this->assertPattern('/admin_index/', $result);
	}

/**
 * test that execute runs all when the first arg == all
 *
 * @return void
 */
	public function testExecuteIntoAll() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}
		if (!defined('ARTICLE_MODEL_CREATED')) {
			$this->markTestSkipped('Execute into all could not be run as an Article, Tag or Comment model was already loaded.');
		}
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');

		$this->Task->expects($this->any())->method('_checkUnitTest')->will($this->returnValue(true));
		$this->Task->Test->expects($this->once())->method('bake');

		$filename = '/my/path/bake_articles_controller.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, 
			new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticlesController/')
		)->will($this->returnValue(true));

		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos` works.
 *
 * @return void
 */
	public function testExecuteWithController() {
		if (!defined('ARTICLE_MODEL_CREATED')) {
			$this->markTestSkipped('Execute with scaffold param requires no Article, Tag or Comment model to be defined');
		}
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');

		$filename = '/my/path/bake_articles_controller.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, 
			new PHPUnit_Framework_Constraint_PCREMatch('/\$scaffold/')
		);

		$this->Task->execute();
	}

/**
 * data provider for testExecuteWithControllerNameVariations
 *
 * @return void
 */
	static function nameVariations() {
		return array(
			array('BakeArticles'), array('BakeArticle'), array('bake_article'), array('bake_articles')
		);
	}

/**
 * test that both plural and singular forms work for controller baking.
 *
 * @dataProvider nameVariations
 * @return void
 */
	public function testExecuteWithControllerNameVariations($name) {
		if (!defined('ARTICLE_MODEL_CREATED')) {
			$this->markTestSkipped('Execute with scaffold param requires no Article, Tag or Comment model to be defined.');
		}
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array($name);

		$filename = '/my/path/bake_articles_controller.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, new PHPUnit_Framework_Constraint_PCREMatch('/\$scaffold/')
		);
		$this->Task->execute();
	}

/**
 * test that `cake bake controller foo scaffold` works.
 *
 * @return void
 */
	public function testExecuteWithPublicParam() {
		if (!defined('ARTICLE_MODEL_CREATED')) {
			$this->markTestSkipped('Execute with public param requires no Article, Tag or Comment model to be defined.');
		}
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');
		$this->Task->params = array('public' => true);

		$filename = '/my/path/bake_articles_controller.php';
		$expected = new PHPUnit_Framework_Constraint_Not(new PHPUnit_Framework_Constraint_PCREMatch('/\$scaffold/'));
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, $expected
		);
		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos both` works.
 *
 * @return void
 */
	public function testExecuteWithControllerAndBoth() {
		if (!defined('ARTICLE_MODEL_CREATED')) {
			$this->markTestSkipped('Execute with controller and both requires no Article, Tag or Comment model to be defined.');
		}
		$this->Task->Project->expects($this->any())->method('getPrefix')->will($this->returnValue('admin_'));
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');
		$this->Task->params = array('public' => true, 'admin' => true);

		$filename = '/my/path/bake_articles_controller.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, new PHPUnit_Framework_Constraint_PCREMatch('/admin_index/')
		);
		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos admin` works.
 *
 * @return void
 */
	public function testExecuteWithControllerAndAdmin() {
		if (!defined('ARTICLE_MODEL_CREATED')) {
			$this->markTestSkipped('Execute with controller and admin requires no Article, Tag or Comment model to be defined.');
		}
		$this->Task->Project->expects($this->any())->method('getPrefix')->will($this->returnValue('admin_'));
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');
		$this->Task->params = array('admin' => true);

		$filename = '/my/path/bake_articles_controller.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, new PHPUnit_Framework_Constraint_PCREMatch('/admin_index/')
		);
		$this->Task->execute();
	}
}
