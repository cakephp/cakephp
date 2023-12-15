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
use Cake\Database\DriverFeatureEnum;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Mockery;
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
            ->onlyMethods(['createPdo'])
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
            'log' => false,
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $driver->expects($this->once())->method('createPdo')
            ->with($dsn, $expected);
        $driver->connect();
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
            ->onlyMethods(['createPdo'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'sqlite:bar.db';

        $expected = $config;
        $expected += ['username' => null, 'password' => null, 'cache' => null, 'mode' => null, 'log' => false];
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $connection = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->onlyMethods(['exec'])
            ->getMock();
        $connection->expects($this->exactly(2))
            ->method('exec')
            ->with(
                ...self::withConsecutive(['Execute this'], ['this too'])
            );

        $driver->expects($this->once())->method('createPdo')
            ->with($dsn, $expected)
            ->willReturn($connection);

        $driver->connect();
    }

    /**
     * Tests creating multiple connections to same db.
     */
    public function testConnectionSharedCached()
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        ConnectionManager::setConfig('test_shared_cache', [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => ':memory:',
            'cache' => 'shared',
        ]);

        $connection = ConnectionManager::get('test_shared_cache');
        $this->assertSame([], $connection->getSchemaCollection()->listTables());

        $connection->execute('CREATE TABLE test (test int);');
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
        $mock = Mockery::mock(PDO::class)
            ->shouldAllowMockingMethod('quoteIdentifier')
            ->makePartial();
        $mock->shouldReceive('quote')
            ->andReturnUsing(function ($value) {
                return '"' . $value . '"';
            });

        $driver = $this->getMockBuilder(Sqlite::class)
            ->onlyMethods(['createPdo'])
            ->getMock();

        $driver->expects($this->any())
            ->method('createPdo')
            ->willReturn($mock);

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
        foreach ($featureVersions as $feature => $version) {
            $this->assertSame(
                version_compare($driver->version(), $version, '>='),
                $driver->supports(DriverFeatureEnum::from($feature))
            );
        }

        $this->assertTrue($driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION));
        $this->assertTrue($driver->supports(DriverFeatureEnum::SAVEPOINT));
        $this->assertTrue($driver->supports(DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS));

        $this->assertFalse($driver->supports(DriverFeatureEnum::JSON));
    }

    /**
     * Tests identifier quoting
     */
    public function testQuoteIdentifier(): void
    {
        $driver = new Sqlite();

        $result = $driver->quoteIdentifier('name');
        $expected = '"name"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.*');
        $expected = '"Model".*';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.No_ 2');
        $expected = '"Items"."No_ 2"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.No_ 2 thing');
        $expected = '"Items"."No_ 2 thing"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.No_ 2 thing AS thing');
        $expected = '"Items"."No_ 2 thing" AS "thing"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Items.Item Category Code = :c1');
        $expected = '"Items"."Item Category Code" = :c1';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('MTD()');
        $expected = 'MTD()';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('(sm)');
        $expected = '(sm)';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('name AS x');
        $expected = '"name" AS "x"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.name AS x');
        $expected = '"Model"."name" AS "x"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Function(Something.foo)');
        $expected = 'Function("Something"."foo")';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Function(SubFunction(Something.foo))');
        $expected = 'Function(SubFunction("Something"."foo"))';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Function(Something.foo) AS x');
        $expected = 'Function("Something"."foo") AS "x"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('name-with-minus');
        $expected = '"name-with-minus"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('my-name');
        $expected = '"my-name"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Foo-Model.*');
        $expected = '"Foo-Model".*';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Team.P%');
        $expected = '"Team"."P%"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Team.G/G');
        $expected = '"Team"."G/G"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.name as y');
        $expected = '"Model"."name" AS "y"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('nämé');
        $expected = '"nämé"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('aßa.nämé');
        $expected = '"aßa"."nämé"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('aßa.*');
        $expected = '"aßa".*';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Modeß.nämé as y');
        $expected = '"Modeß"."nämé" AS "y"';
        $this->assertEquals($expected, $result);

        $result = $driver->quoteIdentifier('Model.näme Datum as y');
        $expected = '"Model"."näme Datum" AS "y"';
        $this->assertEquals($expected, $result);
    }
}
