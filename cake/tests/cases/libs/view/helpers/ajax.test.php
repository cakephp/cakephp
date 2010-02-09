<?php
/* SVN FILE: $Id$ */
/**
 * AjaxHelperTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
uses(
	'view' . DS . 'helpers' . DS . 'app_helper',
	'controller' . DS . 'controller',
	'model' . DS . 'model',
	'view' . DS . 'helper',
	'view' . DS . 'helpers'.DS.'ajax',
	'view' . DS . 'helpers' . DS . 'html',
	'view' . DS . 'helpers' . DS . 'form',
	'view' . DS . 'helpers' . DS . 'javascript'
	);
/**
 * AjaxTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class AjaxTestController extends Controller {
/**
 * name property
 *
 * @var string 'AjaxTest'
 * @access public
 */
	var $name = 'AjaxTest';
/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	var $uses = null;
}
/**
 * PostAjaxTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class PostAjaxTest extends Model {
/**
 * primaryKey property
 *
 * @var string 'id'
 * @access public
 */
	var $primaryKey = 'id';
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
		return array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
	}
}
/**
 * TestAjaxHelper class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TestAjaxHelper extends AjaxHelper {
/**
 * stop method
 *
 * @access public
 * @return void
 */
	function _stop() {
	}
}
/**
 * TestJavascriptHelper class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TestJavascriptHelper extends JavascriptHelper {
/**
 * codeBlocks property
 *
 * @var mixed
 * @access public
 */
	var $codeBlocks;
/**
 * codeBlock method
 *
 * @param mixed $parameter
 * @access public
 * @return void
 */
	function codeBlock($parameter) {
		if (empty($this->codeBlocks)) {
			$this->codeBlocks = array();
		}
		$this->codeBlocks[] = $parameter;
	}
}
/**
 * AjaxTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class AjaxHelperTest extends CakeTestCase {
/**
 * Regexp for CDATA start block
 *
 * @var string
 */
	var $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';
/**
 * Regexp for CDATA end block
 *
 * @var string
 */
	var $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Router::reload();
		$this->Ajax =& new TestAjaxHelper();
		$this->Ajax->Html =& new HtmlHelper();
		$this->Ajax->Form =& new FormHelper();
		$this->Ajax->Javascript =& new JavascriptHelper();
		$this->Ajax->Form->Html =& $this->Ajax->Html;
		$view =& new View(new AjaxTestController());
		ClassRegistry::addObject('view', $view);
		ClassRegistry::addObject('PostAjaxTest', new PostAjaxTest());
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Ajax);
		ClassRegistry::flush();
	}
/**
 * testEvalScripts method
 *
 * @access public
 * @return void
 */
	function testEvalScripts() {
		$result = $this->Ajax->link('Test Link', 'http://www.cakephp.org', array('id' => 'link1', 'update' => 'content', 'evalScripts' => false));
		$expected = array(
			'a' => array('id' => 'link1', 'onclick' => ' event.returnValue = false; return false;', 'href' => 'http://www.cakephp.org'),
			'Test Link',
			'/a',
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Event.observe('link1', 'click', function(event) { new Ajax.Updater('content','http://www.cakephp.org', {asynchronous:true, evalScripts:false, requestHeaders:['X-Update', 'content']}) }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->link('Test Link', 'http://www.cakephp.org', array('id' => 'link1', 'update' => 'content'));
		$expected = array(
			'a' => array('id' => 'link1', 'onclick' => ' event.returnValue = false; return false;', 'href' => 'http://www.cakephp.org'),
			'Test Link',
			'/a',
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Event.observe('link1', 'click', function(event) { new Ajax.Updater('content','http://www.cakephp.org', {asynchronous:true, evalScripts:true, requestHeaders:['X-Update', 'content']}) }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testAutoComplete method
 *
 * @access public
 * @return void
 */
	function testAutoComplete() {
		$result = $this->Ajax->autoComplete('PostAjaxTest.title' , '/posts', array('minChars' => 2));
		$this->assertPattern('/^<input[^<>]+name="data\[PostAjaxTest\]\[title\]"[^<>]+autocomplete="off"[^<>]+\/>/', $result);
		$this->assertPattern('/<div[^<>]+id="PostAjaxTestTitle_autoComplete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<div[^<>]+class="auto_complete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<\/div>\s+<script type="text\/javascript">\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\',')) . '/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\', {minChars:2});')) . '/', $result);
		$this->assertPattern('/<\/script>$/', $result);

		$result = $this->Ajax->autoComplete('PostAjaxTest.title' , '/posts', array('paramName' => 'parameter'));
		$this->assertPattern('/^<input[^<>]+name="data\[PostAjaxTest\]\[title\]"[^<>]+autocomplete="off"[^<>]+\/>/', $result);
		$this->assertPattern('/<div[^<>]+id="PostAjaxTestTitle_autoComplete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<div[^<>]+class="auto_complete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<\/div>\s+<script type="text\/javascript">\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\',')) . '/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\', {paramName:\'parameter\'});')) . '/', $result);
		$this->assertPattern('/<\/script>$/', $result);

		$result = $this->Ajax->autoComplete('PostAjaxTest.title' , '/posts', array('paramName' => 'parameter', 'updateElement' => 'elementUpdated', 'afterUpdateElement' => 'function (input, element) { alert("updated"); }'));
		$this->assertPattern('/^<input[^<>]+name="data\[PostAjaxTest\]\[title\]"[^<>]+autocomplete="off"[^<>]+\/>/', $result);
		$this->assertPattern('/<div[^<>]+id="PostAjaxTestTitle_autoComplete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<div[^<>]+class="auto_complete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<\/div>\s+<script type="text\/javascript">\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\',')) . '/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\', {paramName:\'parameter\', updateElement:elementUpdated, afterUpdateElement:function (input, element) { alert("updated"); }});')) . '/', $result);
		$this->assertPattern('/<\/script>$/', $result);

		$result = $this->Ajax->autoComplete('PostAjaxTest.title' , '/posts', array('callback' => 'function (input, queryString) { alert("requesting"); }'));
		$this->assertPattern('/^<input[^<>]+name="data\[PostAjaxTest\]\[title\]"[^<>]+autocomplete="off"[^<>]+\/>/', $result);
		$this->assertPattern('/<div[^<>]+id="PostAjaxTestTitle_autoComplete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<div[^<>]+class="auto_complete"[^<>]*><\/div>/', $result);
		$this->assertPattern('/<\/div>\s+<script type="text\/javascript">\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\',')) . '/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Autocompleter(\'PostAjaxTestTitle\', \'PostAjaxTestTitle_autoComplete\', \'/posts\', {callback:function (input, queryString) { alert("requesting"); }});')) . '/', $result);
		$this->assertPattern('/<\/script>$/', $result);

		$result = $this->Ajax->autoComplete("PostAjaxText.title", "/post", array("parameters" => "'key=value&key2=value2'"));
		$this->assertPattern('/{parameters:\'key=value&key2=value2\'}/', $result);

		$result = $this->Ajax->autoComplete("PostAjaxText.title", "/post", array("with" => "'key=value&key2=value2'"));
		$this->assertPattern('/{parameters:\'key=value&key2=value2\'}/', $result);

	}
/**
 * testAsynchronous method
 *
 * @access public
 * @return void
 */
	function testAsynchronous() {
		$result = $this->Ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content', 'type' => 'synchronous'));
		$expected = array(
			'a' => array('id' => 'link1', 'onclick' => ' event.returnValue = false; return false;', 'href' => '/'),
			'Test Link',
			'/a',
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Event.observe('link1', 'click', function(event) { new Ajax.Updater('content','/', {asynchronous:false, evalScripts:true, requestHeaders:['X-Update', 'content']}) }, false);",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testDraggable method
 *
 * @access public
 * @return void
 */
	function testDraggable() {
		$result = $this->Ajax->drag('id', array('handle' => 'other_id'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Draggable('id', {handle:'other_id'});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->drag('id', array('onDrag' => 'doDrag', 'onEnd' => 'doEnd'));
		$this->assertPattern('/onDrag:doDrag/', $result);
		$this->assertPattern('/onEnd:doEnd/', $result);
	}
/**
 * testDroppable method
 *
 * @access public
 * @return void
 */
	function testDroppable() {
		$result = $this->Ajax->drop('droppable', array('accept' => 'crap'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Droppables.add('droppable', {accept:'crap'});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->dropRemote('droppable', array('accept' => 'crap'), array('url' => '/posts'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Droppables.add('droppable', {accept:'crap', onDrop:function(element, droppable, event) {new Ajax.Request('/posts', {asynchronous:true, evalScripts:true})}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->dropRemote('droppable', array('accept' => array('crap1', 'crap2')), array('url' => '/posts'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Droppables.add('droppable', {accept:[\"crap1\",\"crap2\"], onDrop:function(element, droppable, event) {new Ajax.Request('/posts', {asynchronous:true, evalScripts:true})}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->dropRemote('droppable', array('accept' => 'crap'), array('url' => '/posts', 'with' => '{drag_id:element.id,drop_id:dropon.id,event:event.whatever_you_want}'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Droppables.add('droppable', {accept:'crap', onDrop:function(element, droppable, event) {new Ajax.Request('/posts', {asynchronous:true, evalScripts:true, parameters:{drag_id:element.id,drop_id:dropon.id,event:event.whatever_you_want}})}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testForm method
 *
 * @access public
 * @return void
 */
	function testForm() {
		$result = $this->Ajax->form('showForm', 'post', array('model' => 'Form', 'url' => array('action' => 'showForm', 'controller' => 'forms'), 'update' => 'form_box'));
		$this->assertNoPattern('/model=/', $result);

		$result = $this->Ajax->form('showForm', 'post', array('name'=> 'SomeFormName', 'id' => 'MyFormID', 'url' => array('action' => 'showForm', 'controller' => 'forms'), 'update' => 'form_box'));
		$this->assertPattern('/id="MyFormID"/', $result);
		$this->assertPattern('/name="SomeFormName"/', $result);
	}
/**
 * testSortable method
 *
 * @access public
 * @return void
 */
	function testSortable() {
		$result = $this->Ajax->sortable('ull', array('constraint' => false, 'ghosting' => true));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Sortable.create('ull', {constraint:false, ghosting:true});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->sortable('ull', array('constraint' => 'false', 'ghosting' => 'true'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Sortable.create('ull', {constraint:false, ghosting:true});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->sortable('ull', array('constraint'=>'false', 'ghosting'=>'true', 'update' => 'myId'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Sortable.create('ull', {constraint:false, ghosting:true, update:'myId'});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->sortable('faqs', array('url'=>'http://www.cakephp.org',
			'update' => 'faqs',
			'tag' => 'tbody',
			'handle' => 'grip',
			'before' => "Element.hide('message')",
			'complete' => "Element.show('message');"
		));
		$expected = 'Sortable.create(\'faqs\', {update:\'faqs\', tag:\'tbody\', handle:\'grip\', onUpdate:function(sortable) {Element.hide(\'message\'); new Ajax.Updater(\'faqs\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {Element.show(\'message\');}, parameters:Sortable.serialize(\'faqs\'), requestHeaders:[\'X-Update\', \'faqs\']})}});';
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote($expected)) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->sortable('div', array('overlap' => 'foo'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"Sortable.create('div', {overlap:'foo'});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->sortable('div', array('block' => false));
		$expected = "Sortable.create('div', {});";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->sortable('div', array('block' => false, 'scroll' => 'someID'));
		$expected = "Sortable.create('div', {scroll:'someID'});";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->sortable('div', array('block' => false, 'scroll' => 'window'));
		$expected = "Sortable.create('div', {scroll:window});";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->sortable('div', array('block' => false, 'scroll' => "$('someElement')"));
		$expected = "Sortable.create('div', {scroll:$('someElement')});";
		$this->assertEqual($result, $expected);
	}
/**
 * testSubmitWithIndicator method
 *
 * @access public
 * @return void
 */
	function testSubmitWithIndicator() {
		$result = $this->Ajax->submit('Add', array('div' => false, 'url' => "http://www.cakephp.org", 'indicator' => 'loading', 'loading' => "doSomething()", 'complete' => 'doSomethingElse() '));
		$this->assertPattern('/onLoading:function\(request\) {doSomething\(\);\s+Element.show\(\'loading\'\);}/', $result);
		$this->assertPattern('/onComplete:function\(request, json\) {doSomethingElse\(\) ;\s+Element.hide\(\'loading\'\);}/', $result);
	}
/**
 * testLink method
 *
 * @access public
 * @return void
 */
	function testLink() {
		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads');
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+id="link\d+"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'link\d+\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Request\(\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('confirm' => 'Are you sure & positive?'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+id="link\d+"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'link\d+\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*if \(confirm\(\'Are you sure & positive\?\'\)\) {\s*new Ajax\.Request\(\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true}\);\s*}\s*else\s*{\s*event.returnValue = false;\s*return false;\s*}\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('update' => 'myDiv'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+id="link\d+"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'link\d+\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Updater\(\'myDiv\',\s*\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true, requestHeaders:\[\'X-Update\', \'myDiv\'\]}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('update' => 'myDiv', 'id' => 'myLink'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+id="myLink"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'myLink\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Updater\(\'myDiv\',\s*\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true, requestHeaders:\[\'X-Update\', \'myDiv\'\]}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('update' => 'myDiv', 'id' => 'myLink', 'complete' => 'myComplete();'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+id="myLink"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'myLink\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Updater\(\'myDiv\',\s*\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true, onComplete:function\(request, json\) {myComplete\(\);}, requestHeaders:\[\'X-Update\', \'myDiv\'\]}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('update' => 'myDiv', 'id' => 'myLink', 'loading' => 'myLoading();', 'complete' => 'myComplete();'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+id="myLink"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'myLink\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Updater\(\'myDiv\',\s*\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true, onComplete:function\(request, json\) {myComplete\(\);}, onLoading:function\(request\) {myLoading\(\);}, requestHeaders:\[\'X-Update\', \'myDiv\'\]}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('update' => 'myDiv', 'encoding' => 'utf-8'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'\w+\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Updater\(\'myDiv\',\s*\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true, encoding:\'utf-8\', requestHeaders:\[\'X-Update\', \'myDiv\'\]}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('update' => 'myDiv', 'success' => 'success();'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'\w+\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Updater\(\'myDiv\',\s*\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true, onSuccess:function\(request\) {success\(\);}, requestHeaders:\[\'X-Update\', \'myDiv\'\]}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', 'http://www.cakephp.org/downloads', array('update' => 'myDiv', 'failure' => 'failure();'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a[^<>]+href="http:\/\/www.cakephp.org\/downloads"[^<>]*>/', $result);
		$this->assertPattern('/^<a[^<>]+onclick="\s*event.returnValue = false;\s*return false;"[^<>]*>/', $result);
		$this->assertPattern('/<script[^<>]+type="text\/javascript"[^<>]*>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/Event.observe\(\'\w+\',\s*\'click\',\s*function\(event\)\s*{.+},\s*false\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/function\(event\)\s*{\s*new Ajax\.Updater\(\'myDiv\',\s*\'http:\/\/www.cakephp.org\/downloads\',\s*{asynchronous:true, evalScripts:true, onFailure:function\(request\) {failure\(\);}, requestHeaders:\[\'X-Update\', \'myDiv\'\]}\)\s*},\s*false\);/', $result);

		$result = $this->Ajax->link('Ajax Link', '/test', array('complete' => 'test'));
		$this->assertPattern('/^<a[^<>]+>Ajax Link<\/a><script [^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*[^<>]+\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern("/Event.observe\('link[0-9]+', [\w\d,'\(\)\s{}]+Ajax\.Request\([\w\d\s,'\(\){}:\/]+onComplete:function\(request, json\) {test}/", $result);
		$this->assertNoPattern('/^<a[^<>]+complete="test"[^<>]*>Ajax Link<\/a>/', $result);
		$this->assertNoPattern('/^<a\s+[^<>]*url="[^"]*"[^<>]*>/', $result);

		$result = $this->Ajax->link(
			'Ajax Link',
			array('controller' => 'posts', 'action' => 'index', '?' => array('one' => '1', 'two' => '2')),
			array('update' => 'myDiv', 'id' => 'myLink')
		);
		$this->assertPattern('#/posts/\?one\=1\&two\=2#', $result);
	}
/**
 * testRemoteTimer method
 *
 * @access public
 * @return void
 */
	function testRemoteTimer() {
		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 10\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true})')) . '/', $result);

		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org', 'frequency' => 25));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 25\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true})')) . '/', $result);

		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org', 'complete' => 'complete();'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 10\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {complete();}})')) . '/', $result);

		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org', 'complete' => 'complete();', 'create' => 'create();'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 10\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {complete();}, onCreate:function(request, xhr) {create();}})')) . '/', $result);

		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org', 'exception' => 'alert(exception);'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 10\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, onException:function(request, exception) {alert(exception);}})')) . '/', $result);

		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org', 'contentType' => 'application/x-www-form-urlencoded'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 10\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, contentType:\'application/x-www-form-urlencoded\'})')) . '/', $result);

		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org', 'method' => 'get', 'encoding' => 'utf-8'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 10\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, method:\'get\', encoding:\'utf-8\'})')) . '/', $result);

		$result = $this->Ajax->remoteTimer(array('url' => 'http://www.cakephp.org', 'postBody' => 'var1=value1'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new PeriodicalExecuter\(function\(\) {.+}, 10\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, postBody:\'var1=value1\'})')) . '/', $result);
	}
/**
 * testObserveField method
 *
 * @access public
 * @return void
 */
	function testObserveField() {
		$result = $this->Ajax->observeField('field', array('url' => 'http://www.cakephp.org'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.Element.EventObserver\(\'field\', function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.Element.serialize(\'field\')})')) . '/', $result);

		$result = $this->Ajax->observeField('field', array('url' => 'http://www.cakephp.org', 'frequency' => 15));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.Element.Observer\(\'field\', 15, function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.Element.serialize(\'field\')})')) . '/', $result);

		$result = $this->Ajax->observeField('field', array('url' => 'http://www.cakephp.org', 'update' => 'divId'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.Element.EventObserver\(\'field\', function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Updater(\'divId\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.Element.serialize(\'field\'), requestHeaders:[\'X-Update\', \'divId\']})')) . '/', $result);

		$result = $this->Ajax->observeField('field', array('url' => 'http://www.cakephp.org', 'update' => 'divId', 'with' => 'Form.Element.serialize(\'otherField\')'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.Element.EventObserver\(\'field\', function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Updater(\'divId\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.Element.serialize(\'otherField\'), requestHeaders:[\'X-Update\', \'divId\']})')) . '/', $result);
	}
/**
 * testObserveForm method
 *
 * @access public
 * @return void
 */
	function testObserveForm() {
		$result = $this->Ajax->observeForm('form', array('url' => 'http://www.cakephp.org'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Form.EventObserver('form', function(element, value) {new Ajax.Request('http://www.cakephp.org', {asynchronous:true, evalScripts:true, parameters:Form.serialize('form')})})",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->observeForm('form', array('url' => 'http://www.cakephp.org', 'frequency' => 15));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Form.Observer('form', 15, function(element, value) {new Ajax.Request('http://www.cakephp.org', {asynchronous:true, evalScripts:true, parameters:Form.serialize('form')})})",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->observeForm('form', array('url' => 'http://www.cakephp.org', 'update' => 'divId'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Form.EventObserver('form', function(element, value) {new Ajax.Updater('divId','http://www.cakephp.org', {asynchronous:true, evalScripts:true, parameters:Form.serialize('form'), requestHeaders:['X-Update', 'divId']})}",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->observeForm('form', array('url' => 'http://www.cakephp.org', 'update' => 'divId', 'with' => "Form.serialize('otherForm')"));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Form.EventObserver('form', function(element, value) {new Ajax.Updater('divId','http://www.cakephp.org', {asynchronous:true, evalScripts:true, parameters:Form.serialize('otherForm'), requestHeaders:['X-Update', 'divId']})}",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testSlider method
 *
 * @access public
 * @return void
 */
	function testSlider() {
		$result = $this->Ajax->slider('sliderId', 'trackId');
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var sliderId = new Control.Slider('sliderId', 'trackId', {});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('axis' => 'vertical'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var sliderId = new Control.Slider('sliderId', 'trackId', {axis:'vertical'});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('axis' => 'vertical', 'minimum' => 60, 'maximum' => 288, 'alignX' => -28, 'alignY' => -5, 'disabled' => true));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var sliderId = new Control.Slider('sliderId', 'trackId', {axis:'vertical', minimum:60, maximum:288, alignX:-28, alignY:-5, disabled:true});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('change' => "alert('changed');"));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var sliderId = new Control.Slider('sliderId', 'trackId', {onChange:function(value) {alert('changed');}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('change' => "alert('changed');", 'slide' => "alert('sliding');"));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var sliderId = new Control.Slider('sliderId', 'trackId', {onChange:function(value) {alert('changed');}, onSlide:function(value) {alert('sliding');}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('values' => array(10, 20, 30)));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var sliderId = new Control.Slider('sliderId', 'trackId', {values:[10,20,30]});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('range' => '$R(10, 30)'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var sliderId = new Control.Slider('sliderId', 'trackId', {range:\$R(10, 30)});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);
	}
/**
 * testRemoteFunction method
 *
 * @access public
 * @return void
 */
	function testRemoteFunction() {
		$result = $this->Ajax->remoteFunction(array('complete' => 'testComplete();'));
		$expected = "new Ajax.Request('/', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {testComplete();}})";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->remoteFunction(array('update' => 'myDiv'));
		$expected = "new Ajax.Updater('myDiv','/', {asynchronous:true, evalScripts:true, requestHeaders:['X-Update', 'myDiv']})";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->remoteFunction(array('update' => array('div1', 'div2')));
		$expected = "new Ajax.Updater(document.createElement('div'),'/', {asynchronous:true, evalScripts:true, requestHeaders:['X-Update', 'div1 div2']})";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->remoteFunction(array('update' => 'myDiv', 'confirm' => 'Are you sure?'));
		$expected = "if (confirm('Are you sure?')) { new Ajax.Updater('myDiv','/', {asynchronous:true, evalScripts:true, requestHeaders:['X-Update', 'myDiv']}); } else { event.returnValue = false; return false; }";
		$this->assertEqual($result, $expected);
	}
/**
 * testDiv method
 *
 * @access public
 * @return void
 */
	function testDiv() {
		ob_flush();
		$oldXUpdate = env('HTTP_X_UPDATE');

		$result = $this->Ajax->div('myDiv');
		$this->assertTags($result, array('div' => array('id' => 'myDiv')));

		$_SERVER['HTTP_X_UPDATE'] = null;
		$result = $this->Ajax->divEnd('myDiv');
		$this->assertTags($result, '/div');

		$_SERVER['HTTP_X_UPDATE'] = 'secondDiv';
		$result = $this->Ajax->div('myDiv');
		$this->assertTags($result, array('div' => array('id' => 'myDiv')));
		$result = $this->Ajax->divEnd('myDiv');
		$this->assertTags($result, '/div');

		$_SERVER['HTTP_X_UPDATE'] = 'secondDiv myDiv anotherDiv';
		$result = $this->Ajax->div('myDiv');
		$this->assertTrue(empty($result));

		$result = $this->Ajax->divEnd('myDiv');
		$this->assertTrue(empty($result));

		$_SERVER['HTTP_X_UPDATE'] = $oldXUpdate;
	}
/**
 * testAfterRender method
 *
 * @access public
 * @return void
 */
	function testAfterRender() {
		$oldXUpdate = env('HTTP_X_UPDATE');
		$this->Ajax->Javascript =& new TestJavascriptHelper();

		$_SERVER['HTTP_X_UPDATE'] = 'secondDiv myDiv anotherDiv';
		$result = $this->Ajax->div('myDiv');
		$this->assertTrue(empty($result));

		echo 'Contents of myDiv';

		$result = $this->Ajax->divEnd('myDiv');
		$this->assertTrue(empty($result));

		ob_start();
		$this->Ajax->afterRender();

		$result = array_shift($this->Ajax->Javascript->codeBlocks);
		$this->assertPattern('/^\s*' . str_replace('/', '\\/', preg_quote('var __ajaxUpdater__ = {myDiv:"Contents%20of%20myDiv"};')) . '\s*' . str_replace('/', '\\/', preg_quote('for (n in __ajaxUpdater__) { if (typeof __ajaxUpdater__[n] == "string" && $(n)) Element.update($(n), unescape(decodeURIComponent(__ajaxUpdater__[n]))); }')) . '\s*$/s', $result);

		$_SERVER['HTTP_X_UPDATE'] = $oldXUpdate;
	}
/**
 * testEditor method
 *
 * @access public
 * @return void
 */
	function testEditor() {
		$result = $this->Ajax->editor('myDiv', '/');
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Ajax.InPlaceEditor('myDiv', '/', {ajaxOptions:{asynchronous:true, evalScripts:true}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->editor('myDiv', '/', array('complete' => 'testComplete();'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Ajax.InPlaceEditor('myDiv', '/', {ajaxOptions:{asynchronous:true, evalScripts:true, onComplete:function(request, json) {testComplete();}}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->editor('myDiv', '/', array('callback' => 'callback();'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Ajax.InPlaceEditor('myDiv', '/', {callback:function(form, value) {callback();}, ajaxOptions:{asynchronous:true, evalScripts:true}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->editor('myDiv', '/', array('collection' => array(1 => 'first', 2 => 'second')));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"new Ajax.InPlaceCollectionEditor('myDiv', '/', {collection:{\"1\":\"first\",\"2\":\"second\"}, ajaxOptions:{asynchronous:true, evalScripts:true}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);

		$result = $this->Ajax->editor('myDiv', '/', array('var' => 'myVar'));
		$expected = array(
			array('script' => array('type' => 'text/javascript')),
			$this->cDataStart,
			"var myVar = new Ajax.InPlaceEditor('myDiv', '/', {ajaxOptions:{asynchronous:true, evalScripts:true}});",
			$this->cDataEnd,
			'/script'
		);
		$this->assertTags($result, $expected);
	}
}
?>