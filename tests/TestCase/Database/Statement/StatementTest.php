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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Statement;

use Cake\Database\DriverInterface;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Statement\Statement;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use DateTime;
use PDOException;
use PDOStatement;

class StatementTest extends TestCase
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
     */
    public function testExecuteNoParams(): void
    {
        $inner = $this->getMockBuilder(PDOStatement::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(3));
        $inner->method('execute')->will($this->returnValue(true));

        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $statement = $this->getMockBuilder(Statement::class)
            ->setConstructorArgs([$inner, $driver])
            ->onlyMethods(['queryString'])
            ->getMock();
        $statement->expects($this->any())->method('queryString')->will($this->returnValue('SELECT bar FROM foo'));
        $statement->setLogger(new QueryLogger(['connection' => 'test']));
        $statement->execute();

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test duration=\d+ rows=3 SELECT bar FROM foo$/', $messages[0]);
    }

    /**
     * Tests that queries are logged when executed with bound params
     */
    public function testExecuteWithBinding(): void
    {
        $inner = $this->getMockBuilder(PDOStatement::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(3));
        $inner->method('execute')->will($this->returnValue(true));

        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $statement = $this->getMockBuilder(Statement::class)
            ->setConstructorArgs([$inner, $driver])
            ->onlyMethods(['queryString'])
            ->getMock();
        $statement->expects($this->any())->method('queryString')->will($this->returnValue('SELECT bar FROM foo WHERE a=:a AND b=:b'));
        $statement->setLogger(new QueryLogger(['connection' => 'test']));

        $statement->bindValue('a', 1);
        $statement->bindValue('b', new DateTime('2013-01-01'), 'date');
        $statement->execute();

        $statement->bindValue('b', new DateTime('2014-01-01'), 'date');
        $statement->execute();

        $messages = Log::engine('queries')->read();
        $this->assertCount(2, $messages);
        $this->assertMatchesRegularExpression("/^debug: connection=test duration=\d+ rows=3 SELECT bar FROM foo WHERE a='1' AND b='2013-01-01'$/", $messages[0]);
        $this->assertMatchesRegularExpression("/^debug: connection=test duration=\d+ rows=3 SELECT bar FROM foo WHERE a='1' AND b='2014-01-01'$/", $messages[1]);
    }

    /**
     * Tests that queries are logged despite database errors
     */
    public function testExecuteWithError(): void
    {
        $inner = $this->getMockBuilder(PDOStatement::class)->getMock();
        $inner->method('rowCount')->will($this->returnValue(3));
        $inner->method('execute')->will($this->throwException(new PDOException()));

        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $statement = $this->getMockBuilder(Statement::class)
            ->setConstructorArgs([$inner, $driver])
            ->onlyMethods(['queryString'])
            ->getMock();
        $statement->expects($this->any())->method('queryString')->will($this->returnValue('SELECT bar FROM foo'));
        $statement->setLogger(new QueryLogger(['connection' => 'test']));

        try {
            $statement->execute();
        } catch (PDOException $e) {
        }

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test duration=\d+ rows=0 SELECT bar FROM foo$/', $messages[0]);
    }
}
