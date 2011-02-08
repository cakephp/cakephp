<?php
/**
 * PrototypeEngine TestCase
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright       Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link            http://cakephp.org CakePHP Project
 * @package         cake.tests
 * @subpackage      cake.tests.cases.views.helpers
 * @license         MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Helper', array('Html', 'Js', 'PrototypeEngine'));

class PrototypeEngineHelperTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 */
	function startTest() {
		$this->Proto =& new PrototypeEngineHelper();
	}

/**
 * end test
 *
 * @return void
 */
	function endTest() {
		unset($this->Proto);
	}

/**
 * test selector method
 *
 * @return void
 */
	function testSelector() {
		$result = $this->Proto->get('#content');
		$this->assertEqual($result, $this->Proto);
		$this->assertEqual($this->Proto->selection, '$("content")');

		$result = $this->Proto->get('a .remove');
		$this->assertEqual($result, $this->Proto);
		$this->assertEqual($this->Proto->selection, '$$("a .remove")');

		$result = $this->Proto->get('document');
		$this->assertEqual($result, $this->Proto);
		$this->assertEqual($this->Proto->selection, "$(document)");

		$result = $this->Proto->get('window');
		$this->assertEqual($result, $this->Proto);
		$this->assertEqual($this->Proto->selection, "$(window)");

		$result = $this->Proto->get('ul');
		$this->assertEqual($result, $this->Proto);
		$this->assertEqual($this->Proto->selection, '$$("ul")');

		$result = $this->Proto->get('#some_long-id.class');
		$this->assertEqual($result, $this->Proto);
		$this->assertEqual($this->Proto->selection, '$$("#some_long-id.class")');
	}

/**
 * test event binding
 *
 * @return void
 */
	function testEvent() {
		$this->Proto->get('#myLink');
		$result = $this->Proto->event('click', 'doClick', array('wrap' => false));
		$expected = '$("myLink").observe("click", doClick);';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->event('click', 'Element.hide(this);', array('stop' => false));
		$expected = '$("myLink").observe("click", function (event) {Element.hide(this);});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->event('click', 'Element.hide(this);');
		$expected = "\$(\"myLink\").observe(\"click\", function (event) {event.stop();\nElement.hide(this);});";
		$this->assertEqual($result, $expected);
	}

/**
 * test dom ready event creation
 *
 * @return void
 */
	function testDomReady() {
		$result = $this->Proto->domReady('foo.name = "bar";');
		$expected = 'document.observe("dom:loaded", function (event) {foo.name = "bar";});';
		$this->assertEqual($result, $expected);
	}

/**
 * test Each method
 *
 * @return void
 */
	function testEach() {
		$this->Proto->get('#foo li');
		$result = $this->Proto->each('item.hide();');
		$expected = '$$("#foo li").each(function (item, index) {item.hide();});';
		$this->assertEqual($result, $expected);
	}

/**
 * test Effect generation
 *
 * @return void
 */
	function testEffect() {
		$this->Proto->get('#foo');
		$result = $this->Proto->effect('show');
		$expected = '$("foo").show();';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('hide');
		$expected = '$("foo").hide();';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('fadeIn');
		$expected = '$("foo").appear();';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('fadeIn', array('speed' => 'fast'));
		$expected = '$("foo").appear({duration:0.50000000000});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('fadeIn', array('speed' => 'slow'));
		$expected = '$("foo").appear({duration:2});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('fadeOut');
		$expected = '$("foo").fade();';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('fadeOut', array('speed' => 'fast'));
		$expected = '$("foo").fade({duration:0.50000000000});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('fadeOut', array('speed' => 'slow'));
		$expected = '$("foo").fade({duration:2});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('slideIn');
		$expected = 'Effect.slideDown($("foo"));';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('slideOut');
		$expected = 'Effect.slideUp($("foo"));';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('slideOut', array('speed' => 'fast'));
		$expected = 'Effect.slideUp($("foo"), {duration:0.50000000000});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->effect('slideOut', array('speed' => 'slow'));
		$expected = 'Effect.slideUp($("foo"), {duration:2});';
		$this->assertEqual($result, $expected);
	}

/**
 * Test Request Generation
 *
 * @return void
 */
	function testRequest() {
		$result = $this->Proto->request(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'var jsRequest = new Ajax.Request("/posts/view/1");';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->request('/posts/view/1', array(
			'method' => 'post',
			'complete' => 'doComplete',
			'before' => 'doBefore',
			'success' => 'doSuccess',
			'error' => 'doError',
			'data' => array('name' => 'jim', 'height' => '185cm'),
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Ajax.Request("/posts/view/1", {method:"post", onComplete:doComplete, onCreate:doBefore, onFailure:doError, onSuccess:doSuccess, parameters:{"name":"jim","height":"185cm"}});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->request('/posts/view/1', array('update' => 'content'));
		$expected = 'var jsRequest = new Ajax.Updater("content", "/posts/view/1");';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'update' => '#update-zone',
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Ajax.Updater("update-zone", "/people/edit/1", {method:"post", onComplete:doSuccess});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'error' => 'handleError',
			'type' => 'json',
			'data' => array('name' => 'jim', 'height' => '185cm'),
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {method:"post", onComplete:doSuccess, onFailure:handleError, parameters:{"name":"jim","height":"185cm"}});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'error' => 'handleError',
			'type' => 'json',
			'data' => '$("element").serialize()',
			'dataExpression' => true,
			'wrapCallbacks' => false
		));
		$expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {method:"post", onComplete:doSuccess, onFailure:handleError, parameters:$("element").serialize()});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->request('/people/edit/1', array(
			'method' => 'post',
			'before' => 'doBefore();',
			'success' => 'doSuccess();',
			'complete' => 'doComplete();',
			'error' => 'handleError();',
		));
		$expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {method:"post", onComplete:function (transport) {doComplete();}, onCreate:function (transport) {doBefore();}, onFailure:function (response, jsonHeader) {handleError();}, onSuccess:function (response, jsonHeader) {doSuccess();}});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->request('/people/edit/1', array(
			'async' => false,
			'method' => 'post',
			'before' => 'doBefore();',
			'success' => 'doSuccess();',
			'complete' => 'doComplete();',
			'error' => 'handleError();',
		));
		$expected = 'var jsRequest = new Ajax.Request("/people/edit/1", {asynchronous:false, method:"post", onComplete:function (transport) {doComplete();}, onCreate:function (transport) {doBefore();}, onFailure:function (response, jsonHeader) {handleError();}, onSuccess:function (response, jsonHeader) {doSuccess();}});';
		$this->assertEqual($result, $expected);

		$this->Proto->get('#submit');
		$result = $this->Proto->request('/users/login', array(
			'before' => 'login.create(event)',
			'complete' => 'login.complete(event)',
			'update' => 'auth',
			'data' => $this->Proto->serializeForm(array('isForm' => false, 'inline' => true)),
			'dataExpression' => true
		));
		$this->assertTrue(strpos($result, '$($("submit").form).serialize()') > 0);
		$this->assertFalse(strpos($result, 'parameters:function () {$($("submit").form).serialize()}') > 0);
	}

/**
 * test sortable list generation
 *
 * @return void
 */
	function testSortable() {
		$this->Proto->get('#myList');
		$result = $this->Proto->sortable(array(
			'complete' => 'onComplete',
			'sort' => 'onSort',
			'wrapCallbacks' => false
		));
		$expected = 'var jsSortable = Sortable.create($("myList"), {onChange:onSort, onUpdate:onComplete});';
		$this->assertEqual($result, $expected);
	}

/**
 * test drag() method.  Scriptaculous lacks the ability to take an Array of Elements
 * in new Drag() when selection is a multiple type.  Iterate over the array.
 *
 * @return void
 */
	function testDrag() {
		$this->Proto->get('#element');
		$result = $this->Proto->drag(array(
			'start' => 'onStart',
			'drag' => 'onDrag',
			'stop' => 'onStop',
			'snapGrid' => array(10, 10),
			'wrapCallbacks' => false
		));
		$expected = 'var jsDrag = new Draggable($("element"), {onDrag:onDrag, onEnd:onStop, onStart:onStart, snap:[10,10]});';
		$this->assertEqual($result, $expected);

		$this->Proto->get('div.dragger');
		$result = $this->Proto->drag(array(
			'start' => 'onStart',
			'drag' => 'onDrag',
			'stop' => 'onStop',
			'snapGrid' => array(10, 10),
			'wrapCallbacks' => false
		));
		$expected = '$$("div.dragger").each(function (item, index) {new Draggable(item, {onDrag:onDrag, onEnd:onStop, onStart:onStart, snap:[10,10]});});';
		$this->assertEqual($result, $expected);
	}

/**
 * test drop() method
 *
 * @return void
 */
	function testDrop() {
		$this->Proto->get('#element');
		$result = $this->Proto->drop(array(
			'hover' => 'onHover',
			'drop' => 'onDrop',
			'accept' => '.drag-me',
			'wrapCallbacks' => false
		));
		$expected = 'Droppables.add($("element"), {accept:".drag-me", onDrop:onDrop, onHover:onHover});';
		$this->assertEqual($result, $expected);
	}

/**
 * ensure that slider() method behaves properly
 *
 * @return void
 */
	function testSlider() {
		$this->Proto->get('#element');
		$result = $this->Proto->slider(array(
			'handle' => '#handle',
			'direction' => 'horizontal',
			'change' => 'onChange',
			'complete' => 'onComplete',
			'value' => 4,
			'wrapCallbacks' => false
		));
		$expected = 'var jsSlider = new Control.Slider($("handle"), $("element"), {axis:"horizontal", onChange:onComplete, onSlide:onChange, sliderValue:4});';
		$this->assertEqual($result, $expected);

		$this->Proto->get('#element');
		$result = $this->Proto->slider(array(
			'handle' => '#handle',
			'change' => 'change();',
			'complete' => 'complete();',
			'value' => 4,
			'min' => 10,
			'max' => 100
		));
		$expected = 'var jsSlider = new Control.Slider($("handle"), $("element"), {onChange:function (value) {complete();}, onSlide:function (value) {change();}, range:$R(10,100), sliderValue:4});';
		$this->assertEqual($result, $expected);
	}

/**
 * test the serializeForm implementation.
 *
 * @return void
 */
	function testSerializeForm() {
		$this->Proto->get('#element');
		$result = $this->Proto->serializeForm(array('isForm' => true));
		$expected = '$("element").serialize();';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->serializeForm(array('isForm' => true, 'inline' => true));
		$expected = '$("element").serialize()';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->serializeForm(array('isForm' => false));
		$expected = '$($("element").form).serialize();';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->serializeForm(array('isForm' => false, 'inline' => true));
		$expected = '$($("element").form).serialize()';
		$this->assertEqual($result, $expected);
	}
}
