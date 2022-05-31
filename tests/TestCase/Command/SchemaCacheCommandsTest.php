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
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\ConnectionManager;
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
     * @var array<string>
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
     */
    public function tearDown(): void
    {
        $this->connection->cacheMetadata(false);
        parent::tearDown();

        unset($this->connection);
        Cache::drop('orm_cache');
    }

    /**
     * Test that clear enables the cache if it was disabled.
     */
    public function testClearEnablesMetadataCache(): void
    {
        $this->connection->cacheMetadata(false);

        $this->exec('schema_cache clear --connection test');
        $this->assertExitSuccess();
        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $this->connection->getSchemaCollection());
    }

    /**
     * Test that build enables the cache if it was disabled.
     */
    public function testBuildEnablesMetadataCache(): void
    {
        $this->connection->cacheMetadata(false);

        $this->exec('schema_cache build --connection test');
        $this->assertExitSuccess();
        $this->assertInstanceOf('Cake\Database\Schema\CachedCollection', $this->connection->getSchemaCollection());
    }

    /**
     * Test build() with no args.
     */
    public function testBuildNoArgs(): void
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
     */
    public function testBuildNamedModel(): void
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
     */
    public function testBuildOverwritesExistingData(): void
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
     */
    public function testBuildInvalidConnection(): void
    {
        $this->exec('schema_cache build --connection derpy-derp articles');
        $this->assertExitError();
    }

    /**
     * Test clear() with an invalid connection name.
     */
    public function testClearInvalidConnection(): void
    {
        $this->exec('schema_cache clear --connection derpy-derp articles');
        $this->assertExitError();
    }

    /**
     * Test clear() with no args.
     */
    public function testClearNoArgs(): void
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
     */
    public function testClearNamedModel(): void
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
