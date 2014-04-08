<?php
/**
 * CookieComponentTest file
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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CookieComponent;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * CookieComponentTest class
 */
class CookieComponentTest extends TestCase {

/**
 * start
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$controller = $this->getMock(
			'Cake\Controller\Controller',
			array('redirect'),
			array(new Request(), new Response())
		);
		$controller->components = array('Cookie');
		$controller->constructClasses();
		$this->Controller = $controller;
		$this->Cookie = $controller->Cookie;
		$this->request = $controller->request;

		$this->Cookie->config([
			'name' => 'CakeTestCookie',
			'time' => 10,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'key' => 'somerandomhaskeysomerandomhaskey'
		]);

		$event = new Event('Controller.startup', $this->Controller);
		$this->Cookie->startup($event);
	}

/**
 * end
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
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
		$Cookie = new CookieComponent(new ComponentRegistry(), $settings);
		$this->assertEquals($Cookie->config('time'), $settings['time']);
		$this->assertEquals($Cookie->config('path'), $settings['path']);
	}

/**
 * testCookieName
 *
 * @return void
 */
	public function testCookieName() {
		$this->assertEquals('CakeTestCookie', $this->Cookie->config('name'));
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
		$this->request->cookies = array(
			'CakeTestCookie' => array(
				'key' => 'value'
			),
			'OtherTestCookie' => array(
				'key' => 'other value'
			)
		);
		$this->assertEquals('value', $this->Cookie->read('key'));

		$this->Cookie->config('name', 'OtherTestCookie');
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
 * test write() encrypted data with falsey value
 *
 * @return void
 */
	public function testWriteWithFalseyValue() {
		$this->Cookie->encryption('aes');
		$this->Cookie->key = 'qSI232qs*&sXOw!adre@34SAv!@*(XSL#$%)asGb$@11~_+!@#HKis~#^';

		$this->Cookie->write('Testing');
		$result = $this->Cookie->read('Testing');
		$this->assertNull($result);

		$this->Cookie->write('Testing', '');
		$result = $this->Cookie->read('Testing');
		$this->assertEquals('', $result);

		$this->Cookie->write('Testing', false);
		$result = $this->Cookie->read('Testing');
		$this->assertFalse($result);

		$this->Cookie->write('Testing', 1);
		$result = $this->Cookie->read('Testing');
		$this->assertEquals(1, $result);

		$this->Cookie->write('Testing', '0');
		$result = $this->Cookie->read('Testing');
		$this->assertSame('0', $result);

		$this->Cookie->write('Testing', 0);
		$result = $this->Cookie->read('Testing');
		$this->assertSame(0, $result);
	}

/**
 * test that two write() calls use the expiry.
 *
 * @return void
 */
	public function testWriteMultipleShareExpiry() {
		$this->Cookie->write('key1', 'value1', false);
		$this->Cookie->write('key2', 'value2', false);

		$name = $this->Cookie->config('name') . '[key1]';
		$result = $this->Controller->response->cookie($name);
		$this->assertWithinMargin(time() + 10, $result['expire'], 2, 'Expiry time is wrong');

		$name = $this->Cookie->config('name') . '[key2]';
		$result = $this->Controller->response->cookie($name);
		$this->assertWithinMargin(time() + 10, $result['expire'], 2, 'Expiry time is wrong');
	}

/**
 * test write with distant future cookies
 *
 * @return void
 */
	public function testWriteFarFuture() {
		$this->Cookie->write('Testing', 'value', false, '+90 years');
		$future = new \DateTime('now');
		$future->modify('+90 years');

		$expected = array(
			'name' => $this->Cookie->config('name') . '[Testing]',
			'value' => 'value',
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => false);
		$result = $this->Controller->response->cookie($this->Cookie->config('name') . '[Testing]');

		$this->assertEquals($future->format('U'), $result['expire'], '', 3);
		unset($result['expire']);

		$this->assertEquals($expected, $result);
	}

/**
 * test write with httpOnly cookies
 *
 * @return void
 */
	public function testWriteHttpOnly() {
		$this->Cookie->config([
			'httpOnly' => true,
			'secure' => false
		]);
		$this->Cookie->write('Testing', 'value', false);
		$expected = array(
			'name' => $this->Cookie->config('name') . '[Testing]',
			'value' => 'value',
			'expire' => time() + 10,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => true);
		$result = $this->Controller->response->cookie($this->Cookie->config('name') . '[Testing]');
		$this->assertEquals($expected, $result);
	}

/**
 * test delete with httpOnly
 *
 * @return void
 */
	public function testDeleteHttpOnly() {
		$this->Cookie->config([
			'httpOnly' => true,
			'secure' => false
		]);
		$this->Cookie->delete('Testing', false);
		$expected = array(
			'name' => $this->Cookie->config('name') . '[Testing]',
			'value' => '',
			'expire' => time() - 42000,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => true);
		$result = $this->Controller->response->cookie($this->Cookie->config('name') . '[Testing]');
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
		$this->Cookie->config('secure', false);
		$this->Cookie->write('Testing', array(1, 2, 3), false);
		$expected = array(
			'name' => $this->Cookie->config('name') . '[Testing]',
			'value' => '[1,2,3]',
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => false);
		$result = $this->Controller->response->cookie($this->Cookie->config('name') . '[Testing]');

		$this->assertWithinMargin($result['expire'], time() + 10, 1);
		unset($result['expire']);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that writing mixed arrays results in the correct data.
 *
 * @return void
 */
	public function testWriteMixedArray() {
		$this->Cookie->config('encrypt', false);
		$this->Cookie->write('User', array('name' => 'mark'), false);
		$this->Cookie->write('User.email', 'mark@example.com', false);
		$expected = array(
			'name' => $this->Cookie->config('name') . '[User]',
			'value' => '{"name":"mark","email":"mark@example.com"}',
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => false
		);
		$result = $this->Controller->response->cookie($this->Cookie->config('name') . '[User]');
		unset($result['expire']);

		$this->assertEquals($expected, $result);

		$this->Cookie->write('User.email', 'mark@example.com', false);
		$this->Cookie->write('User', array('name' => 'mark'), false);
		$expected = array(
			'name' => $this->Cookie->config('name') . '[User]',
			'value' => '{"name":"mark"}',
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => false
		);
		$result = $this->Controller->response->cookie($this->Cookie->config('name') . '[User]');
		unset($result['expire']);

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

		$this->request->cookies['CakeTestCookie'] = array(
			'Encrytped_array' => $this->_encrypt(array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!')),
			'Encrytped_multi_cookies' => array(
				'name' => $this->_encrypt('CakePHP'),
				'version' => $this->_encrypt('1.2.0.x'),
				'tag' => $this->_encrypt('CakePHP Rocks!')
			),
			'Plain_array' => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
			'Plain_multi_cookies' => array(
				'name' => 'CakePHP',
				'version' => '1.2.0.x',
				'tag' => 'CakePHP Rocks!'
			)
		);

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

		$this->request->cookies['CakeTestCookie'] = array(
			'Encrytped_array' => $this->_encrypt(array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!')),
			'Encrytped_multi_cookies' => array(
				'name' => $this->_encrypt('CakePHP'),
				'version' => $this->_encrypt('1.2.0.x'),
				'tag' => $this->_encrypt('CakePHP Rocks!')
			),
			'Plain_array' => '{"name":"CakePHP","version":"1.2.0.x","tag":"CakePHP Rocks!"}',
			'Plain_multi_cookies' => array(
				'name' => 'CakePHP',
				'version' => '1.2.0.x',
				'tag' => 'CakePHP Rocks!'
			)
		);

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
	}

/**
 * Test Reading legacy cookie values.
 *
 * @return void
 */
	public function testReadLegacyCookieValue() {
		$this->request->cookies['CakeTestCookie'] = array(
			'Legacy' => array('value' => $this->_oldImplode(array(1, 2, 3)))
		);
		$result = $this->Cookie->read('Legacy.value');
		$expected = array(1, 2, 3);
		$this->assertEquals($expected, $result);
	}

/**
 * Test reading empty values.
 *
 * @return void
 */
	public function testReadEmpty() {
		$this->request->cookies['CakeTestCookie'] = array(
			'JSON' => '{"name":"value"}',
			'Empty' => '',
			'String' => '{"somewhat:"broken"}',
			'Array' => '{}'
		);
		$this->assertEquals(array('name' => 'value'), $this->Cookie->read('JSON'));
		$this->assertEquals('value', $this->Cookie->read('JSON.name'));
		$this->assertEquals('', $this->Cookie->read('Empty'));
		$this->assertEquals('{"somewhat:"broken"}', $this->Cookie->read('String'));
		$this->assertEquals(array(), $this->Cookie->read('Array'));
	}

/**
 * test that no error is issued for non array data.
 *
 * @return void
 */
	public function testNoErrorOnNonArrayData() {
		$this->request->cookies['CakeTestCookie'] = 'kaboom';

		$this->assertNull($this->Cookie->read('value'));
	}

/**
 * testCheck method
 *
 * @return void
 */
	public function testCheck() {
		$this->Cookie->write('CookieComponentTestCase', 'value');
		$this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

		$this->assertFalse($this->Cookie->check('NotExistingCookieComponentTestCase'));
	}

/**
 * testCheckingSavedEmpty method
 *
 * @return void
 */
	public function testCheckingSavedEmpty() {
		$this->Cookie->write('CookieComponentTestCase', 0);
		$this->assertTrue($this->Cookie->check('CookieComponentTestCase'));

		$this->Cookie->write('CookieComponentTestCase', '0');
		$this->assertTrue($this->Cookie->check('CookieComponentTestCase'));
	}

/**
 * testCheckKeyWithSpaces method
 *
 * @return void
 */
	public function testCheckKeyWithSpaces() {
		$this->Cookie->write('CookieComponent Test', "test");
		$this->assertTrue($this->Cookie->check('CookieComponent Test'));
		$this->Cookie->delete('CookieComponent Test');

		$this->Cookie->write('CookieComponent Test.Test Case', "test");
		$this->assertTrue($this->Cookie->check('CookieComponent Test.Test Case'));
	}

/**
 * testCheckEmpty
 *
 * @return void
 */
	public function testCheckEmpty() {
		$this->assertFalse($this->Cookie->check());
	}

/**
 * test that deleting a top level keys kills the child elements too.
 *
 * @return void
 */
	public function testDeleteRemovesChildren() {
		$this->request->cookies['CakeTestCookie'] = array(
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
 * @param array $array
 * @return string
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
 * @param array|string $value
 * @return string
 */
	protected function _encrypt($value) {
		if (is_array($value)) {
			$value = $this->_implode($value);
		}
		return "Q2FrZQ==." . base64_encode(Security::encrypt($value, $this->Cookie->config('key')));
	}

}
