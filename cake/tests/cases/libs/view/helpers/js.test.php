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
App::import('Helper', array('Js', 'Html'));
App::import('Core', array('View', 'ClassRegistry'));

Mock::generate('JsBaseEngineHelper', 'TestJsEngineHelper', array('methodOne'));
Mock::generate('View', 'JsHelperMockView');

/**
 * JsHelper TestCase.
 *
 * @package       cake.tests
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class JsHelperTestCase extends CakeTestCase {
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
	function startTest() {
		$this->Js =& new JsHelper('JsBase');
		$this->Js->Html =& new HtmlHelper(); 
		$this->Js->JsBaseEngine =& new JsBaseEngineHelper();

		$view =& new JsHelperMockView();
		ClassRegistry::addObject('view', $view);
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function endTest() {
		ClassRegistry::removeObject('view');
		unset($this->Js);
	}
/**
 * test object construction
 *
 * @return void
 **/
	function testConstruction() {
		$js = new JsHelper();
		$this->assertEqual($js->helpers, array('Html', 'jqueryEngine')); 

		$js = new JsHelper(array('mootools'));
		$this->assertEqual($js->helpers, array('Html', 'mootoolsEngine')); 

		$js = new JsHelper('prototype');
		$this->assertEqual($js->helpers, array('Html', 'prototypeEngine'));

		$js = new JsHelper('MyPlugin.Dojo');
		$this->assertEqual($js->helpers, array('Html', 'MyPlugin.DojoEngine'));
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

		$js->TestEngine = new StdClass();
		$this->expectError();
		$js->someMethodThatSurelyDoesntExist();
	}
/**
 * test that writeScripts generates scripts inline.
 *
 * @return void
 **/
	function testWriteScriptsNoFile() {
		$this->Js->JsBaseEngine = new TestJsEngineHelper();
		$this->Js->JsBaseEngine->setReturnValue('getCache', array('one = 1;', 'two = 2;'));
		$result = $this->Js->writeScripts(array('onDomReady' => false, 'cache' => false));
		$expected = array(
			'script' => array('type' => 'text/javascript'),
			$this->cDataStart,
			"one = 1;\ntwo = 2;",
			$this->cDataEnd,
			'/script',
		);
		$this->assertTags($result, $expected, true);

		$this->Js->JsBaseEngine->expectAtLeastOnce('domReady');
		$result = $this->Js->writeScripts(array('onDomReady' => true, 'cache' => false));

		$view =& new JsHelperMockView();
		$view->expectAt(0, 'addScript', array(new PatternExpectation('/one\s=\s1;\ntwo\=\2;/')));
		$result = $this->Js->writeScripts(array('onDomReady' => false, 'inline' => false, 'cache' => false));
	}
}


/**
 * JsBaseEngine Class Test case
 *
 * @package cake.tests.view.helpers
 **/
class JsBaseEngineTestCase extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->JsEngine = new JsBaseEngineHelper();
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function endTest() {
		ClassRegistry::removeObject('view');
		unset($this->JsEngine);
	}
/**
 * test escape string skills
 *
 * @return void
 **/
	function testEscaping() {
		$result = $this->JsEngine->escape('');
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('CakePHP' . "\n" . 'Rapid Development Framework');
		$expected = 'CakePHP\\nRapid Development Framework';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('CakePHP' . "\r\n" . 'Rapid Development Framework' . "\r" . 'For PHP');
		$expected = 'CakePHP\\nRapid Development Framework\\nFor PHP';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('CakePHP: "Rapid Development Framework"');
		$expected = 'CakePHP: \\"Rapid Development Framework\\"';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('CakePHP: \'Rapid Development Framework\'');
		$expected = 'CakePHP: \\\'Rapid Development Framework\\\'';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->escape('my \\"string\\"');
		$expected = 'my \\\"string\\\"';
		$this->assertEqual($result, $expected);
	}
/**
 * test prompt() creation
 *
 * @return void
 **/
	function testPrompt() {
		$result = $this->JsEngine->prompt('Hey, hey you', 'hi!');
		$expected = 'prompt("Hey, hey you", "hi!");';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->prompt('"Hey"', '"hi"');
		$expected = 'prompt("\"Hey\"", "\"hi\"");';
		$this->assertEqual($result, $expected);
	}
/**
 * test alert generation
 *
 * @return void
 **/
	function testAlert() {
		$result = $this->JsEngine->alert('Hey there');
		$expected = 'alert("Hey there");';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->alert('"Hey"');
		$expected = 'alert("\"Hey\"");';
		$this->assertEqual($result, $expected);	
	}
/**
 * test confirm generation
 *
 * @return void
 **/
	function testConfirm() {
		$result = $this->JsEngine->confirm('Are you sure?');
		$expected = 'confirm("Are you sure?");';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->confirm('"Are you sure?"');
		$expected = 'confirm("\"Are you sure?\"");';
		$this->assertEqual($result, $expected);	
	}
/**
 * test Redirect
 *
 * @return void
 **/
	function testRedirect() {
		$result = $this->JsEngine->redirect(array('controller' => 'posts', 'action' => 'view', 1));
		$expected = 'window.location = "/posts/view/1";';
		$this->assertEqual($result, $expected);
	}
/**
 * testObject encoding with non-native methods.
 *
 * @return void
 **/
	function testObject() {
		$this->JsEngine->useNative = false;

		$object = array('title' => 'New thing', 'indexes' => array(5, 6, 7, 8));
		$result = $this->JsEngine->object($object);
		$expected = '{"title":"New thing","indexes":[5,6,7,8]}';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->object(array('default' => 0));
		$expected = '{"default":0}';
		$this->assertEqual($result, $expected);

		$result = $this->JsEngine->object(array(
			'2007' => array(
				'Spring' => array(
					'1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				),
				'Fall' => array(
					'1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				)
			), 
			'2006' => array(
				'Spring' => array(
				    '1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				),
				'Fall' => array(
				    '1' => array('id' => 1, 'name' => 'Josh'), '2' => array('id' => 2, 'name' => 'Becky')
				)
			)
		));
		$expected = '{"2007":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}},"2006":{"Spring":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}},"Fall":{"1":{"id":1,"name":"Josh"},"2":{"id":2,"name":"Becky"}}}}';
		$this->assertEqual($result, $expected);
	}
}

?>