<?php
/**
 * ViewTask Test file
 *
 * Test Case for view generation shell task
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('ViewTask', 'Console/Command/Task');
App::uses('ControllerTask', 'Console/Command/Task');
App::uses('TemplateTask', 'Console/Command/Task');
App::uses('ProjectTask', 'Console/Command/Task');
App::uses('DbConfigTask', 'Console/Command/Task');
App::uses('Model', 'Model');
App::uses('Controller', 'Controller');

/**
 * Test View Task Comment Model
 *
 * @package       Cake.Test.Case.Console.Command.Task
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ViewTaskComment extends Model {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'ViewTaskComment';

/**
 * Table name
 *
 * @var string
 */
	public $useTable = 'comments';

/**
 * Belongs To Associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Article' => array(
			'className' => 'TestTest.ViewTaskArticle',
			'foreignKey' => 'article_id'
		)
	);
}

/**
 * Test View Task Article Model
 *
 * @package       Cake.Test.Case.Console.Command.Task
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ViewTaskArticle extends Model {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'ViewTaskArticle';

/**
 * Table name
 *
 * @var string
 */
	public $useTable = 'articles';
}

/**
 * Test View Task Comments Controller
 *
 * @package       Cake.Test.Case.Console.Command.Task
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ViewTaskCommentsController extends Controller {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'ViewTaskComments';

/**
 * Testing public controller action
 *
 * @return void
 */
	public function index() {
	}

/**
 * Testing public controller action
 *
 * @return void
 */
	public function add() {
	}

}

/**
 * Test View Task Articles Controller
 *
 * @package       Cake.Test.Case.Console.Command.Task
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ViewTaskArticlesController extends Controller {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'ViewTaskArticles';

/**
 * Test public controller action
 *
 * @return void
 */
	public function index() {
	}

/**
 * Test public controller action
 *
 * @return void
 */
	public function add() {
	}

/**
 * Test admin prefixed controller action
 *
 * @return void
 */
	public function admin_index() {
	}

/**
 * Test admin prefixed controller action
 *
 * @return void
 */
	public function admin_add() {
	}

/**
 * Test admin prefixed controller action
 *
 * @return void
 */
	public function admin_view() {
	}

/**
 * Test admin prefixed controller action
 *
 * @return void
 */
	public function admin_edit() {
	}

/**
 * Test admin prefixed controller action
 *
 * @return void
 */
	public function admin_delete() {
	}

}

/**
 * ViewTaskTest class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ViewTaskTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * setUp method
 *
 * Ensure that the default theme is used
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('ViewTask',
			array('in', 'err', 'createFile', '_stop'),
			array($out, $out, $in)
		);
		$this->Task->Template = new TemplateTask($out, $out, $in);
		$this->Task->Controller = $this->getMock('ControllerTask', array(), array($out, $out, $in));
		$this->Task->Project = $this->getMock('ProjectTask', array(), array($out, $out, $in));
		$this->Task->DbConfig = $this->getMock('DbConfigTask', array(), array($out, $out, $in));

		$this->Task->path = TMP;
		$this->Task->Template->params['theme'] = 'default';
		$this->Task->Template->templatePaths = array('default' => CAKE . 'Console' . DS . 'Templates' . DS . 'default' . DS);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task, $this->Dispatch);
	}

/**
 * Test getContent and parsing of Templates.
 *
 * @return void
 */
	public function testGetContent() {
		$vars = array(
			'modelClass' => 'TestViewModel',
			'schema' => array(),
			'primaryKey' => 'id',
			'displayField' => 'name',
			'singularVar' => 'testViewModel',
			'pluralVar' => 'testViewModels',
			'singularHumanName' => 'Test View Model',
			'pluralHumanName' => 'Test View Models',
			'fields' => array('id', 'name', 'body'),
			'associations' => array()
		);
		$result = $this->Task->getContent('view', $vars);

		$this->assertRegExp('/Delete Test View Model/', $result);
		$this->assertRegExp('/Edit Test View Model/', $result);
		$this->assertRegExp('/List Test View Models/', $result);
		$this->assertRegExp('/New Test View Model/', $result);

		$this->assertRegExp('/testViewModel\[\'TestViewModel\'\]\[\'id\'\]/', $result);
		$this->assertRegExp('/testViewModel\[\'TestViewModel\'\]\[\'name\'\]/', $result);
		$this->assertRegExp('/testViewModel\[\'TestViewModel\'\]\[\'body\'\]/', $result);
	}

/**
 * test getContent() using an admin_prefixed action.
 *
 * @return void
 */
	public function testGetContentWithAdminAction() {
		$_back = Configure::read('Routing');
		Configure::write('Routing.prefixes', array('admin'));
		$vars = array(
			'modelClass' => 'TestViewModel',
			'schema' => array(),
			'primaryKey' => 'id',
			'displayField' => 'name',
			'singularVar' => 'testViewModel',
			'pluralVar' => 'testViewModels',
			'singularHumanName' => 'Test View Model',
			'pluralHumanName' => 'Test View Models',
			'fields' => array('id', 'name', 'body'),
			'associations' => array()
		);
		$result = $this->Task->getContent('admin_view', $vars);

		$this->assertRegExp('/Delete Test View Model/', $result);
		$this->assertRegExp('/Edit Test View Model/', $result);
		$this->assertRegExp('/List Test View Models/', $result);
		$this->assertRegExp('/New Test View Model/', $result);

		$this->assertRegExp('/testViewModel\[\'TestViewModel\'\]\[\'id\'\]/', $result);
		$this->assertRegExp('/testViewModel\[\'TestViewModel\'\]\[\'name\'\]/', $result);
		$this->assertRegExp('/testViewModel\[\'TestViewModel\'\]\[\'body\'\]/', $result);

		$result = $this->Task->getContent('admin_add', $vars);
		$this->assertRegExp("/input\('name'\)/", $result);
		$this->assertRegExp("/input\('body'\)/", $result);
		$this->assertRegExp('/List Test View Models/', $result);

		Configure::write('Routing', $_back);
	}

/**
 * test Bake method
 *
 * @return void
 */
	public function testBakeView() {
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'view.ctp',
				$this->stringContains('View Task Articles')
			);

		$this->Task->bake('view', true);
	}

/**
 * test baking an edit file
 *
 * @return void
 */
	public function testBakeEdit() {
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'edit.ctp',
				new PHPUnit_Framework_Constraint_IsAnything()
			);
		$this->Task->bake('edit', true);
	}

/**
 * test baking an index
 *
 * @return void
 */
	public function testBakeIndex() {
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'index.ctp',
				$this->stringContains("\$viewTaskComment['Article']['title']")
			);
		$this->Task->bake('index', true);
	}

/**
 * test that baking a view with no template doesn't make a file.
 *
 * @return void
 */
	public function testBakeWithNoTemplate() {
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->never())->method('createFile');
		$this->Task->bake('delete', true);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeWithPlugin() {
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->plugin = 'TestTest';
		$this->Task->name = 'View';

		//fake plugin path
		CakePlugin::load('TestTest', array('path' => APP . 'Plugin' . DS . 'TestTest' . DS));
		$path = APP . 'Plugin' . DS . 'TestTest' . DS . 'View' . DS . 'ViewTaskComments' . DS . 'view.ctp';

		$result = $this->Task->getContent('index');
		$this->assertNotContains('List Test Test.view Task Articles', $result);

		$this->Task->expects($this->once())
			->method('createFile')
			->with($path, $this->anything());

		$this->Task->bake('view', true);
		CakePlugin::unload();
	}

/**
 * test bake actions baking multiple actions.
 *
 * @return void
 */
	public function testBakeActions() {
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'view.ctp',
				$this->stringContains('View Task Comments')
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'edit.ctp',
				$this->stringContains('Edit View Task Comment')
			);
		$this->Task->expects($this->at(2))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'index.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->bakeActions(array('view', 'edit', 'index'), array());
	}

/**
 * test baking a customAction (non crud)
 *
 * @return void
 */
	public function testCustomAction() {
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('', 'my_action', 'y'));

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'my_action.ctp',
				$this->anything()
			);

		$this->Task->customAction();
	}

/**
 * Test all()
 *
 * @return void
 */
	public function testExecuteIntoAll() {
		$this->Task->args[0] = 'all';

		$this->Task->Controller->expects($this->once())->method('listAll')
			->will($this->returnValue(array('view_task_comments')));

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'index.ctp',
				$this->anything()
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'add.ctp',
				$this->anything()
			);
		$this->Task->expects($this->exactly(2))->method('createFile');

		$this->Task->execute();
	}

/**
 * Test all() with action parameter
 *
 * @return void
 */
	public function testExecuteIntoAllWithActionName() {
		$this->Task->args = array('all', 'index');

		$this->Task->Controller->expects($this->once())->method('listAll')
			->will($this->returnValue(array('view_task_comments')));

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'index.ctp',
				$this->anything()
			);

		$this->Task->execute();
	}

/**
 * test `cake bake view $controller view`
 *
 * @return void
 */
	public function testExecuteWithActionParam() {
		$this->Task->args[0] = 'ViewTaskComments';
		$this->Task->args[1] = 'view';

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'view.ctp',
				$this->anything()
			);
		$this->Task->execute();
	}

/**
 * test `cake bake view $controller`
 * Ensure that views are only baked for actions that exist in the controller.
 *
 * @return void
 */
	public function testExecuteWithController() {
		$this->Task->args[0] = 'ViewTaskComments';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'index.ctp',
				$this->anything()
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'add.ctp',
				$this->anything()
			);
		$this->Task->expects($this->exactly(2))->method('createFile');

		$this->Task->execute();
	}

/**
 * static dataprovider for test cases
 *
 * @return void
 */
	public static function nameVariations() {
		return array(array('ViewTaskComments'), array('ViewTaskComment'), array('view_task_comment'));
	}

/**
 * test that both plural and singular forms can be used for baking views.
 *
 * @dataProvider nameVariations
 * @return void
 */
	public function testExecuteWithControllerVariations($name) {
		$this->Task->args = array($name);

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'index.ctp',
				$this->anything()
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'add.ctp',
				$this->anything()
			);
		$this->Task->execute();
	}

/**
 * test `cake bake view $controller --admin`
 * Which only bakes admin methods, not non-admin methods.
 *
 * @return void
 */
	public function testExecuteWithControllerAndAdminFlag() {
		$_back = Configure::read('Routing');
		Configure::write('Routing.prefixes', array('admin'));
		$this->Task->args[0] = 'ViewTaskArticles';
		$this->Task->params['admin'] = 1;

		$this->Task->Project->expects($this->any())->method('getPrefix')->will($this->returnValue('admin_'));

		$this->Task->expects($this->exactly(4))->method('createFile');

		$views = array('admin_index.ctp', 'admin_add.ctp', 'admin_view.ctp', 'admin_edit.ctp');
		foreach ($views as $i => $view) {
			$this->Task->expects($this->at($i))->method('createFile')
				->with(
					TMP . 'ViewTaskArticles' . DS . $view,
					$this->anything()
				);
		}
		$this->Task->execute();
		Configure::write('Routing', $_back);
	}

/**
 * test execute into interactive.
 *
 * @return void
 */
	public function testExecuteInteractive() {
		$this->Task->connection = 'test';
		$this->Task->args = array();
		$this->Task->params = array();

		$this->Task->Controller->expects($this->once())->method('getName')
			->will($this->returnValue('ViewTaskComments'));

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('y', 'y', 'n'));

		$this->Task->expects($this->at(3))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'index.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(4))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'view.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(5))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'add.ctp',
				$this->stringContains('Add View Task Comment')
			);

		$this->Task->expects($this->at(6))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'edit.ctp',
				$this->stringContains('Edit View Task Comment')
			);

		$this->Task->expects($this->exactly(4))->method('createFile');
		$this->Task->execute();
	}

/**
 * test `cake bake view posts index list`
 *
 * @return void
 */
	public function testExecuteWithAlternateTemplates() {
		$this->Task->connection = 'test';
		$this->Task->args = array('ViewTaskComments', 'index', 'list');
		$this->Task->params = array();

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'list.ctp',
				$this->stringContains('ViewTaskComment')
			);
		$this->Task->execute();
	}

/**
 * test execute into interactive() with admin methods.
 *
 * @return void
 */
	public function testExecuteInteractiveWithAdmin() {
		Configure::write('Routing.prefixes', array('admin'));
		$this->Task->connection = 'test';
		$this->Task->args = array();

		$this->Task->Controller->expects($this->once())->method('getName')
			->will($this->returnValue('ViewTaskComments'));

		$this->Task->Project->expects($this->once())->method('getPrefix')
			->will($this->returnValue('admin_'));

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('y', 'n', 'y'));

		$this->Task->expects($this->at(3))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'admin_index.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(4))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'admin_view.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(5))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'admin_add.ctp',
				$this->stringContains('Add View Task Comment')
			);

		$this->Task->expects($this->at(6))->method('createFile')
			->with(
				TMP . 'ViewTaskComments' . DS . 'admin_edit.ctp',
				$this->stringContains('Edit View Task Comment')
			);

		$this->Task->expects($this->exactly(4))->method('createFile');
		$this->Task->execute();
	}

/**
 * test getting templates, make sure noTemplateActions works and prefixed template is used before generic one.
 *
 * @return void
 */
	public function testGetTemplate() {
		$result = $this->Task->getTemplate('delete');
		$this->assertFalse($result);

		$result = $this->Task->getTemplate('add');
		$this->assertEquals('form', $result);

		Configure::write('Routing.prefixes', array('admin'));

		$result = $this->Task->getTemplate('admin_add');
		$this->assertEquals('form', $result);

		$this->Task->Template->templatePaths = array(
			'test' => CAKE . 'Test' . DS . 'test_app' . DS . 'Console' . DS . 'Templates' . DS . 'test' . DS
		);
		$this->Task->Template->params['theme'] = 'test';

		$result = $this->Task->getTemplate('admin_edit');
		$this->assertEquals('admin_edit', $result);
	}

}
