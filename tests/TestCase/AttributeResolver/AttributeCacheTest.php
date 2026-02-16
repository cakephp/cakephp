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

use Cake\AttributeResolver\AttributeCache;
use Cake\AttributeResolver\AttributeCollection;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\AttributeResolver\ValueObject\AttributeTarget;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use stdClass;
use TestApp\Attribute\Resolver\TestPriority;
use TestApp\Attribute\Resolver\ValueObject\TestConfig;

class AttributeCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::drop('attribute_test');
        Cache::setConfig('attribute_test', [
            'className' => 'File',
            'prefix' => 'attr_test_',
            'serialize' => true,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Cache::clear('attribute_test');
        Cache::drop('attribute_test');
    }

    /**
     * Test constructor accepts cache config and validateFiles settings
     */
    public function testConstructor(): void
    {
        $cache = new AttributeCache(
            cacheConfig: 'attribute_test',
            validateFiles: true,
        );

        $this->assertInstanceOf(AttributeCache::class, $cache);
    }

    /**
     * Test write() stores AttributeInfo objects and read() retrieves them as AttributeCollection
     */
    public function testWriteAndReadWithAttributeInfo(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $attributeInfos = [
            new AttributeInfo(
                className: 'App\\TestClass',
                attributeName: stdClass::class,
                arguments: [],
                filePath: '/app/src/TestClass.php',
                lineNumber: 10,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'TestClass',
                    declaringClass: 'App\\TestClass',
                ),
                pluginName: null,
            ),
        ];

        $success = $cache->write('test_config', $attributeInfos);
        $this->assertTrue($success);

        $result = $cache->read('test_config');
        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertCount(1, $result);

        // Data should be returned as AttributeInfo objects via lazy hydration
        $first = $result->first();
        $this->assertInstanceOf(AttributeInfo::class, $first);
        $this->assertSame('App\\TestClass', $first->className);
        $this->assertSame(stdClass::class, $first->attributeName);
        $this->assertSame('/app/src/TestClass.php', $first->filePath);
    }

    /**
     * Test write() works with multiple AttributeInfo objects
     */
    public function testWriteWithMultipleObjects(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $data = [
            new AttributeInfo(
                className: 'TestClass1',
                attributeName: 'TestAttribute1',
                arguments: [],
                filePath: '/test1.php',
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'TestClass1',
                    declaringClass: 'TestClass1',
                ),
                fileTime: time(),
            ),
            new AttributeInfo(
                className: 'TestClass2',
                attributeName: 'TestAttribute2',
                arguments: ['key' => 'value'],
                filePath: '/test2.php',
                lineNumber: 5,
                target: new AttributeTarget(
                    type: AttributeTargetType::METHOD,
                    name: 'testMethod',
                    declaringClass: 'TestClass2',
                ),
                fileTime: time(),
            ),
        ];

        $success = $cache->write('test_multiple', $data);
        $this->assertTrue($success);

        $result = $cache->read('test_multiple');
        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertCount(2, $result);

        $items = $result->toList();
        $this->assertInstanceOf(AttributeInfo::class, $items[0]);
        $this->assertInstanceOf(AttributeInfo::class, $items[1]);
        $this->assertSame('TestClass1', $items[0]->className);
        $this->assertSame('TestClass2', $items[1]->className);
    }

    /**
     * Test read() returns null when no cache exists
     */
    public function testReadReturnsNullWhenNoCacheExists(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $result = $cache->read('nonexistent');
        $this->assertNull($result);
    }

    /**
     * Test delete() removes cached data
     */
    public function testDelete(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $data = [
            new AttributeInfo(
                className: 'Test',
                attributeName: stdClass::class,
                arguments: [],
                filePath: '/test.php',
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'Test',
                    declaringClass: 'Test',
                ),
                fileTime: time(),
            ),
        ];

        $cache->write('test_delete', $data);
        $this->assertInstanceOf(AttributeCollection::class, $cache->read('test_delete'));

        $deleted = $cache->delete('test_delete');
        $this->assertTrue($deleted);
        $this->assertNull($cache->read('test_delete'));
    }

    /**
     * Test file validation checks modification times when enabled
     */
    public function testFileValidationChecksModificationTimes(): void
    {
        $sourceFile = TMP . 'attr_cache_source_' . uniqid() . '.php';
        file_put_contents($sourceFile, '<?php class TestClass {}');
        $sourceTime = filemtime($sourceFile);

        try {
            $cache = new AttributeCache('attribute_test', validateFiles: true);

            $attributeInfo = new AttributeInfo(
                className: 'TestClass',
                attributeName: stdClass::class,
                arguments: [],
                filePath: $sourceFile,
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'TestClass',
                    declaringClass: 'TestClass',
                ),
                fileTime: (int)$sourceTime,
            );

            $cache->write('test_validation', [$attributeInfo]);

            // First read should work
            $result = $cache->read('test_validation');
            $this->assertInstanceOf(AttributeCollection::class, $result);
            $this->assertCount(1, $result);

            // Touch source file to make it newer
            touch($sourceFile, $sourceTime + 2);
            clearstatcache(true, $sourceFile);

            // Second read should return null due to validation failure
            $result = $cache->read('test_validation');
            $this->assertNull($result);
        } finally {
            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }
        }
    }

    /**
     * Test that cache persists when validation is disabled
     */
    public function testValidationDisabledByDefault(): void
    {
        $sourceFile = TMP . 'attr_cache_source_no_val_' . uniqid() . '.php';
        file_put_contents($sourceFile, '<?php class TestClass {}');
        $sourceTime = filemtime($sourceFile);

        try {
            $cache = new AttributeCache('attribute_test', validateFiles: false);

            $attributeInfo = new AttributeInfo(
                className: 'TestClass',
                attributeName: stdClass::class,
                arguments: [],
                filePath: $sourceFile,
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'TestClass',
                    declaringClass: 'TestClass',
                ),
                fileTime: (int)$sourceTime,
            );

            $cache->write('test_no_validation', [$attributeInfo]);

            // First read should work
            $result = $cache->read('test_no_validation');
            $this->assertInstanceOf(AttributeCollection::class, $result);
            $this->assertCount(1, $result);

            // Touch source file to make it newer
            sleep(1);
            touch($sourceFile, $sourceTime + 2);
            clearstatcache(true, $sourceFile);

            // Should still return cached data because validation is disabled
            $result2 = $cache->read('test_no_validation');
            $this->assertInstanceOf(AttributeCollection::class, $result2, 'Second read should return collection because validateFiles=false');
            $this->assertCount(1, $result2);
        } finally {
            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }
        }
    }

    /**
     * Test read() validates data structure
     */
    public function testReadValidatesStructure(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        // Write invalid data directly to cache
        Cache::write('attribute_resolver_test_invalid', [
            ['invalid' => 'structure'], // Missing required keys
        ], 'attribute_test');

        $result = $cache->read('test_invalid');
        $this->assertNull($result);
    }

    /**
     * Test complex arguments are preserved through cache
     */
    public function testComplexArgumentTypes(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $attributeInfo = new AttributeInfo(
            className: 'App\\Controller\\UsersController',
            attributeName: 'App\\Routing\\Route',
            arguments: [
                'path' => '/users/{id}',
                'methods' => ['GET', 'POST'],
                'targetType' => AttributeTargetType::METHOD,
                'options' => [
                    'auth' => true,
                    'roles' => ['admin', 'user'],
                    'limit' => 100,
                    'ratio' => 0.75,
                    'nullable' => null,
                ],
            ],
            filePath: '/app/src/Controller/UsersController.php',
            lineNumber: 25,
            target: new AttributeTarget(
                type: AttributeTargetType::METHOD,
                name: 'show',
                declaringClass: 'App\\Controller\\UsersController',
            ),
            pluginName: null,
        );

        $cache->write('test_complex', [$attributeInfo]);
        $result = $cache->read('test_complex');

        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertCount(1, $result);

        $first = $result->first();
        $this->assertInstanceOf(AttributeInfo::class, $first);
        $this->assertSame('App\\Controller\\UsersController', $first->className);
        $this->assertSame('/users/{id}', $first->arguments['path']);
        $this->assertSame(['GET', 'POST'], $first->arguments['methods']);
        $this->assertTrue($first->arguments['options']['auth']);
        $this->assertSame(100, $first->arguments['options']['limit']);
    }

    /**
     * Test cacheKey generation is consistent
     */
    public function testCacheKeyGeneration(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $data = [
            new AttributeInfo(
                className: 'TestClass',
                attributeName: 'TestAttribute',
                arguments: [],
                filePath: '/test.php',
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'TestClass',
                    declaringClass: 'TestClass',
                ),
                fileTime: time(),
            ),
        ];

        // Write with same name should overwrite
        $cache->write('same_key', $data);
        $cache->write('same_key', $data);

        $result = $cache->read('same_key');
        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertCount(1, $result);
    }

    /**
     * Test validation with non-existent source files
     */
    public function testValidationWithMissingSourceFile(): void
    {
        $cache = new AttributeCache('attribute_test', validateFiles: true);

        $attributeInfo = new AttributeInfo(
            className: 'TestClass',
            attributeName: 'TestAttribute',
            arguments: [],
            filePath: '/non/existent/file.php',
            lineNumber: 1,
            target: new AttributeTarget(
                type: AttributeTargetType::CLASS_TYPE,
                name: 'TestClass',
                declaringClass: 'TestClass',
            ),
            fileTime: time(),
        );

        $cache->write('test_missing', [$attributeInfo]);

        // Should still load successfully even if source file doesn't exist
        $result = $cache->read('test_missing');
        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertCount(1, $result);
    }

    /**
     * Test multiple AttributeInfo objects with different targets
     */
    public function testMultipleObjectsWithDifferentTargets(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $data = [
            new AttributeInfo(
                className: 'Class1',
                attributeName: 'Attr1',
                arguments: [],
                filePath: '/test1.php',
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::CLASS_TYPE,
                    name: 'Class1',
                    declaringClass: 'Class1',
                ),
                pluginName: null,
            ),
            new AttributeInfo(
                className: 'Class2',
                attributeName: 'Attr2',
                arguments: [],
                filePath: '/test2.php',
                lineNumber: 2,
                target: new AttributeTarget(
                    type: AttributeTargetType::METHOD,
                    name: 'testMethod',
                    declaringClass: 'Class2',
                ),
                pluginName: 'TestPlugin',
            ),
        ];

        $cache->write('test_targets', $data);
        $result = $cache->read('test_targets');

        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertCount(2, $result);

        $items = $result->toList();
        $this->assertInstanceOf(AttributeInfo::class, $items[0]);
        $this->assertInstanceOf(AttributeInfo::class, $items[1]);
        $this->assertSame('Class1', $items[0]->className);
        $this->assertSame('Class2', $items[1]->className);
        $this->assertSame(AttributeTargetType::CLASS_TYPE, $items[0]->target->type);
        $this->assertSame(AttributeTargetType::METHOD, $items[1]->target->type);
    }

    /**
     * Test delete returns false for non-existent key
     */
    public function testDeleteNonExistentKey(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        // Deleting a key that doesn't exist returns false
        $result = $cache->delete('nonexistent_key');
        $this->assertFalse($result);
    }

    /**
     * Test read returns null for non-array cached data
     */
    public function testReadReturnsNullForNonArrayData(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        // Write a string directly to cache (invalid data)
        Cache::write('attribute_resolver_test_string', 'not an array', 'attribute_test');

        $result = $cache->read('test_string');
        $this->assertNull($result);
    }

    /**
     * Test isValid method with multiple files
     */
    public function testValidationWithMultipleFiles(): void
    {
        $sourceFile1 = TMP . 'attr_cache_multi1_' . uniqid() . '.php';
        $sourceFile2 = TMP . 'attr_cache_multi2_' . uniqid() . '.php';

        file_put_contents($sourceFile1, '<?php class Test1 {}');
        file_put_contents($sourceFile2, '<?php class Test2 {}');
        $time1 = filemtime($sourceFile1);
        $time2 = filemtime($sourceFile2);

        try {
            $cache = new AttributeCache('attribute_test', validateFiles: true);

            $data = [
                new AttributeInfo(
                    className: 'Test1',
                    attributeName: 'Attr1',
                    arguments: [],
                    filePath: $sourceFile1,
                    lineNumber: 1,
                    target: new AttributeTarget(
                        type: AttributeTargetType::CLASS_TYPE,
                        name: 'Test1',
                        declaringClass: 'Test1',
                    ),
                    fileTime: (int)$time1,
                ),
                new AttributeInfo(
                    className: 'Test2',
                    attributeName: 'Attr2',
                    arguments: [],
                    filePath: $sourceFile2,
                    lineNumber: 1,
                    target: new AttributeTarget(
                        type: AttributeTargetType::CLASS_TYPE,
                        name: 'Test2',
                        declaringClass: 'Test2',
                    ),
                    fileTime: (int)$time2,
                ),
            ];

            $cache->write('test_multi_files', $data);

            // First read should work
            $result = $cache->read('test_multi_files');
            $this->assertInstanceOf(AttributeCollection::class, $result);
            $this->assertCount(2, $result);

            // Touch only the second file
            touch($sourceFile2, $time2 + 2);
            clearstatcache(true, $sourceFile2);

            // Should invalidate because one file changed
            $result = $cache->read('test_multi_files');
            $this->assertNull($result);
        } finally {
            if (file_exists($sourceFile1)) {
                unlink($sourceFile1);
            }
            if (file_exists($sourceFile2)) {
                unlink($sourceFile2);
            }
        }
    }

    /**
     * Test write with empty data array
     */
    public function testWriteEmptyData(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $success = $cache->write('test_empty', []);
        $this->assertTrue($success);

        $result = $cache->read('test_empty');
        $this->assertInstanceOf(AttributeCollection::class, $result);
        $this->assertCount(0, $result);
    }

    /**
     * Test that cache stores data as arrays (not objects) for performance
     */
    public function testCacheStoresArraysNotObjects(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $data = [
            new AttributeInfo(
                className: 'TestClass',
                attributeName: 'TestAttribute',
                arguments: ['key' => 'value'],
                filePath: '/test.php',
                lineNumber: 1,
                target: new AttributeTarget(
                    type: AttributeTargetType::METHOD,
                    name: 'testMethod',
                    declaringClass: 'TestClass',
                ),
                fileTime: time(),
            ),
        ];

        $cache->write('test_array_storage', $data);

        // Verify raw cache contains arrays, not objects
        $rawData = Cache::read('attribute_resolver_test_array_storage', 'attribute_test');
        $this->assertIsArray($rawData);
        $this->assertArrayHasKey('data', $rawData);
        $this->assertArrayHasKey('indexes', $rawData);

        // data should be arrays
        $this->assertIsArray($rawData['data'][0]);
        $this->assertArrayHasKey('className', $rawData['data'][0]);
        $this->assertArrayHasKey('attributeName', $rawData['data'][0]);

        // indexes should exist
        $this->assertArrayHasKey('byAttribute', $rawData['indexes']);
        $this->assertArrayHasKey('byClassName', $rawData['indexes']);
        $this->assertArrayHasKey('byTargetType', $rawData['indexes']);
    }

    /**
     * Test caching attributes with object arguments
     */
    public function testCacheWithObjectArguments(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $config = new TestConfig('database', ['host' => 'localhost']);

        $attributeInfo = new AttributeInfo(
            className: 'App\\Controller\\UsersController',
            attributeName: 'App\\Attribute\\TestComplexArgument',
            arguments: [
                'value' => 'test',
                'object' => $config,
            ],
            filePath: '/app/src/Controller/UsersController.php',
            lineNumber: 10,
            target: new AttributeTarget(
                type: AttributeTargetType::METHOD,
                name: 'index',
                declaringClass: 'App\\Controller\\UsersController',
            ),
            pluginName: null,
        );

        $cache->write('test_with_objects', [$attributeInfo]);
        $result = $cache->read('test_with_objects');

        $this->assertInstanceOf(AttributeCollection::class, $result);
        $first = $result->first();
        $this->assertNotNull($first);

        // Verify object argument is preserved
        $this->assertArrayHasKey('object', $first->arguments);
        $this->assertInstanceOf(TestConfig::class, $first->arguments['object']);
        $this->assertSame('database', $first->arguments['object']->name);
        $this->assertSame(['host' => 'localhost'], $first->arguments['object']->options);
    }

    /**
     * Test caching attributes with enum arguments
     */
    public function testCacheWithEnumArguments(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $attributeInfo = new AttributeInfo(
            className: 'App\\Service\\TaskService',
            attributeName: 'App\\Attribute\\TestComplexArgument',
            arguments: [
                'priority' => TestPriority::HIGH,
                'status' => TestPriority::CRITICAL,
            ],
            filePath: '/app/src/Service/TaskService.php',
            lineNumber: 15,
            target: new AttributeTarget(
                type: AttributeTargetType::METHOD,
                name: 'execute',
                declaringClass: 'App\\Service\\TaskService',
            ),
            pluginName: null,
        );

        $cache->write('test_with_enums', [$attributeInfo]);
        $result = $cache->read('test_with_enums');

        $this->assertInstanceOf(AttributeCollection::class, $result);
        $first = $result->first();
        $this->assertNotNull($first);

        // Verify enum arguments are preserved
        $this->assertArrayHasKey('priority', $first->arguments);
        $this->assertArrayHasKey('status', $first->arguments);
        $this->assertInstanceOf(TestPriority::class, $first->arguments['priority']);
        $this->assertInstanceOf(TestPriority::class, $first->arguments['status']);
        $this->assertSame(TestPriority::HIGH, $first->arguments['priority']);
        $this->assertSame(TestPriority::CRITICAL, $first->arguments['status']);
    }

    /**
     * Test caching attributes with nested objects
     */
    public function testCacheWithNestedObjects(): void
    {
        $cache = new AttributeCache('attribute_test', false);

        $nestedConfig = new TestConfig('nested', ['level' => 2]);
        $mainConfig = new TestConfig(
            'main',
            ['nested' => $nestedConfig, 'timeout' => 30],
        );

        $attributeInfo = new AttributeInfo(
            className: 'App\\Model\\Entity\\User',
            attributeName: 'App\\Attribute\\TestComplexArgument',
            arguments: [
                'config' => $mainConfig,
            ],
            filePath: '/app/src/Model/Entity/User.php',
            lineNumber: 20,
            target: new AttributeTarget(
                type: AttributeTargetType::PROPERTY,
                name: 'settings',
                declaringClass: 'App\\Model\\Entity\\User',
            ),
            pluginName: null,
        );

        $cache->write('test_with_nested', [$attributeInfo]);
        $result = $cache->read('test_with_nested');

        $this->assertInstanceOf(AttributeCollection::class, $result);
        $first = $result->first();
        $this->assertNotNull($first);

        // Verify nested object structure is preserved
        $this->assertArrayHasKey('config', $first->arguments);
        $config = $first->arguments['config'];
        $this->assertInstanceOf(TestConfig::class, $config);
        $this->assertSame('main', $config->name);

        // Verify nested level
        $this->assertArrayHasKey('nested', $config->options);
        $this->assertInstanceOf(TestConfig::class, $config->options['nested']);
        $this->assertSame('nested', $config->options['nested']->name);
        $this->assertSame(['level' => 2], $config->options['nested']->options);
    }
}
