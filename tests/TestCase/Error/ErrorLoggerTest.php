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
 * @since         5.3.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Database\Driver\Mysql;
use Cake\Database\Exception\QueryException;
use Cake\Database\Log\LoggedQuery;
use Cake\Error\ErrorLogger;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDOException;
use TestApp\Log\Engine\TestAppLog;

/**
 * ErrorLogger Test
 */
class ErrorLoggerTest extends TestCase
{
    /**
     * @var \Cake\Error\ErrorLogger
     */
    protected ErrorLogger $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->logger = new ErrorLogger();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Log::drop('test_error');
    }

    public function testLogExceptionWithQueryException(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);

        $driver = $this->createStub(Mysql::class);
        $driver->method('config')->willReturn(['name' => 'test_connection']);

        $query = new LoggedQuery();
        $query->setContext(['query' => 'SELECT 1', 'driver' => $driver]);

        $exception = new QueryException($query, new PDOException('SQLSTATE[42000]: Syntax error'));

        $this->logger->logException($exception);

        $logs = Log::engine('test_error')->read();
        $this->assertNotEmpty($logs);
        $this->assertStringContainsString('SQLSTATE[42000]', $logs[0]);
    }

    public function testLogExceptionWithRegularException(): void
    {
        Log::setConfig('test_error', [
            'className' => 'Array',
        ]);

        $exception = new InvalidArgumentException('Something went wrong');

        $this->logger->logException($exception);

        $logs = Log::engine('test_error')->read();
        $this->assertNotEmpty($logs);
        $this->assertStringContainsString('Something went wrong', $logs[0]);
    }

    public function testLogExceptionContextWithQueryException(): void
    {
        Log::setConfig('test_error', [
            'className' => TestAppLog::class,
        ]);

        $driver = $this->createStub(Mysql::class);
        $driver->method('config')->willReturn(['name' => 'my_connection']);

        $query = new LoggedQuery();
        $query->setContext(['query' => 'SELECT 1', 'driver' => $driver]);

        $exception = new QueryException($query, new PDOException('Test error'));

        $this->logger->logException($exception);

        /** @var \TestApp\Log\Engine\TestAppLog $engine */
        $engine = Log::engine('test_error');
        $this->assertArrayHasKey('connection', $engine->passedScope);
        $this->assertSame('my_connection', $engine->passedScope['connection']);
    }

    public function testLogExceptionContextWithRegularException(): void
    {
        Log::setConfig('test_error', [
            'className' => TestAppLog::class,
        ]);

        $exception = new InvalidArgumentException('Test');

        $this->logger->logException($exception);

        /** @var \TestApp\Log\Engine\TestAppLog $engine */
        $engine = Log::engine('test_error');
        $this->assertArrayNotHasKey('connection', $engine->passedScope);
    }
}
