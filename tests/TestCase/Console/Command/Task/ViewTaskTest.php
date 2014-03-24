<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\TemplateTask;
use Cake\Console\Command\Task\ViewTask;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Test View Task Comment Model
 *
 */
class ViewTaskCommentsTable extends Table {

	public function initialize(array $config) {
		$this->table('comments');
		$this->belongsTo('Articles', [
			'foreignKey' => 'article_id'
		]);
	}
}

/**
 * Test View Task Article Model
 *
 */
class ViewTaskArticlesTable extends Table {

	public function intialize(array $config) {
		$this->table('articles');
	}
}

/**
 * Test View Task Comments Controller
 *
 */
class ViewTaskCommentsController extends Controller {

	public $modelClass = 'Cake\Test\TestCase\Console\Command\Task\ViewTaskCommentsTable';

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
 * ViewTaskTest class
 */
class ViewTaskTest extends TestCase {

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

		$out = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ViewTask',
			['in', 'err', 'createFile', '_stop'],
			[$out, $out, $in]
		);
		$this->Task->Template = new TemplateTask($out, $out, $in);
		$this->Task->Controller = $this->getMock('Cake\Console\Command\Task\ControllerTask', [], [$out, $out, $in]);
		$this->Task->Model = $this->getMock('Cake\Console\Command\Task\ModelTask', [], [$out, $out, $in]);
		$this->Task->Project = $this->getMock('Cake\Console\Command\Task\ProjectTask', [], [$out, $out, $in]);
		$this->Task->DbConfig = $this->getMock('Cake\Console\Command\Task\DbConfigTask', [], [$out, $out, $in]);

		$this->Task->path = TMP;
		$this->Task->Template->params['theme'] = 'default';
		$this->Task->Template->templatePaths = ['default' => CAKE . 'Console/Templates/default/'];

		Configure::write('App.namespace', 'TestApp');

		TableRegistry::get('ViewTaskComments', [
			'className' => __NAMESPACE__ . '\ViewTaskCommentsTable',
		]);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
		unset($this->Task);
	}

/**
 * Test the controller() method.
 *
 * @return void
 */
	public function testController() {
		$this->Task->controller('Comments');
		$this->assertEquals('Comments', $this->Task->controllerName);
		$this->assertEquals(
			'TestApp\Controller\CommentsController',
			$this->Task->controllerClass
		);
	}

/**
 * Test controller method with plugins.
 *
 * @return void
 */
	public function testControllerPlugin() {
		$this->Task->params['plugin'] = 'TestPlugin';
		$this->Task->controller('TestPlugin');
		$this->assertEquals('TestPlugin', $this->Task->controllerName);
		$this->assertEquals(
			'TestPlugin\Controller\TestPluginController',
			$this->Task->controllerClass
		);
	}

/**
 * Test controller method with prefixes.
 *
 * @return void
 */
	public function testControllerPrefix() {
		$this->Task->params['prefix'] = 'Admin';
		$this->Task->controller('Posts');
		$this->assertEquals('Posts', $this->Task->controllerName);
		$this->assertEquals(
			'TestApp\Controller\Admin\PostsController',
			$this->Task->controllerClass
		);

		$this->Task->params['plugin'] = 'TestPlugin';
		$this->Task->controller('Comments');
		$this->assertEquals('Comments', $this->Task->controllerName);
		$this->assertEquals(
			'TestPlugin\Controller\Admin\CommentsController',
			$this->Task->controllerClass
		);
	}

/**
 * Test getContent and parsing of Templates.
 *
 * @return void
 */
	public function testGetContent() {
		$vars = array(
			'modelClass' => 'TestViewModel',
			'schema' => [],
			'primaryKey' => ['id'],
			'displayField' => 'name',
			'singularVar' => 'testViewModel',
			'pluralVar' => 'testViewModels',
			'singularHumanName' => 'Test View Model',
			'pluralHumanName' => 'Test View Models',
			'fields' => ['id', 'name', 'body'],
			'associations' => []
		);
		$result = $this->Task->getContent('view', $vars);

		$this->assertContains('Delete Test View Model', $result);
		$this->assertContains('Edit Test View Model', $result);
		$this->assertContains('List Test View Models', $result);
		$this->assertContains('New Test View Model', $result);

		$this->assertContains('$testViewModel->id', $result);
		$this->assertContains('$testViewModel->name', $result);
		$this->assertContains('$testViewModel->body', $result);
	}

/**
 * test getContent() using a routing prefix action.
 *
 * @return void
 */
	public function testGetContentWithRoutingPrefix() {
		$vars = array(
			'modelClass' => 'TestViewModel',
			'schema' => [],
			'primaryKey' => ['id'],
			'displayField' => 'name',
			'singularVar' => 'testViewModel',
			'pluralVar' => 'testViewModels',
			'singularHumanName' => 'Test View Model',
			'pluralHumanName' => 'Test View Models',
			'fields' => ['id', 'name', 'body'],
			'associations' => []
		);
		$this->Task->params['prefix'] = 'Admin';
		$result = $this->Task->getContent('view', $vars);

		$this->assertContains('Delete Test View Model', $result);
		$this->assertContains('Edit Test View Model', $result);
		$this->assertContains('List Test View Models', $result);
		$this->assertContains('New Test View Model', $result);

		$this->assertContains('$testViewModel->id', $result);
		$this->assertContains('$testViewModel->name', $result);
		$this->assertContains('$testViewModel->body', $result);

		$result = $this->Task->getContent('add', $vars);
		$this->assertContains("input('name')", $result);
		$this->assertContains("input('body')", $result);
		$this->assertContains('List Test View Models', $result);
	}

/**
 * test Bake method
 *
 * @return void
 */
	public function testBakeView() {
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with(
				TMP . 'ViewTaskComments/view.ctp',
				$this->stringContains('View Task Comments')
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
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/edit.ctp',
				$this->anything()
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
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/index.ctp',
				$this->stringContains("\$viewTaskComment->article->title")
			);
		$this->Task->bake('index', true);
	}

/**
 * test that baking a view with no template doesn't make a file.
 *
 * @return void
 */
	public function testBakeWithNoTemplate() {
		$this->markTestIncomplete('Model baking will not work as models do not work.');
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->markTestIncomplete('Still fails because of issues with modelClass');

		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->plugin = 'TestTest';
		$this->Task->name = 'View';

		//fake plugin path
		Plugin::load('TestTest', array('path' => APP . 'Plugin/TestTest/'));
		$path = APP . 'Plugin/TestTest/View/ViewTaskComments/view.ctp';

		$result = $this->Task->getContent('index');
		$this->assertNotContains('List Test Test.view Task Articles', $result);

		$this->Task->expects($this->once())
			->method('createFile')
			->with($path, $this->anything());

		$this->Task->bake('view', true);
		Plugin::unload();
	}

/**
 * test bake actions baking multiple actions.
 *
 * @return void
 */
	public function testBakeActions() {
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/view.ctp',
				$this->stringContains('View Task Comments')
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/edit.ctp',
				$this->stringContains('Edit View Task Comment')
			);
		$this->Task->expects($this->at(2))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/index.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->controllerName = 'ViewTaskComments';

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('', 'my_action', 'y'));

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments/my_action.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->args[0] = 'all';

		$this->Task->Controller->expects($this->once())->method('listAll')
			->will($this->returnValue(array('view_task_comments')));

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/index.ctp',
				$this->anything()
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/add.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->args = array('all', 'index');

		$this->Task->Controller->expects($this->once())->method('listAll')
			->will($this->returnValue(array('view_task_comments')));

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments/index.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->args[0] = 'ViewTaskComments';
		$this->Task->args[1] = 'view';

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments/view.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->args[0] = 'ViewTaskComments';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/index.ctp',
				$this->anything()
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/add.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->args = array($name);

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/index.ctp',
				$this->anything()
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/add.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
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
					TMP . 'ViewTaskArticles/' . $view,
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->connection = 'test';
		$this->Task->args = array();
		$this->Task->params = array();

		$this->Task->Controller->expects($this->once())->method('getName')
			->will($this->returnValue('ViewTaskComments'));

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('y', 'y', 'n'));

		$this->Task->expects($this->at(3))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/index.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(4))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/view.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(5))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/add.ctp',
				$this->stringContains('Add View Task Comment')
			);

		$this->Task->expects($this->at(6))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/edit.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
		$this->Task->connection = 'test';
		$this->Task->args = array('ViewTaskComments', 'index', 'list');
		$this->Task->params = array();

		$this->Task->expects($this->once())->method('createFile')
			->with(
				TMP . 'ViewTaskComments/list.ctp',
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
		$this->markTestIncomplete('Model baking will not work as models do not work.');
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
				TMP . 'ViewTaskComments/admin_index.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(4))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/admin_view.ctp',
				$this->stringContains('ViewTaskComment')
			);

		$this->Task->expects($this->at(5))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/admin_add.ctp',
				$this->stringContains('Add View Task Comment')
			);

		$this->Task->expects($this->at(6))->method('createFile')
			->with(
				TMP . 'ViewTaskComments/admin_edit.ctp',
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

		$result = $this->Task->getTemplate('edit');
		$this->assertEquals('form', $result);

		$result = $this->Task->getTemplate('view');
		$this->assertEquals('view', $result);

		$result = $this->Task->getTemplate('index');
		$this->assertEquals('index', $result);
	}

/**
 * Test getting prefixed views.
 *
 * @return void
 */
	public function testGetTemplatePrefixed() {
		$this->Task->params['prefix'] = 'Admin';

		$result = $this->Task->getTemplate('add');
		$this->assertEquals('form', $result);

		$this->Task->Template->templatePaths = array(
			'test' => CORE_TESTS . '/test_app/TestApp/Console/Templates/test/'
		);
		$this->Task->Template->params['theme'] = 'test';

		$result = $this->Task->getTemplate('edit');
		$this->assertEquals('admin_edit', $result);

		$result = $this->Task->getTemplate('add');
		$this->assertEquals('admin_form', $result);

		$result = $this->Task->getTemplate('view');
		$this->assertEquals('view', $result);
	}

}
