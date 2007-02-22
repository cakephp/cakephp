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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	require_once LIBS.'router.php';
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class RouterTest extends UnitTestCase {

	function setUp() {
		$this->router =& Router::getInstance();
	}

	function testReturnedInstanceReference() {
		$this->router->testVar = 'test';
		$this->assertIdentical($this->router, Router::getInstance());
		unset($this->router->testVar);
	}

	function testRouteWriting() {
		$this->router->reload();
		$this->router->connect('/');
		$this->assertEqual($this->router->routes[0][0], '/');
		$this->assertEqual($this->router->routes[0][1], '/^[\/]*$/');
		$this->assertEqual($this->router->routes[0][2], array());

		$this->router->routes = array();
		$this->router->connect('/', array('controller' => 'testing'));
		$this->assertTrue(is_array($this->router->routes[0][3]) && !empty($this->router->routes[0][3]));
		$this->assertEqual($this->router->routes[0][3]['controller'], 'testing');
		$this->assertEqual($this->router->routes[0][3]['action'], 'index');
		$this->assertEqual(count($this->router->routes[0][3]), 3);

		$this->router->routes = array();
		$this->router->connect('/:controller', array('controller' => 'testing2'));
		$this->assertTrue(is_array($this->router->routes[0][3]) && !empty($this->router->routes[0][3]), '/');
		$this->assertEqual($this->router->routes[0][3]['controller'], 'testing2');
		$this->assertEqual($this->router->routes[0][3]['action'], 'index');
		$this->assertEqual(count($this->router->routes[0][3]), 3);

		$this->router->routes = array();
		$this->router->connect('/:controller/:action', array('controller' => 'testing3'));
		$this->assertEqual($this->router->routes[0][0], '/:controller/:action');
		$this->assertEqual($this->router->routes[0][1], '#^(?:\/([^\/]+))?(?:\/([^\/]+))?[\/]*$#');
		$this->assertEqual($this->router->routes[0][2], array('controller', 'action'));
		$this->assertEqual($this->router->routes[0][3], array('controller' => 'testing3', 'action' => 'index', 'plugin' => null));

		$this->router->routes = array();
		$this->router->connect('/:controller/:action/:id', array('controller' => 'testing4', 'id' => null), array('id' => $this->router->__named['ID']));
		$this->assertEqual($this->router->routes[0][0], '/:controller/:action/:id');
		$this->assertEqual($this->router->routes[0][1], '#^(?:\/([^\/]+))?(?:\/([^\/]+))?(?:\/([0-9]+))?[\/]*$#');
		$this->assertEqual($this->router->routes[0][2], array('controller', 'action', 'id'));

		$this->router->routes = array();
		$this->router->connect('/:controller/:action/:id', array('controller' => 'testing4'), array('id' => $this->router->__named['ID']));
		$this->assertEqual($this->router->routes[0][1], '#^(?:\/([^\/]+))?(?:\/([^\/]+))?(?:\/([0-9]+))[\/]*$#');
	}

	function testRouterIdentity() {
		$router =& Router::getInstance();
		$this->vars = get_object_vars($router);

		$this->router->routes = $this->router->__paths = $this->router->__params = $this->router->__currentRoute = array();
		$this->router->__parseExtensions = false;
		$router2 = new Router();
		$this->assertEqual(get_object_vars($router), get_object_vars($router2));
	}

	function testUrlGeneration() {
		$router =& Router::getInstance();
		foreach ($this->vars as $var => $val) {
			$this->router->{$var} = $val;
		}
		$this->router->routes = array();

		$this->router->connect('/', array('controller'=>'pages', 'action'=>'display', 'home'));
		$out = $this->router->url(array('controller'=>'pages', 'action'=>'display', 'home'));
		$this->assertEqual($out, '/');

		$this->router->connect('/pages/*', array('controller'=>'pages', 'action'=>'display'));
		$out = $this->router->url(array('controller'=>'pages', 'action'=>'display', 'about'));
		$expected = '/pages/about';
		$this->assertEqual($out, $expected);


		$this->router->connect('/:plugin/:controller/*', array('plugin'=>'cake_plugin', 'controller'=>'posts', 'action'=>'view', '1'));
		$out = $this->router->url(array('plugin'=>'cake_plugin', 'controller'=>'posts', '1'));
		$expected = '/cake_plugin/posts/';
		$this->assertEqual($out, $expected);

		$this->router->connect('/:controller/:action/:id', array(), array('id' => '1'));
		$out = $this->router->url(array('controller'=>'posts', 'action'=>'view', '1'));
		$expected = '/posts/view/1';
		$this->assertEqual($out, $expected);

		$this->router->connect('/:controller/:id', array('action' => 'view'), array('id' => '1'));
		$out = $this->router->url(array('controller'=>'posts', '1'));
		$expected = '/posts/1';
		$this->assertEqual($out, $expected);

		$out = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0'));
		$expected = '/posts/index/0';
		$this->assertEqual($out, $expected);
	}

	function testExtensionParsingSetting() {
		if (PHP5) {
			$router = Router::getInstance();
			$this->router->reload();
			$this->assertFalse($this->router->__parseExtensions);

			$this->router->parseExtensions();
			$this->assertTrue($this->router->__parseExtensions);
		}
	}
}
?>