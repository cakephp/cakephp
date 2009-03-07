<?php
/* SVN FILE: $Id$ */
/**
 * JsHelper Test Case
 *
 * TestCase for the JsHelper
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
 * @package       cake.tests
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Helper', 'Js');

Mock::generate('Helper', 'TestJsEngineHelper', array('methodOne'));
/**
 * JsHelper TestCase.
 *
 * @package       cake.tests
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class JsHelperTestCase extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->Js = new JsHelper();
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function endTest() {
		unset($this->Js);
	}
/**
 * test object construction
 *
 * @return void
 **/
	function testConstruction() {
		$js = new JsHelper();
		$this->assertEqual($js->helpers, array('jqueryEngine')); 
		
		$js = new JsHelper(array('mootools'));
		$this->assertEqual($js->helpers, array('mootoolsEngine')); 
		
		$js = new JsHelper('prototype');
		$this->assertEqual($js->helpers, array('prototypeEngine'));
		
		$js = new JsHelper('MyPlugin.Dojo');
		$this->assertEqual($js->helpers, array('MyPlugin.DojoEngine'));
	}
/**
 * test that methods dispatch internally and to the engine class
 *
 * @return void
 **/
	function testMethodDispatching() {
		$js = new JsHelper(array('TestJs'));
		$js->TestJsEngine = new TestJsEngineHelper();
		$js->TestJsEngine->expectOnce('dispatchMethod', array('methodOne', array()));
		
		$js->methodOne();
	}
/**
 * test escape string skills
 *
 * @return void
 **/
	function testEscaping() {
		$result = $this->Js->escape('');
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->Js->escape('CakePHP' . "\n" . 'Rapid Development Framework');
		$expected = 'CakePHP\\nRapid Development Framework';
		$this->assertEqual($result, $expected);

		$result = $this->Js->escape('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
		$expected = 'CakePHP\\nRapid Development Framework\\nFor PHP';
		$this->assertEqual($result, $expected);

		$result = $this->Js->escape('CakePHP: "Rapid Development Framework"');
		$expected = 'CakePHP: \\"Rapid Development Framework\\"';
		$this->assertEqual($result, $expected);

		$result = $this->Js->escape('CakePHP: \'Rapid Development Framework\'');
		$expected = 'CakePHP: \\\'Rapid Development Framework\\\'';
		$this->assertEqual($result, $expected);

		$result = $this->Js->escape('my \\"string\\"');
		$expected = 'my \\\"string\\\"';
		$this->assertEqual($result, $expected);
	}
/**
 * test prompt() creation
 *
 * @return void
 **/
	function testPrompt() {
		$result = $this->Js->prompt('Hey, hey you', 'hi!');
		$expected = 'prompt("Hey, hey you", "hi!");';
		$this->assertEqual($result, $expected);
		
		$result = $this->Js->prompt('"Hey"', '"hi"');
		$expected = 'prompt("\"Hey\"", "\"hi\"");';
		$this->assertEqual($result, $expected);
	}
/**
 * test alert generation
 *
 * @return void
 **/
	function testAlert() {
		$result = $this->Js->alert('Hey there');
		$expected = 'alert("Hey there");';
		$this->assertEqual($result, $expected);
		
		$result = $this->Js->alert('"Hey"');
		$expected = 'alert("\"Hey\"");';
		$this->assertEqual($result, $expected);	
	}
/**
 * test confirm generation
 *
 * @return void
 **/
	function testConfirm() {
		$result = $this->Js->confirm('Are you sure?');
		$expected = 'confirm("Are you sure?");';
		$this->assertEqual($result, $expected);

		$result = $this->Js->confirm('"Are you sure?"');
		$expected = 'confirm("\"Are you sure?\"");';
		$this->assertEqual($result, $expected);	
	}
/**
 * test Redirect 
 *
 * @return void
 **/
	function testRedirect() {
		$result = $this->Js->redirect(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'window.location = "/posts/view/1";';
		$this->assertEqual($result, $expected);
	}
}

?>