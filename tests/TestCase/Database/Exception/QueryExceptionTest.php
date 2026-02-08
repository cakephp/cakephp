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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Exception;

use Cake\Database\Exception\QueryException;
use Cake\Database\Log\LoggedQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDOException;

/**
 * Tests QueryException class
 */
class QueryExceptionTest extends TestCase
{
    /**
     * Test exception with string query
     */
    public function testWithStringQuery(): void
    {
        $pdoException = new PDOException('Table not found');
        $exception = new QueryException('SELECT * FROM missing_table', $pdoException);

        $this->assertSame('', $exception->getConnectionName());
        $this->assertSame('SELECT * FROM missing_table', $exception->getQueryString());
        $this->assertStringContainsString('Table not found', $exception->getMessage());
        $this->assertStringContainsString('SELECT * FROM missing_table', $exception->getMessage());
        $this->assertStringNotContainsString('[', $exception->getMessage());
    }

    /**
     * Test exception with LoggedQuery without driver
     */
    public function testWithLoggedQueryWithoutDriver(): void
    {
        $loggedQuery = new LoggedQuery();
        $loggedQuery->setContext([
            'query' => 'SELECT * FROM users',
        ]);

        $pdoException = new PDOException('Connection refused');
        $exception = new QueryException($loggedQuery, $pdoException);

        $this->assertSame('', $exception->getConnectionName());
        $this->assertSame('SELECT * FROM users', $exception->getQueryString());
        $this->assertStringNotContainsString('[', $exception->getMessage());
    }

    /**
     * Test exception with LoggedQuery with driver includes connection name
     */
    public function testWithLoggedQueryWithDriver(): void
    {
        $driver = ConnectionManager::get('test')->getDriver();

        $loggedQuery = new LoggedQuery();
        $loggedQuery->setContext([
            'query' => 'SELECT * FROM users',
            'driver' => $driver,
        ]);

        $pdoException = new PDOException('Table not found');
        $exception = new QueryException($loggedQuery, $pdoException);

        $this->assertSame('test', $exception->getConnectionName());
        $this->assertSame('SELECT * FROM users', $exception->getQueryString());
        $this->assertStringContainsString('[test]', $exception->getMessage());
        $this->assertStringContainsString('Table not found', $exception->getMessage());
    }

    /**
     * Test that previous exception is preserved
     */
    public function testPreviousException(): void
    {
        $pdoException = new PDOException('Original error', 42);
        $exception = new QueryException('SELECT 1', $pdoException);

        $this->assertSame($pdoException, $exception->getPrevious());
        $this->assertSame(42, $exception->getCode());
    }
}
