<?php
/**
 * MooEngineTestCase
 *
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright       Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link            http://cakephp.org CakePHP Project
 * @package         cake.tests
 * @subpackage      cake.tests.cases.views.helpers
 * @license         MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Helper', array('Html', 'Js', 'MootoolsEngine'));

class MooEngineHelperTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 */
	function startTest() {
		$this->Moo =& new MootoolsEngineHelper();
	}
/**
 * end test
 *
 * @return void
 */
	function endTest() {
		unset($this->Moo);
	}
/**
 * test selector method
 *
 * @return void
 */
	function testSelector() {
		$result = $this->Moo->get('#content');
		$this->assertEqual($result, $this->Moo);
		$this->assertEqual($this->Moo->selection, '$("content")');

		$result = $this->Moo->get('a .remove');
		$this->assertEqual($result, $this->Moo);
		$this->assertEqual($this->Moo->selection, '$$("a .remove")');

		$result = $this->Moo->get('document');
		$this->assertEqual($result, $this->Moo);
		$this->assertEqual($this->Moo->selection, "$(document)");

		$result = $this->Moo->get('window');
		$this->assertEqual($result, $this->Moo);
		$this->assertEqual($this->Moo->selection, "$(window)");

		$result = $this->Moo->get('ul');
		$this->assertEqual($result, $this->Moo);
		$this->assertEqual($this->Moo->selection, '$$("ul")');

		$result = $this->Moo->get('#some_long-id.class');
		$this->assertEqual($result, $this->Moo);
		$this->assertEqual($this->Moo->selection, '$$("#some_long-id.class")');
	}
/**
 * test event binding
 *
 * @return void
 */
	function testEvent() {
		$this->Moo->get('#myLink');
		$result = $this->Moo->event('click', 'doClick', array('wrap' => false));
		$expected = '$("myLink").addEvent("click", doClick);';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->event('click', 'this.setStyle("display", "");', array('stop' => false));
		$expected = '$("myLink").addEvent("click", function (event) {this.setStyle("display", "");});';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->event('click', 'this.setStyle("display", "none");');
		$expected = "\$(\"myLink\").addEvent(\"click\", function (event) {event.stop();\nthis.setStyle(\"display\", \"none\");});";
		$this->assertEqual($result, $expected);
	}
/**
 * test dom ready event creation
 *
 * @return void
 */
	function testDomReady() {
		$result = $this->Moo->domReady('foo.name = "bar";');
		$expected = 'window.addEvent("domready", function (event) {foo.name = "bar";});';
		$this->assertEqual($result, $expected);
	}
/**
 * test Each method
 *
 * @return void
 */
	function testEach() {
		$this->Moo->get('#foo');
		$result = $this->Moo->each('item.setStyle("display", "none");');
		$expected = '$("foo").each(function (item, index) {item.setStyle("display", "none");});';
		$this->assertEqual($result, $expected);
	}
/**
 * test Effect generation
 *
 * @return void
 */
	function testEffect() {
		$this->Moo->get('#foo');
		$result = $this->Moo->effect('show');
		$expected = '$("foo").setStyle("display", "");';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->effect('hide');
		$expected = '$("foo").setStyle("display", "none");';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->effect('fadeIn');
		$expected = '$("foo").fade("in");';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->effect('fadeOut');
		$expected = '$("foo").fade("out");';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->effect('slideIn');
		$expected = '$("foo").slide("in");';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->effect('slideOut');
		$expected = '$("foo").slide("out");';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->effect('slideOut', array('speed' => 'fast'));
		$expected = '$("foo").set("slide", {duration:"short"}).slide("out");';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->effect('slideOut', array('speed' => 'slow'));
		$expected = '$("foo").set("slide", {duration:"long"}).slide("out");';
		$this->assertEqual($result, $expected);
	}
/**
 * Test Request Generation
 *
 * @return void
 */
	function testRequest() {
		$result = $this->Moo->request(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'var jsRequest = new Request({url:"\\/posts\\/view\\/1"}).send();';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->request('/posts/view/1', array('update' => 'content'));
		$expected = 'var jsRequest = new Request.HTML({update:"content", url:"\\/posts\\/view\\/1"}).send();';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'error' => 'handleError',
			'type' => 'json',
			'data' => array('name' => 'jim', 'height' => '185cm'),
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Request.JSON({method:"post", onComplete:doSuccess, onFailure:handleError, url:"\\/people\\/edit\\/1"}).send({"name":"jim","height":"185cm"});';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'update' => '#update-zone',
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Request.HTML({method:"post", onComplete:doSuccess, update:"update-zone", url:"\\/people\\/edit\\/1"}).send();';
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

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
		$this->assertEqual($result, $expected);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'before' => 'doBefore',
			'success' => 'doSuccess',
			'complete' => 'doComplete',
			'update' => '#update-zone',
		));
		$expected = 'var jsRequest = new Request.HTML({method:"post", onComplete:function () {doComplete}, onRequest:function () {doBefore}, onSuccess:function (responseText, responseXML) {doSuccess}, update:"update-zone", url:"\\/people\\/edit\\/1"}).send();';
		$this->assertEqual($result, $expected);
	}
/**
 * test sortable list generation
 *
 * @return void
 */
	function testSortable() {
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
		$this->assertEqual($result, $expected);
	}
/**
 * test drag() method
 *
 * @return void
 */
	function testDrag() {
		$this->Moo->get('#drag-me');
		$result = $this->Moo->drag(array(
			'start' => 'onStart',
			'drag' => 'onDrag',
			'stop' => 'onStop',
			'snapGrid' => array(10,10),
			'wrapCallbacks' => false
		));
		$expected = '$("drag-me").makeDraggable({onComplete:onStop, onDrag:onDrag, onStart:onStart, snap:[10,10]});';
		$this->assertEqual($result, $expected);
	}
/**
 * test drop() method
 *
 * @return void
 */
	function testDrop() {
		$this->expectError();
		$this->Moo->get('#drop-me');
		$this->Moo->drop(array(
			'drop' => 'onDrop',
			'leave' => 'onLeave',
			'hover' => 'onHover',
		));

		$result = $this->Moo->drop(array(
			'drop' => 'onDrop',
			'leave' => 'onLeave',
			'hover' => 'onHover',
			'drag' => '#my-drag',
			'wrapCallbacks' => false
		));
		$expected = '$("my-drag").makeDraggable({droppables:$("drop-me"), onDrop:onDrop, onEnter:onHover, onLeave:onLeave});';
		$this->assertEqual($result, $expected);
		$this->assertEqual($this->Moo->selection, '$("drop-me")');

		$result = $this->Moo->drop(array(
			'drop' => 'onDrop',
			'leave' => 'onLeave',
			'hover' => 'onHover',
			'drag' => '#my-drag',
		));
		$expected = '$("my-drag").makeDraggable({droppables:$("drop-me"), onDrop:function (element, droppable, event) {onDrop}, onEnter:function (element, droppable) {onHover}, onLeave:function (element, droppable) {onLeave}});';
		$this->assertEqual($result, $expected);
	}
/**
 * test slider generation
 *
 * @return void
 */
	function testSlider() {
		$this->Moo->get('#slider');
		$result = $this->Moo->slider(array(
			'handle' => '#my-handle',
			'complete' => 'onComplete',
			'change' => 'onChange',
			'direction' => 'horizontal',
			'wrapCallbacks' => false
		));
		$expected = 'var jsSlider = new Slider($("slider"), $("my-handle"), {mode:"horizontal", onChange:onChange, onComplete:onComplete});';
		$this->assertEqual($result, $expected);
		$this->assertEqual($this->Moo->selection, '$("slider")');

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
		$this->assertEqual($result, $expected);

		$this->Moo->get('#slider');
		$result = $this->Moo->slider(array(
			'handle' => '#my-handle',
			'complete' => 'complete;',
			'change' => 'change;',
			'direction' => 'horizontal',
		));
		$expected = 'var jsSlider = new Slider($("slider"), $("my-handle"), {mode:"horizontal", onChange:function (step) {change;}, onComplete:function (event) {complete;}});';
		$this->assertEqual($result, $expected);
	}
/**
 * test the serializeForm implementation.
 *
 * @return void
 */
	function testSerializeForm() {
		$this->Moo->get('#element');
		$result = $this->Moo->serializeForm(array('isForm' => true));
		$expected = '$("element").toQueryString();';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->serializeForm(array('isForm' => true, 'inline' => true));
		$expected = '$("element").toQueryString()';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->serializeForm(array('isForm' => false));
		$expected = '$($("element").form).toQueryString();';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->serializeForm(array('isForm' => false, 'inline' => true));
		$expected = '$($("element").form).toQueryString()';
		$this->assertEqual($result, $expected);
	}
}
