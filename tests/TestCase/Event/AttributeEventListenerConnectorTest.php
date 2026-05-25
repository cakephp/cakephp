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
 * @link          https://cakephp.org CakePHP Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Cache\Cache;
use Cake\Event\AttributeEventListenerConnector;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Event\Exception\EventAttributeException;
use Cake\TestSuite\TestCase;
use ReflectionProperty;
use TestApp\Event\Listener\ClassLevelMethodListener;
use TestApp\Event\Listener\InferredMethodListener;
use TestApp\Event\Listener\InvokableListener;
use TestApp\Event\Listener\OrdersListener;
use TestApp\Event\Listener\PriorityListener;

/**
 * Tests for AttributeEventListenerConnector.
 */
class AttributeEventListenerConnectorTest extends TestCase
{
    /**
     * @var string Default resolver config name used across tests.
     */
    private const string DEFAULT_CONFIG = 'event-test';

    /**
     * @var string Error case resolver config name.
     */
    private const string ERROR_CONFIG = 'event-error-test';

    /**
     * Paths to listener fixtures used in the default resolver configuration.
     *
     * @var list<string>
     */
    private const array DEFAULT_LISTENER_PATHS = [
        'Event/Listener/OrdersListener.php',
        'Event/Listener/InvokableListener.php',
        'Event/Listener/InferredMethodListener.php',
        'Event/Listener/ClassLevelMethodListener.php',
        'Event/Listener/PriorityListener.php',
        'Event/Listener/AbstractListener.php',
    ];

    /**
     * Initializes the attribute resolver with the default listener fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        AttributeResolver::setConfig(self::DEFAULT_CONFIG, [
            'paths' => self::DEFAULT_LISTENER_PATHS,
            'basePath' => APP,
            'excludePaths' => [],
            'excludeAttributes' => [],
            'cache' => false,
        ]);
    }

    /**
     * Clears resolver state between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (AttributeResolver::getConfig(self::DEFAULT_CONFIG)) {
            AttributeResolver::drop(self::DEFAULT_CONFIG);
        }
        if (AttributeResolver::getConfig(self::ERROR_CONFIG)) {
            AttributeResolver::drop(self::ERROR_CONFIG);
        }

        $reflection = new ReflectionProperty(AttributeResolver::class, 'collections');
        $reflection->setValue(null, []);

        Cache::clear('_cake_attributes_');

        parent::tearDown();
    }

    /**
     * Returns a connector that injects a fixed listener instance for the given class.
     *
     * Allows tests to inspect side-effects on the concrete listener instance after dispatch.
     *
     * @param \Cake\Event\EventManagerInterface $manager Event manager.
     * @param object $instance Pre-built listener instance to use.
     * @param string $className Listener class name the instance replaces.
     * @return \Cake\Event\AttributeEventListenerConnector
     */
    private function connectorWithFixedInstance(
        EventManagerInterface $manager,
        object $instance,
        string $className,
    ): AttributeEventListenerConnector {
        return new class ($manager, $instance, $className) extends AttributeEventListenerConnector {
            public function __construct(
                EventManagerInterface $eventManager,
                private readonly object $fixedInstance,
                private readonly string $fixedClassName,
            ) {
                parent::__construct($eventManager);
            }

            protected function createListener(string $className): object
            {
                if ($className === $this->fixedClassName) {
                    return $this->fixedInstance;
                }

                return parent::createListener($className);
            }
        };
    }

    /**
     * Tests that a method-level EventListener attribute registers the method as a listener.
     *
     * @return void
     */
    public function testConnectMethodLevelAttribute(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig('orders-only', [
            'paths' => ['Event/Listener/OrdersListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('orders-only');

        // OrdersListener declares sendReceipt (priority 10) and updateMetrics (priority 5) on Order.afterPlace
        $this->assertCount(2, $manager->listeners('Order.afterPlace'));

        AttributeResolver::drop('orders-only');
    }

    /**
     * Tests that a repeatable method-level attribute registers the method for multiple events.
     *
     * @return void
     */
    public function testConnectRepeatableMethodAttributeRegistersMultipleEvents(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig('orders-only', [
            'paths' => ['Event/Listener/OrdersListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('orders-only');

        // updateMetrics listens to Order.afterPlace AND Order.afterCancel
        $this->assertCount(1, $manager->listeners('Order.afterCancel'));

        AttributeResolver::drop('orders-only');
    }

    /**
     * Tests that a class-level EventListener attribute with an explicit method name registers correctly.
     *
     * @return void
     */
    public function testConnectClassLevelAttributeWithExplicitMethod(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig('explicit-method-test', [
            'paths' => ['Event/Listener/ClassLevelMethodListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('explicit-method-test');

        $this->assertCount(1, $manager->listeners('Order.afterPlace'));

        AttributeResolver::drop('explicit-method-test');
    }

    /**
     * Tests that a class-level EventListener attribute resolves __invoke when present.
     *
     * @return void
     */
    public function testConnectClassLevelAttributeWithInvoke(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig('invokable-test', [
            'paths' => ['Event/Listener/InvokableListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('invokable-test');

        $this->assertCount(1, $manager->listeners('Order.afterPlace'));

        AttributeResolver::drop('invokable-test');
    }

    /**
     * Tests that a class-level EventListener attribute infers the method name from the event name.
     *
     * @return void
     */
    public function testConnectClassLevelAttributeInfersMethodName(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig('inferred-test', [
            'paths' => ['Event/Listener/InferredMethodListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('inferred-test');

        $this->assertCount(1, $manager->listeners('Order.afterPlace'));

        AttributeResolver::drop('inferred-test');
    }

    /**
     * Tests that listener priority is respected when connecting attribute listeners.
     *
     * @return void
     */
    public function testPriorityIsRespected(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig('priority-test', [
            'paths' => ['Event/Listener/PriorityListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('priority-test');

        $prioritised = $manager->prioritisedListeners('Model.afterSave');
        $this->assertSame([5, 100], array_keys($prioritised));

        AttributeResolver::drop('priority-test');
    }

    /**
     * Tests that abstract classes with EventListener attributes are silently skipped.
     *
     * @return void
     */
    public function testAbstractClassIsSkipped(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig('abstract-test', [
            'paths' => ['Event/Listener/AbstractListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('abstract-test');

        $this->assertCount(0, $manager->listeners('Order.afterPlace'));

        AttributeResolver::drop('abstract-test');
    }

    /**
     * Tests that dispatching an event invokes the registered listener method.
     *
     * @return void
     */
    public function testListenerIsInvokedOnDispatch(): void
    {
        $manager = new EventManager();
        $ordersListener = new OrdersListener();

        $connector = $this->connectorWithFixedInstance($manager, $ordersListener, OrdersListener::class);
        $connector->connect(self::DEFAULT_CONFIG);

        $manager->dispatch(new Event('Order.afterPlace', $this));

        $this->assertContains('sendReceipt', $ordersListener->called);
        $this->assertContains('updateMetrics', $ordersListener->called);
    }

    /**
     * Tests that listener priority determines the invocation order.
     *
     * @return void
     */
    public function testListenerPriorityDeterminesInvocationOrder(): void
    {
        $manager = new EventManager();
        $priorityListener = new PriorityListener();

        AttributeResolver::setConfig('order-test', [
            'paths' => ['Event/Listener/PriorityListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = $this->connectorWithFixedInstance($manager, $priorityListener, PriorityListener::class);
        $connector->connect('order-test');

        $manager->dispatch(new Event('Model.afterSave', $this));

        $this->assertSame(['highPriority', 'lowPriority'], $priorityListener->called);

        AttributeResolver::drop('order-test');
    }

    /**
     * Tests that class-level EventListener with __invoke is called on dispatch.
     *
     * @return void
     */
    public function testInvokableListenerIsCalledOnDispatch(): void
    {
        $manager = new EventManager();
        $invokableListener = new InvokableListener();

        AttributeResolver::setConfig('invoke-dispatch-test', [
            'paths' => ['Event/Listener/InvokableListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = $this->connectorWithFixedInstance($manager, $invokableListener, InvokableListener::class);
        $connector->connect('invoke-dispatch-test');

        $manager->dispatch(new Event('Order.afterPlace', $this));

        $this->assertSame(['__invoke'], $invokableListener->called);

        AttributeResolver::drop('invoke-dispatch-test');
    }

    /**
     * Tests that the inferred method listener is called on dispatch.
     *
     * @return void
     */
    public function testInferredMethodListenerIsCalledOnDispatch(): void
    {
        $manager = new EventManager();
        $inferredListener = new InferredMethodListener();

        AttributeResolver::setConfig('inferred-dispatch-test', [
            'paths' => ['Event/Listener/InferredMethodListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = $this->connectorWithFixedInstance($manager, $inferredListener, InferredMethodListener::class);
        $connector->connect('inferred-dispatch-test');

        $manager->dispatch(new Event('Order.afterPlace', $this));

        $this->assertSame(['onOrderAfterPlace'], $inferredListener->called);

        AttributeResolver::drop('inferred-dispatch-test');
    }

    /**
     * Tests that a class-level listener with an explicit method is called on dispatch.
     *
     * @return void
     */
    public function testClassLevelExplicitMethodListenerIsCalledOnDispatch(): void
    {
        $manager = new EventManager();
        $classLevelListener = new ClassLevelMethodListener();

        AttributeResolver::setConfig('explicit-dispatch-test', [
            'paths' => ['Event/Listener/ClassLevelMethodListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = $this->connectorWithFixedInstance($manager, $classLevelListener, ClassLevelMethodListener::class);
        $connector->connect('explicit-dispatch-test');

        $manager->dispatch(new Event('Order.afterPlace', $this));

        $this->assertSame(['handleOrder'], $classLevelListener->called);

        AttributeResolver::drop('explicit-dispatch-test');
    }

    /**
     * Tests that a class-level EventListener referencing a non-existent method throws EventAttributeException.
     *
     * @return void
     */
    public function testMissingMethodThrowsException(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig(self::ERROR_CONFIG, [
            'paths' => ['Event/Listener/MissingMethodListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);

        $this->expectException(EventAttributeException::class);
        $this->expectExceptionMessageMatches('/nonExistentMethod/');

        $connector->connect(self::ERROR_CONFIG);
    }

    /**
     * Tests that a non-public method decorated with EventListener throws EventAttributeException.
     *
     * @return void
     */
    public function testNonPublicMethodThrowsException(): void
    {
        $manager = new EventManager();

        AttributeResolver::setConfig(self::ERROR_CONFIG, [
            'paths' => ['Event/Listener/NonPublicMethodListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);

        $this->expectException(EventAttributeException::class);
        $this->expectExceptionMessageMatches('/must be public/');

        $connector->connect(self::ERROR_CONFIG);
    }

    /**
     * Tests that duplicate event+priority+method combinations are registered only once.
     *
     * Deduplication prevents double registration when the same attribute metadata
     * appears multiple times (e.g., from repeated scans or misconfigured resolver paths).
     *
     * @return void
     */
    public function testDeduplicationPreventsDuplicateRegistration(): void
    {
        $manager = new EventManager();

        // Configure the resolver to scan the same file twice via two path entries.
        AttributeResolver::setConfig('dedup-test', [
            'paths' => [
                'Event/Listener/OrdersListener.php',
                'Event/Listener/OrdersListener.php',
            ],
            'basePath' => APP,
            'cache' => false,
        ]);

        $connector = new AttributeEventListenerConnector($manager);
        $connector->connect('dedup-test');

        // Even though the file appears twice in paths, each event+priority+method is registered once.
        $this->assertCount(2, $manager->listeners('Order.afterPlace'));

        AttributeResolver::drop('dedup-test');
    }

    /**
     * Tests that EventManager::attachAttributes() delegates to the connector.
     *
     * @return void
     */
    public function testAttachAttributesOnEventManager(): void
    {
        AttributeResolver::setConfig('default', [
            'paths' => ['Event/Listener/OrdersListener.php'],
            'basePath' => APP,
            'cache' => false,
        ]);

        $manager = new EventManager();
        $result = $manager->attachAttributes('default');

        $this->assertSame($manager, $result);
        $this->assertCount(2, $manager->listeners('Order.afterPlace'));

        AttributeResolver::drop('default');
    }
}
