<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CookieComponent;
use Cake\Controller\Controller;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use TestApp\Controller\ComponentTestController;
use TestApp\Controller\Component\AppleComponent;
use TestApp\Controller\Component\BananaComponent;
use TestApp\Controller\Component\ConfiguredComponent;
use TestApp\Controller\Component\OrangeComponent;
use TestApp\Controller\Component\SomethingWithCookieComponent;

/**
 * ComponentTest class
 */
class ComponentTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        static::setAppNamespace();
    }

    /**
     * test accessing inner components.
     *
     * @return void
     */
    public function testInnerComponentConstruction()
    {
        $Collection = new ComponentRegistry();
        $Component = new AppleComponent($Collection);

        $this->assertInstanceOf(OrangeComponent::class, $Component->Orange, 'class is wrong');
    }

    /**
     * test component loading
     *
     * @return void
     */
    public function testNestedComponentLoading()
    {
        $Collection = new ComponentRegistry();
        $Apple = new AppleComponent($Collection);

        $this->assertInstanceOf(OrangeComponent::class, $Apple->Orange, 'class is wrong');
        $this->assertInstanceOf(BananaComponent::class, $Apple->Orange->Banana, 'class is wrong');
        $this->assertEmpty($Apple->Session);
        $this->assertEmpty($Apple->Orange->Session);
    }

    /**
     * test that component components are not enabled in the collection.
     *
     * @return void
     */
    public function testInnerComponentsAreNotEnabled()
    {
        $mock = $this->getMockBuilder(EventManager::class)->getMock();
        $controller = new Controller();
        $controller->eventManager($mock);

        $mock->expects($this->once())
            ->method('on')
            ->with($this->isInstanceOf(AppleComponent::class));

        $Collection = new ComponentRegistry($controller);
        $Apple = $Collection->load('Apple');

        $this->assertInstanceOf(OrangeComponent::class, $Apple->Orange, 'class is wrong');
    }

    /**
     * test a component being used more than once.
     *
     * @return void
     */
    public function testMultipleComponentInitialize()
    {
        $Collection = new ComponentRegistry();
        $Banana = $Collection->load('Banana');
        $Orange = $Collection->load('Orange');

        $this->assertSame($Banana, $Orange->Banana, 'Should be references');
        $Banana->testField = 'OrangeField';

        $this->assertSame($Banana->testField, $Orange->Banana->testField, 'References are broken');
    }

    /**
     * Test a duplicate component being loaded more than once with same and differing configurations.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "Banana" alias has already been loaded with the following config:
     * @return void
     */
    public function testDuplicateComponentInitialize()
    {
        $Collection = new ComponentRegistry();
        $Collection->load('Banana', ['property' => ['closure' => function () {
        }]]);
        $Collection->load('Banana', ['property' => ['closure' => function () {
        }]]);

        $this->assertInstanceOf(BananaComponent::class, $Collection->Banana, 'class is wrong');

        $Collection->load('Banana', ['property' => ['differs']]);
    }

    /**
     * Test mutually referencing components.
     *
     * @return void
     */
    public function testSomethingReferencingCookieComponent()
    {
        $Controller = new ComponentTestController();
        $Controller->loadComponent('SomethingWithCookie');
        $Controller->startupProcess();

        $this->assertInstanceOf(SomethingWithCookieComponent::class, $Controller->SomethingWithCookie);
        $this->assertInstanceOf(CookieComponent::class, $Controller->SomethingWithCookie->Cookie);
    }

    /**
     * Tests __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $Collection = new ComponentRegistry();
        $Component = new AppleComponent($Collection);

        $expected = [
            'components' => [
                'Orange'
            ],
            'implementedEvents' => [
                'Controller.startup' => 'startup'
            ],
            '_config' => []
        ];
        $result = $Component->__debugInfo();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests null return for unknown magic properties.
     *
     * @return void
     */
    public function testMagicReturnsNull()
    {
        $Component = new AppleComponent(new ComponentRegistry());
        $this->assertNull($Component->ShouldBeNull);
    }

    /**
     * Tests config via constructor
     *
     * @return void
     */
    public function testConfigViaConstructor()
    {
        $Component = new ConfiguredComponent(new ComponentRegistry(), ['chicken' => 'soup']);
        $this->assertEquals(['chicken' => 'soup'], $Component->configCopy);
        $this->assertEquals(['chicken' => 'soup'], $Component->config());
    }

    /**
     * Lazy load a component without events.
     *
     * @return void
     */
    public function testLazyLoading()
    {
        $Component = new ConfiguredComponent(new ComponentRegistry(), [], ['Apple', 'Banana', 'Orange']);
        $this->assertInstanceOf(AppleComponent::class, $Component->Apple, 'class is wrong');
        $this->assertInstanceOf(OrangeComponent::class, $Component->Orange, 'class is wrong');
        $this->assertInstanceOf(BananaComponent::class, $Component->Banana, 'class is wrong');
    }

    /**
     * Lazy load a component that does not exist.
     *
     * @expectedException \Cake\Controller\Exception\MissingComponentException
     * @expectedExceptionMessage Component class YouHaveNoBananasComponent could not be found.
     * @return void
     */
    public function testLazyLoadingDoesNotExists()
    {
        $Component = new ConfiguredComponent(new ComponentRegistry(), [], ['YouHaveNoBananas']);
        $bananas = $Component->YouHaveNoBananas;
    }

    /**
     * Lazy loaded components can have config options
     *
     * @return void
     */
    public function testConfiguringInnerComponent()
    {
        $Component = new ConfiguredComponent(new ComponentRegistry(), [], ['Configured' => ['foo' => 'bar']]);
        $this->assertInstanceOf(ConfiguredComponent::class, $Component->Configured, 'class is wrong');
        $this->assertNotSame($Component, $Component->Configured, 'Component instance was reused');
        $this->assertEquals(['foo' => 'bar', 'enabled' => false], $Component->Configured->config());
    }

    /**
     * Test enabling events for lazy loaded components
     *
     * @return void
     */
    public function testEventsInnerComponent()
    {
        $eventManager = $this->getMockBuilder(EventManager::class)->getMock();
        $eventManager->expects($this->once())
            ->method('on')
            ->with($this->isInstanceOf(AppleComponent::class));

        $controller = new Controller();
        $controller->eventManager($eventManager);

        $Collection = new ComponentRegistry($controller);

        $Component = new ConfiguredComponent($Collection, [], ['Apple' => ['enabled' => true]]);
        $this->assertInstanceOf(AppleComponent::class, $Component->Apple, 'class is wrong');
    }

    /**
     * Disabled events do not register for event listeners.
     *
     * @return void
     */
    public function testNoEventsInnerComponent()
    {
        $eventManager = $this->getMockBuilder(EventManager::class)->getMock();
        $eventManager->expects($this->never())->method('on');

        $controller = new Controller();
        $controller->eventManager($eventManager);

        $Collection = new ComponentRegistry($controller);

        $Component = new ConfiguredComponent($Collection, [], ['Apple' => ['enabled' => false]]);
        $this->assertInstanceOf(AppleComponent::class, $Component->Apple, 'class is wrong');
    }
}
