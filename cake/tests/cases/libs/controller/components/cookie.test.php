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
App::import('Core', array('Component', 'Controller', 'Cookie'));
class CookieComponentTestController extends Controller {
	var $components = array('Cookie');

	function beforeFilter() {
		$this->Cookie->name = 'CakeTestCookie';
		$this->Cookie->time = 10;
		$this->Cookie->path = '/';
		$this->Cookie->domain = '';
		$this->Cookie->secure = false;
		$this->Cookie->key = 'somerandomhaskey';
	}
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class CookieComponentTest extends CakeTestCase {
	var $Controller;

	function start() {
		$this->Controller = new CookieComponentTestController();
		$this->Controller->constructClasses();
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();
		$this->Controller->Component->startup($this->Controller);
		$this->Controller->Cookie->destroy();
	}

	function testCookieName() {
		$this->assertEqual($this->Controller->Cookie->name, 'CakeTestCookie');
	}

	function testSettingEncryptedCookieData() {
		$this->Controller->Cookie->write('Encrytped_array', array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!'));
		$this->Controller->Cookie->write('Encrytped_multi_cookies.name', 'CakePHP');
		$this->Controller->Cookie->write('Encrytped_multi_cookies.version', '1.2.0.x');
		$this->Controller->Cookie->write('Encrytped_multi_cookies.tag', 'CakePHP Rocks!');
	}

	function testReadEncryptedCookieData() {
		$data = $this->Controller->Cookie->read('Encrytped_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);
	}

	function testSettingPlainCookieData() {
		$this->Controller->Cookie->write('Plain_array', array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!'), false);
		$this->Controller->Cookie->write('Plain_multi_cookies.name', 'CakePHP', false);
		$this->Controller->Cookie->write('Plain_multi_cookies.version', '1.2.0.x', false);
		$this->Controller->Cookie->write('Plain_multi_cookies.tag', 'CakePHP Rocks!', false);
	}

	function testReadPlainCookieData() {
		$data = $this->Controller->Cookie->read('Plain_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);
	}

	function testWritePlainCookieArray() {
		$this->Controller->Cookie->write(array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' => 'CakePHP Rocks!'), null, false);

		$this->assertEqual($this->Controller->Cookie->read('name'), 'CakePHP');
		$this->assertEqual($this->Controller->Cookie->read('version'), '1.2.0.x');
		$this->assertEqual($this->Controller->Cookie->read('tag'), 'CakePHP Rocks!');

		$this->Controller->Cookie->del('name');
		$this->Controller->Cookie->del('version');
		$this->Controller->Cookie->del('tag');
	}

	function testReadingCookieValue() {
		$data = $this->Controller->Cookie->read();
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
		$this->assertEqual($data, $expected);
	}

	function testDeleteCookieValue() {
		$this->Controller->Cookie->del('Encrytped_multi_cookies.name');
		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies');
		$expected = array('version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$this->Controller->Cookie->del('Encrytped_array');
		$data = $this->Controller->Cookie->read('Encrytped_array');
		$expected = array();
		$this->assertEqual($data, $expected);

		$this->Controller->Cookie->del('Plain_multi_cookies.name');
		$data = $this->Controller->Cookie->read('Plain_multi_cookies');
		$expected = array('version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$this->Controller->Cookie->del('Plain_array');
		$data = $this->Controller->Cookie->read('Plain_array');
		$expected = array();
		$this->assertEqual($data, $expected);
	}

	function testSettingCookiesWithArray() {
		$this->Controller->Cookie->destroy();

		$this->Controller->Cookie->write(array('Encrytped_array' => array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!')));
		$this->Controller->Cookie->write(array('Encrytped_multi_cookies.name' => 'CakePHP'));
		$this->Controller->Cookie->write(array('Encrytped_multi_cookies.version' => '1.2.0.x'));
		$this->Controller->Cookie->write(array('Encrytped_multi_cookies.tag' => 'CakePHP Rocks!'));

		$this->Controller->Cookie->write(array('Plain_array' => array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!')), null, false);
		$this->Controller->Cookie->write(array('Plain_multi_cookies.name' => 'CakePHP'), null, false);
		$this->Controller->Cookie->write(array('Plain_multi_cookies.version' => '1.2.0.x'), null, false);
		$this->Controller->Cookie->write(array('Plain_multi_cookies.tag' => 'CakePHP Rocks!'), null, false);
	}

	function testReadingCookieArray() {
		$data = $this->Controller->Cookie->read('Encrytped_array.name');
		$expected = 'CakePHP';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_array.version');
		$expected = '1.2.0.x';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_array.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies.name');
		$expected = 'CakePHP';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies.version');
		$expected = '1.2.0.x';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_array.name');
		$expected = 'CakePHP';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_array.version');
		$expected = '1.2.0.x';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_array.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies.name');
		$expected = 'CakePHP';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies.version');
		$expected = '1.2.0.x';
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies.tag');
		$expected = 'CakePHP Rocks!';
		$this->assertEqual($data, $expected);
	}

	function testReadingCookieDataOnStartup() {
		$this->Controller->Cookie->destroy();

		$data = $this->Controller->Cookie->read('Encrytped_array');
		$expected = array();
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies');
		$expected = array();
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_array');
		$expected = array();
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies');
		$expected = array();
		$this->assertEqual($data, $expected);

		$_COOKIE['CakeTestCookie'] = array(
				'Encrytped_array' => 'Q2FrZQ==.y5J8fefUM83X0rdlMjuYFca8ZMYASU/8hM75rHuvjVNHO2WQ+6wK9nkVxm4abxI=',
				'Encrytped_multi_cookies' => array(
						'name' => 'Q2FrZQ==.5pJ6fcvfAg==',
						'version' => 'Q2FrZQ==.lN0jNqu5Kg==',
						'tag' => 'Q2FrZQ==.5pJ6fcvfAobg7ZxebWw='),
				'Plain_array' => 'name|CakePHP,version|1.2.0.x,tag|CakePHP Rocks!',
				'Plain_multi_cookies' => array(
						'name' => 'CakePHP',
						'version' => '1.2.0.x',
						'tag' => 'CakePHP Rocks!'));
		$this->Controller->Cookie->startup();

		$data = $this->Controller->Cookie->read('Encrytped_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);
		$this->Controller->Cookie->destroy();
		unset($_COOKIE['CakeTestCookie']);
	}

	function testReadingCookieDataWithoutStartup() {
		$data = $this->Controller->Cookie->read('Encrytped_array');
		$expected = array();
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies');
		$expected = array();
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_array');
		$expected = array();
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies');
		$expected = array();
		$this->assertEqual($data, $expected);

		$_COOKIE['CakeTestCookie'] = array(
				'Encrytped_array' => 'Q2FrZQ==.y5J8fefUM83X0rdlMjuYFca8ZMYASU/8hM75rHuvjVNHO2WQ+6wK9nkVxm4abxI=',
				'Encrytped_multi_cookies' => array(
						'name' => 'Q2FrZQ==.5pJ6fcvfAg==',
						'version' => 'Q2FrZQ==.lN0jNqu5Kg==',
						'tag' => 'Q2FrZQ==.5pJ6fcvfAobg7ZxebWw='),
				'Plain_array' => 'name|CakePHP,version|1.2.0.x,tag|CakePHP Rocks!',
				'Plain_multi_cookies' => array(
						'name' => 'CakePHP',
						'version' => '1.2.0.x',
						'tag' => 'CakePHP Rocks!'));

		$data = $this->Controller->Cookie->read('Encrytped_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Encrytped_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_array');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);

		$data = $this->Controller->Cookie->read('Plain_multi_cookies');
		$expected = array('name' => 'CakePHP', 'version' => '1.2.0.x', 'tag' =>'CakePHP Rocks!');
		$this->assertEqual($data, $expected);
		$this->Controller->Cookie->destroy();
		unset($_COOKIE['CakeTestCookie']);
	}

	function end() {
		$this->Controller->Cookie->destroy();
	}
}
?>