<?php
declare(strict_types=1);

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

use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Component\FlashComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Controller\Exception\MissingComponentException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Countable;
use TestApp\Controller\Component\FlashAliasComponent;
use TestPlugin\Controller\Component\OtherComponent;
use Traversable;

class ComponentRegistryTest extends TestCase
{
    /**
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $Components;

    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();
        $controller = new Controller(new ServerRequest(), new Response());
        $this->Components = new ComponentRegistry($controller);
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->Components);
        $this->clearPlugins();
    }

    /**
     * test triggering callbacks on loaded helpers
     */
    public function testLoad(): void
    {
        $result = $this->Components->load('Flash');
        $this->assertInstanceOf(FlashComponent::class, $result);
        $this->assertInstanceOf(FlashComponent::class, $this->Components->Flash);

        $result = $this->Components->loaded();
        $this->assertEquals(['Flash'], $result, 'loaded() results are wrong.');

        $result = $this->Components->load('Flash');
        $this->assertSame($result, $this->Components->Flash);
    }

    /**
     * Tests loading as an alias
     */
    public function testLoadWithAlias(): void
    {
        $result = $this->Components->load('Flash', ['className' => FlashAliasComponent::class, 'somesetting' => true]);
        $this->assertInstanceOf(FlashAliasComponent::class, $result);
        $this->assertInstanceOf(FlashAliasComponent::class, $this->Components->Flash);
        $this->assertTrue($this->Components->Flash->getConfig('somesetting'));

        $result = $this->Components->loaded();
        $this->assertEquals(['Flash'], $result, 'loaded() results are wrong.');

        $result = $this->Components->load('Flash');
        $this->assertInstanceOf(FlashAliasComponent::class, $result);

        $this->loadPlugins(['TestPlugin']);
        $result = $this->Components->load('SomeOther', ['className' => 'TestPlugin.Other']);
        $this->assertInstanceOf(OtherComponent::class, $result);
        $this->assertInstanceOf(OtherComponent::class, $this->Components->SomeOther);

        $result = $this->Components->loaded();
        $this->assertEquals(['Flash', 'SomeOther'], $result, 'loaded() results are wrong.');
    }

    /**
     * test load and enable = false
     */
    public function testLoadWithEnableFalse(): void
    {
        $mock = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $mock->expects($this->never())
            ->method('on');

        $this->Components->getController()->setEventManager($mock);

        $result = $this->Components->load('Flash', ['enabled' => false]);
        $this->assertInstanceOf(FlashComponent::class, $result);
        $this->assertInstanceOf(FlashComponent::class, $this->Components->Flash);
    }

    /**
     * test MissingComponent exception
     */
    public function testLoadMissingComponent(): void
    {
        $this->expectException(MissingComponentException::class);
        $this->Components->load('ThisComponentShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin component.
     */
    public function testLoadPluginComponent(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $result = $this->Components->load('TestPlugin.Other');
        $this->assertInstanceOf(OtherComponent::class, $result, 'Component class is wrong.');
        $this->assertInstanceOf(OtherComponent::class, $this->Components->Other, 'Class is wrong');
    }

    /**
     * Test loading components with aliases and plugins.
     */
    public function testLoadWithAliasAndPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $result = $this->Components->load('AliasedOther', ['className' => 'TestPlugin.Other']);
        $this->assertInstanceOf(OtherComponent::class, $result);
        $this->assertInstanceOf(OtherComponent::class, $this->Components->AliasedOther);

        $result = $this->Components->loaded();
        $this->assertEquals(['AliasedOther'], $result, 'loaded() results are wrong.');
    }

    /**
     * test getting the controller out of the collection
     */
    public function testGetController(): void
    {
        $result = $this->Components->getController();
        $this->assertInstanceOf('Cake\Controller\Controller', $result);
    }

    /**
     * Test reset.
     */
    public function testReset(): void
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
     */
    public function testUnload(): void
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
     */
    public function testUnset(): void
    {
        $eventManager = $this->Components->getController()->getEventManager();

        $this->Components->load('Auth');
        unset($this->Components->Auth);

        $this->assertFalse(isset($this->Components->Auth), 'Should be gone');
        $this->assertCount(0, $eventManager->listeners('Controller.startup'));
    }

    /**
     * Test that unloading a none existing component triggers an error.
     */
    public function testUnloadUnknown(): void
    {
        $this->expectException(MissingComponentException::class);
        $this->expectExceptionMessage('Component class FooComponent could not be found.');
        $this->Components->unload('Foo');
    }

    /**
     * Test set.
     */
    public function testSet(): void
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
     */
    public function testMagicSet(): void
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
     */
    public function testCountable(): void
    {
        $this->Components->load('Auth');
        $this->assertInstanceOf(Countable::class, $this->Components);
        $count = count($this->Components);
        $this->assertSame(1, $count);
    }

    /**
     * Test Traversable.
     */
    public function testTraversable(): void
    {
        $this->Components->load('Auth');
        $this->assertInstanceOf(Traversable::class, $this->Components);

        $result = null;
        foreach ($this->Components as $component) {
            $result = $component;
        }
        $this->assertNotNull($result);
    }
}
