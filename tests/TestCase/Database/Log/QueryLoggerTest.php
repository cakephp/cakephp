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
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\QueryLogger;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Tests QueryLogger class
 */
class QueryLoggerTest extends TestCase
{
    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Log::drop('queryLoggerTest');
        Log::drop('queryLoggerTest2');
        Log::drop('newScope');
    }

    /**
     * Tests that the logged query object is passed to the built-in logger using
     * the correct scope
     */
    public function testLogFunction(): void
    {
        $logger = new QueryLogger(['connection' => '']);
        $query = new LoggedQuery();
        $query->setContext([
            'query' => 'SELECT a FROM b where a = ? AND b = ? AND c = ?',
            'params' => ['string', '3', null],
        ]);

        Log::setConfig('queryLoggerTest', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);
        Log::setConfig('newScope', [
            'className' => 'Array',
            'scopes' => ['cake.database.queries'],
        ]);
        Log::setConfig('queryLoggerTest2', [
            'className' => 'Array',
            'scopes' => ['foo'],
        ]);
        $logger->log(LogLevel::DEBUG, $query, ['query' => $query]);

        $this->assertCount(1, Log::engine('queryLoggerTest')->read());
        $this->assertCount(1, Log::engine('newScope')->read());
        $this->assertCount(0, Log::engine('queryLoggerTest2')->read());
    }

    /**
     * Tests that passed Stringable also work.
     */
    public function testLogFunctionStringable(): void
    {
        Log::setConfig('queryLoggerTest', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);

        $logger = new QueryLogger(['connection' => '']);

        $stringable = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'FooBar';
            }
        };

        $logger->log(LogLevel::DEBUG, $stringable, ['query' => null]);
        $logs = Log::engine('queryLoggerTest')->read();
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('FooBar', $logs[0]);
    }

    /**
     * Tests that the connection name is logged with the query.
     */
    public function testLogConnection(): void
    {
        $logger = new QueryLogger(['connection' => 'test']);
        $query = new LoggedQuery();
        $query->setContext(['query' => 'SELECT a']);

        Log::setConfig('queryLoggerTest', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);
        $logger->log(LogLevel::DEBUG, '', ['query' => $query]);

        $this->assertStringContainsString('connection=test role= duration=', current(Log::engine('queryLoggerTest')->read()));
    }
}
