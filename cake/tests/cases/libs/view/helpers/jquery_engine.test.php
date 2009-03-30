<?php
/**
 * JqueryEngineTestCase
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
App::import('Helper', array('Html', 'Js', 'JqueryEngine'));

class JqueryEngineHelperTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->Jquery =& new JqueryEngineHelper();
	}
/**
 * end test
 *
 * @return void
 **/
	function endTest() {
		unset($this->Jquery);
	}
/**
 * test selector method
 *
 * @return void
 **/
	function testSelector() {
		$result = $this->Jquery->get('#content');
		$this->assertEqual($result, $this->Jquery);
		$this->assertEqual($this->Jquery->selection, '$("#content")');
		
		$result = $this->Jquery->get('document');
		$this->assertEqual($result, $this->Jquery);
		$this->assertEqual($this->Jquery->selection, '$(document)');
		
		$result = $this->Jquery->get('window');
		$this->assertEqual($result, $this->Jquery);
		$this->assertEqual($this->Jquery->selection, '$(window)');
		
		$result = $this->Jquery->get('ul');
		$this->assertEqual($result, $this->Jquery);
		$this->assertEqual($this->Jquery->selection, '$("ul")');
	}
/**
 * test event binding
 *
 * @return void
 **/
	function testEvent() {
		$result = $this->Jquery->get('#myLink')->event('click', 'doClick', array('wrap' => false));
		$expected = '$("#myLink").bind("click", doClick);';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->get('#myLink')->event('click', '$(this).show();', array('stop' => false));
		$expected = '$("#myLink").bind("click", function (event) {$(this).show();});';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->get('#myLink')->event('click', '$(this).hide();');
		$expected = '$("#myLink").bind("click", function (event) {$(this).hide();'."\n".'return false;});';
		$this->assertEqual($result, $expected);
	}
/**
 * test dom ready event creation
 *
 * @return void
 **/
	function testDomReady() {
		$result = $this->Jquery->domReady('foo.name = "bar";');
		$expected = '$(document).bind("ready", function (event) {foo.name = "bar";});';
		$this->assertEqual($result, $expected);
	}
/**
 * test Each method
 *
 * @return void
 **/
	function testEach() {
		$result = $this->Jquery->get('#foo')->each('$(this).hide();');
		$expected = '$("#foo").each(function () {$(this).hide();});';
		$this->assertEqual($result, $expected);
	}
/**
 * test Effect generation
 *
 * @return void
 **/
	function testEffect() {
		$result = $this->Jquery->get('#foo')->effect('show');
		$expected = '$("#foo").show();';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->effect('hide');
		$expected = '$("#foo").hide();';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->effect('hide', array('speed' => 'fast'));
		$expected = '$("#foo").hide("fast");';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->effect('fadeIn');
		$expected = '$("#foo").fadeIn();';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->effect('fadeOut');
		$expected = '$("#foo").fadeOut();';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->effect('slideIn');
		$expected = '$("#foo").slideDown();';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->effect('slideOut');
		$expected = '$("#foo").slideUp();';
		$this->assertEqual($result, $expected);
	}
/**
 * Test Request Generation
 *
 * @return void
 **/
	function testRequest() {
		$result = $this->Jquery->request(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = '$.ajax({url:"/posts/view/1"});';
		$this->assertEqual($result, $expected);

		$result = $this->Jquery->request('/people/edit/1', array(
			'method' => 'post',
			'complete' => 'doSuccess',
			'error' => 'handleError',
			'type' => 'json',
			'data' => array('name' => 'jim', 'height' => '185cm')
		));
		$expected = '$.ajax({data:"name=jim&height=185cm", dataType:"json", error:handleError, method:"post", success:doSuccess, url:"/people/edit/1"});';
		$this->assertEqual($result, $expected);
		
		$result = $this->Jquery->request('/people/edit/1', array(
			'update' => '#updated',
			'success' => 'doFoo',
			'method' => 'post'
		));
		$expected = '$.ajax({method:"post", success:function (msg, status) {$("#updated").html(msg);}, url:"/people/edit/1"});';
		$this->assertEqual($result, $expected);
	}
/**
 * test sortable list generation
 *
 * @return void
 **/
	function testSortable() {
		$result = $this->Jquery->get('#myList')->sortable(array(
			'distance' => 5,
			'containment' => 'parent',
			'start' => 'onStart',
			'complete' => 'onStop',
			'sort' => 'onSort',
		));
		$expected = '$("#myList").sortable({containment:"parent", distance:5, sort:onSort, start:onStart, stop:onStop});';
		$this->assertEqual($result, $expected);
	}
/**
 * test drag() method
 *
 * @return void
 **/
	function testDrag() {
		
	}
/**
 * test drop() method
 *
 * @return void
 **/
	function testDrop() {
		
	}
}
?>