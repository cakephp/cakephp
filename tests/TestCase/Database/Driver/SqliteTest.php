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
namespace Cake\Test\TestCase\Database\Driver;

use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Database\DriverInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Tests Sqlite driver
 */
class SqliteTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        ConnectionManager::drop('test_shared_cache');
        ConnectionManager::drop('test_shared_cache2');
    }

    /**
     * Test connecting to Sqlite with default configuration
     */
    public function testConnectionConfigDefault(): void
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlite')
            ->onlyMethods(['_connect'])
            ->getMock();
        $dsn = 'sqlite::memory:';
        $expected = [
            'persistent' => false,
            'database' => ':memory:',
            'encoding' => 'utf8',
            'cache' => null,
            'mode' => null,
            'username' => null,
            'password' => null,
            'flags' => [],
            'init' => [],
            'mask' => 420,
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->connect([]);
    }

    /**
     * Test connecting to Sqlite with custom configuration
     */
    public function testConnectionConfigCustom(): void
    {
        $config = [
            'persistent' => true,
            'host' => 'foo',
            'database' => 'bar.db',
            'flags' => [1 => true, 2 => false],
            'encoding' => 'a-language',
            'init' => ['Execute this', 'this too'],
            'mask' => 0666,
        ];
        $driver = $this->getMockBuilder('Cake\Database\driver\Sqlite')
            ->onlyMethods(['_connect', 'getConnection'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'sqlite:bar.db';

        $expected = $config;
        $expected += ['username' => null, 'password' => null, 'cache' => null, 'mode' => null];
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $connection = $this->getMockBuilder('StdClass')
            ->addMethods(['exec'])
            ->getMock();
        $connection->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(['Execute this'], ['this too']);

        $driver->expects($this->once())->method('_connect')
            ->with($dsn, $expected);
        $driver->expects($this->any())->method('getConnection')
            ->will($this->returnValue($connection));
        $driver->connect($config);
    }

    /**
     * Tests creating multiple connections to same db.
     */
    public function testConnectionSharedCached()
    {
        $this->skipIf(PHP_VERSION_ID < 80100 || !extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        ConnectionManager::setConfig('test_shared_cache', [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => ':memory:',
            'cache' => 'shared',
        ]);

        $connection = ConnectionManager::get('test_shared_cache');
        $this->assertSame([], $connection->getSchemaCollection()->listTables());

        $connection->query('CREATE TABLE test (test int);');
        $this->assertSame(['test'], $connection->getSchemaCollection()->listTables());

        ConnectionManager::setConfig('test_shared_cache2', [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => ':memory:',
            'cache' => 'shared',
        ]);
        $connection = ConnectionManager::get('test_shared_cache2');
        $this->assertSame(['test'], $connection->getSchemaCollection()->listTables());
        $this->assertFileDoesNotExist('file::memory:?cache=shared');
    }

    /**
     * Data provider for schemaValue()
     *
     * @return array
     */
    public static function schemaValueProvider(): array
    {
        return [
            [null, 'NULL'],
            [false, 'FALSE'],
            [true, 'TRUE'],
            [3.14159, '3.14159'],
            ['33', '33'],
            [66, 66],
            [0, 0],
            [10e5, '1000000'],
            ['farts', '"farts"'],
        ];
    }

    /**
     * Test the schemaValue method on Driver.
     *
     * @dataProvider schemaValueProvider
     * @param mixed $input
     * @param mixed $expected
     */
    public function testSchemaValue($input, $expected): void
    {
        $driver = new Sqlite();
        $mock = $this->getMockBuilder(PDO::class)
            ->onlyMethods(['quote'])
            ->addMethods(['quoteIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($value) {
                return '"' . $value . '"';
            }));
        $driver->setConnection($mock);
        $this->assertEquals($expected, $driver->schemaValue($input));
    }

    /**
     * Tests driver-specific feature support check.
     */
    public function testSupports(): void
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(!$driver instanceof Sqlite);

        $featureVersions = [
            'cte' => '3.8.3',
            'window' => '3.28.0',
        ];

        $this->assertSame(
            version_compare($driver->version(), $featureVersions['cte'], '>='),
            $driver->supports(DriverInterface::FEATURE_CTE)
        );
        $this->assertSame(
            version_compare($driver->version(), $featureVersions['window'], '>='),
            $driver->supports(DriverInterface::FEATURE_WINDOW)
        );
        $this->assertFalse($driver->supports(DriverInterface::FEATURE_JSON));
        $this->assertTrue($driver->supports(DriverInterface::FEATURE_SAVEPOINT));
        $this->assertTrue($driver->supports(DriverInterface::FEATURE_QUOTE));

        $this->assertFalse($driver->supports('this-is-fake'));
    }

    /**
     * Tests driver-specific feature support check.
     */
    public function testDeprecatedSupports(): void
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(!$driver instanceof Sqlite);

        $this->deprecated(function () use ($driver) {
            $this->assertSame($driver->supportsCTEs(), $driver->supports(DriverInterface::FEATURE_CTE));
            $this->assertSame($driver->supportsWindowFunctions(), $driver->supports(DriverInterface::FEATURE_WINDOW));
        });
    }
}
