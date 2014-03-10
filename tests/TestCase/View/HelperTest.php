<?php
/**
 * HelperTest file
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
namespace Cake\Test\TestCase\View;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;
use Cake\View\View;

/**
 * HelperTestPost class
 *
 */
class HelperTestPostsTable extends Table {

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'title' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
			'body' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => ''),
			'number' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'date' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('HelperTestTag' => array('with' => 'HelperTestPostsTag'));
}

/**
 * HelperTestComment class
 *
 */
class HelperTestCommentsTable extends Table {

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'author_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'title' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
			'body' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => ''),
			'BigField' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => ''),
			'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

}

/**
 * HelperTestTag class
 *
 */
class HelperTestTagsTable extends Table {

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
			'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}

}

/**
 * HelperTestPostsTag class
 *
 */
class HelperTestPostsTagsTable extends Table {

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		$this->_schema = array(
			'helper_test_post_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'helper_test_tag_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
		);
		return $this->_schema;
	}

}

class TestHelper extends Helper {

/**
 * Settings for this helper.
 *
 * @var array
 */
	protected $_defaultConfig = array(
		'key1' => 'val1',
		'key2' => array('key2.1' => 'val2.1', 'key2.2' => 'val2.2')
	);

/**
 * Helpers for this helper.
 *
 * @var array
 */
	public $helpers = array('Html', 'TestPlugin.OtherHelper');

/**
 * expose a method as public
 *
 * @param string $options
 * @param string $exclude
 * @param string $insertBefore
 * @param string $insertAfter
 * @return void
 */
	public function parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		return $this->_parseAttributes($options, $exclude, $insertBefore, $insertAfter);
	}

}

/**
 * HelperTest class
 *
 */
class HelperTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Router::reload();
		$this->View = new View();
		$this->Helper = new Helper($this->View);
		$this->Helper->request = new Request();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::delete('Asset');

		Plugin::unload();
		unset($this->Helper, $this->View);
	}

/**
 * Test settings merging
 *
 * @return void
 */
	public function testSettingsMerging() {
		$Helper = new TestHelper($this->View, array(
			'key3' => 'val3',
			'key2' => array('key2.2' => 'newval')
		));
		$expected = array(
			'key1' => 'val1',
			'key2' => array('key2.1' => 'val2.1', 'key2.2' => 'newval'),
			'key3' => 'val3'
		);
		$this->assertEquals($expected, $Helper->config());
	}

/**
 * Ensure HTML escaping of URL params. So link addresses are valid and not exploited
 *
 * @return void
 */
	public function testUrlConversion() {
		Router::connect('/:controller/:action/*');

		$result = $this->Helper->url('/controller/action/1');
		$this->assertEquals('/controller/action/1', $result);

		$result = $this->Helper->url('/controller/action/1?one=1&two=2');
		$this->assertEquals('/controller/action/1?one=1&amp;two=2', $result);

		$result = $this->Helper->url(array('controller' => 'posts', 'action' => 'index', 'page' => '1" onclick="alert(\'XSS\');"'));
		$this->assertEquals("/posts/index?page=1%22+onclick%3D%22alert%28%27XSS%27%29%3B%22", $result);

		$result = $this->Helper->url('/controller/action/1/param:this+one+more');
		$this->assertEquals('/controller/action/1/param:this+one+more', $result);

		$result = $this->Helper->url('/controller/action/1/param:this%20one%20more');
		$this->assertEquals('/controller/action/1/param:this%20one%20more', $result);

		$result = $this->Helper->url('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');
		$this->assertEquals('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24', $result);

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24'
		));
		$this->assertEquals("/posts/index?param=%257Baround%2520here%257D%255Bthings%255D%255Bare%255D%2524%2524", $result);

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'page' => '1',
			'?' => array('one' => 'value', 'two' => 'value', 'three' => 'purple')
		));
		$this->assertEquals("/posts/index?page=1&amp;one=value&amp;two=value&amp;three=purple", $result);
	}

/**
 * test assetTimestamp application
 *
 * @return void
 */
	public function testAssetTimestamp() {
		Configure::write('Foo.bar', 'test');
		Configure::write('Asset.timestamp', false);
		$result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
		$this->assertEquals(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

		Configure::write('Asset.timestamp', true);
		Configure::write('debug', false);

		$result = $this->Helper->assetTimestamp('/%3Cb%3E/cake.generic.css');
		$this->assertEquals('/%3Cb%3E/cake.generic.css', $result);

		$result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
		$this->assertEquals(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

		Configure::write('Asset.timestamp', true);
		Configure::write('debug', true);
		$result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
		$this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		Configure::write('Asset.timestamp', 'force');
		Configure::write('debug', false);
		$result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
		$this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		$result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam');
		$this->assertEquals(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam', $result);

		$this->Helper->request->webroot = '/some/dir/';
		$result = $this->Helper->assetTimestamp('/some/dir/' . Configure::read('App.cssBaseUrl') . 'cake.generic.css');
		$this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
	}

/**
 * test assetUrl application
 *
 * @return void
 */
	public function testAssetUrl() {
		Router::connect('/:controller/:action/*');

		$this->Helper->webroot = '';
		$result = $this->Helper->assetUrl(array(
				'controller' => 'js',
				'action' => 'post',
				'ext' => 'js'
			),
			array('fullBase' => true)
		);
		$this->assertEquals(Router::fullBaseUrl() . '/js/post.js', $result);

		$result = $this->Helper->assetUrl('foo.jpg', array('pathPrefix' => 'img/'));
		$this->assertEquals('img/foo.jpg', $result);

		$result = $this->Helper->assetUrl('foo.jpg', array('fullBase' => true));
		$this->assertEquals(Router::fullBaseUrl() . '/foo.jpg', $result);

		$result = $this->Helper->assetUrl('style', array('ext' => '.css'));
		$this->assertEquals('style.css', $result);

		$result = $this->Helper->assetUrl('dir/sub dir/my image', array('ext' => '.jpg'));
		$this->assertEquals('dir/sub%20dir/my%20image.jpg', $result);

		$result = $this->Helper->assetUrl('foo.jpg?one=two&three=four');
		$this->assertEquals('foo.jpg?one=two&amp;three=four', $result);

		$result = $this->Helper->assetUrl('dir/big+tall/image', array('ext' => '.jpg'));
		$this->assertEquals('dir/big%2Btall/image.jpg', $result);
	}

/**
 * Test assetUrl with no rewriting.
 *
 * @return void
 */
	public function testAssetUrlNoRewrite() {
		$this->Helper->request->addPaths(array(
			'base' => '/cake_dev/index.php',
			'webroot' => '/cake_dev/app/webroot/',
			'here' => '/cake_dev/index.php/tasks',
		));
		$result = $this->Helper->assetUrl('img/cake.icon.png', array('fullBase' => true));
		$expected = Configure::read('App.fullBaseUrl') . '/cake_dev/app/webroot/img/cake.icon.png';
		$this->assertEquals($expected, $result);
	}

/**
 * Test assetUrl with plugins.
 *
 * @return void
 */
	public function testAssetUrlPlugin() {
		$this->Helper->webroot = '';
		Plugin::load('TestPlugin');

		$result = $this->Helper->assetUrl('TestPlugin.style', array('ext' => '.css'));
		$this->assertEquals('test_plugin/style.css', $result);

		$result = $this->Helper->assetUrl('TestPlugin.style', array('ext' => '.css', 'plugin' => false));
		$this->assertEquals('TestPlugin.style.css', $result);

		Plugin::unload('TestPlugin');
	}

/**
 * test assetUrl and Asset.timestamp = force
 *
 * @return void
 */
	public function testAssetUrlTimestampForce() {
		$this->Helper->webroot = '';
		Configure::write('Asset.timestamp', 'force');

		$result = $this->Helper->assetUrl('cake.generic.css', array('pathPrefix' => Configure::read('App.cssBaseUrl')));
		$this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
	}

/**
 * test assetTimestamp with plugins and themes
 *
 * @return void
 */
	public function testAssetTimestampPluginsAndThemes() {
		Configure::write('Asset.timestamp', 'force');
		Plugin::load(array('TestPlugin'));

		$result = $this->Helper->assetTimestamp('/test_plugin/css/test_plugin_asset.css');
		$this->assertRegExp('#/test_plugin/css/test_plugin_asset.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

		$result = $this->Helper->assetTimestamp('/test_plugin/css/i_dont_exist.css');
		$this->assertRegExp('#/test_plugin/css/i_dont_exist.css\?$#', $result, 'No error on missing file');

		$result = $this->Helper->assetTimestamp('/theme/test_theme/js/theme.js');
		$this->assertRegExp('#/theme/test_theme/js/theme.js\?[0-9]+$#', $result, 'Missing timestamp theme');

		$result = $this->Helper->assetTimestamp('/theme/test_theme/js/non_existant.js');
		$this->assertRegExp('#/theme/test_theme/js/non_existant.js\?$#', $result, 'No error on missing file');
	}

/**
 * Test generating paths with webroot().
 *
 * @return void
 */
	public function testWebrootPaths() {
		$this->Helper->request->webroot = '/';
		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/img/cake.power.gif';
		$this->assertEquals($expected, $result);

		$this->Helper->theme = 'test_theme';

		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/theme/test_theme/img/cake.power.gif';
		$this->assertEquals($expected, $result);

		$result = $this->Helper->webroot('/img/test.jpg');
		$expected = '/theme/test_theme/img/test.jpg';
		$this->assertEquals($expected, $result);

		$webRoot = Configure::read('App.www_root');
		Configure::write('App.www_root', TEST_APP . 'TestApp/webroot/');

		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/theme/test_theme/img/cake.power.gif';
		$this->assertEquals($expected, $result);

		$result = $this->Helper->webroot('/img/test.jpg');
		$expected = '/theme/test_theme/img/test.jpg';
		$this->assertEquals($expected, $result);

		$result = $this->Helper->webroot('/img/cake.icon.gif');
		$expected = '/img/cake.icon.gif';
		$this->assertEquals($expected, $result);

		$result = $this->Helper->webroot('/img/cake.icon.gif?some=param');
		$expected = '/img/cake.icon.gif?some=param';
		$this->assertEquals($expected, $result);

		Configure::write('App.www_root', $webRoot);
	}

/**
 * test lazy loading helpers is seamless
 *
 * @return void
 */
	public function testLazyLoadingHelpers() {
		Plugin::load(array('TestPlugin'));

		$Helper = new TestHelper($this->View);
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $Helper->OtherHelper);
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $Helper->Html);
	}

/**
 * test that a helpers Helper is not 'attached' to the collection
 *
 * @return void
 */
	public function testThatHelperHelpersAreNotAttached() {
		Plugin::loadAll();

		$events = $this->getMock('\Cake\Event\EventManager');
		$this->View->setEventManager($events);

		$events->expects($this->never())
			->method('attach');

		$Helper = new TestHelper($this->View);
		$Helper->OtherHelper;
	}

/**
 * test that the lazy loader doesn't duplicate objects on each access.
 *
 * @return void
 */
	public function testLazyLoadingUsesReferences() {
		$Helper = new TestHelper($this->View);
		$resultA = $Helper->Html;
		$resultB = $Helper->Html;

		$resultA->testprop = 1;
		$this->assertEquals($resultA->testprop, $resultB->testprop);
	}

}
