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

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\DriverInterface;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use DateTime;
use TestApp\Error\Exception\MyPDOException;
use TestApp\Error\Exception\MyPDOStringException;

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
        Configure::delete('Error.convertStatementToDatabaseException');
    }

    /**
     * Tests that queries are logged when executed without params
     */
    public function testExecuteNoParams(): void
    {
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(3));
        $inner->method('execute')->will($this->returnValue(true));

        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->any())
            ->method('getRole')
            ->will($this->returnValue(Connection::ROLE_WRITE));
        $st = $this->getMockBuilder(LoggingStatement::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$inner, $driver])
            ->getMock();
        $st->expects($this->any())
            ->method('__get')
            ->willReturn('SELECT bar FROM foo');
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        $st->execute();
        $st->fetchAll();

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test role=write duration=\d+ rows=3 SELECT bar FROM foo$/', $messages[0]);
    }

    /**
     * Tests that queries are logged when executed with params
     */
    public function testExecuteWithParams(): void
    {
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(4));
        $inner->method('execute')->will($this->returnValue(true));

        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->any())
            ->method('getRole')
            ->will($this->returnValue(Connection::ROLE_WRITE));

        $st = $this->getMockBuilder(LoggingStatement::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$inner, $driver])
            ->getMock();
        $st->expects($this->any())
            ->method('__get')
            ->willReturn('SELECT bar FROM foo WHERE x=:a AND y=:b');
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        $st->execute(['a' => 1, 'b' => 2]);
        $st->fetchAll();

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test role=write duration=\d+ rows=4 SELECT bar FROM foo WHERE x=1 AND y=2$/', $messages[0]);
    }

    /**
     * Tests that queries are logged when executed with bound params
     */
    public function testExecuteWithBinding(): void
    {
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(4));
        $inner->method('execute')->will($this->returnValue(true));

        $date = new DateTime('2013-01-01');
        $inner->expects($this->atLeast(2))
              ->method('bindValue')
              ->withConsecutive(['a', 1], ['b', $date]);

        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $st = $this->getMockBuilder(LoggingStatement::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$inner, $driver])
            ->getMock();
        $st->expects($this->any())
            ->method('__get')
            ->willReturn('SELECT bar FROM foo WHERE a=:a AND b=:b');
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        $st->bindValue('a', 1);
        $st->bindValue('b', $date, 'date');
        $st->execute();
        $st->fetchAll();

        $st->bindValue('b', new DateTime('2014-01-01'), 'date');
        $st->execute();
        $st->fetchAll();

        $messages = Log::engine('queries')->read();
        $this->assertCount(2, $messages);
        $this->assertMatchesRegularExpression("/^debug: connection=test role= duration=\d+ rows=4 SELECT bar FROM foo WHERE a='1' AND b='2013-01-01'$/", $messages[0]);
        $this->assertMatchesRegularExpression("/^debug: connection=test role= duration=\d+ rows=4 SELECT bar FROM foo WHERE a='1' AND b='2014-01-01'$/", $messages[1]);
    }

    /**
     * Tests that queries are logged despite database errors
     */
    public function testExecuteWithError(): void
    {
        $this->skipIf(
            version_compare(PHP_VERSION, '8.2.0', '>='),
            'Setting queryString on exceptions does not work on 8.2+'
        );
        $exception = new MyPDOException('This is bad');
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->expects($this->once())
            ->method('execute')
            ->will($this->throwException($exception));

        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->any())
            ->method('getRole')
            ->will($this->returnValue(Connection::ROLE_WRITE));
        $st = $this->getMockBuilder(LoggingStatement::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$inner, $driver])
            ->getMock();
        $st->expects($this->any())
            ->method('__get')
            ->willReturn('SELECT bar FROM foo');
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        $this->deprecated(function () use ($st) {
            try {
                $st->execute();
            } catch (MyPDOException $e) {
                $this->assertSame('This is bad', $e->getMessage());
                $this->assertSame($st->queryString, $e->queryString);
            }
        });

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression("/^debug: connection=test role=write duration=\d+ rows=0 SELECT bar FROM foo$/", $messages[0]);
    }

    /**
     * Tests that we do exception wrapping correctly.
     * The exception returns a string code like most PDOExceptions
     */
    public function testExecuteWithErrorWrapStatementStringCode(): void
    {
        Configure::write('Error.convertStatementToDatabaseException', true);
        $exception = new MyPDOStringException('This is bad', 1234);
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->expects($this->once())
            ->method('execute')
            ->will($this->throwException($exception));

        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->any())
            ->method('getRole')
            ->will($this->returnValue(ConnectionInterface::ROLE_WRITE));
        $st = $this->getMockBuilder(LoggingStatement::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$inner, $driver])
            ->getMock();
        $st->expects($this->any())
            ->method('__get')
            ->willReturn('SELECT bar FROM foo');
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        try {
            $st->execute();
            $this->fail('Exception not thrown');
        } catch (DatabaseException $e) {
            $attrs = $e->getAttributes();

            $this->assertSame('This is bad', $e->getMessage());
            $this->assertArrayHasKey('queryString', $attrs);
            $this->assertSame($st->queryString, $attrs['queryString']);
        }

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression("/^debug: connection=test role=write duration=\d+ rows=0 SELECT bar FROM foo$/", $messages[0]);
    }

    /**
     * Tests that we do exception wrapping correctly.
     * The exception returns an int code.
     */
    public function testExecuteWithErrorWrapStatementIntCode(): void
    {
        Configure::write('Error.convertStatementToDatabaseException', true);
        $exception = new MyPDOException('This is bad', 1234);
        $inner = $this->getMockBuilder(StatementInterface::class)->getMock();
        $inner->expects($this->once())
            ->method('execute')
            ->will($this->throwException($exception));

        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->any())
            ->method('getRole')
            ->will($this->returnValue(ConnectionInterface::ROLE_WRITE));
        $st = $this->getMockBuilder(LoggingStatement::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$inner, $driver])
            ->getMock();
        $st->expects($this->any())
            ->method('__get')
            ->willReturn('SELECT bar FROM foo');
        $st->setLogger(new QueryLogger(['connection' => 'test']));
        try {
            $st->execute();
            $this->fail('Exception not thrown');
        } catch (DatabaseException $e) {
            $attrs = $e->getAttributes();

            $this->assertSame('This is bad', $e->getMessage());
            $this->assertArrayHasKey('queryString', $attrs);
            $this->assertSame($st->queryString, $attrs['queryString']);
        }

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression("/^debug: connection=test role=write duration=\d+ rows=0 SELECT bar FROM foo$/", $messages[0]);
    }

    /**
     * Tests setting and getting the logger
     */
    public function testSetAndGetLogger(): void
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
