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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Attribute;

use Cake\Attribute\Resolver;
use Cake\Attribute\Resolver\AttributeCollection;
use Cake\Attribute\Resolver\Event\AfterResolveEvent;
use Cake\Attribute\Resolver\Event\AfterScanEvent;
use Cake\Attribute\Resolver\Event\BeforeArtifactsClearEvent;
use Cake\Attribute\Resolver\Event\BeforeResolveEvent;
use Cake\Attribute\Resolver\Event\BeforeScanEvent;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Event\EventInterface;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Attribute\Resolver\TestRoute;

class ResolverTest extends TestCase
{
    protected string $artifactPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->artifactPath = TMP . 'tests' . DS . 'resolver_test_artifact.php';
        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        @unlink($this->artifactPath);

        Resolver::drop('default');
        Resolver::drop('test');

        $eventManager = EventManager::instance();
        $eventManager->setEventList(new EventList());
        $eventManager->trackEvents(true);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        @unlink($this->artifactPath);
        Resolver::drop('default');
        Resolver::drop('test');
    }

    public function testSetConfigAndGetConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];

        Resolver::setConfig('test', $config);
        $result = Resolver::getConfig('test');

        $this->assertSame($config, $result);
    }

    public function testGetConfigOrFail(): void
    {
        $config = ['paths' => [TEST_APP]];
        Resolver::setConfig('test', $config);

        $result = Resolver::getConfigOrFail('test');
        $this->assertSame($config, $result);
    }

    public function testGetConfigOrFailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected configuration `nonexistent` not found');

        Resolver::getConfigOrFail('nonexistent');
    }

    public function testDrop(): void
    {
        Resolver::setConfig('test', ['paths' => []]);
        $this->assertNotNull(Resolver::getConfig('test'));

        Resolver::drop('test');
        $this->assertNull(Resolver::getConfig('test'));
    }

    public function testResolveWithoutArtifactDispatchesAllEvents(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $eventList = EventManager::instance()->getEventList();
        $this->assertNotNull($eventList);
        $collection = Resolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection);

        $events = iterator_to_array($eventList);
        $eventNames = array_map(fn(EventInterface $e) => $e->getName(), $events);

        $this->assertContains(BeforeResolveEvent::NAME, $eventNames);
        $this->assertContains(BeforeScanEvent::NAME, $eventNames);
        $this->assertContains(AfterScanEvent::NAME, $eventNames);
        $this->assertContains(AfterResolveEvent::NAME, $eventNames);
    }

    public function testResolveReturnsAttributeCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $collection = Resolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testResolveWithArtifactLoadsFromCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        // First resolve creates artifact
        $collection1 = Resolver::collection('test');
        $this->assertFileExists($this->artifactPath);

        // Clear event list
        $eventList = EventManager::instance()->getEventList();
        $this->assertNotNull($eventList);
        $eventList->flush();

        // Second resolve should load from artifact
        $collection2 = Resolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection2);
        $this->assertSame($collection1->count(), $collection2->count());

        // Verify BeforeScan and AfterScan were NOT dispatched
        $events = iterator_to_array($eventList);
        $eventNames = array_map(fn(EventInterface $e) => $e->getName(), $events);

        $this->assertContains(BeforeResolveEvent::NAME, $eventNames);
        $this->assertNotContains(BeforeScanEvent::NAME, $eventNames);
        $this->assertNotContains(AfterScanEvent::NAME, $eventNames);
        $this->assertContains(AfterResolveEvent::NAME, $eventNames);
    }

    public function testResolveUsesInMemoryCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $collection1 = Resolver::collection('test');
        $collection2 = Resolver::collection('test');

        // Should be the exact same instance due to in-memory cache
        $this->assertSame($collection1, $collection2);
    }

    public function testResolveCanChainCollectionMethods(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $routes = Resolver::collection('test')->withAttribute(TestRoute::class);

        $this->assertInstanceOf(AttributeCollection::class, $routes);
        $this->assertGreaterThan(0, $routes->count());
    }

    public function testClearDispatchesEvents(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        // Create artifact first
        Resolver::collection('test');
        $this->assertFileExists($this->artifactPath);

        $eventList = EventManager::instance()->getEventList();
        $this->assertNotNull($eventList);
        $eventList->flush();

        $result = Resolver::clear('test');

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($this->artifactPath);

        $events = iterator_to_array($eventList);
        $eventNames = array_map(fn(EventInterface $e) => $e->getName(), $events);

        $this->assertContains(BeforeArtifactsClearEvent::NAME, $eventNames);
        $this->assertContains('Attribute.Resolver.afterArtifactsClear', $eventNames);
    }

    public function testClearClearsInMemoryCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        Resolver::collection('test');
        Resolver::clear('test');

        // After clearing, resolve should scan again
        $eventList = EventManager::instance()->getEventList();
        $this->assertNotNull($eventList);
        $eventList->flush();

        Resolver::collection('test');

        $events = iterator_to_array($eventList);
        $eventNames = array_map(fn(EventInterface $e) => $e->getName(), $events);

        // Should have scan events since cache was cleared
        $this->assertContains(BeforeScanEvent::NAME, $eventNames);
        $this->assertContains(AfterScanEvent::NAME, $eventNames);
    }

    public function testWarmBuildsCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $this->assertFileDoesNotExist($this->artifactPath);

        $result = Resolver::warm('test');

        $this->assertTrue($result);
        $this->assertFileExists($this->artifactPath);
    }

    public function testBeforeResolveEventCanStopResolution(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        EventManager::instance()->on(BeforeResolveEvent::NAME, function ($event): void {
            $event->stopPropagation();
        });

        $collection = Resolver::collection('test');

        // Should return empty collection when stopped
        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertSame(0, $collection->count());
    }

    public function testBeforeScanEventCanStopScanning(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        EventManager::instance()->on(BeforeScanEvent::NAME, function ($event): void {
            $event->stopPropagation();
        });

        $collection = Resolver::collection('test');

        // Should return empty collection when scanning is stopped
        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertSame(0, $collection->count());
    }

    public function testBeforeArtifactsClearEventCanStopClearing(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        // Create artifact first
        Resolver::collection('test');
        $this->assertFileExists($this->artifactPath);

        EventManager::instance()->on(BeforeArtifactsClearEvent::NAME, function ($event): void {
            $event->stopPropagation();
        });

        $result = Resolver::clear('test');

        $this->assertFalse($result);
        $this->assertFileExists($this->artifactPath);
    }

    public function testResolveWithDefaultConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('default', $config);

        $collection = Resolver::collection();

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testAfterResolveEventContainsCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $capturedCollection = null;
        EventManager::instance()->on(AfterResolveEvent::NAME, function ($event) use (&$capturedCollection): void {
            $capturedCollection = $event->getCollection();
        });

        $collection = Resolver::collection('test');

        $this->assertNotNull($capturedCollection);
        $this->assertInstanceOf(AttributeCollection::class, $capturedCollection);
        $this->assertSame($collection->count(), $capturedCollection->count());
    }

    public function testAfterScanEventContainsCounts(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $capturedFileCount = null;
        $capturedAttributeCount = null;
        EventManager::instance()->on(AfterScanEvent::NAME, function ($event) use (&$capturedFileCount, &$capturedAttributeCount): void {
            $capturedFileCount = $event->getFileCount();
            $capturedAttributeCount = $event->getAttributeCount();
        });

        Resolver::collection('test');

        $this->assertNotNull($capturedFileCount);
        $this->assertNotNull($capturedAttributeCount);
        $this->assertGreaterThan(0, $capturedFileCount);
        $this->assertGreaterThan(0, $capturedAttributeCount);
    }

    public function testDropReturnsFalseForNonexistentConfig(): void
    {
        $result = Resolver::drop('nonexistent');

        $this->assertFalse($result);
    }

    public function testClearReturnsFalseWhenNoArtifactExists(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        Resolver::setConfig('test', $config);

        $result = Resolver::clear('test');

        $this->assertFalse($result);
    }

    public function testResolveWithExcludeAttributes(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'excludeAttributes' => [TestRoute::class],
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        $collection = Resolver::collection('test');

        // Should not contain any TestRoute attributes
        $routes = $collection->withAttribute(TestRoute::class);
        $this->assertSame(0, $routes->count());
    }

    public function testResolveWithMultipleConfigs(): void
    {
        $config1 = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/Controllers/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        $config2 = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/Models/*.php'],
            'basePath' => TEST_APP,
            'artifact' => TMP . 'tests' . DS . 'resolver_test_artifact2.php',
        ];

        Resolver::setConfig('controllers', $config1);
        Resolver::setConfig('models', $config2);

        $controllerCollection = Resolver::collection('controllers');
        $modelCollection = Resolver::collection('models');

        $this->assertInstanceOf(AttributeCollection::class, $controllerCollection);
        $this->assertInstanceOf(AttributeCollection::class, $modelCollection);

        // Cleanup second artifact
        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        @unlink(TMP . 'tests' . DS . 'resolver_test_artifact2.php');
        Resolver::drop('controllers');
        Resolver::drop('models');
    }

    public function testAfterArtifactsClearEventContainsSuccess(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'artifact' => $this->artifactPath,
        ];
        Resolver::setConfig('test', $config);

        // Create artifact first
        Resolver::collection('test');

        $capturedSuccess = null;
        EventManager::instance()->on('Attribute.Resolver.afterArtifactsClear', function ($event) use (&$capturedSuccess): void {
            $capturedSuccess = $event->isSuccess();
        });

        Resolver::clear('test');

        $this->assertNotNull($capturedSuccess);
        $this->assertTrue($capturedSuccess);
    }

    public function testResolveReturnsEmptyCollectionForMissingConfig(): void
    {
        $collection = Resolver::collection('nonexistent');

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertSame(0, $collection->count());
    }

    public function testCollectionMethodReturnsAttributeCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        Resolver::setConfig('test', $config);

        $collection = Resolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testCollectionMethodDefaultsToDefaultConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        Resolver::setConfig('default', $config);

        $collection = Resolver::collection();

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testMagicCallStaticForwardsToDefaultCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        Resolver::setConfig('default', $config);

        // Call withAttribute() directly on Resolver (should forward to collection)
        $filtered = Resolver::withAttribute(TestRoute::class);

        $this->assertInstanceOf(AttributeCollection::class, $filtered);
        $this->assertGreaterThan(0, $filtered->count());
    }

    public function testMagicCallStaticSupportsChaining(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        Resolver::setConfig('default', $config);

        // Chain multiple collection methods
        $result = Resolver::withAttribute(TestRoute::class)
            ->first();

        $this->assertInstanceOf(AttributeInfo::class, $result);
    }

    public function testCollectionSupportsChaining(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        Resolver::setConfig('plugins', $config);

        // Named config chaining
        $result = Resolver::collection('plugins')
            ->withAttribute(TestRoute::class);

        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertGreaterThan(0, $result->count());
    }
}
