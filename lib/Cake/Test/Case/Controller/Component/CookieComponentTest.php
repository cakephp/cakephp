<?php
/**
 * CookieComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Component', 'Controller');
App::uses('Controller', 'Controller');
App::uses('CookieComponent', 'Controller/Component');


/**
 * CookieComponentTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class CookieComponentTestController extends Controller {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Cookie');

/**
 * beforeFilter method
 *
 * @return void
 */
	public function beforeFilter() {
		$this->Cookie->name = 'CakeTestCookie';
		$this->Cookie->time = 10;
		$this->Cookie->path = '/';
		$this->Cookie->domain = '';
		$this->Cookie->secure = false;
		$this->Cookie->key = 'somerandomhaskey';
	}

}

/**
 * CookieComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class CookieComponentTest extends CakeTestCase {

/**
 * Controller property
 *
 * @var CookieComponentTestController
 */
	public $Controller;

/**
 * start
 *
 * @return void
 */
	public function setUp() {
		$_COOKIE = array();
		$this->Controller = new CookieComponentTestController(new CakeRequest(), new CakeResponse());
		$this->Controller->constructClasses();
		$this->Cookie = $this->Controller->Cookie;

		$this->Cookie->name = 'CakeTestCookie';
		$this->Cookie->time = 10;
		$this->Cookie->path = '/';
		$this->Cookie->domain = '';
		$this->Cookie->secure = false;
		$this->Cookie->key = 'somerandomhaskey';

		$this->Cookie->startup($this->Controller);
	}

/**
 * end
 *
 * @return void
 */
	public function tearDown() {
		$this->Cookie->destroy();
	}

/**
 * sets up some default cookie data.
 *
 * @return void
 */
	protected function _setCookieData() {
		$this->Cookie->write(array('Encrytped_array' => array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!')));
		$this->Cookie->write(array('Encrytped_multi_cookies.name' => 'CakePHP'));
		$this->Cookie->write(array('Encrytped_multi_cookies.version' => '1.2.0.x'));
		$this->Cookie->write(array('Encrytped_multi_cookies.tag' => 'CakePHP Rocks!'));

		$this->Cookie->write(array('Plain_array' => array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!')), null, false);
		$this->Cookie->write(array('Plain_multi_cookies.name' => 'CakePHP'), null, false);
		$this->Cookie->write(array('Plain_multi_cookies.version' => '1.2.0.x'), null, false);
		$this->Cookie->write(array('Plain_multi_cookies.tag' => 'CakePHP Rocks!'), null, false);
	}

/**
 * test that initialize sets settings from components array
 *
 * @return void
 */
	public function testSettings() {
		$settings = array(
			'time' => '5 days',
			'path' => '/'
		);
		$Cookie = new CookieComponent(new ComponentCollection(), $settings);
		$this->assertEquals($Cookie->time, $settings['time']);
		$this->assertEquals($Cookie->path, $settings['path']);
	}

/**
 * testCookieName
 *
 * @return void
 */
	public function testCookieName() {
		$this->assertEquals('CakeTestCookie', $this->Cookie->name);
	}

/**
 * testReadEncryptedCookieData
 *
 * @return void
 */
	public function testReadEncryptedCookieData() {
		$this->_setCookieData();
		$data = $this->Cookie->read('Encrytped_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);
	}

/**
 * testReadPlainCookieData
 *
 * @return void
 */
	public function testReadPlainCookieData() {
		$this->_setCookieData();
		$data = $this->Cookie->read('Plain_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);
	}

/**
 * test read() after switching the cookie name.
 *
 * @return void
 */
	public function testReadWithNameSwitch() {
		$_COOKIE = array(
			'CakeTestCookie' => array(
				'key' => 'value'
			),
			'OtherTestCookie' => array(
				'key' => 'other value'
			)
		);
		$this->assertEquals('value', $this->Cookie->read('key'));

		$this->Cookie->name = 'OtherTestCookie';
		$this->assertEquals('other value', $this->Cookie->read('key'));
	}

/**
 * test a simple write()
 *
 * @return void
 */
	public function testWriteSimple() {
		$this->Cookie->write('Testing', 'value');
		$result = $this->Cookie->read('Testing');

		$this->assertEquals('value', $result);
	}

/**
 * test write with httpOnly cookies
 *
 * @return void
 */
	public function testWriteHttpOnly() {
		$this->Cookie->httpOnly = true;
		$this->Cookie->secure = false;
		$this->Cookie->write('Testing', 'value', false);
		$expected = array(
			'name' => $this->Cookie->name . '[Testing]',
			'value' => 'value',
			'expire' => time() + 10,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => true);
		$result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');
		$this->assertEquals($expected, $result);
	}

/**
 * test delete with httpOnly
 *
 * @return void
 */
	public function testDeleteHttpOnly() {
		$this->Cookie->httpOnly = true;
		$this->Cookie->secure = false;
		$this->Cookie->delete('Testing', false);
		$expected = array(
			'name' => $this->Cookie->name . '[Testing]',
			'value' => '',
			'expire' => time() - 42000,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => true);
		$result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');
		$this->assertEquals($expected, $result);
	}

/**
 * testWritePlainCookieArray
 *
 * @return void
 */
	public function testWritePlainCookieArray() {
		$this->Cookie->write(array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'), null, false);

		$this->assertEquals('CakePHP', $this->Cookie->read('name'));
		$this->assertEquals('1.2.0.x', $this->Cookie->read('version'));
		$this->assertEquals('CakePHP Rocks!', $this->Cookie->read('tag'));

		$this->Cookie->delete('name');
		$this->Cookie->delete('version');
		$this->Cookie->delete('tag');
	}

/**
 * test writing values that are not scalars
 *
 * @return void
 */
	public function testWriteArrayValues() {
		$this->Cookie->secure = false;
		$this->Cookie->write('Testing', array(1, 2, 3), false);
		$expected = array(
			'name' => $this->Cookie->name . '[Testing]',
			'value' => '[1,2,3]',
			'expire' => time() + 10,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => false);
		$result = $this->Controller->response->cookie($this->Cookie->name . '[Testing]');
		$this->assertEquals($expected, $result);
	}

/**
 * testReadingCookieValue
 *
 * @return void
 */
	public function testReadingCookieValue() {
		$this->_setCookieData();
		$data = $this->Cookie->read();
		$expected = array(
			'Encrytped_array' => array(
				'name' => 'CakePHP',
				'version' => '1.2.0.x',
				'tag' => 'CakePHP Rocks!'),
			'Encrytped_multi_cookies' => array(
				'name' => 'CakePHP',
				'version' => '1.2.0.x',
				'tag' => 'CakePHP Rocks!'),
			'Plain_array' => array(
				'name' => 'CakePHP',
				'version' => '1.2.0.x',
				'tag' => 'CakePHP Rocks!'),
			'Plain_multi_cookies' => array(
				'name' => 'CakePHP',
				'version' => '1.2.0.x',
				'tag' => 'CakePHP Rocks!'));
		$this->assertEquals($expected, $data);
	}

/**
 * testDeleteCookieValue
 *
 * @return void
 */
	public function testDeleteCookieValue() {
		$this->_setCookieData();
		$this->Cookie->delete('Encrytped_multi_cookies.name');
		$data = $this->Cookie->read('Encrytped_multi_cookies');
		$expected = array('version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$this->Cookie->delete('Encrytped_array');
		$data = $this->Cookie->read('Encrytped_array');
		$this->assertNull($data);

		$this->Cookie->delete('Plain_multi_cookies.name');
		$data = $this->Cookie->read('Plain_multi_cookies');
		$expected = array('version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$this->Cookie->delete('Plain_array');
		$data = $this->Cookie->read('Plain_array');
		$this->assertNull($data);
	}

/**
 * testReadingCookieArray
 *
 * @return void
 */
	public function testReadingCookieArray() {
		$this->_setCookieData();

		$data = $this->Cookie->read('Encrytped_array.name');
		$expected = 'CakePHP';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_array.version');
		$expected = '1.2.0.x';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_array.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_multi_cookies.name');
		$expected = 'CakePHP';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_multi_cookies.version');
		$expected = '1.2.0.x';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_multi_cookies.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_array.name');
		$expected = 'CakePHP';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_array.version');
		$expected = '1.2.0.x';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_array.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_multi_cookies.name');
		$expected = 'CakePHP';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_multi_cookies.version');
		$expected = '1.2.0.x';
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_multi_cookies.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEquals($expected, $data);
	}

/**
 * testReadingCookieDataOnStartup
 *
 * @return void
 */
	public function testReadingCookieDataOnStartup() {
		$data = $this->Cookie->read('Encrytped_array');
		$this->assertNull($data);

		$data = $this->Cookie->read('Encrytped_multi_cookies');
		$this->assertNull($data);

		$data = $this->Cookie->read('Plain_array');
		$this->assertNull($data);

		$data = $this->Cookie->read('Plain_multi_cookies');
		$this->assertNull($data);

		$_COOKIE['CakeTestCookie'] = array(
				'Encrytped_array' => $this->__encrypt(array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!')),
				'Encrytped_multi_cookies' => array(
						'name' => $this->__encrypt('CakePHP'),
						'version' => $this->__encrypt('1.2.0.x'),
						'tag' => $this->__encrypt('CakePHP Rocks!')),
				'Plain_array' => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
				'Plain_multi_cookies' => array(
						'name' => 'CakePHP',
						'version' => '1.2.0.x',
						'tag' => 'CakePHP Rocks!'));

		$this->Cookie->startup(new CookieComponentTestController());

		$data = $this->Cookie->read('Encrytped_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);
		$this->Cookie->destroy();
		unset($_COOKIE['CakeTestCookie']);
	}

/**
 * testReadingCookieDataWithoutStartup
 *
 * @return void
 */
	public function testReadingCookieDataWithoutStartup() {
		$data = $this->Cookie->read('Encrytped_array');
		$expected = null;
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_multi_cookies');
		$expected = null;
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_array');
		$expected = null;
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_multi_cookies');
		$expected = null;
		$this->assertEquals($expected, $data);

		$_COOKIE['CakeTestCookie'] = array(
				'Encrytped_array' => $this->__encrypt(array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!')),
				'Encrytped_multi_cookies' => array(
						'name' => $this->__encrypt('CakePHP'),
						'version' => $this->__encrypt('1.2.0.x'),
						'tag' => $this->__encrypt('CakePHP Rocks!')),
				'Plain_array' => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
				'Plain_multi_cookies' => array(
						'name' => 'CakePHP',
						'version' => '1.2.0.x',
						'tag' => 'CakePHP Rocks!'));

		$data = $this->Cookie->read('Encrytped_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Encrytped_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);

		$data = $this->Cookie->read('Plain_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!');
		$this->assertEquals($expected, $data);
		$this->Cookie->destroy();
		unset($_COOKIE['CakeTestCookie']);
	}

/**
 * Test Reading legacy cookie values.
 *
 * @return void
 */
	public function testReadLegacyCookieValue() {
		$_COOKIE['CakeTestCookie'] = array(
			'Legacy' => array('value' => $this->_oldImplode(array(1, 2, 3)))
		);
		$result = $this->Cookie->read('Legacy.value');
		$expected = array(1, 2, 3);
		$this->assertEquals($expected, $result);
	}

/**
 * Test reading empty values.
 */
	public function testReadEmpty() {
		$_COOKIE['CakeTestCookie'] = array(
		  'JSON' => '{"name":"value"}',
		  'Empty' => '',
		  'String' => '{"somewhat:"broken"}'
		);
		$this->assertEqual(array('name' => 'value'), $this->Cookie->read('JSON'));
		$this->assertEqual('value', $this->Cookie->read('JSON.name'));
		$this->assertEqual('', $this->Cookie->read('Empty'));
		$this->assertEqual('{"somewhat:"broken"}', $this->Cookie->read('String'));
	}

/**
 * test that no error is issued for non array data.
 *
 * @return void
 */
	public function testNoErrorOnNonArrayData() {
		$this->Cookie->destroy();
		$_COOKIE['CakeTestCookie'] = 'kaboom';

		$this->assertNull($this->Cookie->read('value'));
	}

/**
 * test that deleting a top level keys kills the child elements too.
 *
 * @return void
 */
	public function testDeleteRemovesChildren() {
		$_COOKIE['CakeTestCookie'] = array(
			'User' => array('email' => 'example@example.com', 'name' => 'mark'),
			'other' => 'value'
		);
		$this->assertEquals('mark', $this->Cookie->read('User.name'));

		$this->Cookie->delete('User');
		$this->assertNull($this->Cookie->read('User.email'));
		$this->Cookie->destroy();
	}

/**
 * Test deleting recursively with keys that don't exist.
 *
 * @return void
 */
	public function testDeleteChildrenNotExist() {
		$this->assertNull($this->Cookie->delete('NotFound'));
		$this->assertNull($this->Cookie->delete('Not.Found'));
	}

/**
 * Helper method for generating old style encoded cookie values.
 *
 * @return string.
 */
	protected function _oldImplode(array $array) {
		$string = '';
		foreach ($array as $key => $value) {
			$string .= ',' . $key . '|' . $value;
		}
		return substr($string, 1);
	}

/**
 * Implode method to keep keys are multidimensional arrays
 *
 * @param array $array Map of key and values
 * @return string String in the form key1|value1,key2|value2
 */
	protected function _implode(array $array) {
		return json_encode($array);
	}

/**
 * encrypt method
 *
 * @param mixed $value
 * @return string
 */
	protected function __encrypt($value) {
		if (is_array($value)) {
			$value = $this->_implode($value);
		}
		return "Q2FrZQ==." . base64_encode(Security::cipher($value, $this->Cookie->key));
	}

}
