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
namespace Cake\Test\TestCase\Command;

use Cake\Cache\Cache;
use Cake\Cache\Engine\NullEngine;
use Cake\Console\ConsoleIo;
use Cake\Database\SchemaCache;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * SchemaCacheCommands test.
 */
class SchemaCacheCommandsTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures.
     *
     * @var array
     */
    protected $fixtures = ['core.Articles', 'core.Tags'];

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Cake\Cache\Engine\NullEngine|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

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

        $this->cache = $this->getMockBuilder(NullEngine::class)
            ->onlyMethods(['set', 'get', 'delete'])
            ->getMock();
        Cache::setConfig('orm_cache', $this->cache);

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

        $this->connection->cacheMetadata('_cake_model_');
        unset($this->connection);
        Cache::drop('orm_cache');
    }

    protected function getShell()
    {
        $io = $this->getMockBuilder(ConsoleIo::class)->getMock();
        $shell = $this->getMockBuilder(SchemaCacheShell::class)
            ->setConstructorArgs([$io])
            ->onlyMethods(['_getSchemaCache'])
            ->getMock();

        $schemaCache = new SchemaCache($this->connection);
        $shell->expects($this->once())
            ->method('_getSchemaCache')
            ->willReturn($schemaCache);

        return $shell;
    }

    /**
     * Test that clear enables the cache if it was disabled.
     *
     * @return void
     */
    public function testClearEnablesMetadataCache()
    {
        $this->connection->cacheMetadata(false);

        $this->exec('schema_cache clear --connection test');
        $this->assertExitSuccess();
        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $this->connection->getSchemaCollection());
    }

    /**
     * Test that build enables the cache if it was disabled.
     *
     * @return void
     */
    public function testBuildEnablesMetadataCache()
    {
        $this->connection->cacheMetadata(false);

        $this->exec('schema_cache build --connection test');
        $this->assertExitSuccess();
        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $this->connection->getSchemaCollection());
    }

    /**
     * Test build() with no args.
     *
     * @return void
     */
    public function testBuildNoArgs()
    {
        $this->cache->expects($this->atLeastOnce())
            ->method('set')
            ->withConsecutive(['test_articles'])
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
     * Test build() with a nonexistent connection name.
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
        $this->cache->expects($this->atLeastOnce())
            ->method('delete')
            ->withConsecutive(['test_articles'])
            ->will($this->returnValue(true));

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
