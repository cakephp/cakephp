<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\SlowQueryLogger;

/**
 * Tests QueryLogger class
 */
class SlowQueryLoggerTest extends QueryLoggerTest
{

    /**
     * Tests that only queries that run for a specific time are logged
     *
     * @return void
     */
    public function testThreshold()
    {
        $logger = $this->getMockBuilder(SlowQueryLogger::class)
            ->setConstructorArgs([
                ['threshold' => 500]
            ])
            ->setMethods(['_logSlow'])
            ->getMock();

        $slowQuery = new LoggedQuery;
        $slowQuery->took = 5000;

        $fastQuery = new LoggedQuery;
        $fastQuery->took = 100;

        $logger->expects($this->exactly(1))->method('_logSlow');

        $logger->log($fastQuery);
        $logger->log($slowQuery);
    }

    /**
     * Tests the filter callback
     *
     * @return void
     */
    public function testCallback()
    {
        $filter = function ($query) {
            if ($query->query !== 'cakephp') {
                return false;
            }

            return $query;
        };

        $logger = $this->getMockBuilder(SlowQueryLogger::class)
            ->setConstructorArgs([
                [
                    'filter' => $filter,
                    'threshold' => 0
                ]
            ])
            ->setMethods(['_logSlow'])
            ->getMock();

        $queryOne = new LoggedQuery;
        $queryOne->query = 'cakephp';

        $queryTwo = new LoggedQuery;
        $queryTwo->query = 'something-else';

        $logger->expects($this->exactly(1))->method('_logSlow');

        $logger->log($queryOne);
        $logger->log($queryTwo);
    }
}
