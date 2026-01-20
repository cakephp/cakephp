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
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Attribute\Resolver\TestRoute;

class ResolverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Resolver::drop('default');
        Resolver::drop('test');
        Cache::clear('_cake_attributes_');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Resolver::drop('default');
        Resolver::drop('test');
        Cache::clear('_cake_attributes_');
    }

    public function testSetConfigAndGetConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
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

    public function testResolveReturnsAttributeCollection(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        Resolver::setConfig('test', $config);

        $collection = Resolver::collection('test');

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
        Resolver::setConfig('test', $config);

        // First resolve creates cache
        $collection1 = Resolver::collection('test');

        // Second resolve should load from cache
        $collection2 = Resolver::collection('test');

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
            'cache' => '_cake_attributes_',
        ];
        Resolver::setConfig('test', $config);

        $routes = Resolver::collection('test')->withAttribute(TestRoute::class);

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
        Resolver::setConfig('test', $config);

        $collection1 = Resolver::collection('test');
        $collection2 = Resolver::collection('test');

        // Should be the same instance due to in-memory cache
        $this->assertSame($collection1, $collection2);

        Resolver::clear('test');

        // After clearing, should get a new collection instance
        $collection3 = Resolver::collection('test');
        $this->assertNotSame($collection1, $collection3);
    }

    public function testWarmBuildsCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        Resolver::setConfig('test', $config);

        $result = Resolver::warm('test');

        $this->assertTrue($result);
    }

    public function testResolveWithDefaultConfig(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];
        Resolver::setConfig('default', $config);

        $collection = Resolver::collection();

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testDropReturnsFalseForNonexistentConfig(): void
    {
        $result = Resolver::drop('nonexistent');

        $this->assertFalse($result);
    }

    public function testClearAlwaysUsesCache(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
        ];
        Resolver::setConfig('test', $config);

        // First populate the cache by calling collection
        Resolver::collection('test');

        // Clear will delete from cache
        $result = Resolver::clear('test');

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
            'cache' => '_cake_attributes_',
        ];
        $config2 = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/Models/*.php'],
            'basePath' => TEST_APP,
            'cache' => '_cake_attributes_',
        ];

        Resolver::setConfig('controllers', $config1);
        Resolver::setConfig('models', $config2);

        $controllerCollection = Resolver::collection('controllers');
        $modelCollection = Resolver::collection('models');

        $this->assertInstanceOf(AttributeCollection::class, $controllerCollection);
        $this->assertInstanceOf(AttributeCollection::class, $modelCollection);

        Resolver::drop('controllers');
        Resolver::drop('models');
    }

    public function testResolveReturnsEmptyCollectionForMissingConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `nonexistent` attribute resolver configuration does not exist.');

        Resolver::collection('nonexistent');
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

    public function testResolveWithCacheDisabled(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => false,
        ];
        Resolver::setConfig('test', $config);

        $collection = Resolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());

        // Clear should work without error when cache is disabled
        $result = Resolver::clear('test');
        $this->assertTrue($result);
    }

    public function testResolveWithCacheDisabledAlwaysScans(): void
    {
        $config = [
            'paths' => ['TestApp/Attribute/Resolver/Fixture/*.php'],
            'basePath' => TEST_APP,
            'cache' => false,
        ];
        Resolver::setConfig('test', $config);

        $collection1 = Resolver::collection('test');
        Resolver::drop('test');
        Resolver::setConfig('test', $config);

        // After drop and re-config, should still get results (no cache to load from)
        $collection2 = Resolver::collection('test');

        $this->assertInstanceOf(AttributeCollection::class, $collection2);
        $this->assertSame($collection1->count(), $collection2->count());
    }
}
