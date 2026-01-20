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
namespace Cake\Test\TestCase\Attribute\Resolver;

use Cake\Attribute\Resolver\AttributeCache;
use Cake\Attribute\Resolver\Enum\AttributeTargetType;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Attribute\Resolver\ValueObject\AttributeTarget;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use stdClass;

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
     * Test write() stores AttributeInfo objects and read() retrieves them
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
        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        // Data should be returned as AttributeInfo objects after unserialization
        $this->assertInstanceOf(AttributeInfo::class, $result[0]);
        $this->assertSame('App\\TestClass', $result[0]->className);
        $this->assertSame(stdClass::class, $result[0]->attributeName);
        $this->assertSame('/app/src/TestClass.php', $result[0]->filePath);
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
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(AttributeInfo::class, $result[0]);
        $this->assertInstanceOf(AttributeInfo::class, $result[1]);
        $this->assertSame('TestClass1', $result[0]->className);
        $this->assertSame('TestClass2', $result[1]->className);
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
        $this->assertIsArray($cache->read('test_delete'));

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
            $this->assertIsArray($result);
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
            $this->assertIsArray($result);
            $this->assertCount(1, $result);

            // Touch source file to make it newer
            sleep(1);
            touch($sourceFile, $sourceTime + 2);
            clearstatcache(true, $sourceFile);

            // Should still return cached data because validation is disabled
            $result2 = $cache->read('test_no_validation');
            $this->assertIsArray($result2, 'Second read should return array because validateFiles=false');
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

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AttributeInfo::class, $result[0]);
        $this->assertSame('App\\Controller\\UsersController', $result[0]->className);
        $this->assertSame('/users/{id}', $result[0]->arguments['path']);
        $this->assertSame(['GET', 'POST'], $result[0]->arguments['methods']);
        $this->assertTrue($result[0]->arguments['options']['auth']);
        $this->assertSame(100, $result[0]->arguments['options']['limit']);
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
        $this->assertIsArray($result);
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
        $this->assertIsArray($result);
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

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(AttributeInfo::class, $result[0]);
        $this->assertInstanceOf(AttributeInfo::class, $result[1]);
        $this->assertSame('Class1', $result[0]->className);
        $this->assertSame('Class2', $result[1]->className);
        $this->assertSame(AttributeTargetType::CLASS_TYPE, $result[0]->target->type);
        $this->assertSame(AttributeTargetType::METHOD, $result[1]->target->type);
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
            $this->assertIsArray($result);
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
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }
}
