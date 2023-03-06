<?php
declare(strict_types=1);

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

use Cake\Controller\Component\FlashComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Controller\Exception\MissingComponentException;
use Cake\Core\Exception\CakeException;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestApp\Controller\Component\AppleComponent;
use TestApp\Controller\Component\BananaComponent;
use TestApp\Controller\Component\ConfiguredComponent;
use TestApp\Controller\Component\OrangeComponent;
use TestApp\Controller\Component\SomethingWithFlashComponent;
use TestApp\Controller\ComponentTestController;

/**
 * ComponentTest class
 */
class ComponentTest extends TestCase
{
    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
    }

    /**
     * test accessing inner components.
     */
    public function testInnerComponentConstruction(): void
    {
        $Collection = new ComponentRegistry(new Controller(new ServerRequest()));
        $Component = new AppleComponent($Collection);

        $this->assertInstanceOf(OrangeComponent::class, $Component->Orange, 'class is wrong');
    }

    /**
     * test component loading
     */
    public function testNestedComponentLoading(): void
    {
        $Collection = new ComponentRegistry(new Controller(new ServerRequest()));
        $Apple = new AppleComponent($Collection);

        $this->assertInstanceOf(OrangeComponent::class, $Apple->Orange, 'class is wrong');
        $this->assertInstanceOf(BananaComponent::class, $Apple->Orange->Banana, 'class is wrong');
        $this->assertEmpty($Apple->Session);
        $this->assertEmpty($Apple->Orange->Session);
    }

    /**
     * test that component components are not enabled in the collection.
     */
    public function testInnerComponentsAreNotEnabled(): void
    {
        $mock = $this->getMockBuilder(EventManager::class)->getMock();
        $controller = new Controller(new ServerRequest());
        $controller->setEventManager($mock);

        $mock->expects($this->once())
            ->method('on')
            ->with($this->isInstanceOf(AppleComponent::class));

        $Collection = new ComponentRegistry($controller);
        $Apple = $Collection->load('Apple');

        $this->assertInstanceOf(OrangeComponent::class, $Apple->Orange, 'class is wrong');
    }

    /**
     * test a component being used more than once.
     */
    public function testMultipleComponentInitialize(): void
    {
        $Collection = new ComponentRegistry(new Controller(new ServerRequest()));
        $Banana = $Collection->load('Banana');
        $Orange = $Collection->load('Orange');

        $this->assertSame($Banana, $Orange->Banana, 'Should be references');
        $Banana->testField = 'OrangeField';

        $this->assertSame($Banana->testField, $Orange->Banana->testField, 'References are broken');
    }

    /**
     * Test a duplicate component being loaded more than once with same and differing configurations.
     */
    public function testDuplicateComponentInitialize(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The `Banana` alias has already been loaded. The `property` key');
        $Collection = new ComponentRegistry(new Controller(new ServerRequest()));
        $Collection->load('Banana', ['property' => ['closure' => function (): void {
        }]]);
        $Collection->load('Banana', ['property' => ['closure' => function (): void {
        }]]);

        $this->assertInstanceOf(BananaComponent::class, $Collection->Banana, 'class is wrong');

        $Collection->load('Banana', ['property' => ['differs']]);
    }

    /**
     * Test mutually referencing components.
     */
    public function testSomethingReferencingFlashComponent(): void
    {
        $Controller = new ComponentTestController(new ServerRequest());
        $Controller->loadComponent('SomethingWithFlash');
        $Controller->startupProcess();

        $this->assertInstanceOf(SomethingWithFlashComponent::class, $Controller->SomethingWithFlash);
        $this->assertInstanceOf(FlashComponent::class, $Controller->SomethingWithFlash->Flash);
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $Collection = new ComponentRegistry(new Controller(new ServerRequest()));
        $Component = new AppleComponent($Collection);

        $expected = [
            'components' => [
                'Orange' => [],
            ],
            'implementedEvents' => [
                'Controller.startup' => 'startup',
            ],
            '_config' => [],
        ];
        $result = $Component->__debugInfo();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests null return for unknown magic properties.
     */
    public function testMagicReturnsNull(): void
    {
        $Component = new AppleComponent(new ComponentRegistry(new Controller(new ServerRequest())));
        $this->assertNull($Component->ShouldBeNull);
    }

    /**
     * Tests config via constructor
     */
    public function testConfigViaConstructor(): void
    {
        $Component = new ConfiguredComponent(
            new ComponentRegistry(new Controller(new ServerRequest())),
            ['chicken' => 'soup']
        );
        $this->assertEquals(['chicken' => 'soup'], $Component->configCopy);
        $this->assertEquals(['chicken' => 'soup'], $Component->getConfig());
    }

    /**
     * Lazy load a component without events.
     */
    public function testLazyLoading(): void
    {
        $Component = new ConfiguredComponent(
            new ComponentRegistry(new Controller(new ServerRequest())),
            [],
            ['Apple', 'Banana', 'Orange']
        );
        $this->assertInstanceOf(AppleComponent::class, $Component->Apple, 'class is wrong');
        $this->assertInstanceOf(OrangeComponent::class, $Component->Orange, 'class is wrong');
        $this->assertInstanceOf(BananaComponent::class, $Component->Banana, 'class is wrong');
    }

    /**
     * Lazy load a component that does not exist.
     */
    public function testLazyLoadingDoesNotExists(): void
    {
        $this->expectException(MissingComponentException::class);
        $this->expectExceptionMessage('Component class `YouHaveNoBananasComponent` could not be found.');
        $Component = new ConfiguredComponent(new ComponentRegistry(new Controller(new ServerRequest())), [], ['YouHaveNoBananas']);
        $Component->YouHaveNoBananas;
    }

    /**
     * Lazy loaded components can have config options
     */
    public function testConfiguringInnerComponent(): void
    {
        $Component = new ConfiguredComponent(
            new ComponentRegistry(new Controller(new ServerRequest())),
            [],
            ['Configured' => ['foo' => 'bar']]
        );
        $this->assertInstanceOf(ConfiguredComponent::class, $Component->Configured, 'class is wrong');
        $this->assertNotSame($Component, $Component->Configured, 'Component instance was reused');
        $this->assertEquals(['foo' => 'bar', 'enabled' => false], $Component->Configured->getConfig());
    }

    /**
     * Test enabling events for lazy loaded components
     */
    public function testEventsInnerComponent(): void
    {
        $eventManager = $this->getMockBuilder(EventManager::class)->getMock();
        $eventManager->expects($this->once())
            ->method('on')
            ->with($this->isInstanceOf(AppleComponent::class));

        $controller = new Controller(new ServerRequest());
        $controller->setEventManager($eventManager);

        $Collection = new ComponentRegistry($controller);

        $Component = new ConfiguredComponent($Collection, [], ['Apple' => ['enabled' => true]]);
        $this->assertInstanceOf(AppleComponent::class, $Component->Apple, 'class is wrong');
    }

    /**
     * Disabled events do not register for event listeners.
     */
    public function testNoEventsInnerComponent(): void
    {
        $eventManager = $this->getMockBuilder(EventManager::class)->getMock();
        $eventManager->expects($this->never())->method('on');

        $controller = new Controller(new ServerRequest());
        $controller->setEventManager($eventManager);

        $Collection = new ComponentRegistry($controller);

        $Component = new ConfiguredComponent($Collection, [], ['Apple' => ['enabled' => false]]);
        $this->assertInstanceOf(AppleComponent::class, $Component->Apple, 'class is wrong');
    }
}
