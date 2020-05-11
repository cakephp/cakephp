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

/**
 * Tests QueryLogger class
 */
class QueryLoggerTest extends TestCase
{
    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Log::drop('queryLoggerTest');
        Log::drop('queryLoggerTest2');
    }

    /**
     * Tests that the logged query object is passed to the built-in logger using
     * the correct scope
     *
     * @return void
     */
    public function testLogFunction()
    {
        $logger = new QueryLogger(['connection' => '']);
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ?';
        $query->params = ['string', '3', null];

        Log::setConfig('queryLoggerTest', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);
        Log::setConfig('queryLoggerTest2', [
            'className' => 'Array',
            'scopes' => ['foo'],
        ]);
        $logger->log(LogLevel::DEBUG, (string)$query, compact('query'));

        $this->assertCount(1, Log::engine('queryLoggerTest')->read());
        $this->assertCount(0, Log::engine('queryLoggerTest2')->read());
    }

    /**
     * Tests that the connection name is logged with the query.
     *
     * @return void
     */
    public function testLogConnection()
    {
        $logger = new QueryLogger(['connection' => 'test']);
        $query = new LoggedQuery();
        $query->query = 'SELECT a';

        Log::setConfig('queryLoggerTest', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);
        $logger->log(LogLevel::DEBUG, '', compact('query'));

        $this->assertStringContainsString('connection=test duration=', current(Log::engine('queryLoggerTest')->read()));
    }
}
