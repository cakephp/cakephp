<?php
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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Cache\Cache;
use Cake\Cache\CacheEngine;
use Cake\Datasource\ConnectionManager;
use Cake\Orm\OrmCache;
use Cake\TestSuite\TestCase;

/**
 * OrmCache test.
 */
class OrmCacheTest extends TestCase
{

    /**
     * Fixtures.
     *
     * @var array
     */
    public $fixtures = ['core.articles', 'core.tags'];

    /**
     * Cache Engine Mock
     *
     * @var \Cake\Cache\CacheEngine
     */
    public $cache;

    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->cache = $this->getMockBuilder(CacheEngine::class)->getMock();
        $this->cache->expects($this->any())
            ->method('init')
            ->will($this->returnValue(true));
        Cache::setConfig('orm_cache', $this->cache);

        $ds = ConnectionManager::get('test');
        $ds->cacheMetadata('orm_cache');
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('orm_cache');

        $ds = ConnectionManager::get('test');
        $ds->cacheMetadata(false);
    }

    /**
     * Test that clear enables the cache if it was disabled.
     *
     * @return void
     */
    public function testClearEnablesMetadataCache()
    {
        $ds = ConnectionManager::get('test');
        $ds->cacheMetadata(false);

        $ormCache = new OrmCache('test');
        $ormCache->clear();

        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $ds->schemaCollection());
    }

    /**
     * Test that build enables the cache if it was disabled.
     *
     * @return void
     */
    public function testBuildEnablesMetadataCache()
    {
        $ds = ConnectionManager::get('test');
        $ds->cacheMetadata(false);

        $ormCache = new OrmCache('test');
        $ormCache->build();

        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $ds->schemaCollection());
    }

    /**
     * Test build() with no args.
     *
     * @return void
     */
    public function testBuildNoArgs()
    {
        $this->cache->expects($this->at(3))
            ->method('write')
            ->with('test_articles');

        $ormCache = new OrmCache('test');
        $ormCache->build();
    }

    /**
     * Test build() with one arg.
     *
     * @return void
     */
    public function testBuildNamedModel()
    {
        $this->cache->expects($this->once())
            ->method('write')
            ->with('test_articles');
        $this->cache->expects($this->never())
            ->method('delete');

        $ormCache = new OrmCache('test');
        $ormCache->build('articles');
    }

    /**
     * Test build() overwrites cached data.
     *
     * @return void
     */
    public function testBuildOverwritesExistingData()
    {
        $this->cache->expects($this->once())
            ->method('write')
            ->with('test_articles');
        $this->cache->expects($this->never())
            ->method('read');
        $this->cache->expects($this->never())
            ->method('delete');

        $ormCache = new OrmCache('test');
        $ormCache->build('articles');
    }

    /**
     * Test getting an instance with an invalid connection name.
     *
     * @expectedException \Cake\Datasource\Exception\MissingDatasourceConfigException
     * @return void
     */
    public function testInvalidConnection()
    {
        new OrmCache('invalid-connection');
    }

    /**
     * Test clear() with no args.
     *
     * @return void
     */
    public function testClearNoArgs()
    {
        $this->cache->expects($this->at(3))
            ->method('delete')
            ->with('test_articles');

        $ormCache = new OrmCache('test');
        $ormCache->clear();
    }

    /**
     * Test clear() with a model name.
     *
     * @return void
     */
    public function testClearNamedModel()
    {
        $this->cache->expects($this->never())
            ->method('write');
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('test_articles');

        $ormCache = new OrmCache('test');
        $ormCache->clear('articles');
    }
}
