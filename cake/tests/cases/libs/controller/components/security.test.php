<?php
/* SVN FILE: $Id$ */
/**
 * SecurityComponentTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5435
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Component', 'Security');
/**
* TestSecurityComponent
*
* @package       cake
* @subpackage    cake.tests.cases.libs.controller.components
*/
class TestSecurityComponent extends SecurityComponent {
/**
 * validatePost method
 *
 * @param Controller $controller
 * @return unknown
 */
	function validatePost(&$controller) {
		return $this->_validatePost($controller);
	}
}
/**
* SecurityTestController
*
* @package       cake
* @subpackage    cake.tests.cases.libs.controller.components
*/
class SecurityTestController extends Controller {
/**
 * name property
 *
 * @var string 'SecurityTest'
 * @access public
 */
	var $name = 'SecurityTest';
/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('TestSecurity');
/**
 * failed property
 *
 * @var bool false
 * @access public
 */
	var $failed = false;
/**
 * Used for keeping track of headers in test
 *
 * @var array
 * @access public
 */
	var $testHeaders = array();
/**
 * fail method
 *
 * @access public
 * @return void
 */
	function fail() {
		$this->failed = true;
	}
/**
 * redirect method
 *
 * @param mixed $option
 * @param mixed $code
 * @param mixed $exit
 * @access public
 * @return void
 */
	function redirect($option, $code, $exit) {
		return $code;
	}
/**
 * Conveinence method for header()
 *
 * @param string $status
 * @return void
 * @access public
 */
	function header($status) {
		$this->testHeaders[] = $status;
	}
}
/**
 * SecurityComponentTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class SecurityComponentTest extends CakeTestCase {
/**
 * Controller property
 *
 * @var SecurityTestController
 * @access public
 */
	var $Controller;
/**
 * oldSalt property
 *
 * @var string
 * @access public
 */
	var $oldSalt;
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Controller =& new SecurityTestController();
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Security =& $this->Controller->TestSecurity;
		$this->Controller->Security->blackHoleCallback = 'fail';
		$this->oldSalt = Configure::read('Security.salt');
		Configure::write('Security.salt', 'foo!');
	}
/**
 * Tear-down method. Resets environment state.
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('Security.salt', $this->oldSalt);
		$this->Controller->Session->del('_Token');
		unset($this->Controller->Security);
		unset($this->Controller->Component);
		unset($this->Controller);
	}
/**
 * testStartup method
 *
 * @access public
 * @return void
 */
	function testStartup() {
		$this->Controller->Security->startup($this->Controller);
		$result = $this->Controller->params['_Token']['key'];
		$this->assertNotNull($result);
		$this->assertTrue($this->Controller->Session->check('_Token'));
	}
/**
 * testRequirePostFail method
 *
 * @access public
 * @return void
 */
	function testRequirePostFail() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->action = 'posted';
		$this->Controller->Security->requirePost('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}
/**
 * testRequirePostSucceed method
 *
 * @access public
 * @return void
 */
	function testRequirePostSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'posted';
		$this->Controller->Security->requirePost('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireSecureFail method
 *
 * @access public
 * @return void
 */
	function testRequireSecureFail() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireSecure('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}
/**
 * testRequireSecureSucceed method
 *
 * @access public
 * @return void
 */
	function testRequireSecureSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'Secure';
		$this->Controller->action = 'posted';
		$_SERVER['HTTPS'] = 'on';
		$this->Controller->Security->requireSecure('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireAuthFail method
 *
 * @access public
 * @return void
 */
	function testRequireAuthFail() {
		$_SERVER['REQUEST_METHOD'] = 'AUTH';
		$this->Controller->action = 'posted';
		$this->Controller->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);

		$this->Controller->Session->write('_Token', array('allowedControllers' => array()));
		$this->Controller->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);

		$this->Controller->Session->write('_Token', array(
			'allowedControllers' => array('SecurityTest'), 'allowedActions' => array('posted2')
		));
		$this->Controller->data = array('username' => 'willy', 'password' => 'somePass');
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}
/**
 * testRequireAuthSucceed method
 *
 * @access public
 * @return void
 */
	function testRequireAuthSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'AUTH';
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireAuth('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);

		$this->Controller->Security->Session->write('_Token', serialize(array(
			'allowedControllers' => array('SecurityTest'), 'allowedActions' => array('posted')
		)));
		$this->Controller->params['controller'] = 'SecurityTest';
		$this->Controller->params['action'] = 'posted';

		$this->Controller->data = array(
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
 * @access public
 * @return void
 */
	function testRequirePostSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->action = 'getted';
		$this->Controller->Security->requirePost('posted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireGetFail method
 *
 * @access public
 * @return void
 */
	function testRequireGetFail() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'getted';
		$this->Controller->Security->requireGet('getted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}
/**
 * testRequireGetSucceed method
 *
 * @access public
 * @return void
 */
	function testRequireGetSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->Controller->action = 'getted';
		$this->Controller->Security->requireGet('getted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireLogin method
 *
 * @access public
 * @return void
 */
	function testRequireLogin() {
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireLogin(
			'posted',
			array('type' => 'basic', 'users' => array('admin' => 'password'))
		);
		$_SERVER['PHP_AUTH_USER'] = 'admin';
		$_SERVER['PHP_AUTH_PW'] = 'password';
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);


		$this->Controller->action = 'posted';
		$this->Controller->Security->requireLogin(
			'posted',
			array('type' => 'basic', 'users' => array('admin' => 'password'))
		);
		$_SERVER['PHP_AUTH_USER'] = 'admin2';
		$_SERVER['PHP_AUTH_PW'] = 'password';
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);

		$this->Controller->action = 'posted';
		$this->Controller->Security->requireLogin(
			'posted',
			array('type' => 'basic', 'users' => array('admin' => 'password'))
		);
		$_SERVER['PHP_AUTH_USER'] = 'admin';
		$_SERVER['PHP_AUTH_PW'] = 'password2';
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}
/**
 * testDigestAuth method
 *
 * @access public
 * @return void
 */
	function testDigestAuth() {
		$skip = $this->skipIf((version_compare(PHP_VERSION, '5.1') == -1) XOR (!function_exists('apache_request_headers')),
			"%s Cannot run Digest Auth test for PHP versions < 5.1"
		);

		if ($skip) {
			return;
		}

		$this->Controller->action = 'posted';
		$_SERVER['PHP_AUTH_DIGEST'] = $digest = <<<DIGEST
		Digest username="Mufasa",
		realm="testrealm@host.com",
		nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",
		uri="/dir/index.html",
		qop=auth,
		nc=00000001,
		cnonce="0a4f113b",
		response="460d0d3c6867c2f1ab85b1ada1aece48",
		opaque="5ccc069c403ebaf9f0171e9517f40e41"
DIGEST;
		$this->Controller->Security->requireLogin('posted', array(
			'type' => 'digest', 'users' => array('Mufasa' => 'password'),
			'realm' => 'testrealm@host.com'
		));
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireGetSucceedWrongMethod method
 *
 * @access public
 * @return void
 */
	function testRequireGetSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireGet('getted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequirePutFail method
 *
 * @access public
 * @return void
 */
	function testRequirePutFail() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'putted';
		$this->Controller->Security->requirePut('putted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}
/**
 * testRequirePutSucceed method
 *
 * @access public
 * @return void
 */
	function testRequirePutSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$this->Controller->action = 'putted';
		$this->Controller->Security->requirePut('putted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequirePutSucceedWrongMethod method
 *
 * @access public
 * @return void
 */
	function testRequirePutSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'posted';
		$this->Controller->Security->requirePut('putted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireDeleteFail method
 *
 * @access public
 * @return void
 */
	function testRequireDeleteFail() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'deleted';
		$this->Controller->Security->requireDelete('deleted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertTrue($this->Controller->failed);
	}
/**
 * testRequireDeleteSucceed method
 *
 * @access public
 * @return void
 */
	function testRequireDeleteSucceed() {
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$this->Controller->action = 'deleted';
		$this->Controller->Security->requireDelete('deleted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireDeleteSucceedWrongMethod method
 *
 * @access public
 * @return void
 */
	function testRequireDeleteSucceedWrongMethod() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->Controller->action = 'posted';
		$this->Controller->Security->requireDelete('deleted');
		$this->Controller->Security->startup($this->Controller);
		$this->assertFalse($this->Controller->failed);
	}
/**
 * testRequireLoginSettings method
 *
 * @access public
 * @return void
 */
	function testRequireLoginSettings() {
		$this->Controller->Security->requireLogin(
			'add', 'edit',
			array('type' => 'basic', 'users' => array('admin' => 'password'))
		);
		$this->assertEqual($this->Controller->Security->requireLogin, array('add', 'edit'));
		$this->assertEqual($this->Controller->Security->loginUsers, array('admin' => 'password'));
	}
/**
 * testRequireLoginAllActions method
 *
 * @access public
 * @return void
 */
	function testRequireLoginAllActions() {
		$this->Controller->Security->requireLogin(
			array('type' => 'basic', 'users' => array('admin' => 'password'))
		);
		$this->assertEqual($this->Controller->Security->requireLogin, array('*'));
		$this->assertEqual($this->Controller->Security->loginUsers, array('admin' => 'password'));
	}
/**
 * Simple hash validation test
 *
 * @access public
 * @return void
 */
	function testValidatePost() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3An%3A1%3A%7Bv%3A0%3B';
		$fields .= 'f%3A11%3A%22Zbqry.inyvq%22%3B%7D';

		$this->Controller->data = array(
			'Model' => array('username' => 'nate', 'password' => 'foo', 'valid' => '0'),
			'_Token' => compact('key', 'fields')
		);
		$this->assertTrue($this->Controller->Security->validatePost($this->Controller));
	}
/**
 * Tests validation of checkbox arrays
 *
 * @access public
 * @return void
 */
	function testValidatePostArray() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = 'f7d573650a295b94e0938d32b323fde775e5f32b%3An%3A0%3A%7B%7D';

		$this->Controller->data = array(
			'Model' => array('multi_field' => array('1', '3')),
			'_Token' => compact('key', 'fields')
		);
		$this->assertTrue($this->Controller->Security->validatePost($this->Controller));
	}
/**
 * testValidatePostNoModel method
 *
 * @access public
 * @return void
 */
	function testValidatePostNoModel() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '540ac9c60d323c22bafe997b72c0790f39a8bdef%3An%3A0%3A%7B%7D';

		$this->Controller->data = array(
			'anything' => 'some_data',
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testValidatePostSimple method
 *
 * @access public
 * @return void
 */
	function testValidatePostSimple() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '69f493434187b867ea14b901fdf58b55d27c935d%3An%3A0%3A%7B%7D';

		$this->Controller->data = $data = array(
			'Model' => array('username' => '', 'password' => ''),
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * Tests hash validation for multiple records, including locked fields
 *
 * @access public
 * @return void
 */
	function testValidatePostComplex() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = 'c9118120e680a7201b543f562e5301006ccfcbe2%3An%3A2%3A%7Bv%3A0%3Bf%3A14%3A%';
		$fields .= '22Nqqerffrf.0.vq%22%3Bv%3A1%3Bf%3A14%3A%22Nqqerffrf.1.vq%22%3B%7D';

		$this->Controller->data = array(
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
			'_Token' => compact('key', 'fields')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * test ValidatePost with multiple select elements.
 *
 * @return void
 **/
	function testValidatePostMultipleSelect() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '422cde416475abc171568be690a98cad20e66079%3An%3A0%3A%7B%7D';

		$this->Controller->data = array(
			'Tag' => array('Tag' => array(1, 2)),
			'_Token' => compact('key', 'fields'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->data = array(
			'Tag' => array('Tag' => array(1, 2, 3)),
			'_Token' => compact('key', 'fields'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->data = array(
			'Tag' => array('Tag' => array(1, 2, 3, 4)),
			'_Token' => compact('key', 'fields'),
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$fields = '19464422eafe977ee729c59222af07f983010c5f%3An%3A0%3A%7B%7D';
		$this->Controller->data = array(
			'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
			'Tag' => array('Tag' => array(1)), '_Token' => compact('key', 'fields'),
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
 * @access public
 * @return void
 */
	function testValidatePostCheckbox() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3An%3A1%3A%7Bv%3A0%';
		$fields .= '3Bf%3A11%3A%22Zbqry.inyvq%22%3B%7D';

		$this->Controller->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$fields = '874439ca69f89b4c4a5f50fb9c36ff56a28f5d42%3An%3A0%3A%7B%7D';

		$this->Controller->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);


		$this->Controller->data = array();
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];

		$this->Controller->data = $data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testValidatePostHidden method
 *
 * @access public
 * @return void
 */
	function testValidatePostHidden() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '51ccd8cb0997c7b3d4523ecde5a109318405ef8c%3An%3A2%3A%7Bv%3A0%3Bf%3A12%3A';
		$fields .= '%22Zbqry.uvqqra%22%3Bv%3A1%3Bf%3A18%3A%22Zbqry.bgure_uvqqra%22%3B%7D';

		$this->Controller->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0',
				'other_hidden' => 'some hidden value'
			),
			'_Token' => compact('key', 'fields')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testValidatePostWithDisabledFields method
 *
 * @access public
 * @return void
 */
	function testValidatePostWithDisabledFields() {
		$this->Controller->Security->disabledFields = array('Model.username', 'Model.password');
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = 'ef1082968c449397bcd849f963636864383278b1%3An%3A1%3A%7Bv%';
		$fields .= '3A0%3Bf%3A12%3A%22Zbqry.uvqqra%22%3B%7D';

		$this->Controller->data = array(
			'Model' => array(
				'username' => '', 'password' => '', 'hidden' => '0'
			),
			'_Token' => compact('fields', 'key')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testValidateHiddenMultipleModel method
 *
 * @access public
 * @return void
 */
	function testValidateHiddenMultipleModel() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = 'a2d01072dc4660eea9d15007025f35a7a5b58e18%3An%3A3%3A%7Bv%3A0%3Bf%3A11';
		$fields .= '%3A%22Zbqry.inyvq%22%3Bv%3A1%3Bf%3A12%3A%22Zbqry2.inyvq%22%3Bv%3A2%';
		$fields .= '3Bf%3A12%3A%22Zbqry3.inyvq%22%3B%7D';

		$this->Controller->data = array(
			'Model' => array('username' => '', 'password' => '', 'valid' => '0'),
			'Model2' => array('valid' => '0'),
			'Model3' => array('valid' => '0'),
			'_Token' => compact('key', 'fields')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testLoginValidation method
 *
 * @access public
 * @return void
 */
	function testLoginValidation() {

	}
/**
 * testValidateHasManyModel method
 *
 * @access public
 * @return void
 */
	function testValidateHasManyModel() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '51e3b55a6edd82020b3f29c9ae200e14bbeb7ee5%3An%3A4%3A%7Bv%3A0%3Bf%3A14%3A%2';
		$fields .= '2Zbqry.0.uvqqra%22%3Bv%3A1%3Bf%3A13%3A%22Zbqry.0.inyvq%22%3Bv%3A2%3Bf%3';
		$fields .= 'A14%3A%22Zbqry.1.uvqqra%22%3Bv%3A3%3Bf%3A13%3A%22Zbqry.1.inyvq%22%3B%7D';

		$this->Controller->data = array(
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
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testValidateHasManyRecordsPass method
 *
 * @access public
 * @return void
 */
	function testValidateHasManyRecordsPass() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '7a203edb3d345bbf38fe0dccae960da8842e11d7%3An%3A4%3A%7Bv%3A0%3Bf%3A12%3A%2';
		$fields .= '2Nqqerff.0.vq%22%3Bv%3A1%3Bf%3A17%3A%22Nqqerff.0.cevznel%22%3Bv%3A2%3Bf%';
		$fields .= '3A12%3A%22Nqqerff.1.vq%22%3Bv%3A3%3Bf%3A17%3A%22Nqqerff.1.cevznel%22%3B%7D';

		$this->Controller->data = array(
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
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testValidateHasManyRecords method
 *
 * validatePost should fail, hidden fields have been changed.
 *
 * @access public
 * @return void
 */
	function testValidateHasManyRecordsFail() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '7a203edb3d345bbf38fe0dccae960da8842e11d7%3An%3A4%3A%7Bv%3A0%3Bf%3A12%3A%2';
		$fields .= '2Nqqerff.0.vq%22%3Bv%3A1%3Bf%3A17%3A%22Nqqerff.0.cevznel%22%3Bv%3A2%3Bf%';
		$fields .= '3A12%3A%22Nqqerff.1.vq%22%3Bv%3A3%3Bf%3A17%3A%22Nqqerff.1.cevznel%22%3B%7D';

		$this->Controller->data = array(
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
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);
	}
/**
 * testLoginRequest method
 *
 * @access public
 * @return void
 */
	function testLoginRequest() {
		$this->Controller->Security->startup($this->Controller);
		$realm = 'cakephp.org';
		$options = array('realm' => $realm, 'type' => 'basic');
		$result = $this->Controller->Security->loginRequest($options);
		$expected = 'WWW-Authenticate: Basic realm="'.$realm.'"';
		$this->assertEqual($result, $expected);

		$this->Controller->Security->startup($this->Controller);
		$options = array('realm' => $realm, 'type' => 'digest');
		$result = $this->Controller->Security->loginRequest($options);
		$this->assertPattern('/realm="'.$realm.'"/', $result);
		$this->assertPattern('/qop="auth"/', $result);
	}
/**
 * testGenerateDigestResponseHash method
 *
 * @access public
 * @return void
 */
	function testGenerateDigestResponseHash() {
		$this->Controller->Security->startup($this->Controller);
		$realm = 'cakephp.org';
		$loginData = array('realm' => $realm, 'users' => array('Willy Smith' => 'password'));
		$this->Controller->Security->requireLogin($loginData);

		$data = array(
			'username' => 'Willy Smith',
			'password' => 'password',
			'nonce' => String::uuid(),
			'nc' => 1,
			'cnonce' => 1,
			'realm' => $realm,
			'uri' => 'path_to_identifier',
			'qop' => 'testme'
		);
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$result = $this->Controller->Security->generateDigestResponseHash($data);
		$expected = md5(
			md5($data['username'] . ':' . $loginData['realm'] . ':' . $data['password']) . ':' .
			$data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' .
			md5(env('REQUEST_METHOD') . ':' . $data['uri'])
		);
		$this->assertIdentical($result, $expected);
	}
/**
 * testLoginCredentials method
 *
 * @access public
 * @return void
 */
	function testLoginCredentials() {
		$this->Controller->Security->startup($this->Controller);
		$_SERVER['PHP_AUTH_USER'] = $user = 'Willy Test';
		$_SERVER['PHP_AUTH_PW'] = $pw = 'some password for the nice test';

		$result = $this->Controller->Security->loginCredentials('basic');
		$expected = array('username' => $user, 'password' => $pw);
		$this->assertIdentical($result, $expected);

		if (version_compare(PHP_VERSION, '5.1') != -1) {
			$_SERVER['PHP_AUTH_DIGEST'] = $digest = <<<DIGEST
				Digest username="Mufasa",
				realm="testrealm@host.com",
				nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",
				uri="/dir/index.html",
				qop=auth,
				nc=00000001,
				cnonce="0a4f113b",
				response="6629fae49393a05397450978507c4ef1",
				opaque="5ccc069c403ebaf9f0171e9517f40e41"
DIGEST;
			$expected = array(
				'username' => 'Mufasa',
				'nonce' => 'dcd98b7102dd2f0e8b11d0f600bfb0c093',
				'uri' => '/dir/index.html',
				'qop' => 'auth',
				'nc' => '00000001',
				'cnonce' => '0a4f113b',
				'response' => '6629fae49393a05397450978507c4ef1',
				'opaque' => '5ccc069c403ebaf9f0171e9517f40e41'
			);
			$result = $this->Controller->Security->loginCredentials('digest');
			$this->assertIdentical($result, $expected);
		}
	}
/**
 * testParseDigestAuthData method
 *
 * @access public
 * @return void
 */
	function testParseDigestAuthData() {
		$this->Controller->Security->startup($this->Controller);
		$digest = <<<DIGEST
			Digest username="Mufasa",
			realm="testrealm@host.com",
			nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",
			uri="/dir/index.html",
			qop=auth,
			nc=00000001,
			cnonce="0a4f113b",
			response="6629fae49393a05397450978507c4ef1",
			opaque="5ccc069c403ebaf9f0171e9517f40e41"
DIGEST;
		$expected = array(
			'username' => 'Mufasa',
			'nonce' => 'dcd98b7102dd2f0e8b11d0f600bfb0c093',
			'uri' => '/dir/index.html',
			'qop' => 'auth',
			'nc' => '00000001',
			'cnonce' => '0a4f113b',
			'response' => '6629fae49393a05397450978507c4ef1',
			'opaque' => '5ccc069c403ebaf9f0171e9517f40e41'
		);
		$result = $this->Controller->Security->parseDigestAuthData($digest);
		$this->assertIdentical($result, $expected);

		$result = $this->Controller->Security->parseDigestAuthData('');
		$this->assertNull($result);
	}
/**
 * testFormDisabledFields method
 *
 * @access public
 * @return void
 */
	function testFormDisabledFields() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '11842060341b9d0fc3808b90ba29fdea7054d6ad%3An%3A0%3A%7B%7D';

		$this->Controller->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('key', 'fields')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);

		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Security->disabledFields = array('MyModel.name');
		$key = $this->Controller->params['_Token']['key'];

		$this->Controller->data = array(
			'MyModel' => array('name' => 'some data'),
			'_Token' => compact('key', 'fields')
		);

		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testRadio method
 *
 * @access public
 * @return void
 */
	function testRadio() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];
		$fields = '575ef54ca4fc8cab468d6d898e9acd3a9671c17e%3An%3A0%3A%7B%7D';

		$this->Controller->data = array(
			'_Token' => compact('key', 'fields')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertFalse($result);

		$this->Controller->data = array(
			'_Token' => compact('key', 'fields'),
			'Test' => array('test' => '')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->data = array(
			'_Token' => compact('key', 'fields'),
			'Test' => array('test' => '1')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);

		$this->Controller->data = array(
			'_Token' => compact('key', 'fields'),
			'Test' => array('test' => '2')
		);
		$result = $this->Controller->Security->validatePost($this->Controller);
		$this->assertTrue($result);
	}
/**
 * testInvalidAuthHeaders method
 *
 * @access public
 * @return void
 */
	function testInvalidAuthHeaders() {
		$this->Controller->Security->blackHoleCallback = null;
		$_SERVER['PHP_AUTH_USER'] = 'admin';
		$_SERVER['PHP_AUTH_PW'] = 'password';
		$realm = 'cakephp.org';
		$loginData = array('type' => 'basic', 'realm' => $realm);
		$this->Controller->Security->requireLogin($loginData);
		$this->Controller->Security->startup($this->Controller);

		$expected = 'WWW-Authenticate: Basic realm="'.$realm.'"';
		$this->assertEqual(count($this->Controller->testHeaders), 1);
		$this->assertEqual(current($this->Controller->testHeaders), $expected);
	}
}
?>