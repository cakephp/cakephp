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
	public $fixtures = array('core.article', 'core.post', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * setUp method
 *
 * Ensure that the default theme is used
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');
		$this->_setupTask(['in', 'err', 'createFile', '_stop']);

		TableRegistry::get('ViewTaskComments', [
			'className' => __NAMESPACE__ . '\ViewTaskCommentsTable',
		]);
	}

/**
 * Generate the mock objects used in tests.
 *
 * @return void
 */
	protected function _setupTask($methods) {
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ViewTask',
			$methods,
			[$io]
		);
		$this->Task->Template = new TemplateTask($io);
		$this->Task->Model = $this->getMock('Cake\Console\Command\Task\ModelTask', [], [$io]);

		$this->Task->Template->params['theme'] = 'default';
		$this->Task->Template->templatePaths = ['default' => CAKE . 'Console/Templates/default/'];
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
 * Test the controller() method.
 *
 * @dataProvider nameVariations
 * @return void
 */
	public function testControllerVariations($name) {
		$this->Task->controller($name);
		$this->assertEquals('ViewTaskComments', $this->Task->controllerName);
		$this->assertEquals('ViewTaskComments', $this->Task->tableName);
	}

/**
 * Test controller method with plugins.
 *
 * @return void
 */
	public function testControllerPlugin() {
		$this->Task->params['plugin'] = 'TestPlugin';
		$this->Task->controller('Tests');
		$this->assertEquals('Tests', $this->Task->controllerName);
		$this->assertEquals('Tests', $this->Task->tableName);
		$this->assertEquals(
			'TestPlugin\Controller\TestsController',
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
		$this->assertEquals('Posts', $this->Task->tableName);
		$this->assertEquals(
			'TestApp\Controller\Admin\PostsController',
			$this->Task->controllerClass
		);

		$this->Task->params['plugin'] = 'TestPlugin';
		$this->Task->controller('Comments');
		$this->assertEquals('Comments', $this->Task->controllerName);
		$this->assertEquals('Comments', $this->Task->tableName);
		$this->assertEquals(
			'TestPlugin\Controller\Admin\CommentsController',
			$this->Task->controllerClass
		);
	}

/**
 * test controller with a non-conventional controller name
 *
 * @return void
 */
	public function testControllerWithOverride() {
		$this->Task->controller('Comments', 'Posts');
		$this->assertEquals('Posts', $this->Task->controllerName);
		$this->assertEquals('Comments', $this->Task->tableName);
		$this->assertEquals(
			'TestApp\Controller\PostsController',
			$this->Task->controllerClass
		);
	}

/**
 * Test getPath()
 *
 * @return void
 */
	public function testGetPath() {
		$this->Task->controllerName = 'Posts';

		$result = $this->Task->getPath();
		$this->assertPathEquals(APP . 'Template/Posts/', $result);

		$this->Task->params['prefix'] = 'admin';
		$result = $this->Task->getPath();
		$this->assertPathEquals(APP . 'Template/Admin/Posts/', $result);
	}

/**
 * Test getPath with plugins.
 *
 * @return void
 */
	public function testGetPathPlugin() {
		$this->Task->controllerName = 'Posts';

		$pluginPath = APP . 'Plugin/TestPlugin/';
		Plugin::load('TestPlugin', array('path' => $pluginPath));

		$this->Task->params['plugin'] = $this->Task->plugin = 'TestPlugin';
		$result = $this->Task->getPath();
		$this->assertPathEquals($pluginPath . 'Template/Posts/', $result);

		$this->Task->params['prefix'] = 'admin';
		$result = $this->Task->getPath();
		$this->assertPathEquals($pluginPath . 'Template/Admin/Posts/', $result);

		Plugin::unload('TestPlugin');
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
			'associations' => [],
			'keyFields' => [],
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
			'keyFields' => [],
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
		$this->Task->tableName = 'ViewTaskComments';
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/ViewTaskComments/view.ctp'),
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
		$this->Task->tableName = 'ViewTaskComments';
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/ViewTaskComments/edit.ctp'),
				$this->anything()
			);
		$result = $this->Task->bake('edit', true);

		$this->assertContains("Form->input('id')", $result);
		$this->assertContains("Form->input('article_id', ['options' => \$articles])", $result);
	}

/**
 * test baking an index
 *
 * @return void
 */
	public function testBakeIndex() {
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->tableName = 'ViewTaskComments';
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->at(0))->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/ViewTaskComments/index.ctp'),
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
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->tableName = 'ViewTaskComments';
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->never())->method('createFile');
		$this->Task->bake('delete', true);
	}

/**
 * test bake actions baking multiple actions.
 *
 * @return void
 */
	public function testBakeActions() {
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->tableName = 'ViewTaskComments';
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/ViewTaskComments/view.ctp'),
				$this->stringContains('View Task Comments')
			);
		$this->Task->expects($this->at(1))->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/ViewTaskComments/edit.ctp'),
				$this->stringContains('Edit View Task Comment')
			);
		$this->Task->expects($this->at(2))->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/ViewTaskComments/index.ctp'),
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
		$this->Task->tableName = 'ViewTaskComments';
		$this->Task->controllerClass = __NAMESPACE__ . '\ViewTaskCommentsController';

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('', 'my_action', 'y'));

		$this->Task->expects($this->once())->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/ViewTaskComments/my_action.ctp'),
				$this->anything()
			);

		$this->Task->customAction();
	}

/**
 * Test execute no args.
 *
 * @return void
 */
	public function testMainNoArgs() {
		$this->_setupTask(['in', 'err', 'bake', 'createFile', '_stop']);

		$this->Task->Model->expects($this->once())
			->method('listAll')
			->will($this->returnValue(['comments', 'articles']));

		$this->Task->expects($this->never())
			->method('bake');

		$this->Task->main();
	}

/**
 * Test all() calls execute
 *
 * @return void
 */
	public function testAllCallsMain() {
		$this->_setupTask(['in', 'err', 'createFile', 'main', '_stop']);

		$this->Task->Model->expects($this->once())
			->method('listAll')
			->will($this->returnValue(['comments', 'articles']));

		$this->Task->expects($this->exactly(2))
			->method('main');
		$this->Task->expects($this->at(0))
			->method('main')
			->with('comments');
		$this->Task->expects($this->at(1))
			->method('main')
			->with('articles');

		$this->Task->all();
	}

/**
 * test `cake bake view $controller view`
 *
 * @return void
 */
	public function testMainWithActionParam() {
		$this->_setupTask(['in', 'err', 'createFile', 'bake', '_stop']);

		$this->Task->expects($this->once())
			->method('bake')
			->with('view', true);

		$this->Task->main('ViewTaskComments', 'view');
	}

/**
 * test `cake bake view $controller`
 * Ensure that views are only baked for actions that exist in the controller.
 *
 * @return void
 */
	public function testMainWithController() {
		$this->_setupTask(['in', 'err', 'createFile', 'bake', '_stop']);

		$this->Task->expects($this->exactly(4))
			->method('bake');

		$this->Task->expects($this->at(0))
			->method('bake')
			->with('index', $this->anything());

		$this->Task->expects($this->at(1))
			->method('bake')
			->with('view', $this->anything());

		$this->Task->expects($this->at(2))
			->method('bake')
			->with('add', $this->anything());

		$this->Task->expects($this->at(3))
			->method('bake')
			->with('edit', $this->anything());

		$this->Task->main('ViewTaskComments');
	}

/**
 * static dataprovider for test cases
 *
 * @return void
 */
	public static function nameVariations() {
		return [['ViewTaskComments'], ['ViewTaskComment'], ['view_task_comment']];
	}

/**
 * test `cake bake view $table --controller Blog`
 *
 * @return void
 */
	public function testMainWithControllerFlag() {
		$this->Task->params['controller'] = 'Blog';

		$this->Task->expects($this->exactly(4))
			->method('createFile');

		$views = array('index.ctp', 'view.ctp', 'add.ctp', 'edit.ctp');
		foreach ($views as $i => $view) {
			$this->Task->expects($this->at($i))->method('createFile')
				->with(
					$this->_normalizePath(APP . 'Template/Blog/' . $view),
					$this->anything()
				);
		}
		$this->Task->main('Posts');
	}

/**
 * test `cake bake view $controller --prefix Admin`
 *
 * @return void
 */
	public function testMainWithControllerAndAdminFlag() {
		$this->Task->params['prefix'] = 'Admin';

		$this->Task->expects($this->exactly(2))
			->method('createFile');

		$views = array('index.ctp', 'add.ctp');
		foreach ($views as $i => $view) {
			$this->Task->expects($this->at($i))->method('createFile')
				->with(
					$this->_normalizePath(APP . 'Template/Admin/Posts/' . $view),
					$this->anything()
				);
		}
		$this->Task->main('Posts');
	}

/**
 * test `cake bake view posts index list`
 *
 * @return void
 */
	public function testMainWithAlternateTemplates() {
		$this->_setupTask(['in', 'err', 'createFile', 'bake', '_stop']);

		$this->Task->connection = 'test';
		$this->Task->params = [];

		$this->Task->expects($this->once())
			->method('bake')
			->with('list', true);
		$this->Task->main('ViewTaskComments', 'index', 'list');
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
