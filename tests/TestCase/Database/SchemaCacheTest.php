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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Cache\Cache;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\SchemaCache;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * SchemaCache test.
 */
class SchemaCacheTest extends TestCase
{
    /**
     * Fixtures.
     *
     * @var array
     */
    protected $fixtures = ['core.Articles', 'core.Tags'];

    /**
     * Cache Engine Mock
     *
     * @var \Cake\Cache\CacheEngine
     */
    protected $cache;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Cache::setConfig('orm_cache', ['className' => 'Array']);
        $this->cache = Cache::pool('orm_cache');

        $this->connection = ConnectionManager::get('test');
        $this->connection->cacheMetadata('orm_cache');
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $this->connection->cacheMetadata(false);
        unset($this->connection);
        Cache::drop('orm_cache');
    }

    /**
     * Test that clear enables the cache if it was disabled.
     *
     * @return void
     */
    public function testClearEnablesMetadataCache()
    {
        $this->connection->cacheMetadata(false);

        $ormCache = new SchemaCache($this->connection);
        $ormCache->clear();

        $this->assertInstanceOf(CachedCollection::class, $this->connection->getSchemaCollection());
    }

    /**
     * Test that build enables the cache if it was disabled.
     *
     * @return void
     */
    public function testBuildEnablesMetadataCache()
    {
        $this->connection->cacheMetadata(false);

        $ormCache = new SchemaCache($this->connection);
        $ormCache->build();

        $this->assertInstanceOf(CachedCollection::class, $this->connection->getSchemaCollection());
    }

    /**
     * Test build() with no args.
     *
     * @return void
     */
    public function testBuildNoArgs()
    {
        $ormCache = new SchemaCache($this->connection);
        $ormCache->build();

        $this->assertNotEmpty($this->cache->get('test_articles'));
    }

    /**
     * Test build() with one arg.
     *
     * @return void
     */
    public function testBuildNamedModel()
    {
        $ormCache = new SchemaCache($this->connection);
        $ormCache->build('articles');

        $this->assertNotEmpty($this->cache->get('test_articles'));
    }

    /**
     * Test build() overwrites cached data.
     *
     * @return void
     */
    public function testBuildOverwritesExistingData()
    {
        $this->cache->set('test_articles', 'dummy data');

        $ormCache = new SchemaCache($this->connection);
        $ormCache->build('articles');

        $this->assertNotSame('dummy data', $this->cache->get('test_articles'));
    }

    /**
     * Test clear() with no args.
     *
     * @return void
     */
    public function testClearNoArgs()
    {
        $this->cache->set('test_articles', 'dummy data');

        $ormCache = new SchemaCache($this->connection);
        $ormCache->clear();
        $this->assertFalse($this->cache->has('test_articles'));
    }

    /**
     * Test clear() with a model name.
     *
     * @return void
     */
    public function testClearNamedModel()
    {
        $this->cache->set('test_articles', 'dummy data');

        $ormCache = new SchemaCache($this->connection);
        $ormCache->clear('articles');
        $this->assertFalse($this->cache->has('test_articles'));
    }

    /**
     * Tests getting a schema config from a connection instance
     *
     * @return void
     */
    public function testGetSchemaWithConnectionInstance()
    {
        $ormCache = new SchemaCache($this->connection);
        $result = $ormCache->getSchema($this->connection);

        $this->assertInstanceOf(CachedCollection::class, $result);
    }
}
