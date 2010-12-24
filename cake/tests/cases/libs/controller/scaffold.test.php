<?php
/**
 * ScaffoldTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Scaffold', false);
App::import('Core', 'Controller', false);

/**
 * ScaffoldMockController class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldMockController extends Controller {

/**
 * name property
 *
 * @var string 'ScaffoldMock'
 * @access public
 */
	public $name = 'ScaffoldMock';

/**
 * scaffold property
 *
 * @var mixed
 * @access public
 */
	public $scaffold;
}

/**
 * ScaffoldMockControllerWithFields class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldMockControllerWithFields extends Controller {

/**
 * name property
 *
 * @var string 'ScaffoldMock'
 * @access public
 */
	public $name = 'ScaffoldMock';

/**
 * scaffold property
 *
 * @var mixed
 * @access public
 */
	public $scaffold;

/**
 * function _beforeScaffold
 *
 * @param string method
 */
	function _beforeScaffold($method) {
		$this->set('scaffoldFields', array('title'));
		return true;
	}
}

/**
 * TestScaffoldMock class
 *
 * @package       cake.tests.cases.libs.controller
 */
class TestScaffoldMock extends Scaffold {

/**
 * Overload __scaffold
 *
 * @param unknown_type $params
 */
    function _scaffold(CakeRequest $request) {
        $this->_params = $request;
    }

/**
 * Get Params from the Controller.
 *
 * @return unknown
 */
    function getParams() {
        return $this->_params;
    }
}

/**
 * ScaffoldMock class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldMock extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	public $useTable = 'articles';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'User' => array(
			'className' => 'ScaffoldUser',
			'foreignKey' => 'user_id',
		)
	);

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'Comment' => array(
			'className' => 'ScaffoldComment',
			'foreignKey' => 'article_id',
		)
	);
/**
 * hasAndBelongsToMany property
 *
 * @var string
 */
	public $hasAndBelongsToMany = array(
		'ScaffoldTag' => array(
			'className' => 'ScaffoldTag',
			'foreignKey' => 'something_id',
			'associationForeignKey' => 'something_else_id',
			'joinTable' => 'join_things'
		)
	);
}

/**
 * ScaffoldUser class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldUser extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	public $useTable = 'users';

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
		'Article' => array(
			'className' => 'ScaffoldMock',
			'foreignKey' => 'article_id',
		)
	);
}

/**
 * ScaffoldComment class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldComment extends CakeTestModel {

/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	public $useTable = 'comments';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		'Article' => array(
			'className' => 'ScaffoldMock',
			'foreignKey' => 'article_id',
		)
	);
}

/**
 * ScaffoldTag class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldTag extends CakeTestModel {
/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	public $useTable = 'tags';
}
/**
 * TestScaffoldView class
 *
 * @package       cake.tests.cases.libs.controller
 */
class TestScaffoldView extends ScaffoldView {

/**
 * testGetFilename method
 *
 * @param mixed $action
 * @access public
 * @return void
 */
	function testGetFilename($action) {
		return $this->_getViewFileName($action);
	}
}

/**
 * ScaffoldViewTest class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldViewTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.article', 'core.user', 'core.comment', 'core.join_thing', 'core.tag');

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->request = new CakeRequest(null, false);
		$this->Controller = new ScaffoldMockController($this->request);
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));

		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
	}

/**
 * teardown method
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->Controller, $this->request);
	}

/**
 * testGetViewFilename method
 *
 * @access public
 * @return void
 */
	function testGetViewFilename() {
		$_admin = Configure::read('Routing.prefixes');
		Configure::write('Routing.prefixes', array('admin'));

		$this->Controller->request->params['action'] = 'index';
		$ScaffoldView = new TestScaffoldView($this->Controller);
		$result = $ScaffoldView->testGetFilename('index');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'index.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('edit');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'form.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('add');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'form.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('view');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'view.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('admin_index');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'index.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('admin_view');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'view.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('admin_edit');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'form.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('admin_add');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'scaffolds' . DS . 'form.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('error');
		$expected = CAKE . 'libs' . DS . 'view' . DS . 'errors' . DS . 'scaffold_error.ctp';
		$this->assertEqual($result, $expected);

		$Controller = new ScaffoldMockController($this->request);
		$Controller->scaffold = 'admin';
		$Controller->viewPath = 'posts';
		$Controller->request['action'] = 'admin_edit';

		$ScaffoldView = new TestScaffoldView($Controller);
		$result = $ScaffoldView->testGetFilename('admin_edit');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' .DS . 'views' . DS . 'posts' . DS . 'scaffold.form.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('edit');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' .DS . 'views' . DS . 'posts' . DS . 'scaffold.form.ctp';
		$this->assertEqual($result, $expected);

		$Controller = new ScaffoldMockController($this->request);
		$Controller->scaffold = 'admin';
		$Controller->viewPath = 'tests';
		$Controller->request->addParams(array(
			'plugin' => 'test_plugin',
			'action' => 'admin_add',
			'admin' => true
		));
		$Controller->plugin = 'test_plugin';

		$ScaffoldView = new TestScaffoldView($Controller);
		$result = $ScaffoldView->testGetFilename('admin_add');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins'
			. DS .'test_plugin' . DS . 'views' . DS . 'tests' . DS . 'scaffold.form.ctp';
		$this->assertEqual($result, $expected);

		$result = $ScaffoldView->testGetFilename('add');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins'
			. DS .'test_plugin' . DS . 'views' . DS . 'tests' . DS . 'scaffold.form.ctp';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.prefixes', $_admin);
	}

/**
 * test getting the view file name for themed scaffolds.
 *
 * @return void
 */
	function testGetViewFileNameWithTheme() {
		$this->Controller->request['action'] = 'index';
		$this->Controller->viewPath = 'posts';
		$this->Controller->theme = 'test_theme';
		$ScaffoldView = new TestScaffoldView($this->Controller);

		$result = $ScaffoldView->testGetFilename('index');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS 
			. 'themed' . DS . 'test_theme' . DS . 'posts' . DS . 'scaffold.index.ctp';
		$this->assertEqual($result, $expected);
	}

/**
 * test default index scaffold generation
 *
 * @access public
 * @return void
 */
	function testIndexScaffold() {
		$params = array(
			'plugin' => null,
			'pass' => array(),
			'form' => array(),
			'named' => array(),
			'url' => array('url' =>'scaffold_mock'),
			'controller' => 'scaffold_mock',
			'action' => 'index',
		);
		$this->Controller->request->addParams($params);
		$this->Controller->request->webroot = '/';
		$this->Controller->request->base = '';
		$this->Controller->request->here = '/scaffold_mock/index';

		//set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->constructClasses();
		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();

		$this->assertPattern('#<h2>Scaffold Mock</h2>#', $result);
		$this->assertPattern('#<table cellpadding="0" cellspacing="0">#', $result);

		$this->assertPattern('#<a href="/scaffold_users/view/1">1</a>#', $result); //belongsTo links
		$this->assertPattern('#<li><a href="/scaffold_mock/add">New Scaffold Mock</a></li>#', $result);
		$this->assertPattern('#<li><a href="/scaffold_users">List Scaffold Users</a></li>#', $result);
		$this->assertPattern('#<li><a href="/scaffold_comments/add">New Comment</a></li>#', $result);
	}

/**
 * test default view scaffold generation
 *
 * @access public
 * @return void
 */
	function testViewScaffold() {
		$this->Controller->request->base = '';
		$this->Controller->request->here = '/scaffold_mock';
		$this->Controller->request->webroot = '/';
		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' => 'scaffold_mock/view/1'),
			'controller' => 'scaffold_mock',
			'action' => 'view',
		);
		$this->Controller->request->addParams($params);
	
		//set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);
		$this->Controller->constructClasses();

		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();

		$this->assertPattern('/<h2>View Scaffold Mock<\/h2>/', $result);
		$this->assertPattern('/<dl>/', $result);
		//TODO: add specific tests for fields.
		$this->assertPattern('/<a href="\/scaffold_users\/view\/1">1<\/a>/', $result); //belongsTo links
		$this->assertPattern('/<li><a href="\/scaffold_mock\/edit\/1">Edit Scaffold Mock<\/a>\s<\/li>/', $result);
		$this->assertPattern('/<li><a href="\/scaffold_mock\/delete\/1"[^>]*>Delete Scaffold Mock<\/a>\s*<\/li>/', $result);
		//check related table
		$this->assertPattern('/<div class="related">\s*<h3>Related Scaffold Comments<\/h3>\s*<table cellpadding="0" cellspacing="0">/', $result);
		$this->assertPattern('/<li><a href="\/scaffold_comments\/add">New Comment<\/a><\/li>/', $result);
		$this->assertNoPattern('/<th>JoinThing<\/th>/', $result);
	}

/**
 * test default view scaffold generation
 *
 * @access public
 * @return void
 */
	function testEditScaffold() {
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/scaffold_mock';

		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' =>'scaffold_mock'),
			'controller' => 'scaffold_mock',
			'action' => 'edit',
		);
		$this->Controller->request->addParams($params);

		//set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);
		$this->Controller->constructClasses();
		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();

		$this->assertContains('<form id="ScaffoldMockEditForm" method="post" action="/scaffold_mock/edit/1"', $result);
		$this->assertContains('<legend>Edit Scaffold Mock</legend>', $result);

		$this->assertContains('input type="hidden" name="data[ScaffoldMock][id]" value="1" id="ScaffoldMockId"', $result);
		$this->assertContains('select name="data[ScaffoldMock][user_id]" id="ScaffoldMockUserId"', $result);
		$this->assertContains('input name="data[ScaffoldMock][title]" maxlength="255" type="text" value="First Article" id="ScaffoldMockTitle"', $result);
		$this->assertContains('input name="data[ScaffoldMock][published]" maxlength="1" type="text" value="Y" id="ScaffoldMockPublished"', $result);
		$this->assertContains('textarea name="data[ScaffoldMock][body]" cols="30" rows="6" id="ScaffoldMockBody"', $result);
		$this->assertPattern('/<a href="\#" onclick="if[^>]*>Delete<\/a><\/li>/', $result);
	}

/**
 * Test Admin Index Scaffolding.
 *
 * @access public
 * @return void
 */
	function testAdminIndexScaffold() {
		$_backAdmin = Configure::read('Routing.prefixes');

		Configure::write('Routing.prefixes', array('admin'));
		$params = array(
			'plugin' => null,
			'pass' => array(),
			'form' => array(),
			'named' => array(),
			'prefix' => 'admin',
			'url' => array('url' =>'admin/scaffold_mock'),
			'controller' => 'scaffold_mock',
			'action' => 'admin_index',
			'admin' => 1,
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/admin/scaffold_mock';
		$this->Controller->request->addParams($params);

		//reset, and set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->scaffold = 'admin';
		$this->Controller->constructClasses();

		ob_start();
		$Scaffold = new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Scaffold Mock<\/h2>/', $result);
		$this->assertPattern('/<table cellpadding="0" cellspacing="0">/', $result);
		//TODO: add testing for table generation
		$this->assertPattern('/<li><a href="\/admin\/scaffold_mock\/add">New Scaffold Mock<\/a><\/li>/', $result);

		Configure::write('Routing.prefixes', $_backAdmin);
	}

/**
 * Test Admin Index Scaffolding.
 *
 * @access public
 * @return void
 */
	function testAdminEditScaffold() {
		Configure::write('Routing.prefixes', array('admin'));
		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'prefix' => 'admin',
			'url' => array('url' =>'admin/scaffold_mock/edit/1'),
			'controller' => 'scaffold_mock',
			'action' => 'admin_edit',
			'admin' => 1,
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/admin/scaffold_mock/edit/1';
		$this->Controller->request->addParams($params);

		//reset, and set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->scaffold = 'admin';
		$this->Controller->constructClasses();

		ob_start();
		$Scaffold = new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();

		$this->assertPattern('#admin/scaffold_mock/edit/1#', $result);
		$this->assertPattern('#Scaffold Mock#', $result);
	}

/**
 * Test Admin Index Scaffolding.
 *
 * @access public
 * @return void
 */
	function testMultiplePrefixScaffold() {
		$_backAdmin = Configure::read('Routing.prefixes');

		Configure::write('Routing.prefixes', array('admin', 'member'));
		$params = array(
			'plugin' => null,
			'pass' => array(),
			'form' => array(),
			'named' => array(),
			'prefix' => 'member',
			'url' => array('url' =>'member/scaffold_mock'),
			'controller' => 'scaffold_mock',
			'action' => 'member_index',
			'member' => 1,
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/member/scaffold_mock';
		$this->Controller->request->addParams($params);

		//reset, and set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->scaffold = 'member';
		$this->Controller->constructClasses();

		ob_start();
		$Scaffold = new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();

		$this->assertPattern('/<h2>Scaffold Mock<\/h2>/', $result);
		$this->assertPattern('/<table cellpadding="0" cellspacing="0">/', $result);
		//TODO: add testing for table generation
		$this->assertPattern('/<li><a href="\/member\/scaffold_mock\/add">New Scaffold Mock<\/a><\/li>/', $result);

		Configure::write('Routing.prefixes', $_backAdmin);
	}

}

/**
 * Scaffold Test class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ScaffoldTest extends CakeTestCase {

/**
 * Controller property
 *
 * @var SecurityTestController
 * @access public
 */
	public $Controller;

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.article', 'core.user', 'core.comment', 'core.join_thing', 'core.tag');
/**
 * setUp method
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$request = new CakeRequest(null, false);
		$this->Controller = new ScaffoldMockController($request);
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->Controller);
	}

/**
 * Test the correct Generation of Scaffold Params.
 * This ensures that the correct action and view will be generated
 *
 * @access public
 * @return void
 */
	function testScaffoldParams() {
		$params = array(
			'plugin' => null,
			'pass' => array(),
			'form' => array(),
			'named' => array(),
			'url' => array('url' =>'admin/scaffold_mock/edit'),
			'controller' => 'scaffold_mock',
			'action' => 'admin_edit',
			'admin' => true,
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/admin/scaffold_mock/edit';
		$this->Controller->request->addParams($params);

		//set router.
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->constructClasses();
		$Scaffold = new TestScaffoldMock($this->Controller, $this->Controller->request);
		$result = $Scaffold->getParams();
		$this->assertEqual($result['action'], 'admin_edit');
	}

/**
 * test that the proper names and variable values are set by Scaffold
 *
 * @return void
 */
	function testScaffoldVariableSetting() {
		$params = array(
			'plugin' => null,
			'pass' => array(),
			'form' => array(),
			'named' => array(),
			'url' => array('url' =>'admin/scaffold_mock/edit'),
			'controller' => 'scaffold_mock',
			'action' => 'admin_edit',
			'admin' => true,
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/admin/scaffold_mock/edit';
		$this->Controller->request->addParams($params);

		//set router.
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->constructClasses();
		$Scaffold = new TestScaffoldMock($this->Controller, $this->Controller->request);
		$result = $Scaffold->controller->viewVars;

		$this->assertEqual($result['title_for_layout'], 'Scaffold :: Admin Edit :: Scaffold Mock');
		$this->assertEqual($result['singularHumanName'], 'Scaffold Mock');
		$this->assertEqual($result['pluralHumanName'], 'Scaffold Mock');
		$this->assertEqual($result['modelClass'], 'ScaffoldMock');
		$this->assertEqual($result['primaryKey'], 'id');
		$this->assertEqual($result['displayField'], 'title');
		$this->assertEqual($result['singularVar'], 'scaffoldMock');
		$this->assertEqual($result['pluralVar'], 'scaffoldMock');
		$this->assertEqual($result['scaffoldFields'], array('id', 'user_id', 'title', 'body', 'published', 'created', 'updated'));
	}

/**
 * test that Scaffold overrides the view property even if its set to 'Theme'
 *
 * @return void
 */
	function testScaffoldChangingViewProperty() {
		$this->Controller->action = 'edit';
		$this->Controller->theme = 'test_theme';
		$this->Controller->view = 'Theme';
		$this->Controller->constructClasses();
		$Scaffold = new TestScaffoldMock($this->Controller, $this->Controller->request);

		$this->assertEqual($this->Controller->view, 'Scaffold');
	}

/**
 * test that scaffold outputs flash messages when sessions are unset.
 *
 * @return void
 */
	function testScaffoldFlashMessages() {
		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' =>'scaffold_mock'),
			'controller' => 'scaffold_mock',
			'action' => 'edit',
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/scaffold_mock/edit';
		$this->Controller->request->addParams($params);

		//set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);
		$this->Controller->request->data = array(
			'ScaffoldMock' => array(
				'id' => 1,
				'title' => 'New title',
				'body' => 'new body'
			)
		);
		$this->Controller->constructClasses();
		unset($this->Controller->Session);

		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();
		$this->assertPattern('/Scaffold Mock has been updated/', $result);
	}
/**
 * test that habtm relationship keys get added to scaffoldFields.
 *
 * @see http://code.cakephp.org/tickets/view/48
 * @return void
 */
	function testHabtmFieldAdditionWithScaffoldForm() {
		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' =>'scaffold_mock'),
			'controller' => 'scaffold_mock',
			'action' => 'edit',
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/scaffold_mock/edit';
		$this->Controller->request->addParams($params);

		//set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->constructClasses();
		ob_start();
		$Scaffold = new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();
		$this->assertPattern('/name="data\[ScaffoldTag\]\[ScaffoldTag\]"/', $result);

		$result = $Scaffold->controller->viewVars;
		$this->assertEqual($result['scaffoldFields'], array('id', 'user_id', 'title', 'body', 'published', 'created', 'updated', 'ScaffoldTag'));
	}
/**
 * test that the proper names and variable values are set by Scaffold
 *
 * @return void
 */
	function testEditScaffoldWithScaffoldFields() {
		$request = new CakeRequest(null, false);
		$this->Controller = new ScaffoldMockControllerWithFields($request);
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));

		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' =>'scaffold_mock/edit'),
			'controller' => 'scaffold_mock',
			'action' => 'edit',
		);
		$this->Controller->request->base = '';
		$this->Controller->request->webroot = '/';
		$this->Controller->request->here = '/scaffold_mock/edit';
		$this->Controller->request->addParams($params);

		//set router.
		Router::reload();
		Router::setRequestInfo($this->Controller->request);

		$this->Controller->constructClasses();
		ob_start();
		new Scaffold($this->Controller, $this->Controller->request);
		$result = ob_get_clean();

		$this->assertNoPattern('/textarea name="data\[ScaffoldMock\]\[body\]" cols="30" rows="6" id="ScaffoldMockBody"/', $result);
	}
}
