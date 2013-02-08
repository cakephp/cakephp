<?php
/**
 * ComponentCollectionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('CakeResponse', 'Network');
App::uses('CookieComponent', 'Controller/Component');
App::uses('SecurityComponent', 'Controller/Component');
App::uses('ComponentCollection', 'Controller');

/**
 * Extended CookieComponent
 */
class CookieAliasComponent extends CookieComponent {
}

class ComponentCollectionTest extends CakeTestCase {

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
		$this->assertInstanceOf('CookieComponent', $result);
		$this->assertInstanceOf('CookieComponent', $this->Components->Cookie);

		$result = $this->Components->loaded();
		$this->assertEquals(array('Cookie'), $result, 'loaded() results are wrong.');

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
		$result = $this->Components->load('Cookie', array('className' => 'CookieAlias', 'somesetting' => true));
		$this->assertInstanceOf('CookieAliasComponent', $result);
		$this->assertInstanceOf('CookieAliasComponent', $this->Components->Cookie);
		$this->assertTrue($this->Components->Cookie->settings['somesetting']);

		$result = $this->Components->loaded();
		$this->assertEquals(array('Cookie'), $result, 'loaded() results are wrong.');

		$this->assertTrue($this->Components->enabled('Cookie'));

		$result = $this->Components->load('Cookie');
		$this->assertInstanceOf('CookieAliasComponent', $result);

		App::build(array('Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)));
		CakePlugin::load('TestPlugin');
		$result = $this->Components->load('SomeOther', array('className' => 'TestPlugin.Other'));
		$this->assertInstanceOf('OtherComponent', $result);
		$this->assertInstanceOf('OtherComponent', $this->Components->SomeOther);

		$result = $this->Components->loaded();
		$this->assertEquals(array('Cookie', 'SomeOther'), $result, 'loaded() results are wrong.');
		App::build();
		CakePlugin::unload();
	}

/**
 * test load and enable = false
 *
 * @return void
 */
	public function testLoadWithEnableFalse() {
		$result = $this->Components->load('Cookie', array('enabled' => false));
		$this->assertInstanceOf('CookieComponent', $result);
		$this->assertInstanceOf('CookieComponent', $this->Components->Cookie);

		$this->assertFalse($this->Components->enabled('Cookie'), 'Cookie should be disabled');
	}

/**
 * test missingcomponent exception
 *
 * @expectedException MissingComponentException
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
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		));
		CakePlugin::load('TestPlugin');
		$result = $this->Components->load('TestPlugin.Other');
		$this->assertInstanceOf('OtherComponent', $result, 'Component class is wrong.');
		$this->assertInstanceOf('OtherComponent', $this->Components->Other, 'Class is wrong');
		App::build();
		CakePlugin::unload();
	}

/**
 * test unload()
 *
 * @return void
 */
	public function testUnload() {
		$this->Components->load('Cookie');
		$this->Components->load('Security');

		$result = $this->Components->loaded();
		$this->assertEquals(array('Cookie', 'Security'), $result, 'loaded components is wrong');

		$this->Components->unload('Cookie');
		$this->assertFalse(isset($this->Components->Cookie));
		$this->assertTrue(isset($this->Components->Security));

		$result = $this->Components->loaded();
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
		$controller = $this->getMock('Controller');
		$controller->components = array('Security');
		$this->Components->init($controller);
		$result = $this->Components->getController();

		$this->assertSame($controller, $result);
	}
}
