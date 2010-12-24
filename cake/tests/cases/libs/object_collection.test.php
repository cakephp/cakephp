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
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', 'ObjectCollection');

/**
 * A generic object class
 */
class GenericObject {
}

/**
 * First Extension of Generic Object
 */
class FirstGenericObject extends GenericObject {
/**
 * A generic callback
 */
	public function callback() {
	}
}

/**
 * Second Extension of Generic Object
 */
class SecondGenericObject extends GenericObject {
	public function callback() {
	}
}

/**
 * A collection of Generic objects
 */
class GenericObjectCollection extends ObjectCollection {

/**
 * Loads a generic object
 *
 * @param string $object Object name
 * @param array $settings Settings array
 * @param boolean $enable Start object as enabled
 * @return array List of loaded objects
 */
	public function load($object, $settings = array(), $enable = true) {
		list($plugin, $name) = pluginSplit($object);
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		$objectClass = $name . 'GenericObject';
		$this->_loaded[$name] = new $objectClass($this, $settings);
		if ($enable === true) {
			$this->_enabled[] = $name;
		}
		return $this->_loaded[$name];
	}
}

class ObjectCollectionTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	function setup() {
		$this->Objects = new GenericObjectCollection();
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		unset($this->Objects);
	}

/**
 * test triggering callbacks on loaded helpers
 *
 * @return void
 */
	function testLoad() {
		$result = $this->Objects->load('First');
		$this->assertInstanceOf('FirstGenericObject', $result);
		$this->assertInstanceOf('FirstGenericObject', $this->Objects->First);

		$result = $this->Objects->attached();
		$this->assertEquals(array('First'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Objects->enabled('First'));

		$result = $this->Objects->load('First');
		$this->assertSame($result, $this->Objects->First);
	}

/**
 * test unload()
 *
 * @return void
 */
	function testUnload() {
		$this->Objects->load('First');
		$this->Objects->load('Second');

		$result = $this->Objects->attached();
		$this->assertEquals(array('First', 'Second'), $result, 'loaded objects are wrong');

		$this->Objects->unload('First');
		$this->assertFalse(isset($this->Objects->First));
		$this->assertTrue(isset($this->Objects->Second));

		$result = $this->Objects->attached();
		$this->assertEquals(array('Second'), $result, 'loaded objects are wrong');

		$result = $this->Objects->enabled();
		$this->assertEquals(array('Second'), $result, 'enabled objects are wrong');
	}

/**
 * Tests set()
 *
 * @return void
 */
	function testSet() {
		$this->Objects->load('First');

		$result = $this->Objects->attached();
		$this->assertEquals(array('First'), $result, 'loaded objects are wrong');

		$result = $this->Objects->set('First', new SecondGenericObject());
		$this->assertIsA($result['First'], 'SecondGenericObject', 'set failed');

		$result = $this->Objects->set('Second', new SecondGenericObject());
		$this->assertIsA($result['Second'], 'SecondGenericObject', 'set failed');

		$this->assertEquals(count($result), 2);
	}

/**
 * creates mock classes for testing
 *
 * @return void
 */
	protected function _makeMockClasses() {
		if (!class_exists('TriggerMockFirstGenericObject')) {
			$this->getMock('FirstGenericObject', array(), array(), 'TriggerMockFirstGenericObject', false);
		}
		if (!class_exists('TriggerMockSecondGenericObject')) {
			$this->getMock('SecondGenericObject', array(), array(), 'TriggerMockSecondGenericObject', false);
		}
	}

/**
 * test triggering callbacks.
 *
 * @return void
 */
	function testTrigger() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->will($this->returnValue(true));
		$this->Objects->TriggerMockSecond->expects($this->once())
			->method('callback')
			->will($this->returnValue(true));

		$this->assertTrue($this->Objects->trigger('callback'));
	}

/**
 * test that the initalize callback is triggered on all components even those that are disabled.
 *
 * @return void
 */
	function testTriggerWithTriggerDisabledObjects() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst', array(), false);
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->will($this->returnValue(true));
		$this->Objects->TriggerMockSecond->expects($this->once())
			->method('callback')
			->will($this->returnValue(true));

		$result = $this->Objects->trigger('callback', array(), array('triggerDisabled' => true));
		$this->assertTrue($result);
	}

/**
 * test trigger and disabled objects
 *
 * @return void
 */
	function testTriggerWithDisabledObjects() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->will($this->returnValue(true));
		$this->Objects->TriggerMockSecond->expects($this->never())
			->method('callback')
			->will($this->returnValue(true));

		$this->Objects->disable('TriggerMockSecond');

		$this->assertTrue($this->Objects->trigger('callback', array()));
	}

/**
 * test that the collectReturn option works.
 *
 * @return void
 */
	function testTriggerWithCollectReturn() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->will($this->returnValue(array('one', 'two')));
		$this->Objects->TriggerMockSecond->expects($this->once())
			->method('callback')
			->will($this->returnValue(array('three', 'four')));
	
		$result = $this->Objects->trigger('callback', array(), array('collectReturn' => true));
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
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->will($this->returnValue(false));
		$this->Objects->TriggerMockSecond->expects($this->never())
			->method('callback');

		$result = $this->Objects->trigger(
			'callback',
			array(),
			array('break' => true, 'breakOn' => false)
		);
		$this->assertFalse($result);
	}

/**
 * test that trigger with modParams works.
 *
 * @return void
 */
	function testTriggerWithModParams() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->with(array('value'))
			->will($this->returnValue(array('new value')));

		$this->Objects->TriggerMockSecond->expects($this->once())
			->method('callback')
			->with(array('new value'))
			->will($this->returnValue(array('newer value')));

		$result = $this->Objects->trigger(
			'callback',
			array(array('value')),
			array('modParams' => 0)
		);
		$this->assertEquals(array('newer value'), $result);
	}

/**
 * test that setting modParams to an index that doesn't exist doesn't cause errors.
 *
 * @expectedException CakeException
 * @return void
 */
	function testTriggerModParamsInvalidIndex() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->with(array('value'))
			->will($this->returnValue(array('new value')));

		$this->Objects->TriggerMockSecond->expects($this->once())
			->method('callback')
			->with(array('value'))
			->will($this->returnValue(array('newer value')));

		$result = $this->Objects->trigger(
			'callback',
			array(array('value')),
			array('modParams' => 2)
		);
	}

/**
 * test that returrning null doesn't modify parameters.
 *
 * @expectedException CakeException
 * @return void
 */
	function testTriggerModParamsNullIgnored() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->with(array('value'))
			->will($this->returnValue(null));

		$this->Objects->TriggerMockSecond->expects($this->once())
			->method('callback')
			->with(array('value'))
			->will($this->returnValue(array('new value')));

		$result = $this->Objects->trigger(
			'callback',
			array(array('value')),
			array('modParams' => 2)
		);
		$this->assertEquals('new value', $result);
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
		$result = ObjectCollection::normalizeObjectArray($components);
		$expected = array(
			'Html' => array('class' => 'Html', 'settings' => array()),
			'Bar' => array('class' => 'Foo.Bar', 'settings' => array('one', 'two')),
			'Something' => array('class' => 'Something', 'settings' => array()),
			'Apple' => array('class' => 'Banana.Apple', 'settings' => array('foo' => 'bar')),
		);
		$this->assertEquals($expected, $result);
	}

}