<?php
/**
 * HelperCollectionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', 'HelperCollection');
App::import('View', 'View');

class HelperCollectionTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	function setup() {
		$this->View = $this->getMock('View', array(), array(null));
		$this->Helpers = new HelperCollection($this->View);
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		unset($this->Helpers, $this->View);
	}

/**
 * test triggering callbacks on loaded helpers
 *
 * @return void
 */
	function testLoad() {
		$result = $this->Helpers->load('Html');
		$this->assertType('HtmlHelper', $result);
		$this->assertType('HtmlHelper', $this->Helpers->Html);

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Html'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Helpers->enabled('Html'));
	}

/**
 * test load and enable = false
 *
 * @return void
 */
	function testLoadWithEnableFalse() {
		$result = $this->Helpers->load('Html', array(), false);
		$this->assertType('HtmlHelper', $result);
		$this->assertType('HtmlHelper', $this->Helpers->Html);

		$this->assertFalse($this->Helpers->enabled('Html'), 'Html should be disabled');
	}

/**
 * test that the callbacks setting disables the helper.
 *
 * @return void
 */
	function testLoadWithCallbacksFalse() {
		$result = $this->Helpers->load('Html', array('callbacks' => false));
		$this->assertType('HtmlHelper', $result);
		$this->assertType('HtmlHelper', $this->Helpers->Html);

		$this->assertFalse($this->Helpers->enabled('Html'), 'Html should be disabled');
	}

/**
 * test missinghelper exception
 *
 * @expectedException MissingHelperFileException
 * @return void
 */
	function testLoadMissingHelperFile() {
		$result = $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin helper.
 *
 * @return void
 */
	function testLoadPluginHelper() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
		));
		$result = $this->Helpers->load('TestPlugin.OtherHelper');
		$this->assertType('OtherHelperHelper', $result, 'Helper class is wrong.');
		$this->assertType('OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');

		App::build();
	}

/**
 * test unload()
 *
 * @return void
 */
	function testUnload() {
		$this->Helpers->load('Form');
		$this->Helpers->load('Html');

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Form', 'Html'), $result, 'loaded helpers is wrong');

		$this->Helpers->unload('Html');
		$this->assertFalse(isset($this->Helpers->Html));
		$this->assertTrue(isset($this->Helpers->Form));

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Form'), $result, 'loaded helpers is wrong');
	}

/**
 * test triggering callbacks.
 *
 * @return void
 */
	function testTrigger() {
		if (!class_exists('TriggerMockHtmlHelper')) {
			$this->getMock('HtmlHelper', array(), array($this->View), 'TriggerMockHtmlHelper');
			$this->getMock('FormHelper', array(), array($this->View), 'TriggerMockFormHelper');
		}
		
		$this->Helpers->load('TriggerMockHtml');
		$this->Helpers->load('TriggerMockForm');

		$this->Helpers->TriggerMockHtml->expects($this->once())->method('beforeRender')
			->with('one', 'two');
		$this->Helpers->TriggerMockForm->expects($this->once())->method('beforeRender')
			->with('one', 'two');
		
		$this->mockObjects[] = $this->Helpers->TriggerMockForm;

		$this->assertTrue($this->Helpers->trigger('beforeRender', array('one', 'two')));
	}

/**
 * test trigger and disabled helpers.
 *
 * @return void
 */
	function testTriggerWithDisabledHelpers() {
		if (!class_exists('TriggerMockHtmlHelper')) {
			$this->getMock('HtmlHelper', array(), array(), 'TriggerMockHtmlHelper', false);
			$this->getMock('FormHelper', array(), array(), 'TriggerMockFormHelper', false);
		}

		$this->Helpers->load('TriggerMockHtml');
		$this->Helpers->load('TriggerMockForm');
	
		$this->Helpers->TriggerMockHtml->expects($this->once())->method('beforeRender')
			->with('one', 'two');
		$this->Helpers->TriggerMockForm->expects($this->never())->method('beforeRender');

		$this->mockObjects[] = $this->Helpers->TriggerMockForm;
		$this->mockObjects[] = $this->Helpers->TriggerMockHtml;

		$this->Helpers->disable('TriggerMockForm');

		$this->assertTrue($this->Helpers->trigger('beforeRender', array('one', 'two')));
	}

/**
 * test normalizeObjectArray
 *
 * @return void
 */
	function testnormalizeObjectArray() {
		$helpers = array(
			'Html', 
			'Foo.Bar' => array('one', 'two'),
			'Something',
			'Banana.Apple' => array('foo' => 'bar')
		);
		$result = ObjectCollection::normalizeObjectArray($helpers);
		$expected = array(
			'Html' => array('class' => 'Html', 'settings' => array()),
			'Bar' => array('class' => 'Foo.Bar', 'settings' => array('one', 'two')),
			'Something' => array('class' => 'Something', 'settings' => array()),
			'Apple' => array('class' => 'Banana.Apple', 'settings' => array('foo' => 'bar')),
		);
		$this->assertEquals($expected, $result);
	}
}