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
		$this->belongsTo('BakeUsers');
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

		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);
		$this->Task = $this->getMock('Cake\Console\Command\Task\ControllerTask',
			array('in', 'out', 'err', 'hr', 'createFile', '_stop'),
			array($io)
		);
		$this->Task->name = 'Controller';
		$this->Task->connection = 'test';

		$this->Task->Template = new TemplateTask($io);
		$this->Task->Template->params['theme'] = 'default';

		$this->Task->Model = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'out', 'err', 'createFile', '_stop'),
			array($io)
		);
		$this->Task->Test = $this->getMock(
			'Cake\Console\Command\Task\TestTask',
			[],
			[$io]
		);

		TableRegistry::get('BakeArticles', [
			'className' => __NAMESPACE__ . '\BakeArticlesTable'
		]);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Task);
		TableRegistry::clear();
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
		$this->assertSame([], $result);

		$this->Task->params['components'] = '  , Security, ,  Csrf';
		$result = $this->Task->getComponents();
		$this->assertSame(['Security', 'Csrf'], $result);
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
		$this->assertSame(['Session', 'Number'], $result);
	}

/**
 * test the bake method
 *
 * @return void
 */
	public function testBakeNoActions() {
		$this->Task->expects($this->any())
			->method('createFile')
			->will($this->returnValue(true));

		$this->Task->params['no-actions'] = true;
		$this->Task->params['helpers'] = 'Html,Time';
		$this->Task->params['components'] = 'Csrf, Auth';

		$result = $this->Task->bake('BakeArticles');
		$expected = file_get_contents(CORE_TESTS . '/bake_compare/Controller/NoActions.ctp');
		$this->assertTextEquals($expected, $result);
	}

/**
 * test bake with actions.
 *
 * @return void
 */
	public function testBakeActions() {
		$this->Task->params['helpers'] = 'Html,Time';
		$this->Task->params['components'] = 'Csrf, Auth';

		$filename = APP . 'Controller/BakeArticlesController.php';
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with(
				$this->_normalizePath($filename),
				$this->stringContains('class BakeArticlesController')
			);
		$result = $this->Task->bake('BakeArticles');

		$this->assertTextContains('public function add(', $result);
		$this->assertTextContains('public function index(', $result);
		$this->assertTextContains('public function view(', $result);
		$this->assertTextContains('public function edit(', $result);
		$this->assertTextContains('public function delete(', $result);
	}

/**
 * test bake actions prefixed.
 *
 * @return void
 */
	public function testBakePrefixed() {
		$this->Task->params['prefix'] = 'Admin';

		$filename = $this->_normalizePath(APP . 'Controller/Admin/BakeArticlesController.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename, $this->anything());

		$this->Task->Test->expects($this->at(0))
			->method('bake')
			->with('Controller', 'Admin\BakeArticles');
		$result = $this->Task->bake('BakeArticles');

		$this->assertTextContains('namespace App\Controller\Admin;', $result);
		$this->assertTextContains('use App\Controller\AppController;', $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeWithPlugin() {
		$this->Task->plugin = 'ControllerTest';

		//fake plugin path
		Plugin::load('ControllerTest', array('path' => APP . 'Plugin/ControllerTest/'));
		$path = APP . 'Plugin/ControllerTest/Controller/BakeArticlesController.php';

		$this->Task->expects($this->at(1))
			->method('createFile')
			->with(
				$this->_normalizePath($path),
				$this->stringContains('BakeArticlesController extends AppController')
			)->will($this->returnValue(true));

		$result = $this->Task->bake('BakeArticles');
		$this->assertContains('namespace ControllerTest\Controller;', $result);
		$this->assertContains('use ControllerTest\Controller\AppController;', $result);

		Plugin::unload();
	}

/**
 * test that bakeActions is creating the correct controller Code. (Using sessions)
 *
 * @return void
 */
	public function testBakeActionsContent() {
		$result = $this->Task->bakeActions('BakeArticles');
		$expected = file_get_contents(CORE_TESTS . 'bake_compare/Controller/Actions.ctp');
		$this->assertTextEquals($expected, $result);
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
 * Test execute no args.
 *
 * @return void
 */
	public function testMainNoArgs() {
		$this->Task->expects($this->never())
			->method('createFile');

		$this->Task->expects($this->at(0))
			->method('out')
			->with($this->stringContains('Possible controllers based on your current database'));

		$this->Task->main();
	}

/**
 * test that execute runs all when the first arg == all
 *
 * @return void
 */
	public function testMainIntoAll() {
		$count = count($this->Task->listAll());
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}
		$this->Task->connection = 'test';
		$this->Task->params = ['helpers' => 'Time,Text'];

		$this->Task->Test->expects($this->atLeastOnce())
			->method('bake');

		$filename = $this->_normalizePath(APP . 'Controller/BakeArticlesController.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename, $this->logicalAnd(
				$this->stringContains('class BakeArticlesController'),
				$this->stringContains("\$helpers = ['Time', 'Text']")
			))
			->will($this->returnValue(true));

		$this->Task->all();
	}

/**
 * data provider for testMainWithControllerNameVariations
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
	public function testMainWithControllerNameVariations($name) {
		$this->Task->connection = 'test';

		$filename = $this->_normalizePath(APP . 'Controller/BakeArticlesController.php');
		$this->Task->expects($this->once())
			->method('createFile')
			->with($filename, $this->stringContains('public function index()'));
		$this->Task->main($name);
	}

}
