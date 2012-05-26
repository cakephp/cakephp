<?php
/**
 * ComponentCollectionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Controller;
use Cake\TestSuite\TestCase,
	Cake\Controller\ComponentCollection,
	Cake\Controller\Component\CookieComponent,
	Cake\Core\App,
	Cake\Core\Plugin;

/**
 * Extended CookieComponent
 */
class CookieAliasComponent extends CookieComponent {
}

class ComponentCollectionTest extends TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Components = new ComponentCollection();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Components);
	}

/**
 * test triggering callbacks on loaded helpers
 *
 * @return void
 */
	public function testLoad() {
		$result = $this->Components->load('Cookie');
		$this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $result);
		$this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $this->Components->Cookie);

		$result = $this->Components->attached();
		$this->assertEquals(array('Cookie'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Components->enabled('Cookie'));

		$result = $this->Components->load('Cookie');
		$this->assertSame($result, $this->Components->Cookie);
	}

/**
 * Tests loading as an alias
 *
 * @return void
 */
	public function testLoadWithAlias() {
		$result = $this->Components->load('Cookie', array('className' => __NAMESPACE__ . '\CookieAliasComponent', 'somesetting' => true));
		$this->assertInstanceOf(__NAMESPACE__ . '\CookieAliasComponent', $result);
		$this->assertInstanceOf(__NAMESPACE__ . '\CookieAliasComponent', $this->Components->Cookie);
		$this->assertTrue($this->Components->Cookie->settings['somesetting']);

		$result = $this->Components->attached();
		$this->assertEquals(array('Cookie'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Components->enabled('Cookie'));

		$result = $this->Components->load('Cookie');
		$this->assertInstanceOf(__NAMESPACE__ . '\CookieAliasComponent', $result);

		App::build(array('Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS)));
		Plugin::load('TestPlugin');
		$result = $this->Components->load('SomeOther', array('className' => 'TestPlugin.Other'));
		$this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $result);
		$this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $this->Components->SomeOther);

		$result = $this->Components->attached();
		$this->assertEquals(array('Cookie', 'SomeOther'), $result, 'attached() results are wrong.');
		App::build();
		Plugin::unload();
	}

/**
 * test load and enable = false
 *
 * @return void
 */
	public function testLoadWithEnableFalse() {
		$result = $this->Components->load('Cookie', array('enabled' => false));
		$this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $result);
		$this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $this->Components->Cookie);

		$this->assertFalse($this->Components->enabled('Cookie'), 'Cookie should be disabled');
	}

/**
 * test missingcomponent exception
 *
 * @expectedException Cake\Error\MissingComponentException
 * @return void
 */
	public function testLoadMissingComponent() {
		$this->Components->load('ThisComponentShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin component.
 *
 * @return void
 */
	public function testLoadPluginComponent() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS),
		));
		Plugin::load('TestPlugin');
		$result = $this->Components->load('TestPlugin.Other');
		$this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $result, 'Component class is wrong.');
		$this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $this->Components->Other, 'Class is wrong');
		App::build();
		Plugin::unload();
	}

/**
 * test unload()
 *
 * @return void
 */
	public function testUnload() {
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
 * test getting the controller out of the collection
 *
 * @return void
 */
	public function testGetController() {
		$controller = $this->getMock('Cake\Controller\Controller');
		$controller->components = array('Security');
		$this->Components->init($controller);
		$result = $this->Components->getController();

		$this->assertSame($controller, $result);
	}
}
