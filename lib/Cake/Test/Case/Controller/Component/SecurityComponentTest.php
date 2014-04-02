<?php
/**
 * SecurityComponentTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('SecurityComponent', 'Controller/Component');
App::uses('Controller', 'Controller');

/**
 * TestSecurityComponent
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class TestSecurityComponent extends SecurityComponent {

/**
 * validatePost method
 *
 * @param Controller $controller
 * @return boolean
 */
	public function validatePost(Controller $controller) {
		return $this->_validatePost($controller);
	}

}

/**
 * SecurityTestController
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class SecurityTestController extends Controller {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Session', 'TestSecurity');

/**
 * failed property
 *
 * @var boolean false
 */
	public $failed = false;

/**
 * Used for keeping track of headers in test
 *
 * @var array
 */
	public $testHeaders = array();

/**
 * fail method
 *
 * @return void
 */
	public function fail() {
		$this->failed = true;
	}

/**
 * redirect method
 *
 * @param string|array $url
 * @param mixed $code
 * @param mixed $exit
 * @return void
 */
	public function redirect($url, $status = null, $exit = true) {
		return $status;
	}

/**
 * Convenience method for header()
 *
 * @param string $status
 * @return void
 */
	public function header($status) {
		$this->testHeaders[] = $status;
	}

}

class BrokenCallbackController extends Controller {

	public $name = 'UncallableCallback';

	public $components = array('Session', 'TestSecurity');

	public function index() {
	}

	protected function _fail() {
	}

}

/**
 * SecurityComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class SecurityComponentTest extends CakeTestCase {

/**
 * Controller property
 *
 * @var SecurityTestController
 */
	public $Controller;

/**
 * oldSalt property
 *
 * @var string
 */
	public $oldSalt;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('controller' => 'posts', 'action' => 'index'));
		$this->Controller = new SecurityTestController($request);
		$this->Controller->Components->init($this->Controller);
		$this->Controller->Security = $this->Controller->TestSecurity;
		$this->Controller->Security->blackHoleCallback = 'fail';
		$this->Security = $this->Controller->Security;
		$this->Security->csrfCheck = false;

		Configure::write('Security.salt', 'foo!');
	}

/**
 * Tear-down method. Resets environment state.
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		$this->Controller->Session->delete('_Token');
		unset($this->Controller->Security);
		unset($this->Controller->Component);
		unset($this->Controller);
	}

/**
 * Test that requests are still blackholed when controller has incorrect
 * visibility keyword in the blackhole callback
 *
 * @expectedException BadRequestException
 * @return void
 */
	public function testBlackholeWithBrokenCallback() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array(
			'controller' => 'posts', 'action' => 'index')
		);
		$this->Controller = new BrokenCallbackController($request);
		$this->Controller->Components->init($this->Controller);
		$this->Controller->Security = $this->Controller->TestSecurity;
		$this->Controller->Security->blackHoleCallback = '_fail';
		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Security->blackHole($this->Controller, 'csrf');
	}

/**
 * Ensure that directly requesting the blackholeCallback as the controller
 * action results in an exception.
 *
 * @return void
 */
	public function testExceptionWhenActionIsBlackholeCallback() {
		$this->Controller->request->addParams(array(
			'controller' => 'posts',
			'action' => 'fail'
		));
		$this->assertFalse($this->Controller->failed);
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed, 'Request was blackholed.');
	}

/**
 * test that initialize can set properties.
 *
 * @return void
 */
	public function testConstructorSettingProperties() {
		$settings = array(
			'requirePost' => array('edit', 'update'),
			'requireSecure' => array('update_account'),
			'requireGet' => array('index'),
			'validatePost' => false,
		);
		$Security = new SecurityComponent($this->Controller->Components, $settings);
		$this->Controller->Security->initialize($this->Controller, $settings);
		$this->assertEquals($Security->requirePost, $settings['requirePost']);
		$this->assertEquals($Security->requireSecure, $settings['requireSecure']);
		$this->assertEquals($Security->requireGet, $settings['requireGet']);
		$this->assertEquals($Security->validatePost, $settings['validatePost']);
	}

/**
 * testStartup method
 *
 * @return void
 */
	public function testStartup() {
		$this->Controller->Security->startup($this->Controller);
		$result = $this->Controller->params['_Token']['key'];
		$this->assertNotNull($result);
		$this->assertTrue($this->Controller->Session->check('_Token'));
	}

/**
 * testRequirePostFail method
 *
 * @return void
 */
	public function testRequirePostFail() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requirePost(array('posted'));
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}

/**
 * testRequirePostSucceed method
 *
 * @return void
 */
	public function testRequirePostSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requirePost('posted');
		$this->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequireSecureFail method
 *
 * @return void
 */
	public function testRequireSecureFail() {
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireSecure(array('posted'));
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}

/**
 * testRequireSecureSucceed method
 *
 * @return void
 */
	public function testRequireSecureSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'Secure';
		$this->Controller->request['action'] = 'posted';
		$_SERVER['HTTPS'] = 'on';
		$this->Controller->Security->requireSecure('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequireAuthFail method
 *
 * @return void
 */
	public function testRequireAuthFail() {
		$_SERVER['REQUEST_METHOD'] = 'AUTH';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->request->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->Security->requireAuth(array('posted'));
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);

		$this->Controller->Session->write('_Token', array('allowedControllers' => array()));
		$this->Controller->request->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);

		$this->Controller->Session->write('_Token', array(
			'allowedControllers' => array('SecurityTest'), 'allowedActions' => array('posted2')
		));
		$this->Controller->request->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}

/**
 * testRequireAuthSucceed method
 *
 * @return void
 */
	public function testRequireAuthSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'AUTH';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);

		$this->Controller->Security->Session->write('_Token', array(
			'allowedControllers' => array('SecurityTest'), 'allowedActions' => array('posted')
		));
		$this->Controller->request['controller'] = 'SecurityTest';
		$this->Controller->request['action'] = 'posted';

		$this->Controller->request->data = array(
			'username' => 'willy', 'password' => 'somePass', '_Token' => ''
		);
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequirePostSucceedWrongMethod method
 *
 * @return void
 */
	public function testRequirePostSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->request['action'] = 'getted';
		$this->Controller->Security->requirePost('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequireGetFail method
 *
 * @return void
 */
	public function testRequireGetFail() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'getted';
		$this->Controller->Security->requireGet(array('getted'));
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}

/**
 * testRequireGetSucceed method
 *
 * @return void
 */
	public function testRequireGetSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->request['action'] = 'getted';
		$this->Controller->Security->requireGet('getted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequireGetSucceedWrongMethod method
 *
 * @return void
 */
	public function testRequireGetSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'posted';
		$this->Security->requireGet('getted');
		$this->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequirePutFail method
 *
 * @return void
 */
	public function testRequirePutFail() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'putted';
		$this->Controller->Security->requirePut(array('putted'));
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}

/**
 * testRequirePutSucceed method
 *
 * @return void
 */
	public function testRequirePutSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$this->Controller->request['action'] = 'putted';
		$this->Controller->Security->requirePut('putted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequirePutSucceedWrongMethod method
 *
 * @return void
 */
	public function testRequirePutSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requirePut('putted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequireDeleteFail method
 *
 * @return void
 */
	public function testRequireDeleteFail() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'deleted';
		$this->Controller->Security->requireDelete(array('deleted', 'other_method'));
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}

/**
 * testRequireDeleteSucceed method
 *
 * @return void
 */
	public function testRequireDeleteSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$this->Controller->request['action'] = 'deleted';
		$this->Controller->Security->requireDelete('deleted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequireDeleteSucceedWrongMethod method
 *
 * @return void
 */
	public function testRequireDeleteSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireDelete('deleted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * Simple hash validation test
 *
 * @return void
 */
	public function testValidatePost() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$this->assertTrue($this->Controller->Security->validatePost($this->Controller));
	}

/**
 * Test that validatePost fails if you are missing the session information.
 *
 * @return void
 */
	public function testValidatePostNoSession() {
		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Session->delete('_Token');

		$key = $this->Controller->params['_Token']['key'];
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

		$this->Controller->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'fields')
		);
		$this->assertFalse($this->Controller->Security->validatePost($this->Controller));
	}

/**
 * test that validatePost fails if any of its required fields are missing.
 *
 * @return void
 */
	public function testValidatePostFormHacking() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result, 'validatePost passed when fields were missing. %s');
	}

/**
 * Test that objects can't be passed into the serialized string. This was a vector for RFI and LFI
 * attacks. Thanks to Felix Wilhelm
 *
 * @return void
 */
	public function testValidatePostObjectDeserialize() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877';
		$unlocked = '';

		// a corrupted serialized object, so we can see if it ever gets to deserialize
		$attack = 'O:3:"App":1:{s:5:"__map";a:1:{s:3:"foo";s:7:"Hacked!";s:1:"fail"}}';
		$fields .= urlencode(':' . str_rot13($attack));

		$this->Controller->request->data = array(
			'Model' => array('username' => 'mark', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result, 'validatePost passed when key was missing. %s');
	}

/**
 * Tests validation of checkbox arrays
 *
 * @return void
 */
	public function testValidatePostArray() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'f7d573650a295b94e0938d32b323fde775e5f32b%3A';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('multi_field' => array('1', '3')),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$this->assertTrue($this->Controller->Security->validatePost($this->Controller));
	}

/**
 * testValidatePostNoModel method
 *
 * @return void
 */
	public function testValidatePostNoModel() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '540ac9c60d323c22bafe997b72c0790f39a8bdef%3A';
		$unlocked = '';

		$this->Controller->request->data = array(
			'anything' => 'some_data',
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testValidatePostSimple method
 *
 * @return void
 */
	public function testValidatePostSimple() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '69f493434187b867ea14b901fdf58b55d27c935d%3A';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => ''),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * Tests hash validation for multiple records, including locked fields
 *
 * @return void
 */
	public function testValidatePostComplex() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'c9118120e680a7201b543f562e5301006ccfcbe2%3AAddresses.0.id%7CAddresses.1.id';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Addresses' => array(
				'0' => array(
					'id' => '123456', 'title' => '', 'first_name' => '', 'last_name' => '',
					'address' => '', 'city' => '', 'phone' => '', 'primary' => ''
				),
				'1' => array(
					'id' => '654321', 'title' => '', 'first_name' => '', 'last_name' => '',
					'address' => '', 'city' => '', 'phone' => '', 'primary' => ''
				)
			),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * test ValidatePost with multiple select elements.
 *
 * @return void
 */
	public function testValidatePostMultipleSelect() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '422cde416475abc171568be690a98cad20e66079%3A';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2)),
			'_Token' => compact('key', 'fields', 'unlocked'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2, 3)),
			'_Token' => compact('key', 'fields', 'unlocked'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2, 3, 4)),
			'_Token' => compact('key', 'fields', 'unlocked'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$fields = '19464422eafe977ee729c59222af07f983010c5f%3A';
		$this->Controller->request->data = array(
			'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
			'Tag' => array('Tag' => array(1)),
			'_Token' => compact('key', 'fields', 'unlocked'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testValidatePostCheckbox method
 *
 * First block tests un-checked checkbox
 * Second block tests checked checkbox
 *
 * @return void
 */
	public function testValidatePostCheckbox() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$fields = '874439ca69f89b4c4a5f50fb9c36ff56a28f5d42%3A';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array();
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testValidatePostHidden method
 *
 * @return void
 */
	public function testValidatePostHidden() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '51ccd8cb0997c7b3d4523ecde5a109318405ef8c%3AModel.hidden%7CModel.other_hidden';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0',
				'other_hidden' => 'some hidden value'
			),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testValidatePostWithDisabledFields method
 *
 * @return void
 */
	public function testValidatePostWithDisabledFields() {
		$this->Controller->Security->disabledFields = array('Model.username', 'Model.password');
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'ef1082968c449397bcd849f963636864383278b1%3AModel.hidden';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0'
			),
			'_Token' => compact('fields', 'key', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * test validating post data with posted unlocked fields.
 *
 * @return void
 */
	public function testValidatePostDisabledFieldsInData() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$unlocked = 'Model.username';
		$fields = array('Model.hidden', 'Model.password');
		$fields = urlencode(Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt')));

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'key', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * test that missing 'unlocked' input causes failure
 *
 * @return void
 */
	public function testValidatePostFailNoDisabled() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = array('Model.hidden', 'Model.password', 'Model.username');
		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'key')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);
	}

/**
 * Test that validatePost fails when unlocked fields are changed.
 *
 * @return void
 */
	public function testValidatePostFailDisabledFieldTampering() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$unlocked = 'Model.username';
		$fields = array('Model.hidden', 'Model.password');
		$fields = urlencode(Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt')));

		// Tamper the values.
		$unlocked = 'Model.username|Model.password';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'key', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);
	}

/**
 * testValidateHiddenMultipleModel method
 *
 * @return void
 */
	public function testValidateHiddenMultipleModel() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'a2d01072dc4660eea9d15007025f35a7a5b58e18%3AModel.valid%7CModel2.valid%7CModel3.valid';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'Model2' => array('valid' => '0'),
			'Model3' => array('valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testValidateHasManyModel method
 *
 * @return void
 */
	public function testValidateHasManyModel() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '51e3b55a6edd82020b3f29c9ae200e14bbeb7ee5%3AModel.0.hidden%7CModel.0.valid';
		$fields .= '%7CModel.1.hidden%7CModel.1.valid';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array(
				array(
					'username' => 'username', 'password' => 'password',
					'hidden' => 'value', 'valid' => '0'
				),
				array(
					'username' => 'username', 'password' => 'password',
					'hidden' => 'value', 'valid' => '0'
				)
			),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testValidateHasManyRecordsPass method
 *
 * @return void
 */
	public function testValidateHasManyRecordsPass() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '7a203edb3d345bbf38fe0dccae960da8842e11d7%3AAddress.0.id%7CAddress.0.primary%7C';
		$fields .= 'Address.1.id%7CAddress.1.primary';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Address' => array(
				0 => array(
					'id' => '123',
					'title' => 'home',
					'first_name' => 'Bilbo',
					'last_name' => 'Baggins',
					'address' => '23 Bag end way',
					'city' => 'the shire',
					'phone' => 'N/A',
					'primary' => '1',
				),
				1 => array(
					'id' => '124',
					'title' => 'home',
					'first_name' => 'Frodo',
					'last_name' => 'Baggins',
					'address' => '50 Bag end way',
					'city' => 'the shire',
					'phone' => 'N/A',
					'primary' => '1'
				)
			),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * Test that values like Foo.0.1
 *
 * @return void
 */
	public function testValidateNestedNumericSets() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$unlocked = '';
		$hashFields = array('TaxonomyData');
		$fields = urlencode(Security::hash(serialize($hashFields) . $unlocked . Configure::read('Security.salt')));

		$this->Controller->request->data = array(
			'TaxonomyData' => array(
				1 => array(array(2)),
				2 => array(array(3))
			),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testValidateHasManyRecords method
 *
 * validatePost should fail, hidden fields have been changed.
 *
 * @return void
 */
	public function testValidateHasManyRecordsFail() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '7a203edb3d345bbf38fe0dccae960da8842e11d7%3AAddress.0.id%7CAddress.0.primary%7C';
		$fields .= 'Address.1.id%7CAddress.1.primary';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Address' => array(
				0 => array(
					'id' => '123',
					'title' => 'home',
					'first_name' => 'Bilbo',
					'last_name' => 'Baggins',
					'address' => '23 Bag end way',
					'city' => 'the shire',
					'phone' => 'N/A',
					'primary' => '5',
				),
				1 => array(
					'id' => '124',
					'title' => 'home',
					'first_name' => 'Frodo',
					'last_name' => 'Baggins',
					'address' => '50 Bag end way',
					'city' => 'the shire',
					'phone' => 'N/A',
					'primary' => '1'
				)
			),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);
	}

/**
 * testFormDisabledFields method
 *
 * @return void
 */
	public function testFormDisabledFields() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '11842060341b9d0fc3808b90ba29fdea7054d6ad%3An%3A0%3A%7B%7D';
		$unlocked = '';

		$this->Controller->request->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);

		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Security->disabledFields = array('MyModel.name');
		$key = $this->Controller->request->params['_Token']['key'];

		$this->Controller->request->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('key', 'fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * testRadio method
 *
 * @return void
 */
	public function testRadio() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '575ef54ca4fc8cab468d6d898e9acd3a9671c17e%3An%3A0%3A%7B%7D';
		$unlocked = '';

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked'),
			'Test' => array('test' => '')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked'),
			'Test' => array('test' => '1')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked'),
			'Test' => array('test' => '2')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * test that a requestAction's controller will have the _Token appended to
 * the params.
 *
 * @return void
 * @see https://cakephp.lighthouseapp.com/projects/42648/tickets/68
 */
	public function testSettingTokenForRequestAction() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];

		$this->Controller->params['requested'] = 1;
		unset($this->Controller->request->params['_Token']);

		$this->Controller->Security->startup($this->Controller);
		$this->assertEquals($this->Controller->request->params['_Token']['key'], $key);
	}

/**
 * test that blackhole doesn't delete the _Token session key so repeat data submissions
 * stay blackholed.
 *
 * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/214
 * @return void
 */
	public function testBlackHoleNotDeletingSessionInformation() {
		$this->Controller->Security->startup($this->Controller);

		$this->Controller->Security->blackHole($this->Controller, 'auth');
		$this->assertTrue($this->Controller->Security->Session->check('_Token'), '_Token was deleted by blackHole %s');
	}

/**
 * test that csrf checks are skipped for request action.
 *
 * @return void
 */
	public function testCsrfSkipRequestAction() {
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';
		$this->Controller->request->params['requested'] = 1;
		$this->Security->startup($this->Controller);

		$this->assertFalse($this->Controller->failed, 'fail() was called.');
	}

/**
 * test setting
 *
 * @return void
 */
	public function testCsrfSettings() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';
		$this->Security->startup($this->Controller);

		$token = $this->Security->Session->read('_Token');
		$this->assertEquals(1, count($token['csrfTokens']), 'Missing the csrf token.');
		$this->assertEquals(strtotime('+10 minutes'), current($token['csrfTokens']), 'Token expiry does not match');
		$this->assertEquals(array('key', 'unlockedFields'), array_keys($this->Controller->request->params['_Token']), 'Keys don not match');
	}

/**
 * Test setting multiple nonces, when startup() is called more than once, (ie more than one request.)
 *
 * @return void
 */
	public function testCsrfSettingMultipleNonces() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';
		$csrfExpires = strtotime('+10 minutes');
		$this->Security->startup($this->Controller);
		$this->Security->startup($this->Controller);

		$token = $this->Security->Session->read('_Token');
		$this->assertEquals(2, count($token['csrfTokens']), 'Missing the csrf token.');
		foreach ($token['csrfTokens'] as $expires) {
			$diff = $csrfExpires - $expires;
			$this->assertTrue($diff === 0 || $diff === 1, 'Token expiry does not match');
		}
	}

/**
 * test that nonces are consumed by form submits.
 *
 * @return void
 */
	public function testCsrfNonceConsumption() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('+10 minutes')));

		$this->Controller->request = $this->getMock('CakeRequest', array('is'));
		$this->Controller->request->expects($this->once())->method('is')
			->with(array('post', 'put'))
			->will($this->returnValue(true));

		$this->Controller->request->params['action'] = 'index';
		$this->Controller->request->data = array(
			'_Token' => array(
				'key' => 'nonce1'
			),
			'Post' => array(
				'title' => 'Woot'
			)
		);
		$this->Security->startup($this->Controller);
		$token = $this->Security->Session->read('_Token');
		$this->assertFalse(isset($token['csrfTokens']['nonce1']), 'Token was not consumed');
	}

/**
 * test that expired values in the csrfTokens are cleaned up.
 *
 * @return void
 */
	public function testCsrfNonceVacuum() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';

		$this->Security->Session->write('_Token.csrfTokens', array(
			'valid' => strtotime('+30 minutes'),
			'poof' => strtotime('-11 minutes'),
			'dust' => strtotime('-20 minutes')
		));
		$this->Security->startup($this->Controller);
		$tokens = $this->Security->Session->read('_Token.csrfTokens');
		$this->assertEquals(2, count($tokens), 'Too many tokens left behind');
		$this->assertNotEmpty('valid', $tokens, 'Valid token was removed.');
	}

/**
 * test that when the key is missing the request is blackHoled
 *
 * @return void
 */
	public function testCsrfBlackHoleOnKeyMismatch() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('+10 minutes')));

		$this->Controller->request = $this->getMock('CakeRequest', array('is'));
		$this->Controller->request->expects($this->once())->method('is')
			->with(array('post', 'put'))
			->will($this->returnValue(true));

		$this->Controller->request->params['action'] = 'index';
		$this->Controller->request->data = array(
			'_Token' => array(
				'key' => 'not the right value'
			),
			'Post' => array(
				'title' => 'Woot'
			)
		);
		$this->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed, 'fail() was not called.');
	}

/**
 * test that when the key is missing the request is blackHoled
 *
 * @return void
 */
	public function testCsrfBlackHoleOnExpiredKey() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('-5 minutes')));

		$this->Controller->request = $this->getMock('CakeRequest', array('is'));
		$this->Controller->request->expects($this->once())->method('is')
			->with(array('post', 'put'))
			->will($this->returnValue(true));

		$this->Controller->request->params['action'] = 'index';
		$this->Controller->request->data = array(
			'_Token' => array(
				'key' => 'nonce1'
			),
			'Post' => array(
				'title' => 'Woot'
			)
		);
		$this->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed, 'fail() was not called.');
	}

/**
 * test that csrfUseOnce = false works.
 *
 * @return void
 */
	public function testCsrfNotUseOnce() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfUseOnce = false;
		$this->Security->csrfExpires = '+10 minutes';

		// Generate one token
		$this->Security->startup($this->Controller);
		$token = $this->Security->Session->read('_Token.csrfTokens');
		$this->assertEquals(1, count($token), 'Should only be one token.');

		$this->Security->startup($this->Controller);
		$tokenTwo = $this->Security->Session->read('_Token.csrfTokens');
		$this->assertEquals(1, count($tokenTwo), 'Should only be one token.');
		$this->assertEquals($token, $tokenTwo, 'Tokens should not be different.');

		$key = $this->Controller->request->params['_Token']['key'];
		$this->assertEquals(array($key), array_keys($token), '_Token.key and csrfToken do not match request will blackhole.');
	}

/**
 * ensure that longer session tokens are not consumed
 *
 * @return void
 */
	public function testCsrfNotUseOnceValidationLeavingToken() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfUseOnce = false;
		$this->Security->csrfExpires = '+10 minutes';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('+10 minutes')));

		$this->Controller->request = $this->getMock('CakeRequest', array('is'));
		$this->Controller->request->expects($this->once())->method('is')
			->with(array('post', 'put'))
			->will($this->returnValue(true));

		$this->Controller->request->params['action'] = 'index';
		$this->Controller->request->data = array(
			'_Token' => array(
				'key' => 'nonce1'
			),
			'Post' => array(
				'title' => 'Woot'
			)
		);
		$this->Security->startup($this->Controller);
		$token = $this->Security->Session->read('_Token');
		$this->assertTrue(isset($token['csrfTokens']['nonce1']), 'Token was consumed');
	}

/**
 * Test generateToken()
 *
 * @return void
 */
	public function testGenerateToken() {
		$request = $this->Controller->request;
		$this->Security->generateToken($request);

		$this->assertNotEmpty($request->params['_Token']);
		$this->assertTrue(isset($request->params['_Token']['unlockedFields']));
		$this->assertTrue(isset($request->params['_Token']['key']));
	}

/**
 * Test the limiting of CSRF tokens.
 *
 * @return void
 */
	public function testCsrfLimit() {
		$this->Security->csrfLimit = 3;
		$time = strtotime('+10 minutes');
		$tokens = array(
			'1' => $time,
			'2' => $time,
			'3' => $time,
			'4' => $time,
			'5' => $time,
		);
		$this->Security->Session->write('_Token', array('csrfTokens' => $tokens));
		$this->Security->generateToken($this->Controller->request);
		$result = $this->Security->Session->read('_Token.csrfTokens');

		$this->assertFalse(isset($result['1']));
		$this->assertFalse(isset($result['2']));
		$this->assertFalse(isset($result['3']));
		$this->assertTrue(isset($result['4']));
		$this->assertTrue(isset($result['5']));
	}

/**
 * Test unlocked actions
 *
 * @return void
 */
	public function testUnlockedActions() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->request->data = array('data');
		$this->Controller->Security->unlockedActions = 'index';
		$this->Controller->Security->blackHoleCallback = null;
		$result = $this->Controller->Security->startup($this->Controller);
		$this->assertNull($result);
	}
}
