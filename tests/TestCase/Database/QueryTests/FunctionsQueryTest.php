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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\QueryTests;

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Expression\JsonPathExpression;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests FunctionExpression queries
 */
class FunctionsQueryTest extends TestCase
{
    protected array $fixtures = [
        'core.Comments',
    ];

    /**
     * @var \Cake\Database\Connection
     */
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    protected function requireVersion(array $versions): void
    {
        $driver = $this->connection->getDriver();
        $driverVersion = $driver->version();

        foreach ($versions as $className => $version) {
            $mariadb = $className === 'mariadb';
            $className = $mariadb ? Mysql::class : $className;

            if ($driver instanceof $className) {
                if ($driver instanceof Mysql && $mariadb !== $driver->isMariadb()) {
                    continue;
                }

                $this->skipIf(version_compare($driverVersion, $version, '<'), sprintf('The current database backend does not support the required function. Requires version %s.', $version));
            }
        }

        $this->markTestSkipped('The current database backend does not support the required function.');
    }

    public function testJsonValue(): void
    {
        $this->requireVersion([
            'mariadb' => '10.2.3',
            Mysql::class => '8.0.21',
            Postgres::class => '12',
            Sqlserver::class => '13',
            Sqlite::class => '3.19',
        ]);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonValue(
                    $this->quoteString(json_encode(['a' => 1])),
                    '$.a',
                ),
            ])
            // Set type because drivers like sqlite use non-standard functions that return the sqlite type not json string
            ->setSelectTypeMap(['value' => 'integer'])
            ->execute()->fetchAll('assoc');

        $this->assertSame(1, $result[0]['value']);
    }

    public function testJsonValuePassing(): void
    {
        $this->requireVersion([
            Postgres::class => '17',
        ]);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                'value' => $query->func()->jsonValue(
                    $this->quoteString(json_encode(['a' => 1])),
                    (new JsonPathExpression('$.a'))->passing(['x' => 1]),
                ),
            ])
            ->execute()->fetchAll('assoc');

        $this->assertSame('1', $result[0]['value']);
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
                    (new JsonPathExpression('$.a'))->returning('float'),
                ),
            ])
            ->execute()->fetchAll('assoc');

        $this->assertSame(1.0, $result[0]['value']);
    }

    public function testJsonValueEmptyError(): void
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
                    (new JsonPathExpression('$.a'))->onEmpty('NULL')->onError('DEFAULT', 2.0),
                ),
            ])
            ->execute()->fetchAll('assoc');

        $this->assertSame('1', $result[0]['value']);
    }

    public function testJsonExists(): void
    {
        $this->requireVersion([
            'mariadb' => '10.2.3',
            Mysql::class => '5.7.8',
            Postgres::class => '12.0',
            Sqlserver::class => '16',
            Sqlite::class => '3.9',
        ]);

        $query = new SelectQuery($this->connection);
        $result = $query
            ->select([
                 'present' => $query->func()->jsonExists(
                     $this->quoteString(json_encode(['a' => 1])),
                     '$.a',
                 ),
            ])
            ->execute()->fetchAll('assoc');

        $this->assertTrue((bool)$result[0]['present']);
    }

    private function quoteString(string $value): string
    {
        return sprintf("'%s'", $value);
    }
}
