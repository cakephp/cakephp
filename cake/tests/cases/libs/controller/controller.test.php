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
 * @subpackage		cake.tests.cases.libs.controller
 * @since			CakePHP(tm) v 1.2.0.5436
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Controller');
App::import('Component', 'Security');
App::import('Component', 'Cookie');

class ControllerPost extends CakeTestModel {
	var $name = 'ControllerPost';
	var $useTable = 'posts';
	var $invalidFields = array('name' => 'error_msg');
	var $lastQuery = null;

	function beforeFind($query) {
		$this->lastQuery = $query;
	}

	function find($type, $options = array()) {
		if ($type == 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey => '> 1');
			return parent::find('all', Set::merge($options, compact('conditions')));
		}
		return parent::find($type, $options);
	}
}
class ControllerComment extends CakeTestModel {
	var $name = 'ControllerComment';
	var $useTable = 'comments';
	var $data = array('name' => 'Some Name');
	var $alias = 'ControllerComment';
}
if (!class_exists('AppController')) {
	class AppController extends Controller {
		var $helpers = array('Html', 'Javascript');
		var $uses = array('ControllerPost');
		var $components = array('Cookie');
	}
} else {
	define('AppControllerExists', true);
}
class TestController extends AppController {
	var $helpers = array('Xml');
	var $components = array('Security');
	var $uses = array('ControllerComment');

	function index($testId, $test2Id) {
		$this->data['testId'] = $testId;
		$this->data['test2Id'] = $test2Id;
	}
}
class TestComponent extends Object {
	function beforeRedirect() {
		return true;
	}
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller
 */
class ControllerTest extends CakeTestCase {

	var $fixtures = array('core.post', 'core.comment');

	function testConstructClasses() {
		$Controller =& new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->passedArgs[] = '1';
		$Controller->constructClasses();
		$this->assertEqual($Controller->ControllerPost->id, 1);

		unset($Controller);

		$Controller =& new Controller();
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->constructClasses();
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		$this->assertTrue(is_a($Controller->ControllerComment, 'ControllerComment'));

		unset($Controller);
	}

	function testPersistent() {
		$Controller =& new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->persistModel = true;
		$Controller->constructClasses();
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS .'controllerpost.php'));
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		unlink(CACHE . 'persistent' . DS . 'controllerpost.php');
		unlink(CACHE . 'persistent' . DS . 'controllerpostregistry.php');

		unset($Controller);
	}

	function testPaginate() {
		$Controller =& new Controller();
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$results = Set::extract($Controller->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$results = Set::extract($Controller->paginate('ControllerComment'), '{n}.ControllerComment.id');
		$this->assertEqual($results, array(1, 2, 3, 4, 5, 6));

		$Controller->modelClass = null;

		$Controller->uses[0] = 'Plugin.ControllerPost';
		$results = Set::extract($Controller->paginate(), '{n}.ControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array('page' => '-1');
		$results = Set::extract($Controller->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual($results, array(1, 2, 3));
	}

	function testPaginateExtraParams() {
		$Controller =& new Controller();
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();

		$Controller->passedArgs = array('page' => '-1', 'contain' => array('ControllerComment'));
		$result = $Controller->paginate('ControllerPost');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.id'), array(1, 2, 3));
		$this->assertTrue(!isset($Controller->ControllerPost->lastQuery['contain']));

		$Controller->passedArgs = array('page' => '-1');
		$Controller->paginate = array('ControllerPost' => array('contain' => array('ControllerComment')));
		$result = $Controller->paginate('ControllerPost');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.id'), array(1, 2, 3));
		$this->assertFalse(!isset($Controller->ControllerPost->lastQuery['contain']));

		$Controller->paginate = array('ControllerPost' => array('popular', 'fields' => array('id', 'title')));
		$result = $Controller->paginate('ControllerPost');
		$this->assertEqual(Set::extract($result, '{n}.ControllerPost.id'), array(2, 3));
		$this->assertEqual($Controller->ControllerPost->lastQuery['conditions'], array('ControllerPost.id' => '> 1'));
	}

	function testDefaultPaginateParams() {
		$Controller =& new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->params['url'] = array();
		$Controller->paginate = array('order' => 'ControllerPost.id DESC');
		$Controller->constructClasses();
		$results = Set::extract($Controller->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['defaults']['order'], 'ControllerPost.id DESC');
		$this->assertEqual($Controller->params['paging']['ControllerPost']['options']['order'], 'ControllerPost.id DESC');
		$this->assertEqual($results, array(3, 2, 1));
	}

	function testFlash() {
		$Controller =& new Controller();
		$Controller->flash('this should work', '/flash');
		$result = $Controller->output;

		$expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<title>this should work</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style><!--
		P { text-align:center; font:bold 1.1em sans-serif }
		A { color:#444; text-decoration:none }
		A:HOVER { text-decoration: underline; color:#44E }
		--></style>
		</head>
		<body>
		<p><a href="/flash">this should work</a></p>
		</body>
		</html>';
 		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);
	}

	function testControllerSet() {
		$Controller =& new Controller();
		$Controller->set('variable_with_underscores', null);
		$this->assertTrue(array_key_exists('variable_with_underscores', $Controller->viewVars));

		$Controller->viewVars = array();
		$viewVars = array('ModelName' => array('id' => 1, 'name' => 'value'));
		$Controller->set($viewVars);
		$this->assertTrue(array_key_exists('modelName', $Controller->viewVars));

		$Controller->viewVars = array();
		$Controller->set('variable_with_underscores', 'value');
		$this->assertTrue(array_key_exists('variable_with_underscores', $Controller->viewVars));

		$Controller->viewVars = array();
		$viewVars = array('ModelName' => 'name');
		$Controller->set($viewVars);
		$this->assertTrue(array_key_exists('modelName', $Controller->viewVars));

		$Controller->set('title', 'someTitle');
		$this->assertIdentical($Controller->pageTitle, 'someTitle');

		$Controller->viewVars = array();
		$expected = array('ModelName' => 'name', 'ModelName2' => 'name2');
		$Controller->set(array('ModelName', 'ModelName2'), array('name', 'name2'));
		$this->assertIdentical($Controller->viewVars, $expected);
	}

	function testRender() {
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS, TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS));

		$Controller =& new Controller();
		$Controller->viewPath = 'posts';

		$result = $Controller->render('index');
		$this->assertPattern('/posts index/', $result);

		$result = $Controller->render('/elements/test_element');
		$this->assertPattern('/this is the test element/', $result);
	}

	function testToBeInheritedGuardmethods() {
		$Controller =& new Controller();
		$this->assertTrue($Controller->_beforeScaffold(''));
		$this->assertTrue($Controller->_afterScaffoldSave(''));
		$this->assertTrue($Controller->_afterScaffoldSaveError(''));
		$this->assertFalse($Controller->_scaffoldError(''));
	}

	function test__postConditionMatch() {
		$Controller =& new Controller();
		$value = 'val';

		$result = $Controller->__postConditionMatch('=', $value);
		$expected = $value;
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch('', $value);
		$expected = $value;
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch(null, $value);
		$expected = $value;
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch('LIKE', $value);
		$expected = 'LIKE %'.$value.'%';
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch('>', $value);
		$expected = '> '.$value;
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch('<', $value);
		$expected = '< '.$value;
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch('>=', $value);
		$expected = '>= '.$value;
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch('<=', $value);
		$expected = '<= '.$value;
		$this->assertIdentical($result, $expected);

		$result = $Controller->__postConditionMatch('<>', $value);
		$expected = '<> '.$value;
		$this->assertIdentical($result, $expected);
	}

	function testCleanUpFields() {
		$Controller =& new Controller();
		$Controller->cleanUpFields();
		$this->assertError();
	}

	function testRedirect() {
		$url = 'cakephp.org';
		$codes = array(
			100 => "Continue",
			101 => "Switching Protocols",
			200 => "OK",
			201 => "Created",
			202 => "Accepted",
			203 => "Non-Authoritative Information",
			204 => "No Content",
			205 => "Reset Content",
			206 => "Partial Content",
			300 => "Multiple Choices",
			301 => "Moved Permanently",
			302 => "Found",
			303 => "See Other",
			304 => "Not Modified",
			305 => "Use Proxy",
			307 => "Temporary Redirect",
			400 => "Bad Request",
			401 => "Unauthorized",
			402 => "Payment Required",
			403 => "Forbidden",
			404 => "Not Found",
			405 => "Method Not Allowed",
			406 => "Not Acceptable",
			407 => "Proxy Authentication Required",
			408 => "Request Time-out",
			409 => "Conflict",
			410 => "Gone",
			411 => "Length Required",
			412 => "Precondition Failed",
			413 => "Request Entity Too Large",
			414 => "Request-URI Too Large",
			415 => "Unsupported Media Type",
			416 => "Requested range not satisfiable",
			417 => "Expectation Failed",
			500 => "Internal Server Error",
			501 => "Not Implemented",
			502 => "Bad Gateway",
			503 => "Service Unavailable",
			504 => "Gateway Time-out"
		);

		Mock::generatePartial('Controller', 'MockController', array('header'));
		App::import('Helper', 'Cache');

		foreach ($codes as $code => $msg) {
			$MockController =& new MockController();
			$MockController->components = array('Test');
			$MockController->Component =& new Component();
			$MockController->Component->init($MockController);
			$MockController->expectCallCount('header', 2);
			$MockController->redirect($url, (int) $code, false);
		}
	}

	function testMergeVars() {
		$this->skipIf(defined('AppControllerExists'), 'MergeVars will be skipped as it needs a non-existent AppController. As the an AppController class exists, this cannot be run.');

		$TestController =& new TestController();
		$TestController->constructClasses();

		$testVars = get_class_vars('TestController');
		$appVars = get_class_vars('AppController');
		$components = is_array($appVars['components'])
						? array_merge($appVars['components'], $testVars['components'])
						: $testVars['components'];
		if (!in_array('Session', $components)) {
			$components[] = 'Session';
		}
		$helpers = is_array($appVars['helpers'])
					? array_merge($appVars['helpers'], $testVars['helpers'])
					: $testVars['helpers'];
		$uses = is_array($appVars['uses'])
					? array_merge($appVars['uses'], $testVars['uses'])
					: $testVars['uses'];

		$this->assertEqual(count(array_diff($TestController->helpers, $helpers)), 0);
		$this->assertEqual(count(array_diff($TestController->uses, $uses)), 0);
		$this->assertEqual(count(array_diff($TestController->components, $components)), 0);
	}

	function testReferer() {
		$Controller =& new Controller();
		$_SERVER['HTTP_REFERER'] = 'http://cakephp.org';
		$result = $Controller->referer(null, false);
		$expected = 'http://cakephp.org';
		$this->assertIdentical($result, $expected);

		$_SERVER['HTTP_REFERER'] = '';
		$result = $Controller->referer('http://cakephp.org', false);
		$expected = 'http://cakephp.org';
		$this->assertIdentical($result, $expected);

		$_SERVER['HTTP_REFERER'] = '';
		$result = $Controller->referer(null, false);
		$expected = '/';
		$this->assertIdentical($result, $expected);

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL.$Controller->webroot.'/some/path';
		$result = $Controller->referer(null, false);
		$expected = '/some/path';
		$this->assertIdentical($result, $expected);

		$Controller->webroot .= '/';
		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL.$Controller->webroot.'/some/path';
		$result = $Controller->referer(null, false);
		$expected = '/some/path';
		$this->assertIdentical($result, $expected);

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL.$Controller->webroot.'some/path';
		$result = $Controller->referer(null, false);
		$expected = '/some/path';
		$this->assertIdentical($result, $expected);
	}

	function testSetAction() {
		$TestController =& new TestController();
		$TestController->setAction('index', 1, 2);
		$expected = array('testId' => 1, 'test2Id' => 2);
		$this->assertidentical($TestController->data, $expected);
	}

	function testUnimplementedIsAuthorized() {
		$TestController =& new TestController();
		$TestController->isAuthorized();
		$this->assertError();
	}

	function testValidateErrors() {
		$TestController =& new TestController();
		$TestController->constructClasses();
		$this->assertFalse($TestController->validateErrors());
		$this->assertEqual($TestController->validate(), 0);

		$TestController->ControllerComment->invalidate('some_field', 'error_message');
		$TestController->ControllerComment->invalidate('some_field2', 'error_message2');
		$comment = new ControllerComment;
		$comment->set('someVar', 'data');
		$result = $TestController->validateErrors($comment);
		$expected = array('some_field' => 'error_message', 'some_field2' => 'error_message2');
		$this->assertIdentical($result, $expected);
		$this->assertEqual($TestController->validate($comment), 2);
	}

	function testPostConditions() {
		$Controller =& new Controller();


		$data = array(
			'Model1' => array('field1' => '23'),
			'Model2' => array('field2' => 'string'),
			'Model3' => array('field3' => '23'),
		);
		$expected = array(
			'Model1.field1' => '23',
			'Model2.field2' => 'string',
			'Model3.field3' => '23',
		);
		$result = $Controller->postConditions($data);
		$this->assertIdentical($result, $expected);


		$data = array();
		$Controller->data = array(
			'Model1' => array('field1' => '23'),
			'Model2' => array('field2' => 'string'),
			'Model3' => array('field3' => '23'),
		);
		$expected = array(
			'Model1.field1' => '23',
			'Model2.field2' => 'string',
			'Model3.field3' => '23',
		);
		$result = $Controller->postConditions($data);
		$this->assertIdentical($result, $expected);


		$data = array();
		$Controller->data = array();
		$result = $Controller->postConditions($data);
		$this->assertNull($result);


		$data = array();
		$Controller->data = array(
			'Model1' => array('field1' => '23'),
			'Model2' => array('field2' => 'string'),
			'Model3' => array('field3' => '23'),
		);
		$ops = array(
			'Model1.field1' => '>',
			'Model2.field2' => 'LIKE',
			'Model3.field3' => '<=',
		);
		$expected = array(
			'Model1.field1' => '> 23',
			'Model2.field2' => "LIKE %string%",
			'Model3.field3' => '<= 23',
		);
		$result = $Controller->postConditions($data, $ops);
		$this->assertIdentical($result, $expected);
	}
}
?>