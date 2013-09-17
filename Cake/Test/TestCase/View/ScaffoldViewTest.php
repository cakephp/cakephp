<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\View;

use Cake\Controller\Controller;
use Cake\Controller\Scaffold;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\ScaffoldView;
use TestApp\Controller\ScaffoldArticlesController;

require_once dirname(__DIR__) . DS . 'Model' . DS . 'models.php';

if (!class_exists('Cake\Model\ScaffoldMock')) {
	class_alias('Cake\Test\TestCase\Model\ScaffoldMock', 'Cake\Model\ScaffoldMock');
}

/**
 * TestScaffoldView class
 *
 * @package       Cake.Test.TestCase.View
 */
class TestScaffoldView extends ScaffoldView {

/**
 * testGetFilename method
 *
 * @param string $action
 * @return void
 */
	public function testGetFilename($action) {
		return $this->_getViewFileName($action);
	}

}


/**
 * ScaffoldViewTest class
 *
 * @package       Cake.Test.TestCase.View
 */
class ScaffoldViewTest extends TestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = [
		'core.article',
		'core.user',
		'core.comment',
		'core.articles_tag',
		'core.tag'
	];

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->markTestIncomplete('Need to revisit once models work again.');
		Configure::write('App.namespace', 'TestApp');

		Router::connect('/:controller/:action/*');
		Router::connect('/:controller', ['action' => 'index']);

		$this->request = new Request();
		$this->Controller = new ScaffoldArticlesController($this->request);
		$this->Controller->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));

		Plugin::load('TestPlugin');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Controller, $this->request);
		parent::tearDown();
	}

/**
 * testGetViewFilename method
 *
 * @return void
 */
	public function testGetViewFilename() {
		Configure::write('Routing.prefixes', array('admin'));

		$this->Controller->request->params['action'] = 'index';
		$ScaffoldView = new TestScaffoldView($this->Controller);
		$result = $ScaffoldView->testGetFilename('index');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'index.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('edit');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'form.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('add');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'form.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('view');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'view.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('admin_index');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'index.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('admin_view');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'view.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('admin_edit');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'form.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('admin_add');
		$expected = CAKE . 'View' . DS . 'Scaffold' . DS . 'form.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('error');
		$expected = CAKE . 'View' . DS . 'Error' . DS . 'scaffold_error.ctp';
		$this->assertEquals($expected, $result);

		$Controller = new ScaffoldArticlesController($this->request);
		$Controller->scaffold = 'admin';
		$Controller->viewPath = 'Posts';
		$Controller->request['action'] = 'admin_edit';

		$ScaffoldView = new TestScaffoldView($Controller);
		$result = $ScaffoldView->testGetFilename('admin_edit');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS . 'Posts' . DS . 'scaffold.form.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('edit');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS . 'Posts' . DS . 'scaffold.form.ctp';
		$this->assertEquals($expected, $result);

		$Controller = new ScaffoldArticlesController($this->request);
		$Controller->scaffold = 'admin';
		$Controller->viewPath = 'Tests';
		$Controller->request->addParams(array(
			'plugin' => 'test_plugin',
			'action' => 'admin_add',
			'admin' => true
		));
		$Controller->plugin = 'TestPlugin';

		$ScaffoldView = new TestScaffoldView($Controller);
		$pluginPath = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS . 'TestPlugin' . DS;

		$result = $ScaffoldView->testGetFilename('admin_add');
		$expected = $pluginPath . 'View' . DS . 'Tests' . DS . 'scaffold.form.ctp';
		$this->assertEquals($expected, $result);

		$result = $ScaffoldView->testGetFilename('add');
		$expected = $pluginPath . 'View' . DS . 'Tests' . DS . 'scaffold.form.ctp';
		$this->assertEquals($expected, $result);
	}

/**
 * test getting the view file name for themed scaffolds.
 *
 * @return void
 */
	public function testGetViewFileNameWithTheme() {
		$this->Controller->request['action'] = 'index';
		$this->Controller->viewPath = 'Posts';
		$this->Controller->theme = 'TestTheme';
		$ScaffoldView = new TestScaffoldView($this->Controller);
		$themePath = CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;

		$result = $ScaffoldView->testGetFilename('index');
		$expected = $themePath . 'Posts' . DS . 'scaffold.index.ctp';
		$this->assertEquals($expected, $result);
	}

/**
 * test default index scaffold generation
 *
 * @return void
 */
	public function testIndexScaffold() {
		$params = [
			'plugin' => null,
			'pass' => [],
			'controller' => 'articles',
			'action' => 'index',
		];
		$this->Controller->request->addParams($params);
		$this->Controller->request->webroot = '/';
		$this->Controller->request->base = '';
		$this->Controller->request->here = '/articles/index';

		Router::pushRequest($this->Controller->request);

		$this->Controller->constructClasses();
		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$this->Controller->response->send();
		$result = ob_get_clean();

		$this->assertRegExp('#<h2>Articles</h2>#', $result);
		$this->assertRegExp('#<table cellpadding="0" cellspacing="0">#', $result);

		$this->assertRegExp('#<a href="/users/view/1">1</a>#', $result); //belongsTo links
		$this->assertRegExp('#<li><a href="/articles/add">New Article</a></li>#', $result);
		$this->assertRegExp('#<li><a href="/users">List Users</a></li>#', $result);
		$this->assertRegExp('#<li><a href="/comments/add">New Comment</a></li>#', $result);
	}

/**
 * test default view scaffold generation
 *
 * @return void
 */
	public function testViewScaffold() {
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$params = [
			'plugin' => null,
			'pass' => [1],
			'controller' => 'articles',
			'action' => 'view',
		];
		$this->Controller->request->addParams($params);

		//set router.
		Router::pushRequest($this->Controller->request);
		$this->Controller->constructClasses();

		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$this->Controller->response->send();
		$result = ob_get_clean();

		$this->assertRegExp('#<h2>View Article</h2>#', $result);
		$this->assertRegExp('#<dl>#', $result);

		$this->assertRegExp('#<a href="/users/view/1">1</a>#', $result); //belongsTo links
		$this->assertRegExp('#<li><a href="/articles/edit/1">Edit Article</a>\s*</li>#', $result);
		$this->assertRegExp('#<a href="\#" onclick="if[^>]*>Delete Article</a>\s*</li>#', $result);
		//check related table
		$this->assertRegExp('#<div class="related">\s*<h3>Related Comments</h3>\s*<table cellpadding="0" cellspacing="0">#', $result);
		$this->assertRegExp('#<li><a href="/comments/add">New Comment</a></li>#', $result);
		$this->assertNotRegExp('#<th>JoinThing</th>#', $result);
	}

/**
 * test default edit scaffold generation
 *
 * @return void
 */
	public function testEditScaffold() {
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/articles/edit/1';

		$params = [
			'plugin' => null,
			'pass' => [1],
			'controller' => 'articles',
			'action' => 'edit',
		];
		$this->Controller->request->addParams($params);

		//set router.
		Router::pushRequest($this->Controller->request);
		$this->Controller->constructClasses();

		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$this->Controller->response->send();
		$result = ob_get_clean();

		$this->assertContains('<form action="/articles/edit/1" id="ArticleEditForm" method="post"', $result);
		$this->assertContains('<legend>Edit Article</legend>', $result);

		$this->assertContains('input type="hidden" name="Article[id]" value="1" id="ArticleId"', $result);
		$this->assertContains('select name="Article[user_id]" id="ArticleUserId"', $result);
		$this->assertContains('input name="Article[title]" maxlength="255" type="text" value="First Article" id="ArticleTitle"', $result);
		$this->assertContains('input name="Article[published]" maxlength="1" type="text" value="Y" id="ArticlePublished"', $result);
		$this->assertContains('textarea name="Article[body]" cols="30" rows="6" id="ArticleBody"', $result);
		$this->assertRegExp('/<a href="\#" onclick="if[^>]*>Delete<\/a><\/li>/', $result);
	}

}
