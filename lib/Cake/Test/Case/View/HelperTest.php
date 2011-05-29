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
 * @package       cake.tests.cases.libs
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
 * @package       cake.tests.cases.libs.view
 */
class HelperTestPost extends Model {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
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
 * @access public
 */
	public $hasAndBelongsToMany = array('HelperTestTag'=> array('with' => 'HelperTestPostsTag'));
}

/**
 * HelperTestComment class
 *
 * @package       cake.tests.cases.libs.view
 */
class HelperTestComment extends Model {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	public function schema($field = false) {
		$this->_schema = array(
			'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'author_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'title' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
			'body' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => ''),
			'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
			'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
		);
		return $this->_schema;
	}
}

/**
 * HelperTestTag class
 *
 * @package       cake.tests.cases.libs.view
 */
class HelperTestTag extends Model {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
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
 * @package       cake.tests.cases.libs.view
 */
class HelperTestPostsTag extends Model {

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
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
 * helpers for this helper.
 *
 * @var string
 */
	var $helpers = array('Html', 'TestPlugin.OtherHelper');

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
 * @package       cake.tests.cases.libs.view
 */
class HelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
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
 * @access public
 * @return void
 */
	public function tearDown() {
		CakePlugin::unload();
		unset($this->Helper, $this->View);
		ClassRegistry::flush();
	}

/**
 * testFormFieldNameParsing method
 *
 * @access public
 * @return void
 */
	public function testSetEntity() {
		// PHP4 reference hack
		ClassRegistry::removeObject('view');
		ClassRegistry::addObject('view', $this->View);

		$this->Helper->setEntity('HelperTestPost.id');
		$this->assertFalse($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);

		$this->Helper->setEntity('HelperTestComment.body');
		$this->assertFalse($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestComment');
		$this->assertEqual($this->View->field, 'body');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('HelperTestPost', true);
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, null);
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('_Token.fields');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'fields');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, '_Token');
		$this->assertEqual($this->View->fieldSuffix, null);


		$this->Helper->setEntity('id');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('HelperTestComment.body');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'body');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, 'HelperTestComment');
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('body');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'body');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('Something.else');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'else');
		$this->assertEqual($this->View->modelId, false);
		$this->assertEqual($this->View->association, 'Something');
		$this->assertEqual($this->View->fieldSuffix, '');

		$this->Helper->setEntity('5.id');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, '5');
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->assertEqual($this->View->entity(), array('HelperTestPost', 5, 'id'));

		$this->Helper->setEntity('0.id');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, '0');
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->assertEqual($this->View->entity(), array('HelperTestPost', 0, 'id'));

		$this->Helper->setEntity('5.created.month');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'created');
		$this->assertEqual($this->View->modelId, '5');
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, 'month');

		$this->Helper->setEntity('HelperTestComment.5.id');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, '5');
		$this->assertEqual($this->View->association, 'HelperTestComment');
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('HelperTestComment.id.time');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, 'HelperTestComment');
		$this->assertEqual($this->View->fieldSuffix, 'time');

		$this->Helper->setEntity('HelperTestTag');
		$this->assertTrue($this->View->modelScope);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'HelperTestTag');
		$this->assertEqual($this->View->modelId, '');
		$this->assertEqual($this->View->association, 'HelperTestTag');
		$this->assertEqual($this->View->fieldSuffix, '');

		$this->Helper->setEntity(null);
		$this->Helper->setEntity('ModelThatDoesntExist.field_that_doesnt_exist');
		$this->assertFalse($this->View->modelScope);
		$this->assertEqual($this->View->model, 'ModelThatDoesntExist');
		$this->assertEqual($this->View->field, 'field_that_doesnt_exist');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);
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
		$this->assertEqual($result, 'This is me');

		$this->Helper->request->data = array('Post' => array('name' => 'First Post'));
		$this->Helper->setEntity('Post.name');
		$result = $this->Helper->value('Post.name');
		$this->assertEqual($result, 'First Post');

		$this->Helper->request->data = array('Post' => array(2 => array('name' => 'First Post')));
		$this->Helper->setEntity('Post.2.name');
		$result = $this->Helper->value('Post.2.name');
		$this->assertEqual($result, 'First Post');

		$this->Helper->request->data = array('Post' => array(2 => array('created' => array('year' => '2008'))));
		$this->Helper->setEntity('Post.2.created');
		$result = $this->Helper->value('Post.2.created');
		$this->assertEqual($result, array('year' => '2008'));

		$this->Helper->request->data = array('Post' => array(2 => array('created' => array('year' => '2008'))));
		$this->Helper->setEntity('Post.2.created.year');
		$result = $this->Helper->value('Post.2.created.year');
		$this->assertEqual($result, '2008');

		$this->Helper->request->data = array('HelperTestTag' => array('HelperTestTag' => ''));
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEqual($result, '');

		$this->Helper->request->data = array('HelperTestTag' => array('HelperTestTag' => array(2, 3, 4)));
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEqual($result, array(2, 3, 4));

		$this->Helper->request->data = array(
			'HelperTestTag' => array(
				array('id' => 3),
				array('id' => 5)
			)
		);
		$this->Helper->setEntity('HelperTestTag.HelperTestTag');
		$result = $this->Helper->value('HelperTestTag.HelperTestTag');
		$this->assertEqual($result, array(3 => 3, 5 => 5));

		$this->Helper->request->data = array('zero' => 0);
		$this->Helper->setEntity('zero');
		$result = $this->Helper->value(array('default' => 'something'), 'zero');
		$this->assertEqual($result, array('value' => 0));

		$this->Helper->request->data = array('zero' => '0');
		$result = $this->Helper->value(array('default' => 'something'), 'zero');
		$this->assertEqual($result, array('value' => '0'));

		$this->Helper->setEntity('inexistent');
		$result = $this->Helper->value(array('default' => 'something'), 'inexistent');
		$this->assertEqual($result, array('value' => 'something'));
	}

/**
 * Ensure HTML escaping of url params.  So link addresses are valid and not exploited
 *
 * @return void
 */
	public function testUrlConversion() {
		$result = $this->Helper->url('/controller/action/1');
		$this->assertEqual($result, '/controller/action/1');

		$result = $this->Helper->url('/controller/action/1?one=1&two=2');
		$this->assertEqual($result, '/controller/action/1?one=1&amp;two=2');

		$result = $this->Helper->url(array('controller' => 'posts', 'action' => 'index', 'page' => '1" onclick="alert(\'XSS\');"'));
		$this->assertEqual($result, "/posts/index/page:1&quot; onclick=&quot;alert(&#039;XSS&#039;);&quot;");

		$result = $this->Helper->url('/controller/action/1/param:this+one+more');
		$this->assertEqual($result, '/controller/action/1/param:this+one+more');

		$result = $this->Helper->url('/controller/action/1/param:this%20one%20more');
		$this->assertEqual($result, '/controller/action/1/param:this%20one%20more');

		$result = $this->Helper->url('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');
		$this->assertEqual($result, '/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24'
		));
		$this->assertEqual($result, "/posts/index/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24");

		$result = $this->Helper->url(array(
			'controller' => 'posts', 'action' => 'index', 'page' => '1',
			'?' => array('one' => 'value', 'two' => 'value', 'three' => 'purple')
		));
		$this->assertEqual($result, "/posts/index/page:1?one=value&amp;two=value&amp;three=purple");
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
		$this->assertEqual($result, CSS_URL . 'cake.generic.css');

		Configure::write('Asset.timestamp', true);
		Configure::write('debug', 0);
		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css');
		$this->assertEqual($result, CSS_URL . 'cake.generic.css');

		Configure::write('Asset.timestamp', true);
		Configure::write('debug', 2);
		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css');
		$this->assertPattern('/' . preg_quote(CSS_URL . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		Configure::write('Asset.timestamp', 'force');
		Configure::write('debug', 0);
		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css');
		$this->assertPattern('/' . preg_quote(CSS_URL . 'cake.generic.css?', '/') . '[0-9]+/', $result);

		$result = $this->Helper->assetTimestamp(CSS_URL . 'cake.generic.css?someparam');
		$this->assertEqual($result, CSS_URL . 'cake.generic.css?someparam');

		$this->Helper->request->webroot = '/some/dir/';
		$result = $this->Helper->assetTimestamp('/some/dir/' . CSS_URL . 'cake.generic.css');
		$this->assertPattern('/' . preg_quote(CSS_URL . 'cake.generic.css?', '/') . '[0-9]+/', $result);

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
		$this->assertPattern('#/test_plugin/css/test_plugin_asset.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

		$this->setExpectedException('PHPUnit_Framework_Error_Warning');
		$result = $this->Helper->assetTimestamp('/test_plugin/css/i_dont_exist.css');
		$this->assertPattern('#/test_plugin/css/i_dont_exist.css\?$#', $result, 'No error on missing file');

		$result = $this->Helper->assetTimestamp('/theme/test_theme/js/theme.js');
		$this->assertPattern('#/theme/test_theme/js/theme.js\?[0-9]+$#', $result, 'Missing timestamp theme');

		$result = $this->Helper->assetTimestamp('/theme/test_theme/js/non_existant.js');
		$this->assertPattern('#/theme/test_theme/js/non_existant.js\?$#', $result, 'No error on missing file');

		App::build();
		Configure::write('Asset.timestamp', $_timestamp);
	}

/**
 * testFieldsWithSameName method
 *
 * @access public
 * @return void
 */
	public function testFieldsWithSameName() {
		// PHP4 reference hack
		ClassRegistry::removeObject('view');
		ClassRegistry::addObject('view', $this->View);

		$this->Helper->setEntity('HelperTestTag', true);

		$this->Helper->setEntity('HelperTestTag.id');
		$this->assertEqual($this->View->model, 'HelperTestTag');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('My.id');
		$this->assertEqual($this->View->model, 'HelperTestTag');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, 'My');
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('MyOther.id');
		$this->assertEqual($this->View->model, 'HelperTestTag');
		$this->assertEqual($this->View->field, 'id');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, 'MyOther');
		$this->assertEqual($this->View->fieldSuffix, null);

	}

/**
 * testFieldSameAsModel method
 *
 * @access public
 * @return void
 */
	public function testFieldSameAsModel() {
		// PHP4 reference hack
		ClassRegistry::removeObject('view');
		ClassRegistry::addObject('view', $this->View);

		$this->Helper->setEntity('HelperTestTag', true);

		$this->Helper->setEntity('helper_test_post');
		$this->assertEqual($this->View->model, 'HelperTestTag');
		$this->assertEqual($this->View->field, 'helper_test_post');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('HelperTestTag');
		$this->assertEqual($this->View->model, 'HelperTestTag');
		$this->assertEqual($this->View->field, 'HelperTestTag');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);
		$this->assertEqual($this->View->entityPath, 'HelperTestTag');
	}

/**
 * testFieldSuffixForDate method
 *
 * @access public
 * @return void
 */
	public function testFieldSuffixForDate() {
		// PHP4 reference hack
		ClassRegistry::removeObject('view');
		ClassRegistry::addObject('view', $this->View);

		$this->Helper->setEntity('HelperTestPost', true);
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, null);
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, null);

		$this->Helper->setEntity('date.month');
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->field, 'date');
		$this->assertEqual($this->View->modelId, null);
		$this->assertEqual($this->View->association, null);
		$this->assertEqual($this->View->fieldSuffix, 'month');
	}

/**
 * testMulitDimensionValue method
 *
 * @access public
 * @return void
 */
	public function testMulitDimensionValue() {
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
		$this->assertEqual($expected, $result);

		$this->Helper->request->data['HelperTestComment']['5']['id'] = 'ok';
		$result = $this->Helper->value('HelperTestComment.5.id');
		$this->assertEqual($result, 'ok');

		$this->Helper->setEntity('HelperTestPost', true);
		$this->Helper->request->data['HelperTestPost']['5']['created']['month'] = '10';
		$result = $this->Helper->value('5.created.month');
		$this->assertEqual($result, 10);

		$this->Helper->request->data['HelperTestPost']['0']['id'] = 100;
		$result = $this->Helper->value('0.id');
		$this->assertEqual($result, 100);
	}

/**
 * testClean method
 *
 * @access public
 * @return void
 */
	public function testClean() {
		$result = $this->Helper->clean(array());
		$this->assertEqual($result, null);

		$result = $this->Helper->clean(array('<script>with something</script>', '<applet>something else</applet>'));
		$this->assertEqual($result, array('with something', 'something else'));

		$result = $this->Helper->clean('<script>with something</script>');
		$this->assertEqual($result, 'with something');

		$result = $this->Helper->clean('<script type="text/javascript">alert("ruined");</script>');
		$this->assertNoPattern('#</*script#', $result);

		$result = $this->Helper->clean("<script \ntype=\"text/javascript\">\n\talert('ruined');\n\n\t\t</script>");
		$this->assertNoPattern('#</*script#', $result);

		$result = $this->Helper->clean('<body/onload=do(/something/)>');
		$this->assertEqual($result, '<body/>');

		$result = $this->Helper->clean('&lt;script&gt;alert(document.cookie)&lt;/script&gt;');
		$this->assertEqual($result, '&amp;lt;script&amp;gt;alert(document.cookie)&amp;lt;/script&amp;gt;');
	}

/**
 * testMultiDimensionalField method
 *
 * @access public
 * @return void
 */
	public function testMultiDimensionalField() {
		// PHP4 reference hack
		ClassRegistry::removeObject('view');
		ClassRegistry::addObject('view', $this->View);

		$this->Helper->setEntity('HelperTestPost', true);

		$this->Helper->setEntity('HelperTestPost.2.HelperTestComment.1.title');
		$this->assertEqual($this->View->model, 'HelperTestPost');
		$this->assertEqual($this->View->association, 'HelperTestComment');
		$this->assertEqual($this->View->modelId,2);
		$this->assertEqual($this->View->field, 'title');

		$this->Helper->setEntity('HelperTestPost.1.HelperTestComment.1.HelperTestTag.1.created');
		$this->assertEqual($this->View->field,'created');
		$this->assertEqual($this->View->association,'HelperTestTag');
		$this->assertEqual($this->View->modelId,1);

		$this->Helper->setEntity('HelperTestPost.0.HelperTestComment.1.HelperTestTag.1.fake');
		$this->assertEqual($this->View->model,'HelperTestPost');
		$this->assertEqual($this->View->association,'HelperTestTag');
		$this->assertEqual($this->View->field,null);

		$this->Helper->setEntity('1.HelperTestComment.1.HelperTestTag.created.year');
		$this->assertEqual($this->View->model,'HelperTestPost');
		$this->assertEqual($this->View->association,'HelperTestTag');
		$this->assertEqual($this->View->field,'created');
		$this->assertEqual($this->View->modelId,1);
		$this->assertEqual($this->View->fieldSuffix,'year');

		$this->Helper->request->data['HelperTestPost'][2]['HelperTestComment'][1]['title'] = 'My Title';
		$result = $this->Helper->value('HelperTestPost.2.HelperTestComment.1.title');
		$this->assertEqual($result,'My Title');

		$this->Helper->request->data['HelperTestPost'][2]['HelperTestComment'][1]['created']['year'] = 2008;
		$result = $this->Helper->value('HelperTestPost.2.HelperTestComment.1.created.year');
		$this->assertEqual($result,2008);

		$this->Helper->request->data[2]['HelperTestComment'][1]['created']['year'] = 2008;
		$result = $this->Helper->value('HelperTestPost.2.HelperTestComment.1.created.year');
		$this->assertEqual($result,2008);

		$this->Helper->request->data['HelperTestPost']['title'] = 'My Title';
		$result = $this->Helper->value('title');
		$this->assertEqual($result,'My Title');

		$this->Helper->request->data['My']['title'] = 'My Title';
		$result = $this->Helper->value('My.title');
		$this->assertEqual($result,'My Title');
	}

	public function testWebrootPaths() {
		$this->Helper->request->webroot = '/';
		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/img/cake.power.gif';
		$this->assertEqual($expected, $result);

		$this->Helper->theme = 'test_theme';

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));

		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/theme/test_theme/img/cake.power.gif';
		$this->assertEqual($expected, $result);

		$result = $this->Helper->webroot('/img/test.jpg');
		$expected = '/theme/test_theme/img/test.jpg';
		$this->assertEqual($expected, $result);

		$webRoot = Configure::read('App.www_root');
		Configure::write('App.www_root', CAKE . 'Test' . DS . 'test_app' . DS . 'webroot' . DS);

		$result = $this->Helper->webroot('/img/cake.power.gif');
		$expected = '/theme/test_theme/img/cake.power.gif';
		$this->assertEqual($expected, $result);

		$result = $this->Helper->webroot('/img/test.jpg');
		$expected = '/theme/test_theme/img/test.jpg';
		$this->assertEqual($expected, $result);

		$result = $this->Helper->webroot('/img/cake.icon.gif');
		$expected = '/img/cake.icon.gif';
		$this->assertEqual($expected, $result);

		$result = $this->Helper->webroot('/img/cake.icon.gif?some=param');
		$expected = '/img/cake.icon.gif?some=param';
		$this->assertEqual($expected, $result);

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
