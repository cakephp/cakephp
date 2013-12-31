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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('View', 'View');
App::uses('Helper', 'View');
App::uses('Model', 'Model');
App::uses('Router', 'Routing');

/**
 * HelperTestPost class
 *
 * @package       Cake.Test.Case.View
 */
class HelperTestPost extends Model {

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

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
 * @package       Cake.Test.Case.View
 */
class HelperTestComment extends Model {

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

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
 * @package       Cake.Test.Case.View
 */
class HelperTestTag extends Model {

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

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
 * @package       Cake.Test.Case.View
 */
class HelperTestPostsTag extends Model {

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

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
	public $settings = array(
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
 * @package       Cake.Test.Case.View
 */
class HelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		ClassRegistry::flush();
		Router::reload();
		$null = null;
		$this->View = new View($null);
		$this->Helper = new Helper($this->View);
		$this->Helper->request = new CakeRequest(null, false);

		ClassRegistry::addObject('HelperTestPost', new HelperTestPost());
		ClassRegistry::addObject('HelperTestComment', new HelperTestComment());
		ClassRegistry::addObject('HelperTestTag', new HelperTestTag());

		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::delete('Asset');

		CakePlugin::unload();
		unset($this->Helper, $this->View);
	}

/**
 * Provider for setEntity test.
 *
 * @return array
 */
	public static function entityProvider() {
		return array(
			array(
				'HelperTestPost.id',
				array('HelperTestPost', 'id'),
				'HelperTestPost',
				'id'
			),
			array(
				'HelperTestComment.body',
				array('HelperTestComment', 'body'),
				'HelperTestComment',
				'body'
			),
			array(
				'HelperTest.1.Comment.body',
				array('HelperTest', '1', 'Comment', 'body'),
				'Comment',
				'body'
			),
			array(
				'HelperTestComment.BigField',
				array('HelperTestComment', 'BigField'),
				'HelperTestComment',
				'BigField'
			),
			array(
				'HelperTestComment.min',
				array('HelperTestComment', 'min'),
				'HelperTestComment',
				'min'
			)
		);
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
		$this->assertEquals($expected, $Helper->settings);
	}

/**
 * Test setting an entity and retrieving the entity, model and field.
 *
 * @dataProvider entityProvider
 * @return void
 */
	public function testSetEntity($entity, $expected, $modelKey, $fieldKey) {
		$this->Helper->setEntity($entity);
		$this->assertEquals($expected, $this->Helper->entity());
		$this->assertEquals($modelKey, $this->Helper->model());
		$this->assertEquals($fieldKey, $this->Helper->field());
	}

/**
 * test setEntity with setting a scope.
 *
 * @return void
 */
	public function testSetEntityScoped() {
		$this->Helper->setEntity('HelperTestPost', true);
		$this->assertEquals(array('HelperTestPost'), $this->Helper->entity());

		$this->Helper->setEntity('id');
		$expected = array('HelperTestPost', 'id');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('HelperTestComment.body');
		$expected = array('HelperTestComment', 'body');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('body');
		$expected = array('HelperTestPost', 'body');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('2.body');
		$expected = array('HelperTestPost', '2', 'body');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('Something.else');
		$expected = array('Something', 'else');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('HelperTestComment.5.id');
		$expected = array('HelperTestComment', 5, 'id');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('HelperTestComment.id.time');
		$expected = array('HelperTestComment', 'id', 'time');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('HelperTestComment.created.year');
		$expected = array('HelperTestComment', 'created', 'year');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity(null);
		$this->Helper->setEntity('ModelThatDoesntExist.field_that_doesnt_exist');
		$expected = array('ModelThatDoesntExist', 'field_that_doesnt_exist');
		$this->assertEquals($expected, $this->Helper->entity());
	}

/**
 * Test that setEntity() and model()/field() work with associated models.
 *
 * @return void
 */
	public function testSetEntityAssociated() {
		$this->Helper->setEntity('HelperTestPost', true);

		$this->Helper->setEntity('HelperTestPost.1.HelperTestComment.1.title');
		$expected = array('HelperTestPost', '1', 'HelperTestComment', '1', 'title');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->assertEquals('HelperTestComment', $this->Helper->model());
	}

/**
 * Test creating saveMany() compatible entities
 *
 * @return void
 */
	public function testSetEntitySaveMany() {
		$this->Helper->setEntity('HelperTestPost', true);

		$this->Helper->setEntity('0.HelperTestPost.id');
		$expected = array('0', 'HelperTestPost', 'id');
		$this->assertEquals($expected, $this->Helper->entity());
	}

/**
 * Test that setEntity doesn't make CamelCase fields that are not associations an
 * associated model.
 *
 * @return void
 */
	public function testSetEntityAssociatedCamelCaseField() {
		$this->Helper->fieldset = array(
			'HelperTestComment' => array(
				'fields' => array('BigField' => array('type' => 'integer'))
			)
		);
		$this->Helper->setEntity('HelperTestComment', true);
		$this->Helper->setEntity('HelperTestComment.BigField');

		$this->assertEquals('HelperTestComment', $this->Helper->model());
		$this->assertEquals('BigField', $this->Helper->field());
	}

/**
 * Test that multiple fields work when they are camelcase and in fieldset
 *
 * @return void
 */
	public function testSetEntityAssociatedCamelCaseFieldHabtmMultiple() {
		$this->Helper->fieldset = array(
			'HelperTestComment' => array(
				'fields' => array('Tag' => array('type' => 'multiple'))
			)
		);
		$this->Helper->setEntity('HelperTestComment', true);
		$this->Helper->setEntity('Tag');

		$this->assertEquals('Tag', $this->Helper->model());
		$this->assertEquals('Tag', $this->Helper->field());
		$this->assertEquals(array('Tag', 'Tag'), $this->Helper->entity());
	}

/**
 * Test that habtm associations can have property fields created.
 *
 * @return void
 */
	public function testSetEntityHabtmPropertyFieldNames() {
		$this->Helper->fieldset = array(
			'HelperTestComment' => array(
				'fields' => array('Tag' => array('type' => 'multiple'))
			)
		);
		$this->Helper->setEntity('HelperTestComment', true);

		$this->Helper->setEntity('Tag.name');
		$this->assertEquals('Tag', $this->Helper->model());
		$this->assertEquals('name', $this->Helper->field());
		$this->assertEquals(array('Tag', 'name'), $this->Helper->entity());
	}

/**
 * test that 'view' doesn't break things.
 *
 * @return void
 */
	public function testSetEntityWithView() {
		$this->assertNull($this->Helper->setEntity('Allow.view.group_id'));
		$this->assertNull($this->Helper->setEntity('Allow.view'));
		$this->assertNull($this->Helper->setEntity('View.view'));
	}

/**
 * test getting values from Helper
 *
 * @return void
 */
	public function testValue() {
		$this->Helper->request->data = array('fullname' => 'This is me');
		$this->Helper->setEntity('fullname');
		$result = $this->Helper->value('fullname');
		$this->assertEquals('This is me', $result);

		$this->Helper->request->data = array(
			'Post' => array('name' => 'First Post')
		);
		$this->Helper->setEntity('Post.name');
		$result = $this->Helper->value('Post.name');
		$this->assertEquals('First Post', $result);

		$this->Helper->request->data = array(
			'Post' => array(2 => array('name' => 'First Post'))
		);
		$this->Helper->setEntity('Post.2.name');
		$result = $this->Helper->value('Post.2.name');
		$this->assertEquals('First Post', $result);

		$this->Helper->request->data = array(
			'Post' => array(
				2 => array('created' => array('year' => '2008'))
			)
		);
		$this->Helper->setEntity('Post.2.created');
		$result = $this->Helper->value('Post.2.created');
		$this->assertEquals(array('year' => '2008'), $result);

		$this->Helper->request->data = array(
			'Post' => array(
				2 => array('created' => array('year' => '2008'))
			)
		);
		$this->Helper->setEntity('Post.2.created.year');
		$result = $this->Helper->value('Post.2.created.year');
		$this->assertEquals('2008', $result);
	}

/**
 * Test default values with value()
 *
 * @return void
 */
	public function testValueWithDefault() {
		$this->Helper->request->data = array('zero' => 0);
		$this->Helper->setEntity('zero');
		$result = $this->Helper->value(array('default' => 'something'), 'zero');
		$this->assertEquals(array('value' => 0), $result);

		$this->Helper->request->data = array('zero' => '0');
		$result = $this->Helper->value(array('default' => 'something'), 'zero');
		$this->assertEquals(array('value' => '0'), $result);

		$this->Helper->setEntity('inexistent');
		$result = $this->Helper->value(array('default' => 'something'), 'inexistent');
		$this->assertEquals(array('value' => 'something'), $result);
	}

/**
 * Test habtm data fetching and ensure no pollution happens.
 *
 * @return void
 */
	public function testValueHabtmKeys() {
		$this->Helper->request->data = array(
			'HelperTestTag' => array('HelperTestTag' => '')
		);
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEquals('', $result);

		$this->Helper->request->data = array(
			'HelperTestTag' => array(
				'HelperTestTag' => array(2, 3, 4)
			)
		);
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEquals(array(2, 3, 4), $result);

		$this->Helper->request->data = array(
			'HelperTestTag' => array(
				array('id' => 3),
				array('id' => 5)
			)
		);
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEquals(array(3 => 3, 5 => 5), $result);

		$this->Helper->request->data = array(
			'HelperTestTag' => array(
				'body' => '',
				'title' => 'winning'
			),
		);
		$this->Helper->setEntity('HelperTestTag.body');
		$result = $this->Helper->value('HelperTestTag.body');
		$this->assertEquals('', $result);
	}

/**
 * Ensure HTML escaping of URL params. So link addresses are valid and not exploited
 *
 * @return void
 */
	public function testUrlConversion() {
		$result = $this->Helper->url('/controller/action/1');
		$this->assertEquals('/controller/action/1', $result);

		$result = $this->Helper->url('/controller/action/1?one=1&two=2');
		$this->assertEquals('/controller/action/1?one=1&amp;two=2', $result);

		$result = $this->Helper->url(array('controller' => 'posts', 'action' => 'index', 'page' => '1" onclick="alert(\'XSS\');"'));
		$this->assertEquals("/posts/index/page:1%22%20onclick%3D%22alert%28%27XSS%27%29%3B%22", $result);

		$result = $this->Helper->url('/controller/action/1/param:this+one+more');
		$this->assertEquals('/controller/action/1/param:this+one+more', $result);

		$result = $this->Helper->url('/controller/action/1/param:this%20one%20more');
		$this->assertEquals('/controller/action/1/param:this%20one%20more', $result);

		$result = $this->Helper->url('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');
		$this->assertEquals('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24', $result);

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24'
		));
		$this->assertEquals("/posts/index/param:%257Baround%2520here%257D%255Bthings%255D%255Bare%255D%2524%2524", $result);

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'page' => '1',
			'?' => array('one' => 'value', 'two' => 'value', 'three' => 'purple')
		));
		$this->assertEquals("/posts/index/page:1?one=value&amp;two=value&amp;three=purple", $result);
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
		Configure::write('debug', 0);

		$result = $this->Helper->assetTimestamp('/%3Cb%3E/cake.generic.css');
		$this->assertEquals('/%3Cb%3E/cake.generic.css', $result);

		$result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
		$this->assertEquals(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

		Configure::write('Asset.timestamp', true);
		Configure::write('debug', 2);
		$result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
		$this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		Configure::write('Asset.timestamp', 'force');
		Configure::write('debug', 0);
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
		$expected = FULL_BASE_URL . '/cake_dev/app/webroot/img/cake.icon.png';
		$this->assertEquals($expected, $result);
	}

/**
 * Test assetUrl with plugins.
 *
 * @return void
 */
	public function testAssetUrlPlugin() {
		$this->Helper->webroot = '';
		CakePlugin::load('TestPlugin');

		$result = $this->Helper->assetUrl('TestPlugin.style', array('ext' => '.css'));
		$this->assertEquals('test_plugin/style.css', $result);

		$result = $this->Helper->assetUrl('TestPlugin.style', array('ext' => '.css', 'plugin' => false));
		$this->assertEquals('TestPlugin.style.css', $result);

		CakePlugin::unload('TestPlugin');
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
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
		));
		CakePlugin::load(array('TestPlugin'));

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
 * testFieldsWithSameName method
 *
 * @return void
 */
	public function testFieldsWithSameName() {
		$this->Helper->setEntity('HelperTestTag', true);

		$this->Helper->setEntity('HelperTestTag.id');
		$expected = array('HelperTestTag', 'id');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('My.id');
		$expected = array('My', 'id');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('MyOther.id');
		$expected = array('MyOther', 'id');
		$this->assertEquals($expected, $this->Helper->entity());
	}

/**
 * testFieldSameAsModel method
 *
 * @return void
 */
	public function testFieldSameAsModel() {
		$this->Helper->setEntity('HelperTestTag', true);

		$this->Helper->setEntity('helper_test_post');
		$expected = array('HelperTestTag', 'helper_test_post');
		$this->assertEquals($expected, $this->Helper->entity());

		$this->Helper->setEntity('HelperTestTag');
		$expected = array('HelperTestTag', 'HelperTestTag');
		$this->assertEquals($expected, $this->Helper->entity());
	}

/**
 * testFieldSuffixForDate method
 *
 * @return void
 */
	public function testFieldSuffixForDate() {
		$this->Helper->setEntity('HelperTestPost', true);
		$expected = array('HelperTestPost');
		$this->assertEquals($expected, $this->Helper->entity());

		foreach (array('year', 'month', 'day', 'hour', 'min', 'meridian') as $d) {
			$this->Helper->setEntity('date.' . $d);
			$expected = array('HelperTestPost', 'date', $d);
			$this->assertEquals($expected, $this->Helper->entity());
		}
	}

/**
 * testMulitDimensionValue method
 *
 * @return void
 */
	public function testMultiDimensionValue() {
		$this->Helper->data = array();
		for ($i = 0; $i < 2; $i++) {
			$this->Helper->request->data['Model'][$i] = 'what';
			$result[] = $this->Helper->value("Model.{$i}");
			$this->Helper->request->data['Model'][$i] = array();
			for ($j = 0; $j < 2; $j++) {
				$this->Helper->request->data['Model'][$i][$j] = 'how';
				$result[] = $this->Helper->value("Model.{$i}.{$j}");
			}
		}
		$expected = array('what', 'how', 'how', 'what', 'how', 'how');
		$this->assertEquals($expected, $result);

		$this->Helper->request->data['HelperTestComment']['5']['id'] = 'ok';
		$result = $this->Helper->value('HelperTestComment.5.id');
		$this->assertEquals('ok', $result);

		$this->Helper->setEntity('HelperTestPost', true);
		$this->Helper->request->data['HelperTestPost']['5']['created']['month'] = '10';
		$result = $this->Helper->value('5.created.month');
		$this->assertEquals(10, $result);

		$this->Helper->request->data['HelperTestPost']['0']['id'] = 100;
		$result = $this->Helper->value('HelperTestPost.0.id');
		$this->assertEquals(100, $result);
	}

/**
 * testClean method
 *
 * @return void
 */
	public function testClean() {
		$result = $this->Helper->clean(array());
		$this->assertEquals(null, $result);

		$result = $this->Helper->clean(array('<script>with something</script>', '<applet>something else</applet>'));
		$this->assertEquals(array('with something', 'something else'), $result);

		$result = $this->Helper->clean('<script>with something</script>');
		$this->assertEquals('with something', $result);

		$result = $this->Helper->clean('<script type="text/javascript">alert("ruined");</script>');
		$this->assertNotRegExp('#</*script#', $result);

		$result = $this->Helper->clean("<script \ntype=\"text/javascript\">\n\talert('ruined');\n\n\t\t</script>");
		$this->assertNotRegExp('#</*script#', $result);

		$result = $this->Helper->clean('<body/onload=do(/something/)>');
		$this->assertEquals('<body/>', $result);

		$result = $this->Helper->clean('&lt;script&gt;alert(document.cookie)&lt;/script&gt;');
		$this->assertEquals('&amp;lt;script&amp;gt;alert(document.cookie)&amp;lt;/script&amp;gt;', $result);
	}

/**
 * testDomId method
 *
 * @return void
 */
	public function testDomId() {
		$result = $this->Helper->domId('Foo.bar');
		$this->assertEquals('FooBar', $result);
	}

/**
 * testMultiDimensionalField method
 *
 * @return void
 */
	public function testMultiDimensionalField() {
		$this->Helper->setEntity('HelperTestPost', true);

		$entity = 'HelperTestPost.2.HelperTestComment.1.title';
		$this->Helper->setEntity($entity);
		$expected = array(
			'HelperTestPost', '2', 'HelperTestComment', '1', 'title'
		);
		$this->assertEquals($expected, $this->Helper->entity());

		$entity = 'HelperTestPost.1.HelperTestComment.1.HelperTestTag.1.created';
		$this->Helper->setEntity($entity);
		$expected = array(
			'HelperTestPost', '1', 'HelperTestComment', '1',
			'HelperTestTag', '1', 'created'
		);
		$this->assertEquals($expected, $this->Helper->entity());

		$entity = 'HelperTestPost.0.HelperTestComment.1.HelperTestTag.1.fake';
		$expected = array(
			'HelperTestPost', '0', 'HelperTestComment', '1',
			'HelperTestTag', '1', 'fake'
		);
		$this->Helper->setEntity($entity);

		$entity = '1.HelperTestComment.1.HelperTestTag.created.year';
		$this->Helper->setEntity($entity);

		$this->Helper->request->data['HelperTestPost'][2]['HelperTestComment'][1]['title'] = 'My Title';
		$result = $this->Helper->value('HelperTestPost.2.HelperTestComment.1.title');
		$this->assertEquals('My Title', $result);

		$this->Helper->request->data['HelperTestPost'][2]['HelperTestComment'][1]['created']['year'] = 2008;
		$result = $this->Helper->value('HelperTestPost.2.HelperTestComment.1.created.year');
		$this->assertEquals(2008, $result);

		$this->Helper->request->data[2]['HelperTestComment'][1]['created']['year'] = 2008;
		$result = $this->Helper->value('HelperTestPost.2.HelperTestComment.1.created.year');
		$this->assertEquals(2008, $result);

		$this->Helper->request->data['HelperTestPost']['title'] = 'My Title';
		$result = $this->Helper->value('title');
		$this->assertEquals('My Title', $result);

		$this->Helper->request->data['My']['title'] = 'My Title';
		$result = $this->Helper->value('My.title');
		$this->assertEquals('My Title', $result);
	}

	public function testWebrootPaths() {
		$this->Helper->request->webroot = '/';
		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/img/cake.power.gif';
		$this->assertEquals($expected, $result);

		$this->Helper->theme = 'test_theme';

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));

		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/theme/test_theme/img/cake.power.gif';
		$this->assertEquals($expected, $result);

		$result = $this->Helper->webroot('/img/test.jpg');
		$expected = '/theme/test_theme/img/test.jpg';
		$this->assertEquals($expected, $result);

		$webRoot = Configure::read('App.www_root');
		Configure::write('App.www_root', CAKE . 'Test' . DS . 'test_app' . DS . 'webroot' . DS);

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
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		));
		CakePlugin::load(array('TestPlugin'));
		$Helper = new TestHelper($this->View);
		$this->assertInstanceOf('OtherHelperHelper', $Helper->OtherHelper);
		$this->assertInstanceOf('HtmlHelper', $Helper->Html);
		App::build();
	}

/**
 * test that a helpers Helper is not 'attached' to the collection
 *
 * @return void
 */
	public function testThatHelperHelpersAreNotAttached() {
		$Helper = new TestHelper($this->View);
		$Helper->OtherHelper;

		$result = $this->View->Helpers->enabled();
		$this->assertEquals(array(), $result, 'Helper helpers were attached to the collection.');
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
