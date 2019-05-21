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
     * Set up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::reset();
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Log::reset();
    }

    /**
     * Tests that the logged query object is passed to the built-in logger using
     * the correct scope
     *
     * @return void
     */
    public function testLogFunction()
    {
        $logger = new QueryLogger();
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ?';
        $query->params = ['string', '3', null];

        $this->getMockBuilder('Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->setConstructorArgs(['scopes' => ['queriesLog']])
            ->getMock();
        Log::engine('queryLoggerTest');

        $engine2 = $this->getMockBuilder('Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->setConstructorArgs(['scopes' => ['foo']])
            ->getMock();
        Log::engine('queryLoggerTest2');

        $engine2->expects($this->never())->method('log');
        $logger->log(LogLevel::DEBUG, (string)$query, compact('query'));
    }
}
