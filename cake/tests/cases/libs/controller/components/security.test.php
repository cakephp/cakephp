<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.5435
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'components' . DS .'security');

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
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
	var $components = array('Security');
/**
 * failed property
 * 
 * @var bool false
 * @access public
 */
	var $failed = false;
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
}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.controller.components
 */
class SecurityComponentTest extends CakeTestCase {
/**
 * setUp method
 * 
 * @access public
 * @return void
 */
	function setUp() {
		$this->Controller =& new SecurityTestController();
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Security->blackHoleCallback = 'fail';
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
		$_SERVER['HTTPS'] = true;
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

		$this->Controller->Session->write('_Token', array('allowedControllers' => array('SecurityTest'), 'allowedActions' => array('posted2')));
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

		$this->Controller->Security->Session->write('_Token', serialize(array('allowedControllers' => array('SecurityTest'), 'allowedActions' => array('posted'))));
		$this->Controller->params['controller'] = 'SecurityTest';
		$this->Controller->params['action'] = 'posted';

		$this->Controller->data = array('username' => 'willy', 'password' => 'somePass', '__Token' => '');
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
		$this->Controller->Security->requireLogin(
			'posted',
			array('type' => 'digest', 'users' => array('Mufasa' => 'password'), 'realm' => 'testrealm@host.com')
		);
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
 * testValidatePostNoModel method
 * 
 * @access public
 * @return void
 */
	function testValidatePostNoModel() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];

		$data['anything'] = 'some_data';
		$data['__Token']['key'] = $key;
		$fields = $this->__sortFields(array('anything', '__Token' => array('key' => $key)));

		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$data['__Token']['fields'] = $fields;
		$this->Controller->data = $data;
		$result = $this->Controller->Security->__validatePost($this->Controller);
		$this->assertTrue($result);
		$this->assertEqual($this->Controller->data, $data);
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

		$data['Model']['username'] = '';
		$data['Model']['password'] = '';
		$data['__Token']['key'] = $key;

		$fields = array('Model' => array('username','password'), '__Token' => array('key' => $key));
		$fields = $this->__sortFields($fields);

		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$data['__Token']['fields'] = $fields;
		$this->Controller->data = $data;
		$result = $this->Controller->Security->__validatePost($this->Controller);
		$this->assertTrue($result);
		$this->assertEqual($this->Controller->data, $data);
	}
/**
 * testValidatePostCheckbox method
 * 
 * @access public
 * @return void
 */
	function testValidatePostCheckbox() {
		$this->Controller->Security->startup($this->Controller);
		$key = $this->Controller->params['_Token']['key'];

		$data['Model']['username'] = '';
		$data['Model']['password'] = '';
		$data['_Model']['valid'] = '0';
		$data['__Token']['key'] = $key;

		$fields = array(
			'Model' => array('username', 'password', 'valid'),
			'_Model' => array('valid' => '0'),
			'__Token' => array('key' => $key)
		);
		$fields = $this->__sortFields($fields);

		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$data['__Token']['fields'] = $fields;

		$this->Controller->data = $data;
		$result = $this->Controller->Security->__validatePost($this->Controller);
		$this->assertTrue($result);

		unset($data['_Model']);
		$data['Model']['valid'] = '0';
		$this->assertEqual($this->Controller->data, $data);
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

		$data['Model']['username'] = '';
		$data['Model']['password'] = '';
		$data['_Model']['hidden'] = '0';
		$data['__Token']['key'] = $key;

		$fields = array(
			'Model' => array('username', 'password', 'hidden'),
			'_Model' => array('hidden' => '0'),
			'__Token' => array('key' => $key)
		);
		$fields = $this->__sortFields($fields);

		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$data['__Token']['fields'] = $fields;

		$this->Controller->data = $data;
		$result = $this->Controller->Security->__validatePost($this->Controller);
		$this->assertTrue($result);

		unset($data['_Model']);
		$data['Model']['hidden'] = '0';
		$this->assertTrue($this->Controller->data == $data);
	}
/**
 * testValidatePostWithDisabledFields method
 * 
 * @access public
 * @return void
 */
	function testValidatePostWithDisabledFields() {
		$this->Controller->Security->startup($this->Controller);
		$this->Controller->Security->disabledFields = array('Model.username', 'Model.password');
		$key = $this->Controller->params['_Token']['key'];

		$data['Model']['username'] = '';
		$data['Model']['password'] = '';
		$data['_Model']['hidden'] = '0';
		$data['__Token']['key'] = $key;

		$fields = array(
			'Model' => array('hidden'),
			'_Model' => array('hidden' => '0'),
			'__Token' => array('key' => $key)
		);
		$fields = $this->__sortFields($fields);

		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$data['__Token']['fields'] = $fields;

		$this->Controller->data = $data;
		$result = $this->Controller->Security->__validatePost($this->Controller);
		$this->assertTrue($result);

		unset($data['_Model']);
		$data['Model']['hidden'] = '0';
		$this->assertTrue($this->Controller->data == $data);
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

		$data['Model']['username'] = '';
		$data['Model']['password'] = '';
		$data['_Model']['valid'] = '0';
		$data['_Model2']['valid'] = '0';
		$data['_Model3']['valid'] = '0';
		$data['__Token']['key'] = $key;

		$fields = array(
			'Model' => array('username', 'password', 'valid'),
			'Model2'=> array('valid'),
			'Model3'=> array('valid'),
			'_Model2'=> array('valid' => '0'),
			'_Model3'=> array('valid' => '0'),
			'_Model' => array('valid' => '0'),
			'__Token' => array('key' => $key)
		);

		$fields = urlencode(Security::hash(serialize($this->__sortFields($fields)) . Configure::read('Security.salt')));
		$data['__Token']['fields'] = $fields;

		$this->Controller->data = $data;
		$result = $this->Controller->Security->__validatePost($this->Controller);
		$this->assertTrue($result);

		unset($data['_Model'], $data['_Model2'], $data['_Model3']);
		$data['Model']['valid'] = '0';
		$data['Model2']['valid'] = '0';
		$data['Model3']['valid'] = '0';
		$this->assertTrue($this->Controller->data == $data);
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

		$data['Model'][0]['username'] = 'username';
		$data['Model'][0]['password'] = 'password';
		$data['Model'][1]['username'] = 'username';
		$data['Model'][1]['password'] = 'password';
		$data['_Model'][0]['hidden'] = 'value';
		$data['_Model'][1]['hidden'] = 'value';
		$data['_Model'][0]['valid'] = '0';
		$data['_Model'][1]['valid'] = '0';
		$data['__Token']['key'] = $key;

		$fields = array(
			'Model' => array(
				0 => array('username', 'password', 'valid'),
				1 => array('username', 'password', 'valid')),
			'_Model' => array(
				0 => array('hidden' => 'value', 'valid' => '0'),
				1 => array('hidden' => 'value', 'valid' => '0')),
			'__Token' => array('key' => $key));

		$fields = $this->__sortFields($fields);

		$fields = urlencode(Security::hash(serialize($fields) . Configure::read('Security.salt')));
		$data['__Token']['fields'] = $fields;

		$this->Controller->data = $data;
		$result = $this->Controller->Security->__validatePost($this->Controller);
		$this->assertTrue($result);

		unset($data['_Model']);
		$data['Model'][0]['hidden'] = 'value';
		$data['Model'][1]['hidden'] = 'value';
		$data['Model'][0]['valid'] = '0';
		$data['Model'][1]['valid'] = '0';

		$this->assertTrue($this->Controller->data == $data);
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
			md5($data['username'] . ':' . $loginData['realm'].':'.$data['password']) . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' .
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

		if (version_compare(phpversion(), '5.1') != -1) {
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
 * sortFields method
 * 
 * @param mixed $fields 
 * @access private
 * @return void
 */
	function __sortFields($fields) {
		foreach ($fields as $key => $value) {
			if ($key[0] != '_' && is_array($fields[$key])) {
				sort($fields[$key]);
			}
		}
		ksort($fields, SORT_STRING);
		return $fields;
	}
}
?>