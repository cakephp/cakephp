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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Cache\Cache;
use Cake\Core\Exception\CakeException;
use Cake\Datasource\QueryCacher;
use Cake\TestSuite\TestCase;
use stdClass;

/**
 * Query cacher test
 */
class QueryCacherTest extends TestCase
{
    /**
     * @var \Cake\Cache\CacheEngineInterface
     */
    protected $engine;

    /**
     * Setup method
     */
    public function setUp(): void
    {
        parent::setUp();
        Cache::setConfig('queryCache', ['className' => 'Array']);
        $this->engine = Cache::pool('queryCache');
        Cache::enable();
    }

    /**
     * Teardown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('queryCache');
    }

    /**
     * Test fetching with a function to generate the key.
     */
    public function testFetchFunctionKey(): void
    {
        $this->engine->set('my_key', 'A winner');
        $query = new stdClass();

        $cacher = new QueryCacher(function ($q) use ($query) {
            $this->assertSame($query, $q);

            return 'my_key';
        }, 'queryCache');

        $result = $cacher->fetch($query);
        $this->assertSame('A winner', $result);
    }

    /**
     * Test fetching with a function to generate the key but the function is poop.
     */
    public function testFetchFunctionKeyNoString(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Cache key functions must return a string. Got false.');
        $this->engine->set('my_key', 'A winner');
        $query = new stdClass();

        $cacher = new QueryCacher(function ($q) {
            return false;
        }, 'queryCache');

        $cacher->fetch($query);
    }

    /**
     * Test fetching with a cache instance.
     */
    public function testFetchCacheHitStringEngine(): void
    {
        $this->engine->set('my_key', 'A winner');
        $cacher = new QueryCacher('my_key', 'queryCache');
        $query = new stdClass();
        $result = $cacher->fetch($query);
        $this->assertSame('A winner', $result);
    }

    /**
     * Test fetching with a cache hit.
     */
    public function testFetchCacheHit(): void
    {
        $this->engine->set('my_key', 'A winner');
        $cacher = new QueryCacher('my_key', $this->engine);
        $query = new stdClass();
        $result = $cacher->fetch($query);
        $this->assertSame('A winner', $result);
    }

    /**
     * Test fetching with a cache miss.
     */
    public function testFetchCacheMiss(): void
    {
        $this->engine->set('my_key', false);
        $cacher = new QueryCacher('my_key', $this->engine);
        $query = new stdClass();
        $result = $cacher->fetch($query);
        $this->assertNull($result, 'Cache miss should not have an isset() return.');
    }
}
