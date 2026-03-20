<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database;

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query\SelectQuery;
use Cake\Database\QueryCompiler;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NullSafeComparisonTest extends TestCase
{
    public static function dialectProvider(): array
    {
        return [
            'PostgreSQL' => [Postgres::class, 'col IS DISTINCT FROM :c0', 'col IS NOT DISTINCT FROM :c0'],
            'SQLite' => [Sqlite::class, 'col IS DISTINCT FROM :c0', 'col IS NOT DISTINCT FROM :c0'],
            'SQLServer' => [Sqlserver::class, 'col IS DISTINCT FROM :c0', 'col IS NOT DISTINCT FROM :c0'],
            'MySQL' => [Mysql::class, 'NOT (col <=> :c0)', 'col <=> :c0'],
        ];
    }

    #[DataProvider('dialectProvider')]
    public function testNullSafeComparisonSql(string $driverClass, string $expectedDistinct, string $expectedNotDistinct): void
    {
        $driver = $this->getMockBuilder($driverClass)
            ->disableOriginalConstructor()
            ->onlyMethods(['connect', 'enabled', 'newCompiler', 'version', 'getRole', 'supports', 'isAutoQuotingEnabled'])
            ->getMock();
        $driver->method('enabled')->willReturn(true);
        $driver->method('newCompiler')->willReturn(new QueryCompiler());
        $driver->method('version')->willReturn('8.0.0');
        $driver->method('getRole')->willReturn('write');
        $driver->method('supports')->willReturn(true);
        $driver->method('isAutoQuotingEnabled')->willReturn(false);

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDriver', 'selectQuery', 'role', 'configName'])
            ->getMock();
        $connection->method('role')->willReturn('write');
        $connection->method('configName')->willReturn('test');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('selectQuery')->willReturnCallback(function () use ($connection) {
            return new SelectQuery($connection);
        });

        $binder = new ValueBinder();

        // IS DISTINCT FROM
        $expr = new QueryExpression(['col IS DISTINCT FROM' => 'val']);
        if ($driver instanceof Mysql) {
            $query = $connection->selectQuery();
            $query->where($expr);
            $sql = $driver->compileQuery($query, $binder);
            $this->assertStringContainsString($expectedDistinct, $sql);
        } else {
            $this->assertEqualsSql($expectedDistinct, $expr->sql($binder));
        }

        $binder = new ValueBinder();
        // IS NOT DISTINCT FROM
        $expr = new QueryExpression(['col IS NOT DISTINCT FROM' => 'val']);
        if ($driver instanceof Mysql) {
            $query = $connection->selectQuery();
            $query->where($expr);
            $sql = $driver->compileQuery($query, $binder);
            $this->assertStringContainsString($expectedNotDistinct, $sql);
        } else {
            $this->assertEqualsSql($expectedNotDistinct, $expr->sql($binder));
        }
    }
}
