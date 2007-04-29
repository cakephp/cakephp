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
	uses('router', 'debugger');

	if (!defined('CAKE_ADMIN')) {
		define('CAKE_ADMIN', 'admin');
	}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class RouterTest extends UnitTestCase {

	function setUp() {
		$this->router =& Router::getInstance();
		//$this->router->reload();
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
		$this->router->reload();
		$this->router->__admin = array(
			'/:' . CAKE_ADMIN . '/:controller/:action/*',
			'/^(?:\/(?:(' . CAKE_ADMIN . ')(?:\\/([a-zA-Z0-9_\\-\\.\\;\\:]+)(?:\\/([a-zA-Z0-9_\\-\\.\\;\\:]+)(?:[\\/\\?](.*))?)?)?))[\/]*$/',
			array(CAKE_ADMIN, 'controller', 'action'), array()
		);
		$router2 = new Router();
		$this->assertEqual(get_object_vars($this->router), get_object_vars($router2));
	}

	function testUrlGeneration() {
		extract($this->router->getNamedExpressions());

		$this->router->connect('/', array('controller'=>'pages', 'action'=>'display', 'home'));
		$out = $this->router->url(array('controller'=>'pages', 'action'=>'display', 'home'));
		$this->assertEqual($out, '/');

		$this->router->connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = $this->router->url(array('controller' => 'pages', 'action' => 'display', 'about'));
		$expected = '/pages/about';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'), array('id' => $ID));
		$result = $this->router->url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/cake_plugin/1/';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$expected = '/cake_plugin/1/0';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/:controller/:action/:id', array(), array('id' => $ID));
		$result = $this->router->url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/view/1';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/:controller/:id', array('action' => 'view', 'id' => '1'));
		$result = $this->router->url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/1';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', '0'));
		$expected = '/posts/index/0';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', '0', '?' => 'var=test&var2=test2'));
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', '0', '?' => array('var' => 'test', 'var2' => 'test2')));
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', '0', '?' => 'var=test&var2=test2', '#' => 'unencoded string %'));
		$expected = '/posts/index/0?var=test&var2=test2#unencoded+string+%25';
		$this->assertEqual($result, $expected);
	}

	function testUrlParsing() {
		extract($this->router->getNamedExpressions());

		$this->router->routes = array();
		$this->router->connect('/posts/:value/:somevalue/:othervalue/*', array('controller' => 'posts', 'action' => 'view'), array('value','somevalue', 'othervalue'));
		$result = $this->router->parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('value' => '2007', 'somevalue' => '08', 'othervalue' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = $this->router->parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:day/:year/:month/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = $this->router->parse('/posts/01/2007/08/title-of-post-here');
		$expected = array('day' => '01', 'year' => '2007', 'month' => '08', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:month/:day/:year//*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = $this->router->parse('/posts/08/01/2007/title-of-post-here');
		$expected = array('month' => '08', 'day' => '01', 'year' => '2007', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'));
		$result = $this->router->parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEqual($result, $expected);
	}

	function testAdminRouting() {
		$out = $this->router->url(array(CAKE_ADMIN => true, 'controller' => 'posts', 'action'=>'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/' . CAKE_ADMIN . '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($out, $expected);
	}

	function testExtensionParsingSetting() {
		$router = Router::getInstance();
		$this->router->reload();
		$this->assertFalse($this->router->__parseExtensions);

		$router->parseExtensions();
		$this->assertTrue($this->router->__parseExtensions);
	}

	function testExtensionParsing() {
		$this->router->reload();
		$this->router->parseExtensions();

		$result = $this->router->parse('/posts.rss');
		$expected = array('controller' => 'posts', 'action' => null, 'url' => array ('ext' => 'rss'));
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts/view/1.rss');
		$expected = array('controller' => 'posts', 'action' => 'view', 'pass' => array('1'), 'url' => array('ext' => 'rss'));
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts/view/1.rss?query=test');
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts/view/1.atom');
		$expected['url'] = array('ext' => 'atom');
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parseExtensions('rss', 'xml');

		$result = $this->router->parse('/posts.xml');
		$expected = array('controller' => 'posts', 'action' => null, 'url' => array ('ext' => 'xml'));
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts.atom?hello=goodbye');
		$expected = array('controller' => 'posts.atom', 'action' => null);
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parseExtensions();
		$result = $this->router->__parseExtension('/posts.atom');
		$expected = array('ext' => 'atom', 'url' => '/posts');
		$this->assertEqual($result, $expected);
	}

	function testQuerystringGeneration() {
		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2')));
		$this->assertEqual($result, $expected);

		$expected .= '&more=test+data';
		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2', 'more' => 'test data')));
		$this->assertEqual($result, $expected);
	}

	function testNamedArgsUrlGeneration() {
		Router::setRequestInfo(array(null, array('base' => '/', 'argSeparator' => ':')));

		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', 'published' => 1, 'deleted' => 1));
		$expected = '/posts/index/published:1/deleted:1';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', 'published' => 0, 'deleted' => 0));
		$expected = '/posts/index/published:0/deleted:0';
		$this->assertEqual($result, $expected);
	}
}

?>