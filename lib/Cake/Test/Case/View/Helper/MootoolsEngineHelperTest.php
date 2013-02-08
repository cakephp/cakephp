<?php
/**
 * MooEngineTestCase
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright       Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link            http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.View.Helper
 * @license         MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('HtmlHelper', 'View/Helper');
App::uses('JsHelper', 'View/Helper');
App::uses('MootoolsEngineHelper', 'View/Helper');

class MootoolsEngineHelperTest extends CakeTestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$controller = null;
		$this->View = $this->getMock('View', array('addScript'), array(&$controller));
		$this->Moo = new MootoolsEngineHelper($this->View);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Moo);
	}

/**
 * test selector method
 *
 * @return void
 */
	public function testSelector() {
		$result = $this->Moo->get('#content');
		$this->assertEquals($this->Moo, $result);
		$this->assertEquals($this->Moo->selection, '$("content")');

		$result = $this->Moo->get('a .remove');
		$this->assertEquals($this->Moo, $result);
		$this->assertEquals($this->Moo->selection, '$$("a .remove")');

		$result = $this->Moo->get('document');
		$this->assertEquals($this->Moo, $result);
		$this->assertEquals($this->Moo->selection, "$(document)");

		$result = $this->Moo->get('window');
		$this->assertEquals($this->Moo, $result);
		$this->assertEquals($this->Moo->selection, "$(window)");

		$result = $this->Moo->get('ul');
		$this->assertEquals($this->Moo, $result);
		$this->assertEquals($this->Moo->selection, '$$("ul")');

		$result = $this->Moo->get('#some_long-id.class');
		$this->assertEquals($this->Moo, $result);
		$this->assertEquals($this->Moo->selection, '$$("#some_long-id.class")');
	}

/**
 * test event binding
 *
 * @return void
 */
	public function testEvent() {
		$this->Moo->get('#myLink');
		$result = $this->Moo->event('click', 'doClick', array('wrap' => false));
		$expected = '$("myLink").addEvent("click", doClick);';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->event('click', 'this.setStyle("display", "");', array('stop' => false));
		$expected = '$("myLink").addEvent("click", function (event) {this.setStyle("display", "");});';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->event('click', 'this.setStyle("display", "none");');
		$expected = "\$(\"myLink\").addEvent(\"click\", function (event) {event.stop();\nthis.setStyle(\"display\", \"none\");});";
		$this->assertEquals($expected, $result);
	}

/**
 * test dom ready event creation
 *
 * @return void
 */
	public function testDomReady() {
		$result = $this->Moo->domReady('foo.name = "bar";');
		$expected = 'window.addEvent("domready", function (event) {foo.name = "bar";});';
		$this->assertEquals($expected, $result);
	}

/**
 * test Each method
 *
 * @return void
 */
	public function testEach() {
		$this->Moo->get('#foo');
		$result = $this->Moo->each('item.setStyle("display", "none");');
		$expected = '$("foo").each(function (item, index) {item.setStyle("display", "none");});';
		$this->assertEquals($expected, $result);
	}

/**
 * test Effect generation
 *
 * @return void
 */
	public function testEffect() {
		$this->Moo->get('#foo');
		$result = $this->Moo->effect('show');
		$expected = '$("foo").setStyle("display", "");';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->effect('hide');
		$expected = '$("foo").setStyle("display", "none");';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->effect('fadeIn');
		$expected = '$("foo").fade("in");';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->effect('fadeOut');
		$expected = '$("foo").fade("out");';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->effect('slideIn');
		$expected = '$("foo").slide("in");';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->effect('slideOut');
		$expected = '$("foo").slide("out");';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->effect('slideOut', array('speed' => 'fast'));
		$expected = '$("foo").set("slide", {duration:"short"}).slide("out");';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->effect('slideOut', array('speed' => 'slow'));
		$expected = '$("foo").set("slide", {duration:"long"}).slide("out");';
		$this->assertEquals($expected, $result);
	}

/**
 * Test Request Generation
 *
 * @return void
 */
	public function testRequest() {
		$result = $this->Moo->request(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'var jsRequest = new Request({url:"\\/posts\\/view\\/1"}).send();';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->request('/posts/view/1', array('update' => 'content'));
		$expected = 'var jsRequest = new Request.HTML({update:"content", url:"\\/posts\\/view\\/1"}).send();';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'error' => 'handleError',
			'type' => 'json',
			'data' => array('name' => 'jim', 'height' => '185cm'),
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Request.JSON({method:"post", onComplete:doSuccess, onFailure:handleError, url:"\\/people\\/edit\\/1"}).send({"name":"jim","height":"185cm"});';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'update' => '#update-zone',
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Request.HTML({method:"post", onComplete:doSuccess, update:"update-zone", url:"\\/people\\/edit\\/1"}).send();';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doComplete',
			'success' => 'doSuccess',
			'error' => 'doFailure',
			'before' => 'doBefore',
			'update' => 'update-zone',
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Request.HTML({method:"post", onComplete:doComplete, onFailure:doFailure, onRequest:doBefore, onSuccess:doSuccess, update:"update-zone", url:"\\/people\\/edit\\/1"}).send();';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doComplete',
			'success' => 'doSuccess',
			'error' => 'doFailure',
			'before' => 'doBefore',
			'update' => 'update-zone',
			'dataExpression' => true,
			'data' => '$("foo").toQueryString()',
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Request.HTML({method:"post", onComplete:doComplete, onFailure:doFailure, onRequest:doBefore, onSuccess:doSuccess, update:"update-zone", url:"\\/people\\/edit\\/1"}).send($("foo").toQueryString());';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'before' => 'doBefore',
			'success' => 'doSuccess',
			'complete' => 'doComplete',
			'update' => '#update-zone',
		));
		$expected = 'var jsRequest = new Request.HTML({method:"post", onComplete:function () {doComplete}, onRequest:function () {doBefore}, onSuccess:function (responseText, responseXML) {doSuccess}, update:"update-zone", url:"\\/people\\/edit\\/1"}).send();';
		$this->assertEquals($expected, $result);
	}

/**
 * test sortable list generation
 *
 * @return void
 */
	public function testSortable() {
		$this->Moo->get('#myList');
		$result = $this->Moo->sortable(array(
			'distance' => 5,
			'containment' => 'parent',
			'start' => 'onStart',
			'complete' => 'onStop',
			'sort' => 'onSort',
			'wrapCallbacks' => false
		));
		$expected = 'var jsSortable = new Sortables($("myList"), {constrain:"parent", onComplete:onStop, onSort:onSort, onStart:onStart, snap:5});';
		$this->assertEquals($expected, $result);
	}

/**
 * test drag() method
 *
 * @return void
 */
	public function testDrag() {
		$this->Moo->get('#drag-me');
		$result = $this->Moo->drag(array(
			'start' => 'onStart',
			'drag' => 'onDrag',
			'stop' => 'onStop',
			'snapGrid' => array(10,10),
			'wrapCallbacks' => false
		));
		$expected = '$("drag-me").makeDraggable({onComplete:onStop, onDrag:onDrag, onStart:onStart, snap:[10,10]});';
		$this->assertEquals($expected, $result);
	}

/**
 * test drop() method with the required drag option missing
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testDropWithMissingOption() {
		$this->Moo->get('#drop-me');
		$this->Moo->drop(array(
			'drop' => 'onDrop',
			'leave' => 'onLeave',
			'hover' => 'onHover',
		));
	}

/**
 * test drop() method
 *
 * @return void
 */
	public function testDrop() {
		$this->Moo->get('#drop-me');
		$result = $this->Moo->drop(array(
			'drop' => 'onDrop',
			'leave' => 'onLeave',
			'hover' => 'onHover',
			'drag' => '#my-drag',
			'wrapCallbacks' => false
		));
		$expected = '$("my-drag").makeDraggable({droppables:$("drop-me"), onDrop:onDrop, onEnter:onHover, onLeave:onLeave});';
		$this->assertEquals($expected, $result);
		$this->assertEquals($this->Moo->selection, '$("drop-me")');

		$result = $this->Moo->drop(array(
			'drop' => 'onDrop',
			'leave' => 'onLeave',
			'hover' => 'onHover',
			'drag' => '#my-drag',
		));
		$expected = '$("my-drag").makeDraggable({droppables:$("drop-me"), onDrop:function (element, droppable, event) {onDrop}, onEnter:function (element, droppable) {onHover}, onLeave:function (element, droppable) {onLeave}});';
		$this->assertEquals($expected, $result);
	}

/**
 * test slider generation
 *
 * @return void
 */
	public function testSlider() {
		$this->Moo->get('#slider');
		$result = $this->Moo->slider(array(
			'handle' => '#my-handle',
			'complete' => 'onComplete',
			'change' => 'onChange',
			'direction' => 'horizontal',
			'wrapCallbacks' => false
		));
		$expected = 'var jsSlider = new Slider($("slider"), $("my-handle"), {mode:"horizontal", onChange:onChange, onComplete:onComplete});';
		$this->assertEquals($expected, $result);
		$this->assertEquals($this->Moo->selection, '$("slider")');

		$this->Moo->get('#slider');
		$result = $this->Moo->slider(array(
			'handle' => '#my-handle',
			'complete' => 'onComplete',
			'change' => 'onChange',
			'direction' => 'horizontal',
			'min' => 10,
			'max' => 40,
			'wrapCallbacks' => false
		));
		$expected = 'var jsSlider = new Slider($("slider"), $("my-handle"), {mode:"horizontal", onChange:onChange, onComplete:onComplete, range:[10,40]});';
		$this->assertEquals($expected, $result);

		$this->Moo->get('#slider');
		$result = $this->Moo->slider(array(
			'handle' => '#my-handle',
			'complete' => 'complete;',
			'change' => 'change;',
			'direction' => 'horizontal',
		));
		$expected = 'var jsSlider = new Slider($("slider"), $("my-handle"), {mode:"horizontal", onChange:function (step) {change;}, onComplete:function (event) {complete;}});';
		$this->assertEquals($expected, $result);
	}

/**
 * test the serializeForm implementation.
 *
 * @return void
 */
	public function testSerializeForm() {
		$this->Moo->get('#element');
		$result = $this->Moo->serializeForm(array('isForm' => true));
		$expected = '$("element").toQueryString();';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->serializeForm(array('isForm' => true, 'inline' => true));
		$expected = '$("element").toQueryString()';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->serializeForm(array('isForm' => false));
		$expected = '$($("element").form).toQueryString();';
		$this->assertEquals($expected, $result);

		$result = $this->Moo->serializeForm(array('isForm' => false, 'inline' => true));
		$expected = '$($("element").form).toQueryString()';
		$this->assertEquals($expected, $result);
	}
}
