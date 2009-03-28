<?php
/**
 * PrototypeEngine TestCase
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
App::import('Helper', array('Html', 'Js', 'PrototypeEngine'));

class PrototypeEngineHelperTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->Proto =& new PrototypeEngineHelper();
	}
/**
 * end test
 *
 * @return void
 **/
	function endTest() {
		unset($this->Proto);
	}
/**
 * test selector method
 *
 * @return void
 **/
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
 **/
	function testEvent() {
		$result = $this->Proto->get('#myLink')->event('click', 'doClick', array('wrap' => false));
		$expected = '$("myLink").observe("click", doClick);';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->get('#myLink')->event('click', 'Element.hide(this);', array('stop' => false));
		$expected = '$("myLink").observe("click", function (event) {Element.hide(this);});';
		$this->assertEqual($result, $expected);

		$result = $this->Proto->get('#myLink')->event('click', 'Element.hide(this);');
		$expected = "\$(\"myLink\").observe(\"click\", function (event) {event.stop();\nElement.hide(this);});";
		$this->assertEqual($result, $expected);
	}
/**
 * test dom ready event creation
 *
 * @return void
 **/
	function testDomReady() {
		$result = $this->Proto->domReady('foo.name = "bar";');
		$expected = 'document.observe("dom:loaded", function (event) {foo.name = "bar";});';
		$this->assertEqual($result, $expected);
	}
/**
 * test Each method
 *
 * @return void
 **/
	function testEach() {

	}
/**
 * test Effect generation
 *
 * @return void
 **/
	function testEffect() {

	}
/**
 * Test Request Generation
 *
 * @return void
 **/
	function testRequest() {

	}
/**
 * test sortable list generation
 *
 * @return void
 **/
	function testSortable() {

	}
}
?>