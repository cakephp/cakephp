<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\QueryTests;

use Cake\Database\Connection;
use Cake\Database\Driver\Mariadb;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\JsonPathExpression;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests FunctionExpression queries
 */
class FunctionsQueryTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    /**
     * Skip queries unsupported by the current database type or server version.
     *
     * @param array<class-string<\Cake\Database\Driver>, string> $versions Minimum versions by database type.
     */
    protected function requireVersion(array $versions): void
    {
        $driver = $this->connection->getDriver();
        $driverVersion = $driver->version();

        $driverClass = $driver::class;
        if ($driver instanceof Mysql && str_contains($driverVersion, 'MariaDB')) {
            $driverClass = Mariadb::class;
        }

        $version = $versions[$driverClass] ?? null;
        if ($version === null) {
            $this->skipIf(true, 'Driver does not support the SQL function or behavior.');

            return;
        }

        $this->skipIf(
            version_compare($driverVersion, $version, '<'),
            sprintf('Driver does not support the SQL function or behavior. Requires version %s.', $version),
        );
    }

    /**
     * @return array<string, array{string, string, mixed, string}>
     */
    public static function jsonValueProvider(): array
    {
        return [
            'string' => ['{"a":"hello"}', '$.a', 'hello', 'string'],
            'integer' => ['{"a":42}', '$.a', 42, 'integer'],
            'float' => ['{"a":1.5}', '$.a', 1.5, 'float'],
            'true' => ['{"a":true}', '$.a', true, 'boolean'],
            'false' => ['{"a":false}', '$.a', false, 'boolean'],
            'null' => ['{"a":null}', '$.a', null, 'string'],
        ];
    }

    #[DataProvider('jsonValueProvider')]
    public function testJsonValue(string $json, string $path, mixed $expected, string $returnType): void
    {
        $this->requireVersion([
            Mariadb::class => '10.2.3',
            Mysql::class => '8.0.21',
            Postgres::class => '17',
            Sqlserver::class => '13',
            Sqlite::class => '3.19',
        ]);

        // Test with $returnType parameter
        $query = new SelectQuery($this->connection);
        $expression = $query->func()->jsonValue($this->quoteString($json), $path, returnType: $returnType);
        $this->assertSame($returnType, $expression->getReturnType());

        $result = $query
            ->select(['value' => $expression])
            ->setSelectTypeMap(['value' => $expression->getReturnType()])
            ->execute()->fetchAll('assoc');

        $this->assertSame($expected, $result[0]['value']);

        // Test with setReturnType on path expression
        $query = new SelectQuery($this->connection);
        $path = (new JsonPathExpression($path))->setReturnType($returnType);
        $expression = $query->func()->jsonValue($this->quoteString($json), $path);
        $this->assertSame($returnType, $expression->getReturnType());

        $result = $query
            ->select(['value' => $expression])
            ->setSelectTypeMap(['value' => $expression->getReturnType()])
            ->execute()->fetchAll('assoc');

        $this->assertSame($expected, $result[0]['value']);
    }

    public function testJsonValuePassing(): void
    {
        $this->requireVersion([
            Postgres::class => '17',
        ]);

        // Bound parameters are typed as text by Postgres, so only string values compare correctly
        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonValue(
                    $this->quoteString(json_encode(['a' => 'x'])),
                    new JsonPathExpression('$.a ? (@ == $v)')->passing(['v' => 'x']),
                ),
            ])
            ->execute()->fetchAll('assoc');
        $this->assertSame('x', $result[0]['value']);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonValue(
                    $this->quoteString(json_encode(['a' => 'x'])),
                    new JsonPathExpression('$.a ? (@ == $v)')->passing(['v' => 'y']),
                ),
            ])
            ->execute()->fetchAll('assoc');
        $this->assertNull($result[0]['value']);
    }

    public function testJsonValueReturning(): void
    {
        $this->requireVersion([
            Mysql::class => '8.0.21',
            Postgres::class => '17',
            Sqlserver::class => '17',
        ]);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonValue(
                    $this->quoteString(json_encode(['a' => 1])),
                    new JsonPathExpression('$.a')->returning('float'),
                ),
            ])
            ->setSelectTypeMap(['value' => 'float'])
            ->execute()->fetchAll('assoc');

        $this->assertSame(1.0, $result[0]['value']);
    }

    public function testJsonValueOnEmpty(): void
    {
        $this->requireVersion([
            Mysql::class => '8.0.21',
            Postgres::class => '17',
        ]);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonValue(
                    $this->quoteString(json_encode(['a' => 1])),
                    new JsonPathExpression('$.b')->onEmpty('DEFAULT', 5),
                ),
            ])
            ->execute()->fetchAll('assoc');

        $this->assertSame('5', $result[0]['value']);
    }

    public function testJsonValueOnError(): void
    {
        $this->requireVersion([
            Mysql::class => '8.0.21',
            Postgres::class => '17',
        ]);

        // MySQL returns non-scalars as text, so force a conversion error instead
        $returning = $this->connection->getDriver() instanceof Mysql ? 'signed' : 'integer';

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonValue(
                    $this->quoteString(json_encode(['a' => 'abc'])),
                    new JsonPathExpression('$.a')->returning($returning)->onError('DEFAULT', 7),
                ),
            ])
            ->setSelectTypeMap(['value' => 'integer'])
            ->execute()->fetchAll('assoc');

        $this->assertSame(7, $result[0]['value']);
    }

    public function testJsonExists(): void
    {
        $this->requireVersion([
            Mariadb::class => '10.2',
            Mysql::class => '5.7',
            Postgres::class => '17',
            Sqlserver::class => '16',
        ]);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonExists(
                    $this->quoteString(json_encode(['a' => 1])),
                    new JsonPathExpression('$.a'),
                ),
            ])
            ->setSelectTypeMap(['value' => 'boolean'])
            ->execute()->fetchAll('assoc');
        $this->assertSame(true, $result[0]['value']);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonExists(
                    $this->quoteString(json_encode(['a' => 1])),
                    new JsonPathExpression('$.b'),
                ),
            ])
            ->setSelectTypeMap(['value' => 'boolean'])
            ->execute()->fetchAll('assoc');
        $this->assertSame(false, $result[0]['value']);
    }

    public function testJsonExistsOnError(): void
    {
        $this->requireVersion([
            Postgres::class => '17',
        ]);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonExists(
                    $this->quoteString(json_encode(['a' => 1])),
                    (new JsonPathExpression('strict $.b'))->onError(JsonPathExpression::BEHAVIOR_TRUE),
                ),
            ])
            ->setSelectTypeMap(['value' => 'boolean'])
            ->execute()->fetchAll('assoc');
        $this->assertSame(true, $result[0]['value']);
    }

    private function quoteString(string $value): string
    {
        return sprintf("'%s'", $value);
    }
}
