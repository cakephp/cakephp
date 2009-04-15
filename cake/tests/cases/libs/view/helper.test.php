<?php
/* SVN FILE: $Id$ */
/**
 * HelperTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('View', 'Helper'));
/**
 * HelperTestPost class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class HelperTestPost extends Model {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
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
	var $hasAndBelongsToMany = array('HelperTestTag'=> array('with' => 'HelperTestPostsTag'));
}
/**
 * HelperTestComment class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class HelperTestComment extends Model {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
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
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class HelperTestTag extends Model {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
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
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class HelperTestPostsTag extends Model {
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		$this->_schema = array(
			'helper_test_post_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
			'helper_test_tag_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
		);
		return $this->_schema;
	}
}
/**
 * HelperTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class HelperTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		ClassRegistry::flush();
		Router::reload();
		$null = null;
		$this->View = new View($null);
		$this->Helper = new Helper();
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
	function tearDown() {
		unset($this->Helper, $this->View);
		ClassRegistry::flush();
	}
/**
 * testFormFieldNameParsing method
 *
 * @access public
 * @return void
 */
	function testFormFieldNameParsing() {
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
 * test getting values from Helper
 *
 * @return void
 **/
	function testValue() {
		$this->Helper->data = array('fullname' => 'This is me');
		$this->Helper->setEntity('fullname');
		$result = $this->Helper->value('fullname');
		$this->assertEqual($result, 'This is me');

		$this->Helper->data = array('Post' => array('name' => 'First Post'));
		$this->Helper->setEntity('Post.name');
		$result = $this->Helper->value('Post.name');
		$this->assertEqual($result, 'First Post');

		$this->Helper->data = array('Post' => array(2 => array('name' => 'First Post')));
		$this->Helper->setEntity('Post.2.name');
		$result = $this->Helper->value('Post.2.name');
		$this->assertEqual($result, 'First Post');

		$this->Helper->data = array('Post' => array(2 => array('created' => array('year' => '2008'))));
		$this->Helper->setEntity('Post.2.created');
		$result = $this->Helper->value('Post.2.created');
		$this->assertEqual($result, array('year' => '2008'));

		$this->Helper->data = array('Post' => array(2 => array('created' => array('year' => '2008'))));
		$this->Helper->setEntity('Post.2.created.year');
		$result = $this->Helper->value('Post.2.created.year');
		$this->assertEqual($result, '2008');
	}
/**
 * Ensure HTML escaping of url params.  So link addresses are valid and not exploited
 *
 * @return void
 **/
	function testUrlConversion() {
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
 * testFieldsWithSameName method
 *
 * @access public
 * @return void
 */
	function testFieldsWithSameName() {
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
	function testFieldSameAsModel() {
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

	}
/**
 * testFieldSuffixForDate method
 *
 * @access public
 * @return void
 */
	function testFieldSuffixForDate() {
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
	function testMulitDimensionValue() {
		$this->Helper->data = array();
		for($i = 0; $i < 2; $i++) {
			$this->Helper->data['Model'][$i] = 'what';
			$result[] = $this->Helper->value("Model.{$i}");
			$this->Helper->data['Model'][$i] = array();
			for($j = 0; $j < 2; $j++) {
				$this->Helper->data['Model'][$i][$j] = 'how';
				$result[] = $this->Helper->value("Model.{$i}.{$j}");
			}
		}
		$expected = array('what', 'how', 'how', 'what', 'how', 'how');
		$this->assertEqual($result, $expected);

		$this->Helper->data['HelperTestComment']['5']['id'] = 'ok';
		$result = $this->Helper->value('HelperTestComment.5.id');
		$this->assertEqual($result, 'ok');

		$this->Helper->setEntity('HelperTestPost', true);
		$this->Helper->data['HelperTestPost']['5']['created']['month'] = '10';
		$result = $this->Helper->value('5.created.month');
		$this->assertEqual($result, 10);

		$this->Helper->data['HelperTestPost']['0']['id'] = 100;
		$result = $this->Helper->value('0.id');
		$this->assertEqual($result, 100);
	}
/**
 * testClean method
 *
 * @access public
 * @return void
 */
	function testClean() {
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
}
?>