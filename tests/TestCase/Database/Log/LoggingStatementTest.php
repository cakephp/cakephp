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

use Cake\Database\DriverInterface;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\StatementInterface;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use LogicException;

/**
 * Tests LoggingStatement class
 */
class LoggingStatementTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Log::setConfig('queries', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Log::drop('queries');
    }

    /**
     * Tests that queries are logged when executed without params
     *
     * @return void
     */
    public function testExecuteNoParams()
    {
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(3));
        $inner->method('execute')->will($this->returnValue(true));

        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $st = new LoggingStatement($inner, $driver);
        $st->queryString = 'SELECT bar FROM foo';
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        $st->execute();
        $st->fetchAll();

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertRegExp('/^debug connection=test duration=\d+ rows=3 SELECT bar FROM foo$/', $messages[0]);
    }

    /**
     * Tests that queries are logged when executed with params
     *
     * @return void
     */
    public function testExecuteWithParams()
    {
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(4));
        $inner->method('execute')->will($this->returnValue(true));

        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $st = new LoggingStatement($inner, $driver);
        $st->queryString = 'SELECT bar FROM foo WHERE x=:a AND y=:b';
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        $st->execute(['a' => 1, 'b' => 2]);
        $st->fetchAll();

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertRegExp('/^debug connection=test duration=\d+ rows=4 SELECT bar FROM foo WHERE x=1 AND y=2$/', $messages[0]);
    }

    /**
     * Tests that queries are logged when executed with bound params
     *
     * @return void
     */
    public function testExecuteWithBinding()
    {
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(4));
        $inner->method('execute')->will($this->returnValue(true));

        $date = new \DateTime('2013-01-01');
        $inner->expects($this->at(0))->method('bindValue')->with('a', 1);
        $inner->expects($this->at(1))->method('bindValue')->with('b', $date);

        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $st = new LoggingStatement($inner, $driver);
        $st->queryString = 'SELECT bar FROM foo WHERE a=:a AND b=:b';
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        $st->bindValue('a', 1);
        $st->bindValue('b', $date, 'date');
        $st->execute();
        $st->fetchAll();

        $st->bindValue('b', new \DateTime('2014-01-01'), 'date');
        $st->execute();
        $st->fetchAll();

        $messages = Log::engine('queries')->read();
        $this->assertCount(2, $messages);
        $this->assertRegExp("/^debug connection=test duration=\d+ rows=4 SELECT bar FROM foo WHERE a='1' AND b='2013-01-01'$/", $messages[0]);
        $this->assertRegExp("/^debug connection=test duration=\d+ rows=4 SELECT bar FROM foo WHERE a='1' AND b='2014-01-01'$/", $messages[1]);
    }

    /**
     * Tests that queries are logged despite database errors
     *
     * @return void
     */
    public function testExecuteWithError()
    {
        $exception = new LogicException('This is bad');
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->expects($this->once())
            ->method('execute')
            ->will($this->throwException($exception));

        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $st = new LoggingStatement($inner, $driver);
        $st->queryString = 'SELECT bar FROM foo';
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        try {
            $st->execute();
        } catch (LogicException $e) {
            $this->assertSame('This is bad', $e->getMessage());
            $this->assertSame($st->queryString, $e->queryString);
        }

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertRegExp("/^debug connection=test duration=\d+ rows=0 SELECT bar FROM foo$/", $messages[0]);
    }

    /**
     * Tests setting and getting the logger
     *
     * @return void
     */
    public function testSetAndGetLogger()
    {
        $logger = new QueryLogger(['connection' => 'test']);
        $st = new LoggingStatement(
            $this->getMockBuilder(StatementInterface::class)->getMock(),
            $this->getMockBuilder(DriverInterface::class)->getMock()
        );

        $st->setLogger($logger);
        $this->assertSame($logger, $st->getLogger());
    }
}
