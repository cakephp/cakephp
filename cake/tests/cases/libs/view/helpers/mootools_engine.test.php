<?php
/**
 * MooEngineTestCase
 *
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright       Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link            http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package         cake.tests
 * @subpackage      cake.tests.cases.views.helpers
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Helper', array('Html', 'Js', 'MootoolsEngine'));

class MooEngineHelperTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->Moo =& new MootoolsEngineHelper();
	}
/**
 * end test
 *
 * @return void
 **/
	function endTest() {
		unset($this->Moo);
	}
/**
 * test selector method
 *
 * @return void
 **/
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
 **/
	function testEvent() {
		$result = $this->Moo->get('#myLink')->event('click', 'doClick', array('wrap' => false));
		$expected = '$("myLink").addEvent("click", doClick);';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->get('#myLink')->event('click', 'this.setStyle("display", "");', array('stop' => false));
		$expected = '$("myLink").addEvent("click", function (event) {this.setStyle("display", "");});';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->get('#myLink')->event('click', 'this.setStyle("display", "none");');
		$expected = "\$(\"myLink\").addEvent(\"click\", function (event) {event.stop();\nthis.setStyle(\"display\", \"none\");});";
		$this->assertEqual($result, $expected);
	}
/**
 * test dom ready event creation
 *
 * @return void
 **/
	function testDomReady() {
		$result = $this->Moo->domReady('foo.name = "bar";');
		$expected = 'window.addEvent("domready", function (event) {foo.name = "bar";});';
		$this->assertEqual($result, $expected);
	}
/**
 * test Each method
 *
 * @return void
 **/
	function testEach() {
		$result = $this->Moo->get('#foo')->each('item.setStyle("display", "none");');
		$expected = '$("foo").each(function (item, index) {item.setStyle("display", "none");});';
		$this->assertEqual($result, $expected);
	}
/**
 * test Effect generation
 *
 * @return void
 **/
	function testEffect() {
		$result = $this->Moo->get('#foo')->effect('show');
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
 **/
	function testRequest() {
		$result = $this->Moo->request(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'var jsRequest = new Request({url:"/posts/view/1"}).send();';
		$this->assertEqual($result, $expected);
		
		$result = $this->Moo->request('/posts/view/1', array('update' => 'content'));
		$expected = 'var jsRequest = new Request.HTML({update:"content", url:"/posts/view/1"}).send();';
		$this->assertEqual($result, $expected);

		$result = $this->Moo->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'error' => 'handleError',
			'type' => 'json',
			'data' => array('name' => 'jim', 'height' => '185cm')
		));
		$expected = 'var jsRequest = new Request.JSON({method:"post", onComplete:doSuccess, onFailure:handleError, url:"/people/edit/1"}).send({"name":"jim","height":"185cm"});';
		$this->assertEqual($result, $expected);
	}
}
?>