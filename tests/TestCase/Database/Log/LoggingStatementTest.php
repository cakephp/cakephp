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
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\TestSuite\TestCase;

/**
 * Tests LoggingStatement class
 */
class LoggingStatementTest extends TestCase
{
    /**
     * Tests that queries are logged when executed without params
     *
     * @return void
     */
    public function testExecuteNoParams()
    {
        $inner = $this->getMockBuilder('PDOStatement')->getMock();
        $inner->method('rowCount')->will($this->returnValue(3));
        $inner->method('execute')->will($this->returnValue(true));

        $logger = $this->getMockBuilder(QueryLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('SELECT bar FROM foo'),
                $this->callback(function ($context) {
                    $query = $context['query'];

                    return $query instanceof LoggedQuery
                        && $query->query === 'SELECT bar FROM foo'
                        && $query->numRows === 3
                        && $query->params === [];
                })
            );

        $st = new LoggingStatement($inner);
        $st->queryString = 'SELECT bar FROM foo';
        $st->setLogger($logger);
        $st->execute();
    }

    /**
     * Tests that queries are logged when executed with params
     *
     * @return void
     */
    public function testExecuteWithParams()
    {
        $inner = $this->getMockBuilder('PDOStatement')->getMock();
        $inner->method('rowCount')->will($this->returnValue(4));
        $inner->method('execute')->will($this->returnValue(true));

        $logger = $this->getMockBuilder(QueryLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('SELECT bar FROM foo'),
                $this->callback(function ($context) {
                    $query = $context['query'];

                    return $query instanceof LoggedQuery
                        && $query->query === 'SELECT bar FROM foo'
                        && $query->numRows === 4
                        && $query->params == ['a' => 1, 'b' => 2];
                })
            );

        $st = new LoggingStatement($inner);
        $st->queryString = 'SELECT bar FROM foo';
        $st->setLogger($logger);
        $st->execute(['a' => 1, 'b' => 2]);
    }

    /**
     * Tests that queries are logged when executed with bound params
     *
     * @return void
     */
    public function testExecuteWithBinding()
    {
        $inner = $this->getMockBuilder('PDOStatement')->getMock();
        $inner->method('rowCount')->will($this->returnValue(4));
        $inner->method('execute')->will($this->returnValue(true));

        $logger = $this->getMockBuilder(QueryLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger
            ->expects($this->at(0))
            ->method('debug')
            ->with(
                $this->stringContains('SELECT bar FROM foo'),
                $this->callback(function ($context) {
                    $query = $context['query'];

                    return $query instanceof LoggedQuery
                        && $query->query === 'SELECT bar FROM foo'
                        && $query->numRows === 4
                        && $query->params == ['a' => 1, 'b' => '2013-01-01'];
                })
            );
        $logger
            ->expects($this->at(1))
            ->method('debug')
            ->with(
                $this->stringContains('SELECT bar FROM foo'),
                $this->callback(function ($context) {
                    $query = $context['query'];

                    return $query instanceof LoggedQuery
                        && $query->query === 'SELECT bar FROM foo'
                        && $query->numRows === 4
                        && $query->params == ['a' => 1, 'b' => '2014-01-01'];
                })
            );

        $date = new \DateTime('2013-01-01');
        $inner->expects($this->at(0))->method('bindValue')->with('a', 1);
        $inner->expects($this->at(1))->method('bindValue')->with('b', $date);
        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $st = new LoggingStatement($inner, $driver);
        $st->queryString = 'SELECT bar FROM foo';
        $st->setLogger($logger);
        $st->bindValue('a', 1);
        $st->bindValue('b', $date, 'date');
        $st->execute();
        $st->bindValue('b', new \DateTime('2014-01-01'), 'date');
        $st->execute();
    }

    /**
     * Tests that queries are logged despite database errors
     *
     * @return void
     */
    public function testExecuteWithError()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This is bad');
        $exception = new \LogicException('This is bad');
        $inner = $this->getMockBuilder('PDOStatement')->getMock();
        $inner->expects($this->once())
            ->method('execute')
            ->will($this->throwException($exception));

        $logger = $this->getMockBuilder(QueryLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('SELECT bar FROM foo'),
                $this->callback(function ($context) use ($exception) {
                    $query = $context['query'];

                    return $query instanceof LoggedQuery
                        && $query->query === 'SELECT bar FROM foo'
                        && $query->params === []
                        && $query->error === $exception;
                })
            );

        $st = new LoggingStatement($inner);
        $st->queryString = 'SELECT bar FROM foo';
        $st->setLogger($logger);
        $st->execute();
    }

    /**
     * Tests setting and getting the logger
     *
     * @return void
     */
    public function testSetAndGetLogger()
    {
        $logger = $this->getMockBuilder(QueryLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $st = new LoggingStatement();

        $st->setLogger($logger);
        $this->assertSame($logger, $st->getLogger());
    }
}
