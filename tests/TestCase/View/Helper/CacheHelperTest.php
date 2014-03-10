<?php
/**
 * CacheHelperTest file
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
namespace Cake\Test\TestCase\View\Helper;

use Cake\Cache\Cache;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Model\Model;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\CacheHelper;
use Cake\View\View;

/**
 * CacheTestController class
 *
 */
class CacheTestController extends Controller {

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Html', 'Cache');

/**
 * cache_parsing method
 *
 * @return void
 */
	public function cache_parsing() {
		$this->viewPath = 'Posts';
		$this->layout = 'cache_layout';
		$this->set('variable', 'variableValue');
		$this->set('superman', 'clark kent');
		$this->set('batman', 'bruce wayne');
		$this->set('spiderman', 'peter parker');
	}

}

/**
 * CacheHelperTest class
 *
 */
class CacheHelperTest extends TestCase {

/**
 * Checks if TMP/views is writable, and skips the case if it is not.
 *
 * @return void
 */
	public function skip() {
		if (!is_writable(TMP . 'cache/views/')) {
			$this->markTestSkipped('TMP/views is not writable.');
		}
	}

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$_GET = [];
		$request = new Request();
		$this->Controller = new CacheTestController($request);
		$View = $this->Controller->createView();
		$this->Cache = new CacheHelper($View);
		Configure::write('Cache.check', true);
		Cache::enable();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		clearCache();
		unset($this->Cache);
		parent::tearDown();
	}

/**
 * test cache parsing with no cake:nocache tags in view file.
 *
 * @return void
 */
	public function testLayoutCacheParsingNoTagsInView() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->request->action = 'cache_parsing';

		$View = $this->Controller->createView();
		$result = $View->render('index');
		$this->assertNotContains('cake:nocache', $result);
		$this->assertNotContains('<?php echo', $result);

		$filename = CACHE . 'views/cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertContains('<?= $variable', $contents);
		$this->assertContains('<?= microtime()', $contents);
		$this->assertContains('clark kent', $result);

		unlink($filename);
	}

/**
 * test cache parsing with non-latin characters in current route
 *
 * @return void
 */
	public function testCacheNonLatinCharactersInRoute() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array('風街ろまん'),
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/posts/view/風街ろまん';
		$this->Controller->action = 'view';

		$View = $this->Controller->createView();
		$View->render('index');

		$filename = CACHE . 'views/posts_view_風街ろまん.php';
		$this->assertTrue(file_exists($filename));

		unlink($filename);
	}

/**
 * Test cache parsing with cake:nocache tags in view file.
 *
 * @return void
 */
	public function testLayoutCacheParsingWithTagsInView() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = $this->Controller->createView();
		$result = $View->render('test_nocache_tags');
		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertRegExp('/if \(is_writable\(TMP\)\)\:/', $contents);
		$this->assertRegExp('/= \$variable/', $contents);
		$this->assertRegExp('/= microtime()/', $contents);
		$this->assertNotRegExp('/cake:nocache/', $contents);

		unlink($filename);
	}

/**
 * test that multiple <!--nocache--> tags function with multiple nocache tags in the layout.
 *
 * @return void
 */
	public function testMultipleNoCacheTagsInViewfile() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->cacheAction = 21600;
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = $this->Controller->createView();
		$result = $View->render('multiple_nocache');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertNotRegExp('/cake:nocache/', $contents);
		unlink($filename);
	}

/**
 * testComplexNoCache method
 *
 * @return void
 */
	public function testComplexNoCache() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_complex',
			'pass' => array(),
		));
		$this->Controller->action = 'cache_complex';
		$this->Controller->request->here = '/cacheTest/cache_complex';
		$this->Controller->cacheAction = array('cache_complex' => 21600);
		$this->Controller->layout = 'multi_cache';
		$this->Controller->viewPath = 'Posts';

		$View = $this->Controller->createView();
		$result = $View->render('sequencial_nocache');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);
		$this->assertRegExp('/A\. Layout Before Content/', $result);
		$this->assertRegExp('/B\. In Plain Element/', $result);
		$this->assertRegExp('/C\. Layout After Test Element/', $result);
		$this->assertRegExp('/D\. In View File/', $result);
		$this->assertRegExp('/E\. Layout After Content/', $result);
		$this->assertRegExp('/F\. In Element With No Cache Tags/', $result);
		$this->assertRegExp('/G\. Layout After Content And After Element With No Cache Tags/', $result);
		$this->assertNotRegExp('/1\. layout before content/', $result);
		$this->assertNotRegExp('/2\. in plain element/', $result);
		$this->assertNotRegExp('/3\. layout after test element/', $result);
		$this->assertNotRegExp('/4\. in view file/', $result);
		$this->assertNotRegExp('/5\. layout after content/', $result);
		$this->assertNotRegExp('/6\. in element with no cache tags/', $result);
		$this->assertNotRegExp('/7\. layout after content and after element with no cache tags/', $result);

		$filename = CACHE . 'views/cachetest_cache_complex.php';
		$this->assertTrue(file_exists($filename));
		$contents = file_get_contents($filename);
		unlink($filename);

		$this->assertRegExp('/A\. Layout Before Content/', $contents);
		$this->assertNotRegExp('/B\. In Plain Element/', $contents);
		$this->assertRegExp('/C\. Layout After Test Element/', $contents);
		$this->assertRegExp('/D\. In View File/', $contents);
		$this->assertRegExp('/E\. Layout After Content/', $contents);
		$this->assertRegExp('/F\. In Element With No Cache Tags/', $contents);
		$this->assertRegExp('/G\. Layout After Content And After Element With No Cache Tags/', $contents);
		$this->assertRegExp('/1\. layout before content/', $contents);
		$this->assertNotRegExp('/2\. in plain element/', $contents);
		$this->assertRegExp('/3\. layout after test element/', $contents);
		$this->assertRegExp('/4\. in view file/', $contents);
		$this->assertRegExp('/5\. layout after content/', $contents);
		$this->assertRegExp('/6\. in element with no cache tags/', $contents);
		$this->assertRegExp('/7\. layout after content and after element with no cache tags/', $contents);
	}

/**
 * test cache of view vars
 *
 * @return void
 */
	public function testCacheViewVars() {
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->cacheAction = 21600;

		$View = $this->Controller->createView();
		$result = $View->render('index');
		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);
		$this->assertRegExp('/\$this\-\>viewVars/', $contents);
		$this->assertRegExp('/extract\(\$this\-\>viewVars, EXTR_SKIP\);/', $contents);
		$this->assertRegExp('/= \$variable/', $contents);

		unlink($filename);
	}

/**
 * Test that callback code is generated correctly.
 *
 * @return void
 */
	public function testCacheCallbacks() {
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->cacheAction = array(
			'cache_parsing' => array(
				'duration' => 21600,
				'callbacks' => true)
		);
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->cache_parsing();

		$View = $this->Controller->createView();
		$View->render('index');

		$filename = CACHE . 'views/cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));

		$contents = file_get_contents($filename);

		$this->assertRegExp('/\$controller->startupProcess\(\);/', $contents);

		unlink($filename);
	}

/**
 * test cacheAction set to a boolean
 *
 * @return void
 */
	public function testCacheActionArray() {
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->request->here = '/cache_test/cache_parsing';
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);

		$this->Controller->cache_parsing();

		$View = $this->Controller->createView();
		$result = $View->render('index');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cache_test_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		unlink($filename);
	}

/**
 * Test that cacheAction works with camelcased controller names.
 *
 * @return void
 */
	public function testCacheActionArrayCamelCase() {
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);
		$this->Controller->request->here = '/cacheTest/cache_parsing';
		$this->Controller->cache_parsing();

		$View = $this->Controller->createView();
		$result = $View->render('index');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cachetest_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		unlink($filename);
	}

/**
 * test with named and pass args.
 *
 * @return void
 */
	public function testCacheWithNamedAndPassedArgs() {
		Router::reload();

		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(1, 2),
		));
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);
		$this->Controller->request->here = '/cache_test/cache_parsing/1/2/name:mark/ice:cream';

		$View = $this->Controller->createView();
		$result = $View->render('index');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cache_test_cache_parsing_1_2_name_mark_ice_cream.php';
		$this->assertTrue(file_exists($filename));
		unlink($filename);
	}

/**
 * Test that query string parameters are included in the cache filename.
 *
 * @return void
 */
	public function testCacheWithQueryStringParams() {
		Router::reload();

		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->request->query = array('q' => 'cakephp');
		$this->Controller->request->here = '/cache_test/cache_parsing';
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);

		$View = $this->Controller->createView();
		$result = $View->render('index');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cache_test_cache_parsing_q_cakephp.php';
		$this->assertTrue(file_exists($filename), 'Missing cache file ' . $filename);
		unlink($filename);
	}

/**
 * test that custom routes are respected when generating cache files.
 *
 * @return void
 */
	public function testCacheWithCustomRoutes() {
		Router::reload();
		Router::connect('/:lang/:controller/:action/*', array(), array('lang' => '[a-z]{3}'));

		$this->Controller->cache_parsing();
		$this->Controller->request->addParams(array(
			'lang' => 'en',
			'controller' => 'cache_test',
			'action' => 'cache_parsing',
			'pass' => array(),
		));
		$this->Controller->cacheAction = array(
			'cache_parsing' => 21600
		);
		$this->Controller->request->here = '/en/cache_test/cache_parsing';
		$this->Controller->action = 'cache_parsing';

		$View = $this->Controller->createView();
		$result = $View->render('index');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/en_cache_test_cache_parsing.php';
		$this->assertTrue(file_exists($filename));
		unlink($filename);
	}

/**
 * test ControllerName contains AppName
 *
 * This test verifies view cache is created correctly when the app name is contained in part of the controller name.
 * (webapp Name) base name is 'cache' controller is 'cacheTest' action is 'cache_name'
 * apps URL would look something like http://localhost/cache/cacheTest/cache_name
 *
 * @return void
 */
	public function testCacheBaseNameControllerName() {
		$this->Controller->cache_parsing();
		$this->Controller->cacheAction = array(
			'cache_name' => 21600
		);
		$request = $this->Controller->request;
		$request->params = array(
			'controller' => 'cacheTest',
			'action' => 'cache_name',
			'pass' => array(),
		);
		$request->here = '/cache/cacheTest/cache_name';
		$request->base = '/cache';

		$View = $this->Controller->createView();
		$result = $View->render('index');

		$this->assertNotRegExp('/cake:nocache/', $result);
		$this->assertNotRegExp('/php echo/', $result);

		$filename = CACHE . 'views/cache_cachetest_cache_name.php';
		$this->assertTrue(file_exists($filename));
		unlink($filename);
	}

/**
 * test that afterRender checks the conditions correctly.
 *
 * @return void
 */
	public function testAfterRenderConditions() {
		Configure::write('Cache.check', true);
		$View = $this->Controller->createView();
		$View->cacheAction = '+1 day';
		$View->assign('content', 'test');

		$Cache = $this->getMock('Cake\View\Helper\CacheHelper', array('_parseContent'), array($View));
		$Cache->expects($this->once())
			->method('_parseContent')
			->with('posts/index', 'content')
			->will($this->returnValue(''));

		$event = $this->getMock('Cake\Event\Event', [], ['View.afterRenderFile']);
		$Cache->afterRenderFile($event, 'posts/index', 'content');
	}

/**
 * test that afterRender checks the conditions correctly.
 *
 * @return void
 */
	public function testAfterLayoutConditions() {
		Configure::write('Cache.check', true);
		$View = $this->Controller->createView();
		$View->cacheAction = '+1 day';
		$View->set('content', 'test');

		$Cache = $this->getMock('Cake\View\Helper\CacheHelper', array('cache'), array($View));
		$Cache->expects($this->once())
			->method('cache')
			->with('posts/index', $View->fetch('content'))
			->will($this->returnValue(''));

		$event = $this->getMock('Cake\Event\Event', [], ['View.afterLayout']);
		$Cache->afterLayout($event, 'posts/index');

		Configure::write('Cache.check', false);
		$Cache->afterLayout($event, 'posts/index');

		Configure::write('Cache.check', true);
		$View->cacheAction = false;
		$Cache->afterLayout($event, 'posts/index');
	}

/**
 * testCacheEmptySections method
 *
 * This test must be uncommented/fixed in next release (1.2+)
 *
 * @return void
 */
	public function testCacheEmptySections() {
		Configure::write('Cache.check', true);
		$this->Controller->cache_parsing();
		$this->Controller->request->addParams([
			'controller' => 'cacheTest',
			'action' => 'cache_empty_sections',
			'pass' => [],
		]);
		$this->Controller->request->here = '/cacheTest/cache_empty_sections';
		$this->Controller->cacheAction = ['cache_empty_sections' => 21600];
		$this->Controller->layout = 'cache_empty_sections';
		$this->Controller->viewPath = 'Posts';

		$View = $this->Controller->createView();
		$result = $View->render('cache_empty_sections');
		$this->assertNotContains('nocache', $result);
		$this->assertNotContains('<?php echo', $result);
		$this->assertRegExp(
			'@</title>\s*</head>\s*' .
			'<body>\s*' .
			'View Content\s*' .
			'cached count is: 3\s*' .
			'</body>@', $result);

		$filename = CACHE . 'views/cachetest_cache_empty_sections.php';
		$this->assertTrue(file_exists($filename));
		$contents = file_get_contents($filename);
		$this->assertNotContains('nocache', $contents);
		$this->assertRegExp(
			'@<head>\s*<title>Posts</title>\s*' .
			'<\?php \$x \= 1; \?>\s*' .
			'</head>\s*' .
			'<body>\s*' .
			'<\?php \$x\+\+; \?>\s*' .
			'<\?php \$x\+\+; \?>\s*' .
			'View Content\s*' .
			'<\?php \$y = 1; \?>\s*' .
			'<\?= \'cached count is: \' . \$x; \?>\s*' .
			'@', $contents);
		unlink($filename);
	}
}
