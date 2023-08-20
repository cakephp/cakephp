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
 * @since         3.2.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Sqlserver;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Statement\Statement;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use DateTime;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use TestApp\Database\Driver\RetryDriver;
use TestApp\Database\Driver\StubDriver;

/**
 * Tests Driver class
 */
class DriverTest extends TestCase
{
    /**
     * @var \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();

        Log::setConfig('queries', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);

        $this->driver = $this->getMockBuilder(StubDriver::class)
            ->onlyMethods(['createPdo', 'prepare'])
            ->getMock();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Log::drop('queries');
    }

    /**
     * Test if building the object throws an exception if we're not passing
     * required config data.
     */
    public function testConstructorException(): void
    {
        try {
            new StubDriver(['login' => 'Bear']);
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'Please pass "username" instead of "login" for connecting to the database',
                $e->getMessage()
            );
        }
    }

    /**
     * Test the constructor.
     */
    public function testConstructor(): void
    {
        $driver = new StubDriver(['quoteIdentifiers' => true]);
        $this->assertTrue($driver->isAutoQuotingEnabled());

        $driver = new StubDriver(['username' => 'GummyBear']);
        $this->assertFalse($driver->isAutoQuotingEnabled());
    }

    /**
     * Test schemaValue().
     * Uses a provider for all the different values we can pass to the method.
     *
     * @dataProvider schemaValueProvider
     * @param mixed $input
     */
    public function testSchemaValue($input, string $expected): void
    {
        $result = $this->driver->schemaValue($input);
        $this->assertSame($expected, $result);
    }

    /**
     * Test schemaValue().
     * Asserting that quote() is being called because none of the conditions were met before.
     */
    public function testSchemaValueConnectionQuoting(): void
    {
        $value = 'string';

        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['quote'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('quote')
            ->with($value, PDO::PARAM_STR)
            ->willReturn('string');

        $this->driver->expects($this->any())
            ->method('createPdo')
            ->willReturn($connection);

        $this->driver->schemaValue($value);
    }

    /**
     * Test lastInsertId().
     */
    public function testLastInsertId(): void
    {
        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['lastInsertId'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('all-the-bears');

        $this->driver->expects($this->any())
            ->method('createPdo')
            ->willReturn($connection);

        $this->assertSame('all-the-bears', $this->driver->lastInsertId());
    }

    /**
     * Test isConnected().
     */
    public function testIsConnected(): void
    {
        $this->assertFalse($this->driver->isConnected());

        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['query'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('query')
            ->willReturn(new PDOStatement());

        $this->driver->expects($this->any())
            ->method('createPdo')
            ->willReturn($connection);

        $this->driver->connect();

        $this->assertTrue($this->driver->isConnected());
    }

    /**
     * test autoQuoting().
     */
    public function testAutoQuoting(): void
    {
        $this->assertFalse($this->driver->isAutoQuotingEnabled());

        $this->assertSame($this->driver, $this->driver->enableAutoQuoting(true));
        $this->assertTrue($this->driver->isAutoQuotingEnabled());

        $this->driver->disableAutoQuoting();
        $this->assertFalse($this->driver->isAutoQuotingEnabled());
    }

    /**
     * Test compileQuery().
     */
    public function testCompileQuery(): void
    {
        $compiler = $this->getMockBuilder(QueryCompiler::class)
            ->onlyMethods(['compile'])
            ->getMock();

        $compiler
            ->expects($this->once())
            ->method('compile')
            ->willReturn('1');

        $driver = $this->getMockBuilder(StubDriver::class)
            ->onlyMethods(['newCompiler', 'transformQuery'])
            ->getMock();

        $driver
            ->expects($this->once())
            ->method('newCompiler')
            ->willReturn($compiler);

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('type')->willReturn('select');

        $driver
            ->expects($this->once())
            ->method('transformQuery')
            ->willReturn($query);

        $result = $driver->compileQuery($query, new ValueBinder());

        $this->assertSame('1', $result);
    }

    /**
     * Test newCompiler().
     */
    public function testNewCompiler(): void
    {
        $this->assertInstanceOf(QueryCompiler::class, $this->driver->newCompiler());
    }

    /**
     * Test newTableSchema().
     */
    public function testNewTableSchema(): void
    {
        $tableName = 'articles';
        $actual = $this->driver->newTableSchema($tableName);
        $this->assertInstanceOf(TableSchema::class, $actual);
        $this->assertSame($tableName, $actual->name());
    }

    public function testConnectRetry(): void
    {
        $this->skipIf(!ConnectionManager::get('test')->getDriver() instanceof Sqlserver);

        $driver = new RetryDriver();

        try {
            $driver->connect();
        } catch (MissingConnectionException) {
        }

        $this->assertSame(4, $driver->getConnectRetries());
    }

    /**
     * Test __destruct().
     */
    public function testDestructor(): void
    {
        $this->driver->__destruct();

        $this->assertFalse($this->driver->__debugInfo()['connected']);
    }

    /**
     * Data provider for testSchemaValue().
     *
     * @return array
     */
    public static function schemaValueProvider(): array
    {
        return [
            [null, 'NULL'],
            [false, 'FALSE'],
            [true, 'TRUE'],
            [1, '1'],
            ['0', '0'],
            ['42', '42'],
        ];
    }

    /**
     * Tests that queries are logged when executed without params
     */
    public function testExecuteNoParams(): void
    {
        $inner = $this->getMockBuilder(PDOStatement::class)->getMock();

        $statement = $this->getMockBuilder(Statement::class)
            ->setConstructorArgs([$inner, $this->driver])
            ->onlyMethods(['queryString','rowCount','execute'])
            ->getMock();
        $statement->expects($this->any())->method('queryString')->willReturn('SELECT bar FROM foo');
        $statement->method('rowCount')->willReturn(3);
        $statement->method('execute')->willReturn(true);

        $this->driver->expects($this->any())
            ->method('prepare')
            ->willReturn($statement);
        $this->driver->setLogger(new QueryLogger(['connection' => 'test']));

        $this->driver->execute('SELECT bar FROM foo');

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test role=write duration=[\d\.]+ rows=3 SELECT bar FROM foo$/', $messages[0]);
    }

    /**
     * Tests that queries are logged when executed with bound params
     */
    public function testExecuteWithBinding(): void
    {
        $inner = $this->getMockBuilder(PDOStatement::class)->getMock();

        $statement = $this->getMockBuilder(Statement::class)
            ->setConstructorArgs([$inner, $this->driver])
            ->onlyMethods(['queryString','rowCount','execute'])
            ->getMock();
        $statement->method('rowCount')->willReturn(3);
        $statement->method('execute')->willReturn(true);
        $statement->expects($this->any())->method('queryString')->willReturn('SELECT bar FROM foo WHERE a=:a AND b=:b');

        $this->driver->setLogger(new QueryLogger(['connection' => 'test']));
        $this->driver->expects($this->any())
            ->method('prepare')
            ->willReturn($statement);

        $this->driver->execute(
            'SELECT bar FROM foo WHERE a=:a AND b=:b',
            [
                'a' => 1,
                'b' => new DateTime('2013-01-01'),
            ],
            ['b' => 'date']
        );

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression("/^debug: connection=test role=write duration=[\d\.]+ rows=3 SELECT bar FROM foo WHERE a='1' AND b='2013-01-01'$/", $messages[0]);
    }

    /**
     * Tests that queries are logged despite database errors
     */
    public function testExecuteWithError(): void
    {
        $inner = $this->getMockBuilder(PDOStatement::class)->getMock();

        $statement = $this->getMockBuilder(Statement::class)
            ->setConstructorArgs([$inner, $this->driver])
            ->onlyMethods(['queryString','rowCount','execute'])
            ->getMock();
        $statement->expects($this->any())->method('queryString')->willReturn('SELECT bar FROM foo');
        $statement->method('rowCount')->willReturn(0);
        $statement->method('execute')->will($this->throwException(new PDOException()));

        $this->driver->setLogger(new QueryLogger(['connection' => 'test']));
        $this->driver->expects($this->any())
            ->method('prepare')
            ->willReturn($statement);

        try {
            $this->driver->execute('SELECT foo FROM bar');
        } catch (PDOException $e) {
        }

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test role=write duration=\d+ rows=0 SELECT bar FROM foo$/', $messages[0]);
    }

    public function testGetLoggerDefault(): void
    {
        $driver = $this->getMockBuilder(StubDriver::class)
            ->onlyMethods(['createPdo', 'prepare'])
            ->getMock();
        $this->assertNull($driver->getLogger());

        $driver = $this->getMockBuilder(StubDriver::class)
            ->setConstructorArgs([['log' => true]])
            ->onlyMethods(['createPdo'])
            ->getMock();

        $logger = $driver->getLogger();
        $this->assertInstanceOf(QueryLogger::class, $logger);
    }

    public function testSetLogger(): void
    {
        $logger = new QueryLogger();
        $this->driver->setLogger($logger);
        $this->assertSame($logger, $this->driver->getLogger());
    }

    public function testLogTransaction(): void
    {
        $pdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['beginTransaction', 'commit', 'rollback', 'inTransaction'])
            ->getMock();
        $pdo
            ->expects($this->any())
            ->method('beginTransaction')
            ->willReturn(true);
        $pdo
            ->expects($this->any())
            ->method('commit')
            ->willReturn(true);
        $pdo
            ->expects($this->any())
            ->method('rollBack')
            ->willReturn(true);
        $pdo->expects($this->exactly(5))
            ->method('inTransaction')
            ->willReturn(
                false,
                true,
                true,
                false,
                true,
            );

        $driver = $this->getMockBuilder(StubDriver::class)
            ->setConstructorArgs([['log' => true]])
            ->onlyMethods(['getPdo'])
            ->getMock();

        $driver->expects($this->any())
            ->method('getPdo')
            ->willReturn($pdo);

        $driver->beginTransaction();
        $driver->beginTransaction(); //This one will not be logged
        $driver->rollbackTransaction();

        $driver->beginTransaction();
        $driver->commitTransaction();

        $messages = Log::engine('queries')->read();
        $this->assertCount(4, $messages);
        $this->assertSame('debug: connection= role= duration=0 rows=0 BEGIN', $messages[0]);
        $this->assertSame('debug: connection= role= duration=0 rows=0 ROLLBACK', $messages[1]);
        $this->assertSame('debug: connection= role= duration=0 rows=0 BEGIN', $messages[2]);
        $this->assertSame('debug: connection= role= duration=0 rows=0 COMMIT', $messages[3]);
    }
}
