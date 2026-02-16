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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\AttributeResolver;

use AttributeResolver\AttributeResolver;
use AttributeResolver\AttributeCollection;
use AttributeResolver\ValueObject\AttributeInfo;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Attribute\Resolver\TestRoute;

class AttributeResolverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        AttributeResolver::drop('default');
        AttributeResolver::drop('test');
        Cache::clear('_cake_attributes_');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        AttributeResolver::drop('default');
        AttributeResolver::drop('test');
        Cache::clear('_cake_attributes_');
    }

    public function testSetConfigAndGetConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];

        AttributeResolver::setConfig('test', $config);
        $result = AttributeResolver::getConfig('test');

        $this->assertSame($config, $result);
    }

    public function testGetConfigOrFail(): void
    {
        $config = ['paths' => [TEST_APP]];
        AttributeResolver::setConfig('test', $config);

        $result = AttributeResolver::getConfigOrFail('test');
        $this->assertSame($config, $result);
    }

    public function testGetConfigOrFailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected configuration `nonexistent` not found');

        AttributeResolver::getConfigOrFail('nonexistent');
    }

    public function testDrop(): void
    {
        AttributeResolver::setConfig('test', ['paths' => []]);
        $this->assertNotNull(AttributeResolver::getConfig('test'));

        AttributeResolver::drop('test');
        $this->assertNull(AttributeResolver::getConfig('test'));
    }

    public function testResolveReturnsAttributeCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('test', $config);

        $collection = AttributeResolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testResolveWithCacheLoadsFromCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('test', $config);

        // First resolve creates cache
        $collection1 = AttributeResolver::collection('test');

        // Second resolve should load from cache
        $collection2 = AttributeResolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection2);
        $this->assertSame($collection1->count(), $collection2->count());
    }

    public function testResolveUsesInMemoryCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('test', $config);

        $collection1 = AttributeResolver::collection('test');
        $collection2 = AttributeResolver::collection('test');

        // Should be the exact same instance due to in-memory cache
        $this->assertSame($collection1, $collection2);
    }

    public function testResolveCanChainCollectionMethods(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('test', $config);

        $routes = AttributeResolver::collection('test')->withAttribute(TestRoute::class);

        $this->assertInstanceOf(AttributeCollection::class, $routes);
        $this->assertGreaterThan(0, $routes->count());
    }

    public function testClearClearsInMemoryCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('test', $config);

        $collection1 = AttributeResolver::collection('test');
        $collection2 = AttributeResolver::collection('test');

        // Should be the same instance due to in-memory cache
        $this->assertSame($collection1, $collection2);

        AttributeResolver::clear('test');

        // After clearing, should get a new collection instance
        $collection3 = AttributeResolver::collection('test');
        $this->assertNotSame($collection1, $collection3);
    }

    public function testWarmBuildsCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('test', $config);

        $result = AttributeResolver::warm('test');

        $this->assertInstanceOf(AttributeCollection::class, $result);
    }

    public function testResolveWithDefaultConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('default', $config);

        $collection = AttributeResolver::collection();

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testDropReturnsFalseForNonexistentConfig(): void
    {
        $result = AttributeResolver::drop('nonexistent');

        $this->assertFalse($result);
    }

    public function testClearAlwaysUsesCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        AttributeResolver::setConfig('test', $config);

        // First populate the cache by calling collection
        AttributeResolver::collection('test');

        // Clear will delete from cache
        $result = AttributeResolver::clear('test');

        $this->assertTrue($result);
    }

    public function testResolveWithExcludeAttributes(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'excludeAttributes' => [TestRoute::class],
            'cache' => '_cake_attributes_',
        ];
        AttributeResolver::setConfig('test', $config);

        $collection = AttributeResolver::collection('test');

        // Should not contain any TestRoute attributes
        $routes = $collection->withAttribute(TestRoute::class);
        $this->assertSame(0, $routes->count());
    }

    public function testResolveWithMultipleConfigs(): void
    {
        $config1 = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/Controllers/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        $config2 = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/Models/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];

        AttributeResolver::setConfig('controllers', $config1);
        AttributeResolver::setConfig('models', $config2);

        $controllerCollection = AttributeResolver::collection('controllers');
        $modelCollection = AttributeResolver::collection('models');

        $this->assertInstanceOf(AttributeCollection::class, $controllerCollection);
        $this->assertInstanceOf(AttributeCollection::class, $modelCollection);

        AttributeResolver::drop('controllers');
        AttributeResolver::drop('models');
    }

    public function testResolveReturnsEmptyCollectionForMissingConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `nonexistent` attribute resolver configuration does not exist.');

        AttributeResolver::collection('nonexistent');
    }

    public function testCollectionMethodReturnsAttributeCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        AttributeResolver::setConfig('test', $config);

        $collection = AttributeResolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testCollectionMethodDefaultsToDefaultConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        AttributeResolver::setConfig('default', $config);

        $collection = AttributeResolver::collection();

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testMagicCallStaticForwardsToDefaultCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        AttributeResolver::setConfig('default', $config);

        // Call withAttribute() directly on Resolver (should forward to collection)
        $filtered = AttributeResolver::withAttribute(TestRoute::class);

        $this->assertInstanceOf(AttributeCollection::class, $filtered);
        $this->assertGreaterThan(0, $filtered->count());
    }

    public function testMagicCallStaticSupportsChaining(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        AttributeResolver::setConfig('default', $config);

        // Chain multiple collection methods
        $result = AttributeResolver::withAttribute(TestRoute::class)
            ->first();

        $this->assertInstanceOf(AttributeInfo::class, $result);
    }

    public function testCollectionSupportsChaining(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        AttributeResolver::setConfig('plugins', $config);

        // Named config chaining
        $result = AttributeResolver::collection('plugins')
            ->withAttribute(TestRoute::class);

        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertGreaterThan(0, $result->count());
    }

    public function testResolveWithCacheDisabled(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => false,
        ];
        AttributeResolver::setConfig('test', $config);

        $collection = AttributeResolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());

        // Clear should work without error when cache is disabled
        $result = AttributeResolver::clear('test');
        $this->assertTrue($result);
    }

    public function testResolveWithCacheDisabledAlwaysScans(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => false,
        ];
        AttributeResolver::setConfig('test', $config);

        $collection1 = AttributeResolver::collection('test');
        AttributeResolver::drop('test');
        AttributeResolver::setConfig('test', $config);

        // After drop and re-config, should still get results (no cache to load from)
        $collection2 = AttributeResolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection2);
        $this->assertSame($collection1->count(), $collection2->count());
    }
}
