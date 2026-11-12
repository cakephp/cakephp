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
use Mockery;
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
        $driver = Mockery::mock($driverClass)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $driver->shouldReceive('enabled')->andReturn(true);
        $driver->shouldReceive('newCompiler')->andReturn(new QueryCompiler());
        $driver->shouldReceive('version')->andReturn('8.0.0');
        $driver->shouldReceive('getRole')->andReturn('write');
        $driver->shouldReceive('supports')->andReturn(true);
        $driver->shouldReceive('isAutoQuotingEnabled')->andReturn(false);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('role')->andReturn('write');
        $connection->shouldReceive('configName')->andReturn('test');
        $connection->shouldReceive('getDriver')->andReturn($driver);
        $connection->shouldReceive('selectQuery')->andReturnUsing(function () use ($connection) {
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
