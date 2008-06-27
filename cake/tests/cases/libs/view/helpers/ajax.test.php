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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
uses('view'.DS.'helpers'.DS.'app_helper', 'controller'.DS.'controller', 'model'.DS.'model', 'view'.DS.'helper', 'view'.DS.'helpers'.DS.'ajax',
	'view'.DS.'helpers'.DS.'html', 'view'.DS.'helpers'.DS.'form', 'view'.DS.'helpers'.DS.'javascript');
/**
 * AjaxTestController class
 * 
 * @package              cake
 * @subpackage           cake.tests.cases.libs.view.helpers
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
 * @package              cake
 * @subpackage           cake.tests.cases.libs.view.helpers
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
 * @package              cake
 * @subpackage           cake.tests.cases.libs.view.helpers
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
 * @package              cake
 * @subpackage           cake.tests.cases.libs.view.helpers
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
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class AjaxTest extends CakeTestCase {
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
 * testEvalScripts method
 * 
 * @access public
 * @return void
 */
	function testEvalScripts() {
		$result = $this->Ajax->link('Test Link', 'http://www.cakephp.org', array('id' => 'link1', 'update' => 'content', 'evalScripts' => false));
		$this->assertPattern('/^<a\s+[^<>]+>Test Link<\/a><script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote('Event.observe(\'link1\', \'click\', function(event) { new Ajax.Updater(\'content\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:false, requestHeaders:[\'X-Update\', \'content\']}) }, false);')) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a\s+[^<>]*href="http:\/\/www.cakephp.org"[^<>]*>/', $result);
		$this->assertPattern('/^<a\s+[^<>]*id="link1"[^<>]*>/', $result);
		$this->assertPattern('/^<a\s+[^<>]*onclick="\s*' . str_replace('/', '\\/', preg_quote('event.returnValue = false; return false;')) . '\s*"[^<>]*>/', $result);

		$result = $this->Ajax->link('Test Link', 'http://www.cakephp.org', array('id' => 'link1', 'update' => 'content'));
		$this->assertPattern('/^<a\s+[^<>]+>Test Link<\/a><script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote('Event.observe(\'link1\', \'click\', function(event) { new Ajax.Updater(\'content\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, requestHeaders:[\'X-Update\', \'content\']}) }, false);')) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a\s+[^<>]*href="http:\/\/www.cakephp.org"[^<>]*>/', $result);
		$this->assertPattern('/^<a\s+[^<>]*id="link1"[^<>]*>/', $result);
		$this->assertPattern('/^<a\s+[^<>]*onclick="\s*' . str_replace('/', '\\/', preg_quote('event.returnValue = false; return false;')) . '\s*"[^<>]*>/', $result);
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
	}
/**
 * testAsynchronous method
 * 
 * @access public
 * @return void
 */
	function testAsynchronous() {
		$result = $this->Ajax->link('Test Link', '/', array('id' => 'link1', 'update' => 'content', 'type' => 'synchronous'));
		$this->assertPattern('/^<a\s+[^<>]+>Test Link<\/a><script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote('Event.observe(\'link1\', \'click\', function(event) { new Ajax.Updater(\'content\',\'/\', {asynchronous:false, evalScripts:true, requestHeaders:[\'X-Update\', \'content\']}) }, false);')) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/^<a\s+[^<>]*href="\/"[^<>]*>/', $result);
		$this->assertPattern('/^<a\s+[^<>]*id="link1"[^<>]*>/', $result);
		$this->assertPattern('/^<a\s+[^<>]*onclick="\s*' . str_replace('/', '\\/', preg_quote('event.returnValue = false; return false;')) . '\s*"[^<>]*>/', $result);
	}
/**
 * testDraggable method
 * 
 * @access public
 * @return void
 */
	function testDraggable() {
		$result = $this->Ajax->drag('id', array('handle' => 'other_id'));
		$expected = 'new Draggable(\'id\', {handle:\'other_id\'});';
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote($expected)) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
	}
/**
 * testDroppable method
 * 
 * @access public
 * @return void
 */
	function testDroppable() {
		$result = $this->Ajax->drop('droppable', array('accept' => 'crap'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*Droppables.add\(\'droppable\', {accept:\'crap\'}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->dropRemote('droppable', array('accept' => 'crap'), array('url' => '/posts'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*Droppables.add\(\'droppable\', {accept:\'crap\', onDrop:function\(element, droppable, event\) {.+}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'/posts\', {asynchronous:true, evalScripts:true})')) . '/', $result);

		$result = $this->Ajax->dropRemote('droppable', array('accept' => array('crap1', 'crap2')), array('url' => '/posts'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*Droppables.add\(\'droppable\', {accept:\["crap1","crap2"\], onDrop:function\(element, droppable, event\) {.+}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'/posts\', {asynchronous:true, evalScripts:true})')) . '/', $result);

		$result = $this->Ajax->dropRemote('droppable', array('accept' => 'crap'), array('url' => '/posts', 'with' => '{drag_id:element.id,drop_id:dropon.id,event:event.whatever_you_want}'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*Droppables.add\(\'droppable\', {accept:\'crap\', onDrop:function\(element, droppable, event\) {.+}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'/posts\', {asynchronous:true, evalScripts:true, parameters:{drag_id:element.id,drop_id:dropon.id,event:event.whatever_you_want}})')) . '/', $result);
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
		$expected = 'Sortable.create(\'ull\', {constraint:false, ghosting:true});';
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote($expected)) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->sortable('ull', array('constraint' => 'false', 'ghosting' => 'true'));
		$expected = 'Sortable.create(\'ull\', {constraint:false, ghosting:true});';
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote($expected)) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->sortable('ull', array('constraint'=>'false', 'ghosting'=>'true', 'update' => 'myId'));
		$expected = 'Sortable.create(\'ull\', {constraint:false, ghosting:true, update:\'myId\'});';
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote($expected)) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->sortable('faqs', array('url'=>'http://www.cakephp.org',
			'update' => 'faqs',
			'tag' => 'tbody',
			'handle' => 'grip',
			'before' => 'Element.hide(\'message\')',
			'complete' => 'Element.show(\'message\');'));
		$expected = 'Sortable.create(\'faqs\', {update:\'faqs\', tag:\'tbody\', handle:\'grip\', onUpdate:function(sortable) {Element.hide(\'message\'); new Ajax.Updater(\'faqs\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {Element.show(\'message\');}, parameters:Sortable.serialize(\'faqs\'), requestHeaders:[\'X-Update\', \'faqs\']})}});';
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*' . str_replace('/', '\\/', preg_quote($expected)) . '\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->sortable('div', array('overlap' => 'foo'));
		$expected = "Sortable.create('div', {overlap:'foo'});";
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote($expected)) . '/', $result);

		$result = $this->Ajax->sortable('div', array('block' => false));
		$expected = "Sortable.create('div', {});";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->sortable('div', array('block' => false, 'scroll' => 'someID'));
		$expected = "Sortable.create('div', {scroll:'someID'});";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->sortable('div', array('block' => false, 'scroll' => 'window'));
		$expected = "Sortable.create('div', {scroll:window});";
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->sortable('div', array('block' => false, 'scroll' => '$("someElement")'));
		$expected = "Sortable.create('div', {scroll:$(\"someElement\")});";
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
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.EventObserver\(\'form\', function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.serialize(\'form\')})')) . '/', $result);

		$result = $this->Ajax->observeForm('form', array('url' => 'http://www.cakephp.org', 'frequency' => 15));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.Observer\(\'form\', 15, function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Request(\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.serialize(\'form\')})')) . '/', $result);

		$result = $this->Ajax->observeForm('form', array('url' => 'http://www.cakephp.org', 'update' => 'divId'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.EventObserver\(\'form\', function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Updater(\'divId\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.serialize(\'form\'), requestHeaders:[\'X-Update\', \'divId\']})')) . '/', $result);

		$result = $this->Ajax->observeForm('form', array('url' => 'http://www.cakephp.org', 'update' => 'divId', 'with' => 'Form.serialize(\'otherForm\')'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*new Form.EventObserver\(\'form\', function\(element, value\) {.+}\)\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
		$this->assertPattern('/' . str_replace('/', '\\/', preg_quote('new Ajax.Updater(\'divId\',\'http://www.cakephp.org\', {asynchronous:true, evalScripts:true, parameters:Form.serialize(\'otherForm\'), requestHeaders:[\'X-Update\', \'divId\']})')) . '/', $result);
	}
/**
 * testSlider method
 * 
 * @access public
 * @return void
 */
	function testSlider() {
		$result = $this->Ajax->slider('sliderId', 'trackId');
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*var sliderId = new Control.Slider\(\'sliderId\', \'trackId\', {}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('axis' => 'vertical'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*var sliderId = new Control.Slider\(\'sliderId\', \'trackId\', {axis:\'vertical\'}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('axis' => 'vertical', 'minimum' => 60, 'maximum' => 288, 'alignX' => -28, 'alignY' => -5, 'disabled' => true));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*var sliderId = new Control.Slider\(\'sliderId\', \'trackId\', {axis:\'vertical\', minimum:60, maximum:288, alignX:-28, alignY:-5, disabled:true}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('change' => 'alert(\'changed\');'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*var sliderId = new Control.Slider\(\'sliderId\', \'trackId\', {onChange:function\(value\) {alert\(\'changed\'\);}}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('change' => 'alert(\'changed\');', 'slide' => 'alert(\'sliding\');'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*var sliderId = new Control.Slider\(\'sliderId\', \'trackId\', {onChange:function\(value\) {alert\(\'changed\'\);}, onSlide:function\(value\) {alert\(\'sliding\'\);}}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('values' => array(10, 20, 30)));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*var sliderId = new Control.Slider\(\'sliderId\', \'trackId\', {values:\[10,20,30\]}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);

		$result = $this->Ajax->slider('sliderId', 'trackId', array('range' => '$R(10, 30)'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertNoPattern('/<script[^<>]+[^type]=[^<>]*>/', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '\s*var sliderId = new Control.Slider\(\'sliderId\', \'trackId\', {range:\$R\(10, 30\)}\);\s*' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/', $result);
	}
/**
 * testRemoteFunction method
 * 
 * @access public
 * @return void
 */
	function testRemoteFunction() {
		$result = $this->Ajax->remoteFunction(array('complete' => 'testComplete();'));
		$expected = 'new Ajax.Request(\'/\', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {testComplete();}})';
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->remoteFunction(array('update' => 'myDiv'));
		$expected = 'new Ajax.Updater(\'myDiv\',\'/\', {asynchronous:true, evalScripts:true, requestHeaders:[\'X-Update\', \'myDiv\']})';
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->remoteFunction(array('update' => array('div1', 'div2')));
		$expected = 'new Ajax.Updater(document.createElement(\'div\'),\'/\', {asynchronous:true, evalScripts:true, requestHeaders:[\'X-Update\', \'div1 div2\']})';
		$this->assertEqual($result, $expected);

		$result = $this->Ajax->remoteFunction(array('update' => 'myDiv', 'confirm' => 'Are you sure?'));
		$expected = 'if (confirm(\'Are you sure?\')) { new Ajax.Updater(\'myDiv\',\'/\', {asynchronous:true, evalScripts:true, requestHeaders:[\'X-Update\', \'myDiv\']}); } else { event.returnValue = false; return false; }';
	}
/**
 * testDiv method
 * 
 * @access public
 * @return void
 */
	function testDiv() {
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
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '.+' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/s', $result);
		$this->assertPattern('/^.+\s*' . str_replace('/', '\\/', preg_quote('new Ajax.InPlaceEditor(\'myDiv\', \'/\', {ajaxOptions:{asynchronous:true, evalScripts:true}});')) . '\s*.+$/s', $result);

		$result = $this->Ajax->editor('myDiv', '/', array('complete' => 'testComplete();'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '.+' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/s', $result);
		$this->assertPattern('/^.+\s*' . str_replace('/', '\\/', preg_quote('new Ajax.InPlaceEditor(\'myDiv\', \'/\', {ajaxOptions:{asynchronous:true, evalScripts:true, onComplete:function(request, json) {testComplete();}}});')) . '\s*.+$/s', $result);

		$result = $this->Ajax->editor('myDiv', '/', array('callback' => 'callback();'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '.+' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/s', $result);
		$this->assertPattern('/^.+\s*' . str_replace('/', '\\/', preg_quote('new Ajax.InPlaceEditor(\'myDiv\', \'/\', {callback:function(form, value) {callback();}, ajaxOptions:{asynchronous:true, evalScripts:true}});')) . '\s*.+$/s', $result);

		$result = $this->Ajax->editor('myDiv', '/', array('collection' => array(1 => 'first', 2 => 'second')));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '.+' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/s', $result);
		$this->assertPattern('/^.+\s*' . str_replace('/', '\\/', preg_quote('new Ajax.InPlaceCollectionEditor(\'myDiv\', \'/\', {collection:{"1":"first","2":"second"}, ajaxOptions:{asynchronous:true, evalScripts:true}});')) . '\s*.+$/s', $result);

		$result = $this->Ajax->editor('myDiv', '/', array('var' => 'myVar'));
		$this->assertPattern('/^<script[^<>]+type="text\/javascript"[^<>]*>.+<\/script>$/s', $result);
		$this->assertPattern('/^<script[^<>]+>\s*' . str_replace('/', '\\/', preg_quote('//<![CDATA[')) . '.+' . str_replace('/', '\\/', preg_quote('//]]>')) . '\s*<\/script>$/s', $result);
		$this->assertPattern('/^.+\s*' . str_replace('/', '\\/', preg_quote('var myVar = new Ajax.InPlaceEditor(\'myDiv\', \'/\', {ajaxOptions:{asynchronous:true, evalScripts:true}});')) . '\s*.+$/s', $result);
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
}
?>