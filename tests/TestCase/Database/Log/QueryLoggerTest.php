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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\QueryLogger;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

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
    public function setUp()
    {
        parent::setUp();
        Log::reset();
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Log::reset();
    }

    /**
     * Tests that query placeholders are replaced when logged
     *
     * @return void
     */
    public function testStringInterpolation()
    {
        $logger = $this->getMockBuilder('\Cake\Database\Log\QueryLogger')
            ->setMethods(['_log'])
            ->getMock();
        $query = new LoggedQuery;
        $query->query = 'SELECT a FROM b where a = :p1 AND b = :p2 AND c = :p3 AND d = :p4 AND e = :p5 AND f = :p6';
        $query->params = ['p1' => 'string', 'p3' => null, 'p2' => 3, 'p4' => true, 'p5' => false, 'p6' => 0];

        $logger->expects($this->once())->method('_log')->with($query);
        $logger->log($query);
        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = 3 AND c = NULL AND d = 1 AND e = 0 AND f = 0";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that positional placeholders are replaced when logging a query
     *
     * @return void
     */
    public function testStringInterpolationNotNamed()
    {
        $logger = $this->getMockBuilder('\Cake\Database\Log\QueryLogger')
            ->setMethods(['_log'])
            ->getMock();
        $query = new LoggedQuery;
        $query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ? AND d = ? AND e = ? AND f = ?';
        $query->params = ['string', '3', null, true, false, 0];

        $logger->expects($this->once())->method('_log')->with($query);
        $logger->log($query);
        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = '3' AND c = NULL AND d = 1 AND e = 0 AND f = 0";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that repeated placeholders are correctly replaced
     *
     * @return void
     */
    public function testStringInterpolationDuplicate()
    {
        $logger = $this->getMockBuilder('\Cake\Database\Log\QueryLogger')
            ->setMethods(['_log'])
            ->getMock();
        $query = new LoggedQuery;
        $query->query = 'SELECT a FROM b where a = :p1 AND b = :p1 AND c = :p2 AND d = :p2';
        $query->params = ['p1' => 'string', 'p2' => 3];

        $logger->expects($this->once())->method('_log')->with($query);
        $logger->log($query);
        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = 'string' AND c = 3 AND d = 3";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that named placeholders
     *
     * @return void
     */
    public function testStringInterpolationNamed()
    {
        $logger = $this->getMockBuilder('\Cake\Database\Log\QueryLogger')
            ->setMethods(['_log'])
            ->getMock();
        $query = new LoggedQuery;
        $query->query = 'SELECT a FROM b where a = :p1 AND b = :p11 AND c = :p20 AND d = :p2';
        $query->params = ['p11' => 'test', 'p1' => 'string', 'p2' => 3, 'p20' => 5];

        $logger->expects($this->once())->method('_log')->with($query);
        $logger->log($query);
        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = 'test' AND c = 5 AND d = 3";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that the logged query object is passed to the built-in logger using
     * the correct scope
     *
     * @return void
     */
    public function testLogFunction()
    {
        $logger = new QueryLogger;
        $query = new LoggedQuery;
        $query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ?';
        $query->params = ['string', '3', null];

        $engine = $this->getMockBuilder('\Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->setConstructorArgs(['scopes' => ['queriesLog']])
            ->getMock();
        Log::engine('queryLoggerTest');

        $engine2 = $this->getMockBuilder('\Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->setConstructorArgs(['scopes' => ['foo']])
            ->getMock();
        Log::engine('queryLoggerTest2');

        $engine2->expects($this->never())->method('log');
        $logger->log($query);
    }
}
