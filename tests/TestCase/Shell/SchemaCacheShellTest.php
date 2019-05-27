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
namespace Cake\Test\TestCase\Shell;

use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * SchemaCacheShell test.
 */
class SchemaCacheShellTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures.
     *
     * @var array
     */
    public $fixtures = ['core.Articles', 'core.Tags'];

    /**
     * setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->useCommandRunner();

        $this->cache = $this->getMockBuilder('Cake\Cache\CacheEngine')->getMock();
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
    public function tearDown(): void
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

        $this->exec('schema_cache clear --connection test');
        $this->assertExitSuccess();
        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $ds->getSchemaCollection());
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

        $this->exec('schema_cache build --connection test');
        $this->assertExitSuccess();
        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $ds->getSchemaCollection());
    }

    /**
     * Test build() with no args.
     *
     * @return void
     */
    public function testBuildNoArgs()
    {
        $this->cache->expects($this->any())
            ->method('set')
            ->will($this->returnValue(true));
        $this->cache->expects($this->at(3))
            ->method('set')
            ->with('test_articles')
            ->will($this->returnValue(true));

        $this->exec('schema_cache build --connection test');
        $this->assertExitSuccess();
    }

    /**
     * Test build() with one arg.
     *
     * @return void
     */
    public function testBuildNamedModel()
    {
        $this->cache->expects($this->once())
            ->method('set')
            ->with('test_articles')
            ->will($this->returnValue(true));
        $this->cache->expects($this->never())
            ->method('delete')
            ->will($this->returnValue(false));

        $this->exec('schema_cache build --connection test articles');
        $this->assertExitSuccess();
    }

    /**
     * Test build() overwrites cached data.
     *
     * @return void
     */
    public function testBuildOverwritesExistingData()
    {
        $this->cache->expects($this->once())
            ->method('set')
            ->with('test_articles')
            ->will($this->returnValue(true));
        $this->cache->expects($this->never())
            ->method('get');
        $this->cache->expects($this->never())
            ->method('delete')
            ->will($this->returnValue(false));

        $this->exec('schema_cache build --connection test articles');
        $this->assertExitSuccess();
    }

    /**
     * Test build() with a non-existing connection name.
     *
     * @return void
     */
    public function testBuildInvalidConnection()
    {
        $this->exec('schema_cache build --connection derpy-derp articles');
        $this->assertExitError();
    }

    /**
     * Test clear() with an invalid connection name.
     *
     * @return void
     */
    public function testClearInvalidConnection()
    {
        $this->exec('schema_cache clear --connection derpy-derp articles');
        $this->assertExitError();
    }

    /**
     * Test clear() with no args.
     *
     * @return void
     */
    public function testClearNoArgs()
    {
        $this->cache->method('delete')
            ->will($this->returnValue(true));
        $this->cache->expects($this->at(3))
            ->method('delete')
            ->with('test_articles');

        $this->exec('schema_cache clear --connection test');
        $this->assertExitSuccess();
    }

    /**
     * Test clear() with a model name.
     *
     * @return void
     */
    public function testClearNamedModel()
    {
        $this->cache->expects($this->never())
            ->method('set')
            ->will($this->returnValue(true));
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('test_articles')
            ->will($this->returnValue(false));

        $this->exec('schema_cache clear --connection test articles');
        $this->assertExitSuccess();
    }
}
