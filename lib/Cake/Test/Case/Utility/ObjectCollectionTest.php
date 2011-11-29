<?php
/**
 * ObjectCollectionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ObjectCollection', 'Utility');

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
 * @return array List of loaded objects
 */
	public function load($object, $settings = array()) {
		list($plugin, $name) = pluginSplit($object);
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		$objectClass = $name . 'GenericObject';
		$this->_loaded[$name] = new $objectClass($this, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
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
	public function setup() {
		$this->Objects = new GenericObjectCollection();
	}

/**
 * teardown
 *
 * @return void
 */
	public function teardown() {
		unset($this->Objects);
	}

/**
 * test triggering callbacks on loaded helpers
 *
 * @return void
 */
	public function testLoad() {
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
	public function testUnload() {
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
	public function testSet() {
		$this->Objects->load('First');

		$result = $this->Objects->attached();
		$this->assertEquals(array('First'), $result, 'loaded objects are wrong');

		$result = $this->Objects->set('First', new SecondGenericObject());
		$this->assertInstanceOf('SecondGenericObject', $result['First'], 'set failed');

		$result = $this->Objects->set('Second', new SecondGenericObject());
		$this->assertInstanceOf('SecondGenericObject', $result['Second'], 'set failed');

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
	public function testTrigger() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->mockObjects[] = $this->Objects->TriggerMockFirst;
		$this->mockObjects[] = $this->Objects->TriggerMockSecond;

		$this->Objects->TriggerMockFirst->expects($this->once())
			->method('callback')
			->will($this->returnValue(true));
		$this->Objects->TriggerMockSecond->expects($this->once())
			->method('callback')
			->will($this->returnValue(true));

		$this->assertTrue($this->Objects->trigger('callback'));
	}

/**
 * test trigger and disabled objects
 *
 * @return void
 */
	public function testTriggerWithDisabledObjects() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->mockObjects[] = $this->Objects->TriggerMockFirst;
		$this->mockObjects[] = $this->Objects->TriggerMockSecond;

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
	public function testTriggerWithCollectReturn() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->mockObjects[] = $this->Objects->TriggerMockFirst;
		$this->mockObjects[] = $this->Objects->TriggerMockSecond;

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
	public function testTriggerWithBreak() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->mockObjects[] = $this->Objects->TriggerMockFirst;
		$this->mockObjects[] = $this->Objects->TriggerMockSecond;

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
	public function testTriggerWithModParams() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->mockObjects[] = $this->Objects->TriggerMockFirst;
		$this->mockObjects[] = $this->Objects->TriggerMockSecond;

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
	public function testTriggerModParamsInvalidIndex() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->mockObjects[] = $this->Objects->TriggerMockFirst;
		$this->mockObjects[] = $this->Objects->TriggerMockSecond;

		$this->Objects->TriggerMockFirst->expects($this->never())
			->method('callback');

		$this->Objects->TriggerMockSecond->expects($this->never())
			->method('callback');

		$result = $this->Objects->trigger(
			'callback',
			array(array('value')),
			array('modParams' => 2)
		);
	}

/**
 * test that returrning null doesn't modify parameters.
 *
 * @return void
 */
	public function testTriggerModParamsNullIgnored() {
		$this->_makeMockClasses();
		$this->Objects->load('TriggerMockFirst');
		$this->Objects->load('TriggerMockSecond');

		$this->mockObjects[] = $this->Objects->TriggerMockFirst;
		$this->mockObjects[] = $this->Objects->TriggerMockSecond;

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
			array('modParams' => 0)
		);
		$this->assertEquals(array('new value'), $result);
	}

/**
 * test normalizeObjectArray
 *
 * @return void
 */
	public function testnormalizeObjectArray() {
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

		// This is the result after Controller::_mergeVars
		$components = array(
			'Html' => null,
			'Foo.Bar' => array('one', 'two'),
			'Something' => null,
			'Banana.Apple' => array('foo' => 'bar')
		);
		$result = ObjectCollection::normalizeObjectArray($components);
		$this->assertEquals($expected, $result);
	}

}