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
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\SecurityComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * TestSecurityComponent
 *
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
 */
class SecurityTestController extends Controller {

/**
 * components property
 *
 * @var array
 */
	public $components = array(
		'Session',
		'TestSecurity' => array('className' => 'Cake\Test\TestCase\Controller\Component\TestSecurityComponent')
	);

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

/**
 * SecurityComponentTest class
 *
 */
class SecurityComponentTest extends TestCase {

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

		$request = new Request('posts/index');
		$request->addParams(array('controller' => 'posts', 'action' => 'index'));
		$this->Controller = new SecurityTestController($request);
		$this->Controller->constructClasses();
		$this->Controller->Security = $this->Controller->TestSecurity;
		$this->Controller->Security->blackHoleCallback = 'fail';
		$this->Security = $this->Controller->Security;
		Configure::write('Session', [
			'defaults' => 'php'
		]);

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
 * @expectedException Cake\Error\BadRequestException
 */
	public function testBlackholeWithBrokenCallback() {
		$request = new Request('posts/index');
		$request->addParams([
			'controller' => 'posts',
			'action' => 'index'
		]);
		$Controller = new \TestApp\Controller\SomePagesController($request);
		$event = new Event('Controller.startup', $Controller, $this->Controller);
		$Security = new SecurityComponent($Controller->Components);
		$Security->blackHoleCallback = '_fail';
		$Security->startup($event);
		$Security->blackHole($Controller, 'csrf');
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->assertFalse($this->Controller->failed);
		$this->Controller->Security->startup($event);
		$this->assertTrue($this->Controller->failed, 'Request was blackholed.');
	}

/**
 * test that initialize can set properties.
 *
 * @return void
 */
	public function testConstructorSettingProperties() {
		$settings = array(
			'requireSecure' => array('update_account'),
			'validatePost' => false,
		);
		$Security = new SecurityComponent($this->Controller->Components, $settings);
		$this->assertEquals($Security->validatePost, $settings['validatePost']);
	}

/**
 * testStartup method
 *
 * @return void
 */
	public function testStartup() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$this->assertTrue($this->Controller->Session->check('_Token'));
	}

/**
 * testRequireSecureFail method
 *
 * @return void
 */
	public function testRequireSecureFail() {
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireSecure(array('posted'));
		$this->Controller->Security->startup($event);
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->requireSecure('posted');
		$this->Controller->Security->startup($event);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * testRequireAuthFail method
 *
 * @return void
 */
	public function testRequireAuthFail() {
		$event = new Event('Controller.startup', $this->Controller);
		$_SERVER['REQUEST_METHOD'] = 'AUTH';
		$this->Controller->request['action'] = 'posted';
		$this->Controller->request->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->Security->requireAuth(array('posted'));
		$this->Controller->Security->startup($event);
		$this->assertTrue($this->Controller->failed);

		$this->Controller->Session->write('_Token', array('allowedControllers' => array()));
		$this->Controller->request->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($event);
		$this->assertTrue($this->Controller->failed);

		$this->Controller->Session->write('_Token', array(
			'allowedControllers' => array('SecurityTest'), 'allowedActions' => array('posted2')
		));
		$this->Controller->request->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($event);
		$this->assertTrue($this->Controller->failed);
	}

/**
 * testRequireAuthSucceed method
 *
 * @return void
 */
	public function testRequireAuthSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'AUTH';
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->request['action'] = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($event);
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
		$this->Controller->Security->startup($event);
		$this->assertFalse($this->Controller->failed);
	}

/**
 * Simple hash validation test
 *
 * @return void
 */
	public function testValidatePost() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);

		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('fields', 'unlocked')
		);
		$this->assertTrue($this->Controller->Security->validatePost($this->Controller));
	}

/**
 * Test that validatePost fails if you are missing the session information.
 *
 * @return void
 */
	public function testValidatePostNoSession() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$this->Controller->Session->delete('_Token');

		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

		$this->Controller->request->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('fields')
		);
		$this->assertFalse($this->Controller->Security->validatePost($this->Controller));
	}

/**
 * test that validatePost fails if any of its required fields are missing.
 *
 * @return void
 */
	public function testValidatePostFormHacking() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877';
		$unlocked = '';

		// a corrupted serialized object, so we can see if it ever gets to deserialize
		$attack = 'O:3:"App":1:{s:5:"__map";a:1:{s:3:"foo";s:7:"Hacked!";s:1:"fail"}}';
		$fields .= urlencode(':' . str_rot13($attack));

		$this->Controller->request->data = array(
			'Model' => array('username' => 'mark', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);

		$fields = 'f7d573650a295b94e0938d32b323fde775e5f32b%3A';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('multi_field' => array('1', '3')),
			'_Token' => compact('fields', 'unlocked')
		);
		$this->assertTrue($this->Controller->Security->validatePost($this->Controller));
	}

/**
 * testValidatePostNoModel method
 *
 * @return void
 */
	public function testValidatePostNoModel() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);

		$fields = '540ac9c60d323c22bafe997b72c0790f39a8bdef%3A';
		$unlocked = '';

		$this->Controller->request->data = array(
			'anything' => 'some_data',
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);

		$fields = '69f493434187b867ea14b901fdf58b55d27c935d%3A';
		$unlocked = '';

		$this->Controller->request->data = $data = array(
			'Model' => array('username' => '', 'password' => ''),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);

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
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);

		$fields = '422cde416475abc171568be690a98cad20e66079%3A';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2)),
			'_Token' => compact('fields', 'unlocked'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2, 3)),
			'_Token' => compact('fields', 'unlocked'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'Tag' => array('Tag' => array(1, 2, 3, 4)),
			'_Token' => compact('fields', 'unlocked'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$fields = '19464422eafe977ee729c59222af07f983010c5f%3A';
		$this->Controller->request->data = array(
			'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
			'Tag' => array('Tag' => array(1)),
			'_Token' => compact('fields', 'unlocked'),
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$fields = '874439ca69f89b4c4a5f50fb9c36ff56a28f5d42%3A';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('fields', 'unlocked')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array();
		$this->Controller->Security->startup($event);

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$fields = '51ccd8cb0997c7b3d4523ecde5a109318405ef8c%3AModel.hidden%7CModel.other_hidden';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0',
				'other_hidden' => 'some hidden value'
			),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->disabledFields = array('Model.username', 'Model.password');
		$this->Controller->Security->startup($event);
		$fields = 'ef1082968c449397bcd849f963636864383278b1%3AModel.hidden';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0'
			),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$unlocked = 'Model.username';
		$fields = array('Model.hidden', 'Model.password');
		$fields = urlencode(Security::hash(serialize($fields) . $unlocked . Configure::read('Security.salt')));

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$fields = array('Model.hidden', 'Model.password', 'Model.username');
		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));

		$this->Controller->request->data = array(
			'Model' => array(
				'username' => 'mark',
				'password' => 'sekret',
				'hidden' => '0'
			),
			'_Token' => compact('fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);
	}

/**
 * Test that validatePost fails when unlocked fields are changed.
 *
 * @return
 */
	public function testValidatePostFailDisabledFieldTampering() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
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
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$fields = 'a2d01072dc4660eea9d15007025f35a7a5b58e18%3AModel.valid%7CModel2.valid%7CModel3.valid';
		$unlocked = '';

		$this->Controller->request->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'Model2' => array('valid' => '0'),
			'Model3' => array('valid' => '0'),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
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
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
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
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$unlocked = '';
		$hashFields = array('TaxonomyData');
		$fields = urlencode(Security::hash(serialize($hashFields) . $unlocked . Configure::read('Security.salt')));

		$this->Controller->request->data = array(
			'TaxonomyData' => array(
				1 => array(array(2)),
				2 => array(array(3))
			),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
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
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);

		$this->Controller->Security->startup($event);
		$fields = '11842060341b9d0fc3808b90ba29fdea7054d6ad%3An%3A0%3A%7B%7D';
		$unlocked = '';

		$this->Controller->request->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);

		$this->Controller->Security->startup($event);
		$this->Controller->Security->disabledFields = array('MyModel.name');

		$this->Controller->request->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('fields', 'unlocked')
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
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);
		$fields = '575ef54ca4fc8cab468d6d898e9acd3a9671c17e%3An%3A0%3A%7B%7D';
		$unlocked = '';

		$this->Controller->request->data = array(
			'_Token' => compact('fields', 'unlocked')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);

		$this->Controller->request->data = array(
			'_Token' => compact('fields', 'unlocked'),
			'Test' => array('test' => '')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'_Token' => compact('fields', 'unlocked'),
			'Test' => array('test' => '1')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request->data = array(
			'_Token' => compact('fields', 'unlocked'),
			'Test' => array('test' => '2')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}

/**
 * test that blackhole doesn't delete the _Token session key so repeat data submissions
 * stay blackholed.
 *
 * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/214
 * @return void
 */
	public function testBlackHoleNotDeletingSessionInformation() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Security->startup($event);

		$this->Controller->Security->blackHole($this->Controller, 'auth');
		$this->assertTrue($this->Controller->Security->Session->check('_Token'), '_Token was deleted by blackHole %s');
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
	}

/**
 * Test unlocked actions
 *
 * @return void
 */
	public function testUnlockedActions() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->request->data = array('data');
		$this->Controller->Security->unlockedActions = 'index';
		$this->Controller->Security->blackHoleCallback = null;
		$result = $this->Controller->Security->startup($event);
		$this->assertNull($result);
	}

}
