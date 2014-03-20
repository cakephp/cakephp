<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\ControllerTask;
use Cake\Console\Command\Task\TemplateTask;
use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;

/**
 * Class BakeArticle
 */
class BakeArticlesTable extends Table {

	public function initialize(array $config) {
		$this->hasMany('BakeComments');
		$this->belongsToMany('BakeTags');
	}

}

/**
 * ControllerTaskTest class
 *
 */
class ControllerTaskTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = ['core.bake_article', 'core.bake_articles_bake_tag', 'core.bake_comment', 'core.bake_tag'];

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('Cake\Console\Command\Task\ControllerTask',
			array('in', 'out', 'err', 'hr', 'createFile', '_stop'),
			array($out, $out, $in)
		);
		$this->Task->name = 'Controller';
		$this->Task->connection = 'test';

		$this->Task->Template = new TemplateTask($out, $out, $in);
		$this->Task->Template->params['theme'] = 'default';

		$this->Task->Model = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'out', 'err', 'createFile', '_stop'),
			array($out, $out, $in)
		);
		$this->Task->Project = $this->getMock('Cake\Console\Command\Task\ProjectTask',
			array('in', 'out', 'err', 'createFile', '_stop', 'getPrefix'),
			array($out, $out, $in)
		);
		$this->Task->Test = $this->getMock('Cake\Console\Command\Task\TestTask', array(), array($out, $out, $in));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Task);
		parent::tearDown();
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

		$result = $this->Task->listAll();
		$expected = array('bake_articles', 'bake_articles_bake_tags', 'bake_comments', 'bake_tags');
		$this->assertEquals($expected, $result);
	}

/**
 * test component generation
 *
 * @return void
 */
	public function testGetComponents() {
		$result = $this->Task->getComponents();
		$this->assertSame(['Paginator'], $result);

		$this->Task->params['components'] = '  , Security, ,  Csrf';
		$result = $this->Task->getComponents();
		$this->assertSame(['Security', 'Csrf', 'Paginator'], $result);

		$this->Task->params['components'] = '  Paginator , Security, ,  Csrf';
		$result = $this->Task->getComponents();
		$this->assertSame(['Paginator', 'Security', 'Csrf'], $result);
	}

/**
 * test helper generation
 *
 * @return void
 */
	public function testGetHelpers() {
		$result = $this->Task->getHelpers();
		$this->assertSame([], $result);

		$this->Task->params['helpers'] = '  , Session , ,  Number';
		$result = $this->Task->getHelpers();
		$this->assertSame(['Session', 'Number', 'Form'], $result);

		$this->Task->params['helpers'] = '  Session , Number , ,  Form';
		$result = $this->Task->getHelpers();
		$this->assertSame(['Session', 'Number', 'Form'], $result);
	}

/**
 * test the bake method
 *
 * @return void
 */
	public function testBake() {
		$this->markTestIncomplete();
		$helpers = array('Js', 'Time');
		$components = array('Acl', 'Auth');
		$this->Task->expects($this->any())->method('createFile')->will($this->returnValue(true));

		$result = $this->Task->bake('Articles', null, $helpers, $components);
		$expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'NoActions.ctp');
		$this->assertTextEquals($expected, $result);

		$result = $this->Task->bake('Articles', null, array(), array());
		$expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'NoHelpersOrComponents.ctp');
		$this->assertTextEquals($expected, $result);

		$result = $this->Task->bake('Articles', 'scaffold', $helpers, $components);
		$expected = file_get_contents(CAKE . 'Test' . DS . 'bake_compare' . DS . 'Controller' . DS . 'Scaffold.ctp');
		$this->assertTextEquals($expected, $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeWithPlugin() {
		$this->markTestIncomplete();
		$this->Task->plugin = 'ControllerTest';

		//fake plugin path
		Plugin::load('ControllerTest', array('path' => APP . 'Plugin/ControllerTest/'));
		$path = APP . 'Plugin/ControllerTest/Controller/ArticlesController.php';

		$this->Task->expects($this->at(1))->method('createFile')->with(
			$path,
			$this->anything()
		);
		$this->Task->expects($this->at(3))->method('createFile')->with(
			$path,
			$this->stringContains('ArticlesController extends ControllerTestAppController')
		)->will($this->returnValue(true));

		$this->Task->bake('Articles', '--actions--', array(), array(), array());

		$this->Task->plugin = 'ControllerTest';
		$path = APP . 'Plugin/ControllerTest/Controller/ArticlesController.php';
		$result = $this->Task->bake('Articles', '--actions--', array(), array(), array());

		$this->assertContains("App::uses('ControllerTestAppController', 'ControllerTest.Controller');", $result);
		$this->assertEquals('ControllerTest', $this->Task->Template->viewVars['plugin']);
		$this->assertEquals('ControllerTest.', $this->Task->Template->viewVars['pluginPath']);

		Plugin::unload();
	}

/**
 * test that bakeActions is creating the correct controller Code. (Using sessions)
 *
 * @return void
 */
	public function testBakeActions() {
		TableRegistry::get('BakeArticles', [
			'className' => __NAMESPACE__ . '\BakeArticlesTable'
		]);

		$result = $this->Task->bakeActions('BakeArticles');
		$expected = file_get_contents(CORE_TESTS . 'bake_compare/Controller/Actions.ctp');
		$this->assertTextEquals($expected, $result);

		$result = $this->Task->bakeActions('BakeArticles', 'admin_', true);
		$this->assertContains('function admin_index() {', $result);
		$this->assertContains('function admin_add()', $result);
		$this->assertContains('function admin_view($id = null)', $result);
		$this->assertContains('function admin_edit($id = null)', $result);
		$this->assertContains('function admin_delete($id = null)', $result);
	}

/**
 * test baking a test
 *
 * @return void
 */
	public function testBakeTest() {
		$this->Task->plugin = 'ControllerTest';
		$this->Task->connection = 'test';

		$this->Task->Test->expects($this->once())
			->method('bake')
			->with('Controller', 'BakeArticles');
		$this->Task->bakeTest('BakeArticles');

		$this->assertEquals($this->Task->plugin, $this->Task->Test->plugin);
		$this->assertEquals($this->Task->connection, $this->Task->Test->connection);
	}

/**
 * test baking a test
 *
 * @return void
 */
	public function testBakeTestDisabled() {
		$this->Task->plugin = 'ControllerTest';
		$this->Task->connection = 'test';
		$this->Task->params['no-test'] = true;

		$this->Task->Test->expects($this->never())
			->method('bake');
		$this->Task->bakeTest('BakeArticles');
	}

/**
 * test that execute runs all when the first arg == all
 *
 * @return void
 */
	public function testExecuteIntoAll() {
		$this->markTestIncomplete();
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');

		$this->Task->expects($this->any())->method('_checkUnitTest')->will($this->returnValue(true));
		$this->Task->Test->expects($this->once())->method('bake');

		$filename = '/my/path/BakeArticlesController.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename,
			$this->stringContains('class BakeArticlesController')
		)->will($this->returnValue(true));

		$this->Task->execute();
	}

/**
 * Test execute() with all and --admin
 *
 * @return void
 */
	public function testExecuteIntoAllAdmin() {
		$this->markTestIncomplete();
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->params['admin'] = true;

		$this->Task->Project->expects($this->any())
			->method('getPrefix')
			->will($this->returnValue('admin_'));
		$this->Task->expects($this->any())
			->method('_checkUnitTest')
			->will($this->returnValue(true));
		$this->Task->Test->expects($this->once())->method('bake');

		$filename = '/my/path/BakeArticlesController.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename,
			$this->stringContains('function admin_index')
		)->will($this->returnValue(true));

		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos` works.
 *
 * @return void
 */
	public function testExecuteWithController() {
		$this->markTestIncomplete();
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');

		$filename = '/my/path/BakeArticlesController.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename,
			$this->stringContains('$scaffold')
		);

		$this->Task->execute();
	}

/**
 * data provider for testExecuteWithControllerNameVariations
 *
 * @return void
 */
	public static function nameVariations() {
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
		$this->markTestIncomplete();
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array($name);

		$filename = '/my/path/BakeArticlesController.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, $this->stringContains('$scaffold')
		);
		$this->Task->execute();
	}

/**
 * test that `cake bake controller foo scaffold` works.
 *
 * @return void
 */
	public function testExecuteWithPublicParam() {
		$this->markTestIncomplete();
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');
		$this->Task->params = array('public' => true);

		$filename = '/my/path/BakeArticlesController.php';
		$expected = new \PHPUnit_Framework_Constraint_Not($this->stringContains('$scaffold'));
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
		$this->markTestIncomplete();
		$this->Task->Project->expects($this->any())->method('getPrefix')->will($this->returnValue('admin_'));
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');
		$this->Task->params = array('public' => true, 'admin' => true);

		$filename = '/my/path/BakeArticlesController.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, $this->stringContains('admin_index')
		);
		$this->Task->execute();
	}

/**
 * test that `cake bake controller foos admin` works.
 *
 * @return void
 */
	public function testExecuteWithControllerAndAdmin() {
		$this->markTestIncomplete();
		$this->Task->Project->expects($this->any())->method('getPrefix')->will($this->returnValue('admin_'));
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticles');
		$this->Task->params = array('admin' => true);

		$filename = '/my/path/BakeArticlesController.php';
		$this->Task->expects($this->once())->method('createFile')->with(
			$filename, $this->stringContains('admin_index')
		);
		$this->Task->execute();
	}

}
