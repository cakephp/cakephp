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
use Cake\Database\Driver\Postgres;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Tests Postgres driver
 */
class PostgresTest extends TestCase
{
    /**
     * Test connecting to Postgres with default configuration
     */
    public function testConnectionConfigDefault(): void
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->onlyMethods(['createPdo'])
            ->getMock();
        $dsn = 'pgsql:host=localhost;port=5432;dbname=cake';
        $expected = [
            'persistent' => true,
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'cake',
            'schema' => 'public',
            'port' => 5432,
            'encoding' => 'utf8',
            'timezone' => null,
            'flags' => [],
            'init' => [],
            'log' => false,
        ];

        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $connection = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->onlyMethods(['exec', 'quote'])
            ->getMock();
        $connection->expects($this->any())
            ->method('quote')
            ->willReturnArgument(0);

        $connection->expects($this->exactly(2))
            ->method('exec')
            ->with(
                ...self::withConsecutive(
                    ['SET NAMES utf8'],
                    ['SET search_path TO public']
                )
            );

        $driver->expects($this->once())->method('createPdo')
            ->with($dsn, $expected)
            ->willReturn($connection);

        $driver->connect();
    }

    /**
     * Test connecting to Postgres with custom configuration
     */
    public function testConnectionConfigCustom(): void
    {
        $config = [
            'persistent' => false,
            'host' => 'foo',
            'database' => 'bar',
            'username' => 'user',
            'password' => 'pass',
            'port' => 3440,
            'flags' => [1 => true, 2 => false],
            'encoding' => 'a-language',
            'timezone' => 'Antarctica',
            'schema' => 'fooblic',
            'init' => ['Execute this', 'this too'],
            'log' => false,
        ];
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->onlyMethods(['createPdo'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'pgsql:host=foo;port=3440;dbname=bar';

        $expected = $config;
        $expected['flags'] += [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $connection = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->onlyMethods(['exec', 'quote'])
            ->getMock();
        $connection->expects($this->any())
            ->method('quote')
            ->willReturnArgument(0);

        $connection->expects($this->exactly(5))
            ->method('exec')
            ->with(
                ...self::withConsecutive(
                    ['SET NAMES a-language'],
                    ['SET search_path TO fooblic'],
                    ['Execute this'],
                    ['this too'],
                    ['SET timezone = Antarctica']
                )
            );

        $driver->expects($this->once())->method('createPdo')
            ->with($dsn, $expected)
            ->willReturn($connection);

        $driver->connect();
    }

    /**
     * Tests that insert queries get a "RETURNING *" string at the end
     */
    public function testInsertReturning(): void
    {
        $driver = $this->getMockBuilder(Postgres::class)
            ->onlyMethods(['createPdo', 'getPdo', 'connect', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->method('enabled')->willReturn(true);
        $connection = new Connection(['driver' => $driver, 'log' => false]);

        $query = $connection->insertQuery('articles', ['id' => 1, 'title' => 'foo']);
        $this->assertStringEndsWith(' RETURNING *', $query->sql());

        $query = $connection->insertQuery('articles', ['id' => 1, 'title' => 'foo']);
        $query->epilog('FOO');
        $this->assertStringEndsWith(' FOO', $query->sql());
    }

    /**
     * Test that having queries replace the aggregated alias field.
     */
    public function testHavingReplacesAlias(): void
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->onlyMethods(['connect', 'getPdo', 'version', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->method('enabled')
            ->willReturn(true);

        $connection = new Connection(['driver' => $driver, 'log' => false]);

        $query = new SelectQuery($connection);
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy(['posts.author_id'])
            ->having([$query->newExpr()->gte('post_count', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS "post_count" ' .
            'GROUP BY posts.author_id HAVING COUNT(posts.id) >= :c0';
        $this->assertSame($expected, $query->sql());
    }

    /**
     * Test that having queries replaces nothing if no alias is used.
     */
    public function testHavingWhenNoAliasIsUsed(): void
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')
            ->onlyMethods(['connect', 'getPdo', 'version', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->method('enabled')
            ->willReturn(true);

        $connection = new Connection(['driver' => $driver, 'log' => false]);

        $query = new SelectQuery($connection);
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy(['posts.author_id'])
            ->having([$query->newExpr()->gte('posts.author_id', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS "post_count" ' .
            'GROUP BY posts.author_id HAVING posts.author_id >= :c0';
        $this->assertSame($expected, $query->sql());
    }

    /**
     * Tests driver-specific feature support check.
     */
    public function testSupports(): void
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(!$driver instanceof Postgres);

        $this->assertTrue($driver->supports(DriverFeatureEnum::CTE));
        $this->assertTrue($driver->supports(DriverFeatureEnum::JSON));
        $this->assertTrue($driver->supports(DriverFeatureEnum::SAVEPOINT));
        $this->assertTrue($driver->supports(DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS));
        $this->assertTrue($driver->supports(DriverFeatureEnum::WINDOW));

        $this->assertFalse($driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION));
    }
}
