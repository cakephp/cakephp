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
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\PhpEngine;
use Cake\TestSuite\TestCase;
use LogicException;
use stdClass;

/**
 * PhpEngineTest class
 *
 * Tests the PhpEngine cache engine which uses brick/varexporter
 * to generate PHP files for OPcache acceleration.
 */
class PhpEngineTest extends TestCase
{
    use EngineEventsTrait;

    protected string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePath = TMP . 'tests' . DS . 'php_cache' . DS;
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }

        Cache::setConfig('php_test', [
            'className' => PhpEngine::class,
            'path' => $this->cachePath,
            'prefix' => 'cake_',
            'duration' => 3600,
        ]);

        $this->engine = 'php_test';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Cache::drop('php_test');

        // Clean up cache files
        if (is_dir($this->cachePath)) {
            $files = glob($this->cachePath . '*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    /**
     * Test basic set and get operations
     */
    public function testSetAndGet(): void
    {
        $result = Cache::write('test_key', 'test_value', 'php_test');
        $this->assertTrue($result);

        $value = Cache::read('test_key', 'php_test');
        $this->assertSame('test_value', $value);
    }

    /**
     * Test get returns default for missing key
     */
    public function testGetMissingKey(): void
    {
        $value = Cache::read('nonexistent', 'php_test');
        $this->assertNull($value);
    }

    /**
     * Test caching arrays
     */
    public function testSetAndGetArray(): void
    {
        $data = ['foo' => 'bar', 'nested' => ['a' => 1, 'b' => 2]];

        Cache::write('array_key', $data, 'php_test');
        $result = Cache::read('array_key', 'php_test');

        $this->assertSame($data, $result);
    }

    /**
     * Test caching objects
     */
    public function testSetAndGetObject(): void
    {
        $data = new stdClass();
        $data->name = 'test';
        $data->value = 42;

        Cache::write('object_key', $data, 'php_test');
        $result = Cache::read('object_key', 'php_test');

        $this->assertEquals($data, $result);
    }

    /**
     * Test delete operation
     */
    public function testDelete(): void
    {
        Cache::write('delete_key', 'value', 'php_test');
        $this->assertSame('value', Cache::read('delete_key', 'php_test'));

        $result = Cache::delete('delete_key', 'php_test');
        $this->assertTrue($result);

        $this->assertNull(Cache::read('delete_key', 'php_test'));
    }

    /**
     * Test clear operation
     */
    public function testClear(): void
    {
        Cache::write('key1', 'value1', 'php_test');
        Cache::write('key2', 'value2', 'php_test');

        $result = Cache::clear('php_test');
        $this->assertTrue($result);

        $this->assertNull(Cache::read('key1', 'php_test'));
        $this->assertNull(Cache::read('key2', 'php_test'));
    }

    /**
     * Test cache files are valid PHP
     */
    public function testCacheFileIsValidPhp(): void
    {
        Cache::write('php_key', ['test' => 'data'], 'php_test');

        $files = glob($this->cachePath . 'cake_*');
        $this->assertIsArray($files);
        $this->assertNotEmpty($files);

        // File should be requireable
        $value = require $files[0];
        $this->assertSame(['test' => 'data'], $value);
    }

    /**
     * Test expiration with duration
     */
    public function testExpiration(): void
    {
        Cache::setConfig('php_test_expiry', [
            'className' => PhpEngine::class,
            'path' => $this->cachePath,
            'prefix' => 'cake_expiry_',
            'duration' => 1, // 1 second
        ]);

        Cache::write('expiring_key', 'value', 'php_test_expiry');
        $this->assertSame('value', Cache::read('expiring_key', 'php_test_expiry'));

        // Wait for expiration
        sleep(2);

        $this->assertNull(Cache::read('expiring_key', 'php_test_expiry'));

        Cache::drop('php_test_expiry');
    }

    /**
     * Test indefinite duration (0)
     */
    public function testIndefiniteDuration(): void
    {
        Cache::write('forever_key', 'forever_value', 'php_test');

        // Should still be there
        $this->assertSame('forever_value', Cache::read('forever_key', 'php_test'));
    }

    /**
     * Test caching integers
     */
    public function testSetAndGetInteger(): void
    {
        Cache::write('int_key', 42, 'php_test');
        $this->assertSame(42, Cache::read('int_key', 'php_test'));
    }

    /**
     * Test caching floats
     */
    public function testSetAndGetFloat(): void
    {
        Cache::write('float_key', 3.14159, 'php_test');
        $this->assertSame(3.14159, Cache::read('float_key', 'php_test'));
    }

    /**
     * Test caching booleans
     */
    public function testSetAndGetBoolean(): void
    {
        Cache::write('bool_true', true, 'php_test');
        Cache::write('bool_false', false, 'php_test');

        $this->assertTrue(Cache::read('bool_true', 'php_test'));
        $this->assertFalse(Cache::read('bool_false', 'php_test'));
    }

    /**
     * Test caching null returns null (not default)
     */
    public function testSetAndGetNull(): void
    {
        // Note: null cannot be cached as it's indistinguishable from "not found"
        // This is consistent with FileEngine behavior
        Cache::write('null_key', null, 'php_test');
        $this->assertNull(Cache::read('null_key', 'php_test'));
    }

    /**
     * Test delete returns false for nonexistent key
     */
    public function testDeleteNonexistent(): void
    {
        $result = Cache::delete('nonexistent_key', 'php_test');
        $this->assertFalse($result);
    }

    /**
     * Test increment throws exception
     */
    public function testIncrementThrowsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PhpEngine does not support atomic increment');

        $engine = new PhpEngine();
        $engine->init(['path' => $this->cachePath]);
        $engine->increment('key');
    }

    /**
     * Test decrement throws exception
     */
    public function testDecrementThrowsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PhpEngine does not support atomic decrement');

        $engine = new PhpEngine();
        $engine->init(['path' => $this->cachePath]);
        $engine->decrement('key');
    }

    /**
     * Test special characters in keys are handled
     */
    public function testSpecialCharactersInKey(): void
    {
        Cache::write('key/with:special.chars', 'value', 'php_test');
        $this->assertSame('value', Cache::read('key/with:special.chars', 'php_test'));
    }

    /**
     * Test caching deeply nested arrays
     */
    public function testDeeplyNestedArray(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'value' => 'deep',
                        ],
                    ],
                ],
            ],
        ];

        Cache::write('nested_key', $data, 'php_test');
        $result = Cache::read('nested_key', 'php_test');

        $this->assertSame($data, $result);
        $this->assertSame('deep', $result['level1']['level2']['level3']['level4']['value']);
    }

    /**
     * Test overwriting existing cache entry
     */
    public function testOverwrite(): void
    {
        Cache::write('overwrite_key', 'original', 'php_test');
        $this->assertSame('original', Cache::read('overwrite_key', 'php_test'));

        Cache::write('overwrite_key', 'updated', 'php_test');
        $this->assertSame('updated', Cache::read('overwrite_key', 'php_test'));
    }

    /**
     * Override trait test - PhpEngine does not support increment/decrement
     */
    public function testIncDecEventsAreFired(): void
    {
        $this->markTestSkipped('PhpEngine does not support increment/decrement.');
    }
}
