<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Dispatcher;
use Cake\Routing\Filter\CacheFilter;
use Cake\Routing\Filter\ControllerFactoryFilter;
use Cake\Routing\Filter\RoutingFilter;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Inflector;

/**
 * CacheFilterTest class
 *
 */
class CacheFilterTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$_GET = [];

		Configure::write('App.base', false);
		Configure::write('App.baseUrl', false);
		Configure::write('App.dir', 'app');
		Configure::write('App.webroot', 'webroot');
		Configure::write('App.namespace', 'TestApp');
	}

/**
 * Data provider for cached actions.
 *
 * - Test simple views
 * - Test views with nocache tags
 * - Test requests with named + passed params.
 * - Test requests with query string params
 * - Test themed views.
 *
 * @return array
 */
	public static function cacheActionProvider() {
		return array(
			array('/'),
			array('test_cached_pages/index'),
			array('TestCachedPages/index'),
			array('test_cached_pages/test_nocache_tags'),
			array('TestCachedPages/test_nocache_tags'),
			array('test_cached_pages/view/param/param'),
			array('test_cached_pages/view?q=cakephp'),
			array('test_cached_pages/themed'),
		);
	}

/**
 * testFullPageCachingDispatch method
 *
 * @dataProvider cacheActionProvider
 * @return void
 */
	public function testFullPageCachingDispatch($url) {
		Cache::enable();
		Configure::write('Cache.disable', false);
		Configure::write('Cache.check', true);
		Configure::write('debug', true);

		Router::reload();
		Router::connect('/', array('controller' => 'test_cached_pages', 'action' => 'index'));
		Router::connect('/:controller/:action/*');

		$dispatcher = new Dispatcher();
		$dispatcher->addFilter(new RoutingFilter());
		$dispatcher->addFilter(new ControllerFactoryFilter());
		$request = new Request($url);
		$response = $this->getMock('Cake\Network\Response', array('send'));

		$dispatcher->dispatch($request, $response);
		$out = $response->body();

		$request = new Request($url);
		$response = $this->getMock('Cake\Network\Response', array('send'));
		$dispatcher = new Dispatcher();
		$dispatcher->addFilter(new RoutingFilter());
		$dispatcher->addFilter(new CacheFilter());
		$dispatcher->addFilter(new ControllerFactoryFilter());
		$dispatcher->dispatch($request, $response);
		$cached = $response->body();

		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);

		$this->assertTextEquals($out, $cached);

		$filename = $this->_cachePath($request->here());
		unlink($filename);
	}

/**
 * cachePath method
 *
 * @param string $here
 * @return string
 */
	protected function _cachePath($here) {
		$path = $here;
		if ($here === '/') {
			$path = 'home';
		}
		$path = strtolower(Inflector::slug($path));

		$filename = CACHE . 'views/' . $path . '.php';

		if (!file_exists($filename)) {
			$filename = CACHE . 'views/' . $path . '_index.php';
		}
		return $filename;
	}

}
