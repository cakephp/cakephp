<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Component\CookieComponent;
use Cake\Controller\Controller;
use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * Extended CookieComponent
 */
class CookieAliasComponent extends CookieComponent
{
}

class ComponentRegistryTest extends TestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $controller = new Controller(new ServerRequest(), new Response());
        $this->Components = new ComponentRegistry($controller);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Components);
    }

    /**
     * test triggering callbacks on loaded helpers
     *
     * @return void
     */
    public function testLoad()
    {
        $result = $this->Components->load('Cookie');
        $this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $result);
        $this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $this->Components->Cookie);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie'], $result, 'loaded() results are wrong.');

        $result = $this->Components->load('Cookie');
        $this->assertSame($result, $this->Components->Cookie);
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias()
    {
        $result = $this->Components->load('Cookie', ['className' => __NAMESPACE__ . '\CookieAliasComponent', 'somesetting' => true]);
        $this->assertInstanceOf(__NAMESPACE__ . '\CookieAliasComponent', $result);
        $this->assertInstanceOf(__NAMESPACE__ . '\CookieAliasComponent', $this->Components->Cookie);
        $this->assertTrue($this->Components->Cookie->config('somesetting'));

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie'], $result, 'loaded() results are wrong.');

        $result = $this->Components->load('Cookie');
        $this->assertInstanceOf(__NAMESPACE__ . '\CookieAliasComponent', $result);

        Plugin::load('TestPlugin');
        $result = $this->Components->load('SomeOther', ['className' => 'TestPlugin.Other']);
        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $result);
        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $this->Components->SomeOther);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie', 'SomeOther'], $result, 'loaded() results are wrong.');
    }

    /**
     * test load and enable = false
     *
     * @return void
     */
    public function testLoadWithEnableFalse()
    {
        $mock = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $mock->expects($this->never())
            ->method('attach');

        $this->Components->getController()->setEventManager($mock);

        $result = $this->Components->load('Cookie', ['enabled' => false]);
        $this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $result);
        $this->assertInstanceOf('Cake\Controller\Component\CookieComponent', $this->Components->Cookie);
    }

    /**
     * test MissingComponent exception
     *
     * @return void
     */
    public function testLoadMissingComponent()
    {
        $this->expectException(\Cake\Controller\Exception\MissingComponentException::class);
        $this->Components->load('ThisComponentShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin component.
     *
     * @return void
     */
    public function testLoadPluginComponent()
    {
        Plugin::load('TestPlugin');
        $result = $this->Components->load('TestPlugin.Other');
        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $result, 'Component class is wrong.');
        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $this->Components->Other, 'Class is wrong');
    }

    /**
     * Test loading components with aliases and plugins.
     *
     * @return void
     */
    public function testLoadWithAliasAndPlugin()
    {
        Plugin::load('TestPlugin');
        $result = $this->Components->load('AliasedOther', ['className' => 'TestPlugin.Other']);
        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $result);
        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $this->Components->AliasedOther);

        $result = $this->Components->loaded();
        $this->assertEquals(['AliasedOther'], $result, 'loaded() results are wrong.');
    }

    /**
     * test getting the controller out of the collection
     *
     * @return void
     */
    public function testGetController()
    {
        $result = $this->Components->getController();
        $this->assertInstanceOf('Cake\Controller\Controller', $result);
    }

    /**
     * Test reset.
     *
     * @return void
     */
    public function testReset()
    {
        $eventManager = $this->Components->getController()->getEventManager();
        $instance = $this->Components->load('Auth');
        $this->assertSame(
            $instance,
            $this->Components->Auth,
            'Instance in registry should be the same as previously loaded'
        );
        $this->assertCount(1, $eventManager->listeners('Controller.startup'));

        $this->assertSame($this->Components, $this->Components->reset());
        $this->assertCount(0, $eventManager->listeners('Controller.startup'));

        $this->assertNotSame($instance, $this->Components->load('Auth'));
    }

    /**
     * Test unloading.
     *
     * @return void
     */
    public function testUnload()
    {
        $eventManager = $this->Components->getController()->getEventManager();

        $this->Components->load('Auth');
        $result = $this->Components->unload('Auth');

        $this->assertSame($this->Components, $result);
        $this->assertFalse(isset($this->Components->Auth), 'Should be gone');
        $this->assertCount(0, $eventManager->listeners('Controller.startup'));
    }

    /**
     * Test __unset.
     *
     * @return void
     */
    public function testUnset()
    {
        $eventManager = $this->Components->getController()->getEventManager();

        $this->Components->load('Auth');
        unset($this->Components->Auth);

        $this->assertFalse(isset($this->Components->Auth), 'Should be gone');
        $this->assertCount(0, $eventManager->listeners('Controller.startup'));
    }

    /**
     * Test that unloading a none existing component triggers an error.
     *
     * @return void
     */
    public function testUnloadUnknown()
    {
        $this->expectException(\Cake\Controller\Exception\MissingComponentException::class);
        $this->expectExceptionMessage('Component class FooComponent could not be found.');
        $this->Components->unload('Foo');
    }

    /**
     * Test set.
     *
     * @return void
     */
    public function testSet()
    {
        $eventManager = $this->Components->getController()->getEventManager();
        $this->assertCount(0, $eventManager->listeners('Controller.startup'));

        $auth = new AuthComponent($this->Components);
        $result = $this->Components->set('Auth', $auth);

        $this->assertSame($this->Components, $result);
        $this->assertTrue(isset($this->Components->Auth), 'Should be present');
        $this->assertCount(1, $eventManager->listeners('Controller.startup'));
    }

    /**
     * Test __set.
     *
     * @return void
     */
    public function testMagicSet()
    {
        $eventManager = $this->Components->getController()->getEventManager();
        $this->assertCount(0, $eventManager->listeners('Controller.startup'));

        $auth = new AuthComponent($this->Components);
        $this->Components->Auth = $auth;

        $this->assertTrue(isset($this->Components->Auth), 'Should be present');
        $this->assertCount(1, $eventManager->listeners('Controller.startup'));
    }

    /**
     * Test Countable.
     *
     * @return void
     */
    public function testCountable()
    {
        $this->Components->load('Auth');
        $this->assertInstanceOf('\Countable', $this->Components);
        $count = count($this->Components);
        $this->assertEquals(1, $count);
    }

    /**
     * Test Traversable.
     *
     * @return void
     */
    public function testTraversable()
    {
        $this->Components->load('Auth');
        $this->assertInstanceOf('\Traversable', $this->Components);

        $result = null;
        foreach ($this->Components as $component) {
            $result = $component;
        }
        $this->assertNotNull($result);
    }
}
