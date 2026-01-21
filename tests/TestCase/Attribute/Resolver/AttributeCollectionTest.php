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
namespace Cake\Test\TestCase\Attribute\Resolver;

use Cake\Attribute\Resolver\AttributeCollection;
use Cake\Attribute\Resolver\Enum\AttributeTargetType;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Attribute\Resolver\ValueObject\AttributeTarget;
use Cake\TestSuite\TestCase;
use Countable;
use IteratorAggregate;

class AttributeCollectionTest extends TestCase
{
    private function createAttribute(
        string $attributeName,
        string $className,
        AttributeTargetType $targetType,
        ?string $pluginName = null,
    ): AttributeInfo {
        return new AttributeInfo(
            className: $className,
            attributeName: $attributeName,
            arguments: [],
            filePath: '/app/test.php',
            lineNumber: 1,
            target: new AttributeTarget(type: $targetType, name: 'test'),
            fileTime: time(),
            pluginName: $pluginName,
        );
    }

    private function createAttributeArray(
        string $attributeName,
        string $className,
        string $targetType,
        ?string $pluginName = null,
    ): array {
        return [
            'className' => $className,
            'attributeName' => $attributeName,
            'arguments' => [],
            'filePath' => '/app/test.php',
            'lineNumber' => 1,
            'target' => ['type' => $targetType, 'name' => 'test', 'declaringClass' => null],
            'fileTime' => time(),
            'pluginName' => $pluginName,
        ];
    }

    public function testImplementsIteratorAggregateAndCountable(): void
    {
        $collection = new AttributeCollection([]);

        $this->assertInstanceOf(IteratorAggregate::class, $collection);
        $this->assertInstanceOf(Countable::class, $collection);
    }

    public function testAcceptsArrayOfAttributeInfoObjects(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
        ]);

        $this->assertCount(1, $collection);
        $first = $collection->first();
        $this->assertInstanceOf(AttributeInfo::class, $first);
    }

    public function testAcceptsRawArrayData(): void
    {
        $collection = new AttributeCollection([
            $this->createAttributeArray('App\Attribute\Route', 'App\Controller\UsersController', 'class_constant'),
        ]);

        $this->assertCount(1, $collection);
        $first = $collection->first();
        $this->assertInstanceOf(AttributeInfo::class, $first);
        $this->assertSame('App\Attribute\Route', $first->attributeName);
    }

    public function testAcceptsPrebuiltIndexes(): void
    {
        $data = [
            $this->createAttributeArray('App\Attribute\Route', 'App\Controller\UsersController', 'method'),
            $this->createAttributeArray('App\Attribute\Cache', 'App\Controller\PostsController', 'method'),
        ];
        $indexes = [
            'byAttribute' => ['App\Attribute\Route' => [0], 'App\Attribute\Cache' => [1]],
            'byClassName' => ['App\Controller\UsersController' => [0], 'App\Controller\PostsController' => [1]],
            'byTargetType' => ['method' => [0, 1]],
        ];

        $collection = new AttributeCollection($data, $indexes);

        // Filtering should use indexes for fast lookup
        $filtered = $collection->withAttribute('App\Attribute\Route');
        $this->assertCount(1, $filtered);
    }

    public function testFilterReturnsAttributeCollection(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
        ]);
        $filtered = $collection->filter(fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::METHOD);

        $this->assertInstanceOf(AttributeCollection::class, $filtered);
        $this->assertCount(1, $filtered);
    }

    public function testWithAttributeFiltersByExactName(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Route', 'App\Controller\PostsController', AttributeTargetType::CLASS_CONSTANT),
        ]);
        $filtered = $collection->withAttribute('App\Attribute\Route');

        $this->assertCount(2, $filtered);
        $this->assertInstanceOf(AttributeCollection::class, $filtered);
    }

    public function testWithAttributeAcceptsArrayWithOrLogic(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Validate', 'App\Model\Entity\User', AttributeTargetType::PROPERTY),
        ]);
        $filtered = $collection->withAttribute(['App\Attribute\Route', 'App\Attribute\Cache']);

        $this->assertCount(2, $filtered);
        $names = array_map(fn(AttributeInfo $attr) => $attr->attributeName, $filtered->toList());
        $this->assertContains('App\Attribute\Route', $names);
        $this->assertContains('App\Attribute\Cache', $names);
    }

    public function testWithAttributeReturnsEmptyWhenNoMatches(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
        ]);
        $filtered = $collection->withAttribute('App\Attribute\NonExistent');

        $this->assertCount(0, $filtered);
    }

    public function testWithNamespaceFiltersWithWildcards(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Controller\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Model\Attribute\Validate', 'App\Model\Entity\User', AttributeTargetType::PROPERTY),
            $this->createAttribute('Plugin\Helper\Custom', 'App\View\Helper\MyHelper', AttributeTargetType::METHOD),
        ]);
        $filtered = $collection->withNamespace('App\Controller\*');

        $this->assertCount(1, $filtered);
        $this->assertInstanceOf(AttributeCollection::class, $filtered);
    }

    public function testWithNamespaceExactMatch(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Model\Route', 'App\Model\Entity\User', AttributeTargetType::PROPERTY),
        ]);
        $filtered = $collection->withNamespace('App\Attribute\Route');

        $this->assertCount(1, $filtered);
    }

    public function testWithNamespaceMultipleWildcards(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Controller\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Controller\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Model\Attribute\Validate', 'App\Model\Entity\User', AttributeTargetType::PROPERTY),
        ]);
        $filtered = $collection->withNamespace('App\*\Attribute\*');

        $this->assertCount(3, $filtered);
    }

    public function testWithTargetTypeFiltersBySingleEnum(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Route', 'App\Controller\PostsController', AttributeTargetType::CLASS_CONSTANT),
        ]);
        $filtered = $collection->withTargetType(AttributeTargetType::CLASS_CONSTANT);

        $this->assertCount(2, $filtered);
        $this->assertInstanceOf(AttributeCollection::class, $filtered);
    }

    public function testWithTargetTypeAcceptsArray(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Validate', 'App\Model\Entity\User', AttributeTargetType::PROPERTY),
        ]);
        $filtered = $collection->withTargetType([AttributeTargetType::METHOD, AttributeTargetType::PROPERTY]);

        $this->assertCount(2, $filtered);
    }

    public function testWithClassNameFiltersByExactName(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\PostsController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::METHOD),
        ]);
        $filtered = $collection->withClassName('App\Controller\UsersController');

        $this->assertCount(2, $filtered);
        $this->assertInstanceOf(AttributeCollection::class, $filtered);
    }

    public function testWithClassNameAcceptsArray(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\PostsController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Validate', 'App\Model\Entity\User', AttributeTargetType::PROPERTY),
        ]);
        $filtered = $collection->withClassName(['App\Controller\UsersController', 'App\Controller\PostsController']);

        $this->assertCount(2, $filtered);
    }

    public function testWithAttributeContainsPartialMatching(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
            $this->createAttribute('Plugin\RouteAttribute', 'App\Controller\PostsController', AttributeTargetType::CLASS_CONSTANT),
        ]);
        $filtered = $collection->withAttributeContains('Route');

        $this->assertCount(2, $filtered);
    }

    public function testWithAttributeContainsCaseSensitive(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\route', 'App\Controller\PostsController', AttributeTargetType::METHOD),
        ]);
        $filtered = $collection->withAttributeContains('Route');

        $this->assertCount(1, $filtered);
    }

    public function testWithClassNameContainsPartialMatching(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\PostsController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Validate', 'App\Model\Entity\User', AttributeTargetType::PROPERTY),
        ]);
        $filtered = $collection->withClassNameContains('Controller');

        $this->assertCount(2, $filtered);
    }

    public function testWithPluginFiltersByPluginName(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('TestPlugin\Attribute\Route', 'TestPlugin\Controller\UsersController', AttributeTargetType::METHOD, 'TestPlugin'),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\PostsController', AttributeTargetType::METHOD),
            $this->createAttribute('TestPlugin\Attribute\Cache', 'TestPlugin\Controller\PostsController', AttributeTargetType::METHOD, 'TestPlugin'),
        ]);
        $filtered = $collection->withPlugin('TestPlugin');

        $this->assertCount(2, $filtered);
        $this->assertInstanceOf(AttributeCollection::class, $filtered);
    }

    public function testWithPluginNullFiltersAppAttributes(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('TestPlugin\Attribute\Route', 'TestPlugin\Controller\UsersController', AttributeTargetType::METHOD, 'TestPlugin'),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\PostsController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::METHOD),
        ]);
        $filtered = $collection->withPlugin(null);

        $this->assertCount(2, $filtered);
    }

    public function testChainingMultipleFilters(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::CLASS_CONSTANT),
            $this->createAttribute('App\Attribute\Route', 'App\Controller\PostsController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\UsersController', AttributeTargetType::METHOD),
        ]);
        $filtered = $collection
            ->withAttribute('App\Attribute\Route')
            ->withTargetType(AttributeTargetType::METHOD);

        $this->assertCount(1, $filtered);
        $this->assertInstanceOf(AttributeCollection::class, $filtered);
    }

    public function testEmptyCollection(): void
    {
        $collection = new AttributeCollection([]);

        $filtered = $collection->withAttribute('NonExistent');
        $this->assertCount(0, $filtered);

        $chained = $collection
            ->withNamespace('App\*')
            ->withTargetType(AttributeTargetType::METHOD);
        $this->assertCount(0, $chained);
    }

    /**
     * Test lazy hydration with AttributeInfo objects (not arrays)
     */
    public function testLazyHydrationWithAttributeInfoObjects(): void
    {
        $attributeInfo = new AttributeInfo(
            className: 'App\Controller\TestController',
            attributeName: 'App\Attribute\Route',
            arguments: ['path' => '/test'],
            filePath: '/app/src/Controller/TestController.php',
            lineNumber: 10,
            target: new AttributeTarget(
                type: AttributeTargetType::CLASS_TYPE,
                name: 'TestController',
                declaringClass: 'App\Controller\TestController',
            ),
            pluginName: null,
        );

        // Pass AttributeInfo objects directly (not arrays)
        $collection = new AttributeCollection([$attributeInfo]);

        $this->assertCount(1, $collection);
        $first = $collection->first();
        $this->assertInstanceOf(AttributeInfo::class, $first);
        $this->assertSame('App\Controller\TestController', $first->className);
    }

    /**
     * Test collection works with multiple AttributeInfo objects
     */
    public function testMultipleAttributeInfoObjects(): void
    {
        $attributeInfo1 = new AttributeInfo(
            className: 'App\Controller\FirstController',
            attributeName: 'App\Attribute\Route',
            arguments: [],
            filePath: '/app/src/Controller/FirstController.php',
            lineNumber: 5,
            target: new AttributeTarget(
                type: AttributeTargetType::CLASS_TYPE,
                name: 'FirstController',
                declaringClass: 'App\Controller\FirstController',
            ),
            pluginName: null,
        );

        $attributeInfo2 = new AttributeInfo(
            className: 'App\Controller\SecondController',
            attributeName: 'App\Attribute\Route',
            arguments: ['path' => '/test'],
            filePath: '/app/src/Controller/SecondController.php',
            lineNumber: 10,
            target: new AttributeTarget(
                type: AttributeTargetType::METHOD,
                name: 'index',
                declaringClass: 'App\Controller\SecondController',
            ),
            pluginName: null,
        );

        $collection = new AttributeCollection([$attributeInfo1, $attributeInfo2]);

        $this->assertCount(2, $collection);

        $items = $collection->toList();
        $this->assertSame('App\Controller\FirstController', $items[0]->className);
        $this->assertSame('App\Controller\SecondController', $items[1]->className);
    }

    /**
     * Test lazy hydration from raw arrays
     */
    public function testLazyHydrationFromArrays(): void
    {
        $data = [
            $this->createAttributeArray('App\Attribute\Route', 'App\Controller\UsersController', 'method'),
            $this->createAttributeArray('App\Attribute\Cache', 'App\Controller\PostsController', 'class_type'),
        ];

        $collection = new AttributeCollection($data);

        // Count should work without hydration
        $this->assertCount(2, $collection);

        // Only hydrate when accessing
        $first = $collection->first();
        $this->assertInstanceOf(AttributeInfo::class, $first);
        $this->assertSame('App\Attribute\Route', $first->attributeName);
    }

    /**
     * Test filtering does not require full hydration
     */
    public function testFilteringUsesIndexesWhenAvailable(): void
    {
        $data = [
            $this->createAttributeArray('App\Attribute\Route', 'App\Controller\UsersController', 'method'),
            $this->createAttributeArray('App\Attribute\Cache', 'App\Controller\PostsController', 'method'),
            $this->createAttributeArray('App\Attribute\Route', 'App\Controller\ArticlesController', 'property'),
        ];

        $collection = new AttributeCollection($data);

        // Filter by attribute
        $routes = $collection->withAttribute('App\Attribute\Route');
        $this->assertCount(2, $routes);

        // Filter by target type
        $methods = $collection->withTargetType(AttributeTargetType::METHOD);
        $this->assertCount(2, $methods);

        // Chained filtering
        $routeMethods = $collection
            ->withAttribute('App\Attribute\Route')
            ->withTargetType(AttributeTargetType::METHOD);
        $this->assertCount(1, $routeMethods);
    }

    /**
     * Test toArray and toList return hydrated objects
     */
    public function testToArrayAndToListReturnHydratedObjects(): void
    {
        $data = [
            $this->createAttributeArray('App\Attribute\Route', 'App\Controller\UsersController', 'method'),
        ];

        $collection = new AttributeCollection($data);

        $array = $collection->toArray();
        $this->assertCount(1, $array);
        $this->assertInstanceOf(AttributeInfo::class, $array[0]);

        $list = $collection->toList();
        $this->assertCount(1, $list);
        $this->assertInstanceOf(AttributeInfo::class, $list[0]);
    }

    /**
     * Test iteration yields hydrated AttributeInfo objects
     */
    public function testIterationYieldsHydratedObjects(): void
    {
        $data = [
            $this->createAttributeArray('App\Attribute\Route', 'App\Controller\UsersController', 'method'),
            $this->createAttributeArray('App\Attribute\Cache', 'App\Controller\PostsController', 'property'),
        ];

        $collection = new AttributeCollection($data);

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
            $this->assertInstanceOf(AttributeInfo::class, $item);
        }
        $this->assertCount(2, $items);
    }

    /**
     * Test getCacheData returns raw arrays and indexes
     */
    public function testGetCacheDataReturnsArraysAndIndexes(): void
    {
        $collection = new AttributeCollection([
            $this->createAttribute('App\Attribute\Route', 'App\Controller\UsersController', AttributeTargetType::METHOD),
            $this->createAttribute('App\Attribute\Cache', 'App\Controller\PostsController', AttributeTargetType::PROPERTY),
        ]);

        $cacheData = $collection->getCacheData();

        $this->assertArrayHasKey('data', $cacheData);
        $this->assertArrayHasKey('indexes', $cacheData);
        $this->assertCount(2, $cacheData['data']);

        // Data should be arrays, not objects
        $this->assertIsArray($cacheData['data'][0]);
        $this->assertArrayHasKey('attributeName', $cacheData['data'][0]);

        // Indexes should be built
        $this->assertArrayHasKey('byAttribute', $cacheData['indexes']);
        $this->assertArrayHasKey('byClassName', $cacheData['indexes']);
        $this->assertArrayHasKey('byTargetType', $cacheData['indexes']);
    }
}
