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
		$this->assertEqual($this->Jquery->selection, "$('#content')");
		
		$result = $this->Jquery->get('document');
		$this->assertEqual($result, $this->Jquery);
		$this->assertEqual($this->Jquery->selection, "$(document)");
		
		$result = $this->Jquery->get('window');
		$this->assertEqual($result, $this->Jquery);
		$this->assertEqual($this->Jquery->selection, "$(window)");
		
		$result = $this->Jquery->get('ul');
		$this->assertEqual($result, $this->Jquery);
		$this->assertEqual($this->Jquery->selection, "$('ul')");
	}
/**
 * test event binding
 *
 * @return void
 **/
	function testEvent() {
		$result = $this->Jquery->get('#myLink')->event('click', 'doClick');
		$expected = "$('#myLink').bind('click', doClick);";
		$this->assertEqual($result, $expected);
		
		$result = $this->Jquery->get('#myLink')->event('click', '$(this).hide();', true);
		$expected = "\$('#myLink').bind('click', function (event) {\$(this).hide();});";
		$this->assertEqual($result, $expected);
	}
/**
 * test dom ready event creation
 *
 * @return void
 **/
	function testDomReady() {
		$result = $this->Jquery->domReady('foo.name = "bar";');
		$expected = "\$(document).bind('ready', function (event) {foo.name = \"bar\";});";
		$this->assertEqual($result, $expected);
	}
/**
 * test Each method
 *
 * @return void
 **/
	function testEach() {
		$result = $this->Jquery->get('#foo')->each('$(this).hide();');
		$expected = "\$('#foo').each(function () {\$(this).hide();});";
		$this->assertEqual($result, $expected);
		
	}
	
}
?>