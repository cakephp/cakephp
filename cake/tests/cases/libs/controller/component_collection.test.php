<?php
/**
 * ComponentCollectionTest file
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

App::import('Component', array('Cookie', 'Security'));
App::import('Core', 'ComponentCollection');

class ComponentCollectionTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	function setup() {
		$this->Components = new ComponentCollection();
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		unset($this->Components);
	}

/**
 * test triggering callbacks on loaded helpers
 *
 * @return void
 */
	function testLoad() {
		$result = $this->Components->load('Cookie');
		$this->assertType('CookieComponent', $result);
		$this->assertType('CookieComponent', $this->Components->Cookie);

		$result = $this->Components->attached();
		$this->assertEquals(array('Cookie'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Components->enabled('Cookie'));

		$result = $this->Components->load('Cookie');
		$this->assertSame($result, $this->Components->Cookie);
	}

/**
 * test load and enable = false
 *
 * @return void
 */
	function testLoadWithEnableFalse() {
		$result = $this->Components->load('Cookie', array('enabled' => false));
		$this->assertType('CookieComponent', $result);
		$this->assertType('CookieComponent', $this->Components->Cookie);

		$this->assertFalse($this->Components->enabled('Cookie'), 'Cookie should be disabled');
	}
/**
 * test missingcomponent exception
 *
 * @expectedException MissingComponentFileException
 * @return void
 */
	function testLoadMissingComponentFile() {
		$this->Components->load('ThisComponentShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin component.
 *
 * @return void
 */
	function testLoadPluginComponent() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
		));
		$result = $this->Components->load('TestPlugin.OtherComponent');
		$this->assertType('OtherComponentComponent', $result, 'Component class is wrong.');
		$this->assertType('OtherComponentComponent', $this->Components->OtherComponent, 'Class is wrong');
		App::build();
	}

/**
 * test unload()
 *
 * @return void
 */
	function testUnload() {
		$this->Components->load('Cookie');
		$this->Components->load('Security');

		$result = $this->Components->attached();
		$this->assertEquals(array('Cookie', 'Security'), $result, 'loaded components is wrong');

		$this->Components->unload('Cookie');
		$this->assertFalse(isset($this->Components->Cookie));
		$this->assertTrue(isset($this->Components->Security));

		$result = $this->Components->attached();
		$this->assertEquals(array('Security'), $result, 'loaded components is wrong');

		$result = $this->Components->enabled();
		$this->assertEquals(array('Security'), $result, 'enabled components is wrong');
	}

/**
 * creates mock classes for testing
 *
 * @return void
 */
	protected function _makeMockClasses() {
		if (!class_exists('TriggerMockCookieComponent')) {
			$this->getMock('CookieComponent', array(), array(), 'TriggerMockCookieComponent', false);
			$this->getMock('SecurityComponent', array(), array(), 'TriggerMockSecurityComponent', false);
		}
	}

/**
 * test triggering callbacks.
 *
 * @return void
 */
	function testTrigger() {
		$controller = null;
		$this->_makeMockClasses();
		$this->Components->load('TriggerMockCookie');
		$this->Components->load('TriggerMockSecurity');

		$this->Components->TriggerMockCookie->expects($this->once())->method('startup')
			->with(null);
		$this->Components->TriggerMockSecurity->expects($this->once())->method('startup')
			->with(null);
		
		$this->mockObjects[] = $this->Components->TriggerMockCookie;
		$this->mockObjects[] = $this->Components->TriggerMockSecurity;

		$this->assertTrue($this->Components->trigger('startup', array(&$controller)));
	}

/**
 * test that the initalize callback is triggered on all components even those that are disabled.
 *
 * @return void
 */
	function testTriggerWithTriggerDisabledObjects() {
		$controller = 'Not a controller';
		
		$this->_makeMockClasses();
		$this->Components->load('TriggerMockCookie', array(), false);
		$this->Components->load('TriggerMockSecurity');

		$this->Components->TriggerMockCookie->expects($this->once())->method('initialize')
			->with($controller);
		$this->Components->TriggerMockSecurity->expects($this->once())->method('initialize')
			->with($controller);
	
		$this->mockObjects[] = $this->Components->TriggerMockCookie;
		$this->mockObjects[] = $this->Components->TriggerMockSecurity;

		$result = $this->Components->trigger('initialize', array(&$controller), array('triggerDisabled' => true));
		$this->assertTrue($result);
	}

/**
 * test trigger and disabled helpers.
 *
 * @return void
 */
	function testTriggerWithDisabledComponents() {
		$controller = null;
		$this->_makeMockClasses();
		$this->Components->load('TriggerMockCookie');
		$this->Components->load('TriggerMockSecurity');

		$this->Components->TriggerMockCookie->expects($this->once())->method('startup')
			->with($controller);
		$this->Components->TriggerMockSecurity->expects($this->never())->method('startup');

		$this->mockObjects[] = $this->Components->TriggerMockCookie;
		$this->mockObjects[] = $this->Components->TriggerMockSecurity;

		$this->Components->disable('TriggerMockSecurity');

		$this->assertTrue($this->Components->trigger('startup', array(&$controller)));
	}

/**
 * test that the collectReturn option works.
 *
 * @return void
 */
	function testTriggerWithCollectReturn() {
		$controller = null;
		$this->_makeMockClasses();
		$this->Components->load('TriggerMockCookie');
		$this->Components->load('TriggerMockSecurity');

		$this->Components->TriggerMockCookie->expects($this->once())->method('startup')
			->will($this->returnValue(array('one', 'two')));
		$this->Components->TriggerMockSecurity->expects($this->once())->method('startup')
			->will($this->returnValue(array('three', 'four')));
		
		$this->mockObjects[] = $this->Components->TriggerMockCookie;
		$this->mockObjects[] = $this->Components->TriggerMockSecurity;

		$result = $this->Components->trigger('startup', array(&$controller), array('collectReturn' => true));
		$expected = array(
			array('one', 'two'),
			array('three', 'four')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test that trigger with break & breakOn works.
 *
 * @return void
 */
	function testTriggerWithBreak() {
		$controller = null;
		$this->_makeMockClasses();
		$this->Components->load('TriggerMockCookie');
		$this->Components->load('TriggerMockSecurity');

		$this->Components->TriggerMockCookie->expects($this->once())->method('startup')
			->will($this->returnValue(false));
		$this->Components->TriggerMockSecurity->expects($this->never())->method('startup');
		
		$this->mockObjects[] = $this->Components->TriggerMockCookie;
		$this->mockObjects[] = $this->Components->TriggerMockSecurity;

		$result = $this->Components->trigger(
			'startup', 
			array(&$controller), 
			array('break' => true, 'breakOn' => false)
		);
		$this->assertFalse($result);
	}

/**
 * test normalizeObjectArray
 *
 * @return void
 */
	function testnormalizeObjectArray() {
		$components = array(
			'Html',
			'Foo.Bar' => array('one', 'two'),
			'Something',
			'Banana.Apple' => array('foo' => 'bar')
		);
		$result = ComponentCollection::normalizeObjectArray($components);
		$expected = array(
			'Html' => array('class' => 'Html', 'settings' => array()),
			'Bar' => array('class' => 'Foo.Bar', 'settings' => array('one', 'two')),
			'Something' => array('class' => 'Something', 'settings' => array()),
			'Apple' => array('class' => 'Banana.Apple', 'settings' => array('foo' => 'bar')),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test getting the controller out of the collection
 *
 * @return void
 */
	function testGetController() {
		$controller = $this->getMock('Controller');
		$controller->components = array('Security');
		$this->Components->init($controller);
		$result = $this->Components->getController();

		$this->assertSame($controller, $result);
	}
}