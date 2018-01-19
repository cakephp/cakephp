<?php
/**
 * ScaffoldTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Router', 'Routing');
App::uses('CakeSession', 'Model/Datasource');
App::uses('Controller', 'Controller');
App::uses('Scaffold', 'Controller');
App::uses('ScaffoldView', 'View');
App::uses('AppModel', 'Model');

require_once dirname(dirname(__FILE__)) . DS . 'Model' . DS . 'models.php';

/**
 * ScaffoldMockController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMockController extends Controller {

/**
 * scaffold property
 *
 * @var mixed
 */
	public $scaffold;
}

/**
 * ScaffoldMockControllerWithFields class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMockControllerWithFields extends Controller {

/**
 * name property
 *
 * @var string
 */
	public $name = 'ScaffoldMock';

/**
 * scaffold property
 *
 * @var mixed
 */
	public $scaffold;

/**
 * function beforeScaffold
 *
 * @param string $method Method name.
 * @return bool true
 */
	public function beforeScaffold($method) {
		$this->set('scaffoldFields', array('title'));
		return true;
	}

}

/**
 * ScaffoldMockControllerWithError class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldMockControllerWithError extends Controller {

/**
 * name property
 *
 * @var string
 */
	public $name = 'ScaffoldMock';

/**
 * scaffold property
 *
 * @var mixed
 */
	public $scaffold;

/**
 * function beforeScaffold
 *
 * @param string $method Method name.
 * @return bool false
 */
	public function beforeScaffold($method) {
		return false;
	}

}

/**
 * TestScaffoldMock class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestScaffoldMock extends Scaffold {

/**
 * Overload _scaffold
 *
 * @param CakeRequest $request Request object for scaffolding
 * @return void
 */
	protected function _scaffold(CakeRequest $request) {
		$this->_params = $request;
	}

/**
 * Get Params from the Controller.
 *
 * @return unknown
 */
	public function getParams() {
		return $this->_params;
	}

}

/**
 * Scaffold Test class
 *
 * @package       Cake.Test.Case.Controller
 */
class ScaffoldTest extends CakeTestCase {

/**
 * Controller property
 *
 * @var SecurityTestController
 */
	public $Controller;

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.article', 'core.user', 'core.comment', 'core.join_thing', 'core.tag');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Config.language', 'eng');
		$request = new CakeRequest(null, false);
		$this->Controller = new ScaffoldMockController($request);
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakeSession::destroy();
		unset($this->Controller);
	}

/**
 * Test the correct Generation of Scaffold Params.
 * This ensures that the correct action and view will be generated
 *
 * @return void
 */
	public function testScaffoldParams() {
		$params = array(
			'plugin' => null,
			'pass' => array(),
			'form' => array(),
			'named' => array(),
			'url' => array('url' => 'admin/scaffold_mock/edit'),
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
		$this->assertEquals('admin_edit', $result['action']);
	}

/**
 * test that the proper names and variable values are set by Scaffold
 *
 * @return void
 */
	public function testScaffoldVariableSetting() {
		$params = array(
			'plugin' => null,
			'pass' => array(),
			'form' => array(),
			'named' => array(),
			'url' => array('url' => 'admin/scaffold_mock/edit'),
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

		$this->assertEquals('Scaffold :: Admin Edit :: Scaffold Mock', $result['title_for_layout']);
		$this->assertEquals('Scaffold Mock', $result['singularHumanName']);
		$this->assertEquals('Scaffold Mock', $result['pluralHumanName']);
		$this->assertEquals('ScaffoldMock', $result['modelClass']);
		$this->assertEquals('id', $result['primaryKey']);
		$this->assertEquals('title', $result['displayField']);
		$this->assertEquals('scaffoldMock', $result['singularVar']);
		$this->assertEquals('scaffoldMock', $result['pluralVar']);
		$this->assertEquals(array('id', 'user_id', 'title', 'body', 'published', 'created', 'updated'), $result['scaffoldFields']);
		$this->assertArrayHasKey('plugin', $result['associations']['belongsTo']['User']);
	}

/**
 * test that Scaffold overrides the view property even if its set to 'Theme'
 *
 * @return void
 */
	public function testScaffoldChangingViewProperty() {
		$this->Controller->action = 'edit';
		$this->Controller->theme = 'TestTheme';
		$this->Controller->viewClass = 'Theme';
		$this->Controller->constructClasses();
		new TestScaffoldMock($this->Controller, $this->Controller->request);

		$this->assertEquals('Scaffold', $this->Controller->viewClass);
	}

/**
 * test that scaffold outputs flash messages when sessions are unset.
 *
 * @return void
 */
	public function testScaffoldFlashMessages() {
		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' => 'scaffold_mock'),
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
		$this->Controller->response->send();
		$result = ob_get_clean();
		$this->assertRegExp('/Scaffold Mock has been updated/', $result);
	}

/**
 * test that habtm relationship keys get added to scaffoldFields.
 *
 * @return void
 */
	public function testHabtmFieldAdditionWithScaffoldForm() {
		CakePlugin::unload();
		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' => 'scaffold_mock'),
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
		$this->Controller->response->send();
		$result = ob_get_clean();
		$this->assertRegExp('/name="data\[ScaffoldTag\]\[ScaffoldTag\]"/', $result);

		$result = $Scaffold->controller->viewVars;
		$this->assertEquals(array('id', 'user_id', 'title', 'body', 'published', 'created', 'updated', 'ScaffoldTag'), $result['scaffoldFields']);
	}

/**
 * test that the proper names and variable values are set by Scaffold
 *
 * @return void
 */
	public function testEditScaffoldWithScaffoldFields() {
		$request = new CakeRequest(null, false);
		$this->Controller = new ScaffoldMockControllerWithFields($request);
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));

		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' => 'scaffold_mock/edit'),
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
		$this->Controller->response->send();
		$result = ob_get_clean();

		$this->assertNotRegExp('/textarea name="data\[ScaffoldMock\]\[body\]" cols="30" rows="6" id="ScaffoldMockBody"/', $result);
	}

/**
 * test in case of scaffold error
 *
 * @return void
 */
	public function testScaffoldError() {
		$request = new CakeRequest(null, false);
		$this->Controller = new ScaffoldMockControllerWithError($request);
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));

		$params = array(
			'plugin' => null,
			'pass' => array(1),
			'form' => array(),
			'named' => array(),
			'url' => array('url' => 'scaffold_mock/edit'),
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
		$this->Controller->response->send();
		$result = ob_get_clean();

		$this->assertRegExp('/Scaffold Error/', $result);
	}
}
