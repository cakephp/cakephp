<?php
/**
 * HelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * @var bool false
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
 * @var bool false
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
 * @var bool false
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
 * @var bool false
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
 * Helpers for this helper.
 *
 * @var string
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
		ClassRegistry::flush();
		Router::reload();
		$null = null;
		$this->View = new View($null);
		$this->Helper = new Helper($this->View);
		$this->Helper->request = new CakeRequest(null, false);

		ClassRegistry::addObject('HelperTestPost', new HelperTestPost());
		ClassRegistry::addObject('HelperTestComment', new HelperTestComment());
		ClassRegistry::addObject('HelperTestTag', new HelperTestTag());
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		CakePlugin::unload();
		unset($this->Helper, $this->View);
		ClassRegistry::flush();
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
 * Test setting an entity and retriving the entity, model and field.
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
 * @return
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
		$this->assertEquals($result, 'This is me');

		$this->Helper->request->data = array(
			'Post' => array('name' => 'First Post')
		);
		$this->Helper->setEntity('Post.name');
		$result = $this->Helper->value('Post.name');
		$this->assertEquals($result, 'First Post');

		$this->Helper->request->data = array(
			'Post' => array(2 => array('name' => 'First Post'))
		);
		$this->Helper->setEntity('Post.2.name');
		$result = $this->Helper->value('Post.2.name');
		$this->assertEquals($result, 'First Post');

		$this->Helper->request->data = array(
			'Post' => array(
				2 => array('created' => array('year' => '2008'))
			)
		);
		$this->Helper->setEntity('Post.2.created');
		$result = $this->Helper->value('Post.2.created');
		$this->assertEquals($result, array('year' => '2008'));

		$this->Helper->request->data = array(
			'Post' => array(
				2 => array('created' => array('year' => '2008'))
			)
		);
		$this->Helper->setEntity('Post.2.created.year');
		$result = $this->Helper->value('Post.2.created.year');
		$this->assertEquals($result, '2008');
	}

/**
 * Test default values with value()
 *
 * @return void
 */
	function testValueWithDefault() {
		$this->Helper->request->data = array('zero' => 0);
		$this->Helper->setEntity('zero');
		$result = $this->Helper->value(array('default' => 'something'), 'zero');
		$this->assertEquals($result, array('value' => 0));

		$this->Helper->request->data = array('zero' => '0');
		$result = $this->Helper->value(array('default' => 'something'), 'zero');
		$this->assertEquals($result, array('value' => '0'));

		$this->Helper->setEntity('inexistent');
		$result = $this->Helper->value(array('default' => 'something'), 'inexistent');
		$this->assertEquals($result, array('value' => 'something'));
	}

/**
 * Test habtm data fetching and ensure no pollution happens.
 *
 * @return void
 */
	function testValueHabtmKeys() {
		$this->Helper->request->data = array(
			'HelperTestTag' => array('HelperTestTag' => '')
		);
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEquals($result, '');

		$this->Helper->request->data = array(
			'HelperTestTag' => array(
				'HelperTestTag' => array(2, 3, 4)
			)
		);
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEquals($result, array(2, 3, 4));

		$this->Helper->request->data = array(
			'HelperTestTag' => array(
				array('id' => 3),
				array('id' => 5)
			)
		);
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEquals($result, array(3 => 3, 5 => 5));

		$this->Helper->request->data = array(
			'HelperTestTag' => array(
				'body' => '',
				'title' => 'winning'
			),
		);
		$this->Helper->setEntity('HelperTestTag.body');
		$result = $this->Helper->value('HelperTestTag.body');
		$this->assertEquals($result, '');
	}

/**
 * Ensure HTML escaping of url params.  So link addresses are valid and not exploited
 *
 * @return void
 */
	public function testUrlConversion() {
		$result = $this->Helper->url('/controller/action/1');
		$this->assertEquals($result, '/controller/action/1');

		$result = $this->Helper->url('/controller/action/1?one=1&two=2');
		$this->assertEquals($result, '/controller/action/1?one=1&amp;two=2');

		$result = $this->Helper->url(array('controller' => 'posts', 'action' => 'index', 'page' => '1" onclick="alert(\'XSS\');"'));
		$this->assertEquals($result, "/posts/index/page:1&quot; onclick=&quot;alert(&#039;XSS&#039;);&quot;");

		$result = $this->Helper->url('/controller/action/1/param:this+one+more');
		$this->assertEquals($result, '/controller/action/1/param:this+one+more');

		$result = $this->Helper->url('/controller/action/1/param:this%20one%20more');
		$this->assertEquals($result, '/controller/action/1/param:this%20one%20more');

		$result = $this->Helper->url('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');
		$this->assertEquals($result, '/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24'
		));
		$this->assertEquals($result, "/posts/index/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24");

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'page' => '1',
			'?' => array('one' => 'value', 'two' => 'value', 'three' => 'purple')
		));
		$this->assertEquals($result, "/posts/index/page:1?one=value&amp;two=value&amp;three=purple");
	}

/**
 * test assetTimestamp application
 *
 * @return void
 */
	public function testAssetTimestamp() {
		$_timestamp = Configure::read('Asset.timestamp');
		$_debug = Configure::read('debug');

		Configure::write('Asset.timestamp', false);
		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css');
		$this->assertEquals($result, CSS_URL . 'cake.generic.css');

		Configure::write('Asset.timestamp', true);
		Configure::write('debug', 0);
		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css');
		$this->assertEquals($result, CSS_URL . 'cake.generic.css');

		Configure::write('Asset.timestamp', true);
		Configure::write('debug', 2);
		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css');
		$this->assertRegExp('/' . preg_quote(CSS_URL . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		Configure::write('Asset.timestamp', 'force');
		Configure::write('debug', 0);
		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css');
		$this->assertRegExp('/' . preg_quote(CSS_URL . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css?someparam');
		$this->assertEquals($result, CSS_URL . 'cake.generic.css?someparam');

		$this->Helper->request->webroot = '/some/dir/';
		$result = $this->Helper->assetTimestamp('/some/dir/' . CSS_URL . 'cake.generic.css');
		$this->assertRegExp('/' . preg_quote(CSS_URL . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		Configure::write('debug', $_debug);
		Configure::write('Asset.timestamp', $_timestamp);
	}

/**
 * test assetTimestamp with plugins and themes
 *
 * @return void
 */
	public function testAssetTimestampPluginsAndThemes() {
		$_timestamp = Configure::read('Asset.timestamp');
		Configure::write('Asset.timestamp', 'force');
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
		));
		CakePlugin::loadAll();

		$result = $this->Helper->assetTimestamp('/test_plugin/css/test_plugin_asset.css');
		$this->assertRegExp('#/test_plugin/css/test_plugin_asset.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

		$result = $this->Helper->assetTimestamp('/test_plugin/css/i_dont_exist.css');
		$this->assertRegExp('#/test_plugin/css/i_dont_exist.css\?$#', $result, 'No error on missing file');

		$result = $this->Helper->assetTimestamp('/theme/test_theme/js/theme.js');
		$this->assertRegExp('#/theme/test_theme/js/theme.js\?[0-9]+$#', $result, 'Missing timestamp theme');

		$result = $this->Helper->assetTimestamp('/theme/test_theme/js/non_existant.js');
		$this->assertRegExp('#/theme/test_theme/js/non_existant.js\?$#', $result, 'No error on missing file');

		App::build();
		Configure::write('Asset.timestamp', $_timestamp);
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
		$this->assertEquals($result, 'ok');

		$this->Helper->setEntity('HelperTestPost', true);
		$this->Helper->request->data['HelperTestPost']['5']['created']['month'] = '10';
		$result = $this->Helper->value('5.created.month');
		$this->assertEquals($result, 10);

		$this->Helper->request->data['HelperTestPost']['0']['id'] = 100;
		$result = $this->Helper->value('HelperTestPost.0.id');
		$this->assertEquals($result, 100);
	}

/**
 * testClean method
 *
 * @return void
 */
	public function testClean() {
		$result = $this->Helper->clean(array());
		$this->assertEquals($result, null);

		$result = $this->Helper->clean(array('<script>with something</script>', '<applet>something else</applet>'));
		$this->assertEquals($result, array('with something', 'something else'));

		$result = $this->Helper->clean('<script>with something</script>');
		$this->assertEquals($result, 'with something');

		$result = $this->Helper->clean('<script type="text/javascript">alert("ruined");</script>');
		$this->assertNotRegExp('#</*script#', $result);

		$result = $this->Helper->clean("<script \ntype=\"text/javascript\">\n\talert('ruined');\n\n\t\t</script>");
		$this->assertNotRegExp('#</*script#', $result);

		$result = $this->Helper->clean('<body/onload=do(/something/)>');
		$this->assertEquals($result, '<body/>');

		$result = $this->Helper->clean('&lt;script&gt;alert(document.cookie)&lt;/script&gt;');
		$this->assertEquals($result, '&amp;lt;script&amp;gt;alert(document.cookie)&amp;lt;/script&amp;gt;');
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
		$this->assertEquals($result,'My Title');
	}

	public function testWebrootPaths() {
		$this->Helper->request->webroot = '/';
		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/img/cake.power.gif';
		$this->assertEquals($expected, $result);

		$this->Helper->theme = 'test_theme';

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
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
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		));
		CakePlugin::loadAll();
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
		$expected = array();
		$this->assertEquals($expected, $result, 'Helper helpers were attached to the collection.');
	}

/**
 * test that the lazy loader doesn't duplicate objects on each access.
 *
 * @return void
 */
	public function testLazyLoadingUsesReferences() {
		$Helper = new TestHelper($this->View);
		$result1 = $Helper->Html;
		$result2 = $Helper->Html;

		$result1->testprop = 1;
		$this->assertEquals($result1->testprop, $result2->testprop);
	}
}
