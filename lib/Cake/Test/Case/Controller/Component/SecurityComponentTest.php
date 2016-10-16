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
 * @return bool
 */
	public function validatePost(Controller $controller) {
		return $this->_validatePost($controller);
	}

/**
 * authRequired method
 *
 * @param Controller $controller
 * @return bool
 */
	public function authRequired(Controller $controller) {
		return $this->_authRequired($controller);
	}

/**
 * methodRequired method
 *
 * @param Controller $controller
 * @return bool
 */
	public function methodsRequired(Controller $controller) {
		return $this->_methodsRequired($controller);
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
 * @var bool
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

		$request = $this->getMock('CakeRequest', array('here'), array('posts/index', false));
		$request->addParams(array('controller' => 'posts', 'action' => 'index'));
		$request->expects($this->any())
			->method('here')
			->will($this->returnValue('/posts/index'));

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

	public function validatePost($expectedException = null, $expectedExceptionMessage = null) {
		try {
			return $this->Controller->Security->validatePost($this->Controller);
		} catch (SecurityException $ex) {
			$this->assertInstanceOf($expectedException, $ex);
			$this->assertEquals($expectedExceptionMessage, $ex->getMessage());

			return false;
		}
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
		$this->Controller->Security->unlockedActions = array('posted');
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);

		$this->Controller->Security->Session->write('_Token', array(
			'allowedControllers' => array('SecurityTest'),
			'allowedActions' => array('posted')
		));
		$this->Controller->request['controller'] = 'SecurityTest';
		$this->Controller->request['action'] = 'posted';

		$this->Controller->request->data = array(
			'username' => 'willy',
			'password' => 'somePass',
			'_Token' => ''
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
 * Test that validatePost fires on GET with request data.
 * This could happen when method overriding is used.
 *
 * @return void
 */
	public function testValidatePostOnGetWithData() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->Security->startup($this->Controller);

		$fields = 'an-invalid-token';
		$unlocked = '';
		$unlocked = '';
		$debug = urlencode(json_encode(array(
			'some-action',
			array(),
			array()
		)));

		$this->Controller->request->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('fields', 'unlocked', 'debug')
		);
		$this->assertFalse($this->Controller->failed, 'Should not be failed yet');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed, 'Should fail because of validatePost.');
	}

/**
 * Simple hash validation test
 *
 * @return void
 */
	public function testValidatePost() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '01c1f6dbba02ac6f21b229eab1cc666839b14303%3AModel.valid';
		$unlocked = '';
		$debug = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$this->assertTrue($this->validatePost($this->Controller));
	}

/**
 * Test that validatePost fails if you are missing the session information.
 *
 * @return void
 */
	public function testValidatePostNoSession() {
		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Session->delete('_Token');
		$unlocked = '';
		$debug = urlencode(json_encode(array(
			'/posts/index',
			array(),
			array()
		)));

		$key = $this->Controller->params['_Token']['key'];
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

		$this->Controller->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$this->assertFalse($this->validatePost('AuthSecurityException', 'Unexpected field \'Model.password\' in POST data, Unexpected field \'Model.username\' in POST data'));
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
		$result = $this->validatePost('AuthSecurityException', '\'_Token.fields\' was not found in request data.');
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
		$debug = urlencode(json_encode(array(
			'/posts/index',
			array('Model.password', 'Model.username', 'Model.valid'),
			array()
		)));

		// a corrupted serialized object, so we can see if it ever gets to deserialize
		$attack = 'O:3:"App":1:{s:5:"__map";a:1:{s:3:"foo";s:7:"Hacked!";s:1:"fail"}}';
		$fields .= urlencode(':' . str_rot13($attack));

		$this->Controller->request->data = array(
			'Model' => array('username' => 'mark', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$result = $this->validatePost('SecurityException', 'Bad Request');
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
		$fields = '38504e4a341d4e6eadb437217efd91270e558d55%3A';
		$unlocked = '';
		$debug = urlencode(json_encode(array(
			'some-action',
			array(),
			array()
		)));

		$this->Controller->request->data = array(
			'Model' => array('multi_field' => array('1', '3')),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$this->assertTrue($this->validatePost());
	}

/**
 * testValidatePostNoModel method
 *
 * @return void
 */
	public function testValidatePostNoModel() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = 'c5bc49a6c938c820e7e538df3d8ab7bffbc97ef9%3A';
		$unlocked = '';
		$debug = 'not used';

		$this->Controller->request->data = array(
			'anything' => 'some_data',
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
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
		$fields = '5415d31b4483c1e09ddb58d2a91ba9650b12aa83%3A';
		$unlocked = '';
		$debug = 'not used';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => ''),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
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
		$fields = 'b72a99e923687687bb5e64025d3cc65e1cecced4%3AAddresses.0.id%7CAddresses.1.id';
		$unlocked = '';
		$debug = 'not used';

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
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$result = $this->validatePost();
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
		$fields = '8a764bdb989132c1d46f9a45f64ce2da5f9eebb9%3A';
		$unlocked = '';
		$debug = 'not used';

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2)),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug'),
		);
		$result = $this->validatePost();
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2, 3)),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug'),
		);
		$result = $this->validatePost();
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2, 3, 4)),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug'),
		);
		$result = $this->validatePost();
		$this->assertTrue($result);

		$fields = '722de3615e63fdff899e86e85e6498b11c50bb66%3A';
		$this->Controller->request->data = array(
			'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
			'Tag' => array('Tag' => array(1)),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug'),
		);
		$result = $this->validatePost();
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
		$fields = '01c1f6dbba02ac6f21b229eab1cc666839b14303%3AModel.valid';
		$unlocked = '';
		$debug = 'not used';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
		$this->assertTrue($result);

		$fields = 'efbcf463a2c31e97c85d95eedc41dff9e9c6a026%3A';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
		$this->assertTrue($result);

		$this->Controller->request->data = array();
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
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
		$fields = 'baaf832a714b39a0618238ac89c7065fc8ec853e%3AModel.hidden%7CModel.other_hidden';
		$unlocked = '';
		$debug = 'not used';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0',
				'other_hidden' => 'some hidden value'
			),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$result = $this->validatePost();
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
		$fields = 'aa7f254ebd8bf2ef118bc5ca1e191d1ae96857f5%3AModel.hidden';
		$unlocked = '';
		$debug = 'not used';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0'
			),
			'_Token' => compact('fields', 'key', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
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
		$fields = urlencode(Security::hash(
			'/posts/index' .
			serialize($fields) .
			$unlocked .
			Configure::read('Security.salt'))
		);
		$debug = 'not used';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'key', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
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

		$result = $this->validatePost('SecurityException', '\'_Token.unlocked\' was not found in request data.');
		$this->assertFalse($result);
	}

/**
 * test that missing 'debug' input causes failure
 *
 * @return void
 */
	public function testValidatePostFailNoDebug() {
		$this->Controller->Security->startup($this->Controller);
		$fields = array('Model.hidden', 'Model.password', 'Model.username');
		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'unlocked')
		);

		$result = $this->validatePost('SecurityException', '\'_Token.debug\' was not found in request data.');
		$this->assertFalse($result);
	}

/**
 * test that missing 'debug' input is not the problem when debug mode disabled
 *
 * @return void
 */
	public function testValidatePostFailNoDebugMode() {
		$this->Controller->Security->startup($this->Controller);
		$fields = array('Model.hidden', 'Model.password', 'Model.username');
		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'unlocked')
		);
		Configure::write('debug', false);
		$result = $this->validatePost('SecurityException', 'The request has been black-holed');
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
		$debug = urlencode(json_encode(array(
			'/posts/index',
			array('Model.hidden', 'Model.password'),
			array('Model.username')
		)));

		// Tamper the values.
		$unlocked = 'Model.username|Model.password';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'key', 'unlocked', 'debug')
		);

		$result = $this->validatePost('SecurityException', 'Missing field \'Model.password\' in POST data, Unexpected unlocked field \'Model.password\' in POST data');
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
		$fields = '38dd8a37bbb52e67ee4eb812bf1725a6a18b989b%3AModel.valid%7CModel2.valid%7CModel3.valid';
		$unlocked = '';
		$debug = 'not used';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'Model2' => array('valid' => '0'),
			'Model3' => array('valid' => '0'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$result = $this->validatePost();
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
		$fields = 'dcef68de6634c60d2e60484ad0e2faec003456e6%3AModel.0.hidden%7CModel.0.valid';
		$fields .= '%7CModel.1.hidden%7CModel.1.valid';
		$unlocked = '';
		$debug = 'not used';

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
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
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
		$fields = '8b6880fbbd4b69279155f899652ecffdd9b4c5a1%3AAddress.0.id%7CAddress.0.primary%7C';
		$fields .= 'Address.1.id%7CAddress.1.primary';
		$unlocked = '';
		$debug = 'not used';

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
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
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
		$fields = urlencode(
			Security::hash(
			'/posts/index' .
			serialize($hashFields) .
			$unlocked .
			Configure::read('Security.salt'), 'sha1')
		);
		$debug = 'not used';

		$this->Controller->request->data = array(
			'TaxonomyData' => array(
				1 => array(array(2)),
				2 => array(array(3))
			),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$result = $this->validatePost();
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
		$debug = urlencode(json_encode(array(
			'/posts/index',
			array(
				'Address.0.address',
				'Address.0.city',
				'Address.0.first_name',
				'Address.0.last_name',
				'Address.0.phone',
				'Address.0.title',
				'Address.1.address',
				'Address.1.city',
				'Address.1.first_name',
				'Address.1.last_name',
				'Address.1.phone',
				'Address.1.title',
				'Address.0.id' => '123',
				'Address.0.primary' => '5',
				'Address.1.id' => '124',
				'Address.1.primary' => '1'
			),
			array()
		)));

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
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost('SecurityException', 'Bad Request');
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
		$fields = '216ee717efd1a251a6d6e9efbb96005a9d09f1eb%3An%3A0%3A%7B%7D';
		$unlocked = '';
		$debug = urlencode(json_encode(array(
			'/posts/index',
			array(),
			array()
		)));

		$this->Controller->request->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$result = $this->validatePost('SecurityException', 'Unexpected field \'MyModel.name\' in POST data');
		$this->assertFalse($result);

		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Security->disabledFields = array('MyModel.name');
		$key = $this->Controller->request->params['_Token']['key'];

		$this->Controller->request->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost();
		$this->assertTrue($result);
	}

/**
 * testRadio method
 *
 * @return void
 */
	public function testValidatePostRadio() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '3be63770e7953c6d2119f5377a9303372040f66f%3An%3A0%3A%7B%7D';
		$unlocked = '';
		$debug = urlencode(json_encode(array(
			'/posts/index',
			array(),
			array()
		)));

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$result = $this->validatePost('SecurityException', 'Bad Request');
		$this->assertFalse($result);

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked', 'debug'),
			'Test' => array('test' => '')
		);
		$result = $this->validatePost();
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked', 'debug'),
			'Test' => array('test' => '1')
		);
		$result = $this->validatePost();
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'_Token' => compact('key', 'fields', 'unlocked', 'debug'),
			'Test' => array('test' => '2')
		);
		$result = $this->validatePost();
		$this->assertTrue($result);
	}

/**
 * test validatePost uses here() as a hash input.
 *
 * @return void
 */
	public function testValidatePostUrlAsHashInput() {
		$this->Controller->Security->startup($this->Controller);

		$key = $this->Controller->request->params['_Token']['key'];
		$fields = '5415d31b4483c1e09ddb58d2a91ba9650b12aa83%3A';
		$unlocked = '';
		$debug = urlencode(json_encode(array(
			'another-url',
			array('Model.username', 'Model.password'),
			array()
		)));

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => ''),
			'_Token' => compact('key', 'fields', 'unlocked', 'debug')
		);
		$this->assertTrue($this->validatePost());

		$request = $this->getMock('CakeRequest', array('here'), array('articles/edit/1', false));
		$request->expects($this->at(0))
			->method('here')
			->will($this->returnValue('/posts/index?page=1'));
		$request->expects($this->at(1))
			->method('here')
			->will($this->returnValue('/posts/edit/1'));

		$request->data = $this->Controller->request->data;
		$this->Controller->request = $request;
		$this->assertFalse($this->validatePost('SecurityException', 'URL mismatch in POST data (expected \'another-url\' but found \'/posts/index?page=1\')'));
		$this->assertFalse($this->validatePost('SecurityException', 'URL mismatch in POST data (expected \'another-url\' but found \'/posts/edit/1\')'));
	}

/**
 * test that a requestAction's controller will have the _Token appended to
 * the params.
 *
 * @return void
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
			$this->assertWithinMargin($expires, $csrfExpires, 2, 'Token expiry does not match');
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
 * tests that reusable CSRF-token expiry is renewed
 */
	public function testCsrfReusableTokenRenewal() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfUseOnce = false;
		$csrfExpires = '+10 minutes';
		$this->Security->csrfExpires = $csrfExpires;

		$this->Security->Session->write('_Token.csrfTokens', array('token' => strtotime('+1 minutes')));

		$this->Security->startup($this->Controller);
		$tokens = $this->Security->Session->read('_Token.csrfTokens');
		$this->assertWithinMargin($tokens['token'], strtotime($csrfExpires), 2, 'Token expiry was not renewed');
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
 * test that blackhole throws an exception when the key is missing and balckHoleCallback is not set.
 *
 * @return void
 * @expectedException SecurityException
 * @expectedExceptionMessage Missing CSRF token
 */
	public function testCsrfExceptionOnMissingKey() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->blackHoleCallback = '';

		$this->Controller->request->params['action'] = 'index';
		$this->Controller->request->data = array(
			'Post' => array(
				'title' => 'Woot'
			)
		);
		$this->Security->startup($this->Controller);
	}

/**
 * test that when the keys are mismatched the request is blackHoled
 *
 * @return void
 */
	public function testCsrfBlackHoleOnKeyMismatch() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('+10 minutes')));

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
 * test that blackhole throws an exception when the keys are mismatched and balckHoleCallback is not set.
 *
 * @return void
 * @expectedException SecurityException
 * @expectedExceptionMessage CSRF token mismatch
 */
	public function testCsrfExceptionOnKeyMismatch() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';
		$this->Security->blackHoleCallback = '';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('+10 minutes')));

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
	}

/**
 * test that when the key is expried the request is blackHoled
 *
 * @return void
 */
	public function testCsrfBlackHoleOnExpiredKey() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('-5 minutes')));

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
 * test that blackhole throws an exception when the key is expired and balckHoleCallback is not set
 *
 * @return void
 * @expectedException SecurityException
 * @expectedExceptionMessage CSRF token expired
 */
	public function testCsrfExceptionOnExpiredKey() {
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = true;
		$this->Security->csrfExpires = '+10 minutes';
		$this->Security->blackHoleCallback = '';

		$this->Security->Session->write('_Token.csrfTokens', array('nonce1' => strtotime('-5 minutes')));

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

/**
 * Test that debug token format is right
 *
 * @return void
 */
	public function testValidatePostDebugFormat() {
		$this->Controller->Security->startup($this->Controller);
		$unlocked = 'Model.username';
		$fields = array('Model.hidden', 'Model.password');
		$fields = urlencode(Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt')));
		$debug = urlencode(json_encode(array(
			'/posts/index',
			array('Model.hidden', 'Model.password'),
			array('Model.username'),
			array('not expected')
		)));

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost('SecurityException', 'Invalid security debug token.');
		$this->assertFalse($result);

		$debug = urlencode(json_encode('not an array'));
		$result = $this->validatePost('SecurityException', 'Invalid security debug token.');
		$this->assertFalse($result);
	}

/**
 * test blackhole will now throw passed exception if debug enabled
 *
 * @expectedException SecurityException
 * @expectedExceptionMessage error description
 * @return void
 */
	public function testBlackholeThrowsException() {
		$this->Security->blackHoleCallback = '';
		$this->Security->blackHole($this->Controller, 'auth', new SecurityException('error description'));
	}

/**
 * test blackhole will throw BadRequest if debug disabled
 *
 * @return void
 */
	public function testBlackholeThrowsBadRequest() {
		$this->Security->blackHoleCallback = '';
		$message = '';

		Configure::write('debug', false);
		try {
			$this->Security->blackHole($this->Controller, 'auth', new SecurityException('error description'));
		} catch (SecurityException $ex) {
			$message = $ex->getMessage();
			$reason = $ex->getReason();
		}
		$this->assertEquals('The request has been black-holed', $message);
		$this->assertEquals('error description', $reason);
	}

/**
 * Test that validatePost fails with tampered fields and explanation
 *
 * @return void
 */
	public function testValidatePostFailTampering() {
		$this->Controller->Security->startup($this->Controller);
		$unlocked = '';
		$fields = array('Model.hidden' => 'value', 'Model.id' => '1');
		$debug = urlencode(json_encode(array(
			'/posts/index',
			$fields,
			array()
		)));
		$fields = urlencode(Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt')));
		$fields .= urlencode(':Model.hidden|Model.id');
		$this->Controller->request->data = array(
			'Model' => array(
				'hidden' => 'tampered',
				'id' => '1',
			),
			'_Token' => compact('fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost('SecurityException', 'Tampered field \'Model.hidden\' in POST data (expected value \'value\' but found \'tampered\')');
		$this->assertFalse($result);
	}

/**
 * Test that validatePost fails with tampered fields and explanation
 *
 * @return void
 */
	public function testValidatePostFailTamperingMutatedIntoArray() {
		$this->Controller->Security->startup($this->Controller);
		$unlocked = '';
		$fields = array('Model.hidden' => 'value', 'Model.id' => '1');
		$debug = urlencode(json_encode(array(
			'/posts/index',
			$fields,
			array()
		)));
		$fields = urlencode(Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt')));
		$fields .= urlencode(':Model.hidden|Model.id');
		$this->Controller->request->data = array(
			'Model' => array(
				'hidden' => array('some-key' => 'some-value'),
				'id' => '1',
			),
			'_Token' => compact('fields', 'unlocked', 'debug')
		);

		$result = $this->validatePost('SecurityException', 'Unexpected field \'Model.hidden.some-key\' in POST data, Missing field \'Model.hidden\' in POST data');
		$this->assertFalse($result);
	}

/**
 * Test that debug token should not be sent if debug is disabled
 *
 * @return void
 */
	public function testValidatePostUnexpectedDebugToken() {
		$this->Controller->Security->startup($this->Controller);
		$unlocked = '';
		$fields = array('Model.hidden' => 'value', 'Model.id' => '1');
		$debug = urlencode(json_encode(array(
			'/posts/index',
			$fields,
			array()
		)));
		$fields = urlencode(Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt')));
		$fields .= urlencode(':Model.hidden|Model.id');
		$this->Controller->request->data = array(
			'Model' => array(
				'hidden' => array('some-key' => 'some-value'),
				'id' => '1',
			),
			'_Token' => compact('fields', 'unlocked', 'debug')
		);
		Configure::write('debug', false);
		$result = $this->validatePost('SecurityException', 'Unexpected \'_Token.debug\' found in request data');
		$this->assertFalse($result);
	}

/**
 * Auth required throws exception token not found
 *
 * @return void
 * @expectedException AuthSecurityException
 * @expectedExceptionMessage '_Token' was not found in request data.
 */
	public function testAuthRequiredThrowsExceptionTokenNotFoundPost() {
		$this->Controller->Security->requireAuth = array('protected');
		$this->Controller->request->params['action'] = 'protected';
		$this->Controller->request->data = 'notEmpty';
		$this->Controller->Security->authRequired($this->Controller);
	}

/**
 * Auth required throws exception token not found in Session
 *
 * @return void
 * @expectedException AuthSecurityException
 * @expectedExceptionMessage '_Token' was not found in session.
 */
	public function testAuthRequiredThrowsExceptionTokenNotFoundSession() {
		$this->Controller->Security->requireAuth = array('protected');
		$this->Controller->request->params['action'] = 'protected';
		$this->Controller->request->data = array('_Token' => 'not empty');
		$this->Controller->Security->authRequired($this->Controller);
	}

/**
 * Auth required throws exception controller not allowed
 *
 * @return void
 * @expectedException AuthSecurityException
 * @expectedExceptionMessage Controller 'NotAllowed' was not found in allowed controllers: 'Allowed, AnotherAllowed'.
 */
	public function testAuthRequiredThrowsExceptionControllerNotAllowed() {
		$this->Controller->Security->requireAuth = array('protected');
		$this->Controller->request->params['controller'] = 'NotAllowed';
		$this->Controller->request->params['action'] = 'protected';
		$this->Controller->request->data = array('_Token' => 'not empty');
		$this->Controller->Session->write('_Token', array(
			'allowedControllers' => array('Allowed', 'AnotherAllowed')
		));
		$this->Controller->Security->authRequired($this->Controller);
	}

/**
 * Auth required throws exception controller not allowed
 *
 * @return void
 * @expectedException AuthSecurityException
 * @expectedExceptionMessage Action 'NotAllowed::protected' was not found in allowed actions: 'index, view'.
 */
	public function testAuthRequiredThrowsExceptionActionNotAllowed() {
		$this->Controller->Security->requireAuth = array('protected');
		$this->Controller->request->params['controller'] = 'NotAllowed';
		$this->Controller->request->params['action'] = 'protected';
		$this->Controller->request->data = array('_Token' => 'not empty');
		$this->Controller->Session->write('_Token', array(
			'allowedActions' => array('index', 'view')
		));
		$this->Controller->Security->authRequired($this->Controller);
	}

/**
 * Auth required throws exception controller not allowed
 *
 * @return void
 */
	public function testAuthRequired() {
		$this->Controller->Security->requireAuth = array('protected');
		$this->Controller->request->params['controller'] = 'Allowed';
		$this->Controller->request->params['action'] = 'protected';
		$this->Controller->request->data = array('_Token' => 'not empty');
		$this->Controller->Session->write('_Token', array(
			'allowedActions' => array('protected'),
			'allowedControllers' => array('Allowed'),
		));
		$this->assertTrue($this->Controller->Security->authRequired($this->Controller));
	}

/**
 * Auth required throws exception controller not allowed
 *
 * @return void
 * @expectedException SecurityException
 * @expectedExceptionMessage The request method must be POST
 */
	public function testMethodsRequiredThrowsExceptionMethodNotAllowed() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->Security->requirePost = array('delete');
		$this->Controller->request->params['controller'] = 'Test';
		$this->Controller->request->params['action'] = 'delete';
		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Security->methodsRequired($this->Controller);
	}

/**
 * Auth required throws exception controller not allowed
 *
 * @return void
 */
	public function testMethodsRequired() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->Security->requirePost = array('delete');
		$this->Controller->request->params['controller'] = 'Test';
		$this->Controller->request->params['action'] = 'delete';
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->Security->methodsRequired($this->Controller));
	}

}
