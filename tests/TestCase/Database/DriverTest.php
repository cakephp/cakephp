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
use Cake\Database\Exception\QueryException;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\Schema\TableSchema;
use Cake\Database\StatementInterface;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use DateTime;
use Exception;
use Mockery;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use TestApp\Database\Driver\RetryDriver;
use TestApp\Database\Driver\StubDriver;

/**
 * Tests Driver class
 */
class DriverTest extends TestCase
{
    /**
     * @var \TestApp\Database\Driver\StubDriver
     */
    protected $driver;

    /**
     * Setup.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Log::setConfig('queries', [
            'className' => 'Array',
            'scopes' => ['queriesLog'],
        ]);

        $this->driver = Mockery::mock(StubDriver::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->driver->__construct();
    }

    protected function tearDown(): void
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
                $e->getMessage(),
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
     * @param mixed $input
     */
    #[DataProvider('schemaValueProvider')]
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

        $connection = Mockery::mock(PDO::class);
        $connection->shouldReceive('quote')
            ->with($value, PDO::PARAM_STR)
            ->once()
            ->andReturn('string');

        $this->driver->shouldReceive('createPdo')
            ->once()
            ->andReturn($connection);

        $this->driver->schemaValue($value);
    }

    /**
     * Test lastInsertId().
     */
    public function testLastInsertId(): void
    {
        $connection = Mockery::mock(PDO::class);
        $connection->shouldReceive('lastInsertId')
            ->once()
            ->andReturn('all-the-bears');

        $this->driver->shouldReceive('createPdo')
            ->once()
            ->andReturn($connection);

        $this->assertSame('all-the-bears', $this->driver->lastInsertId());
    }

    /**
     * Test isConnected().
     */
    public function testIsConnected(): void
    {
        $this->assertFalse($this->driver->isConnected());

        $connection = Mockery::mock(PDO::class);
        $connection->shouldReceive('query')
            ->once()
            ->andReturn(Mockery::mock(PDOStatement::class));

        $this->driver->shouldReceive('createPdo')
            ->once()
            ->andReturn($connection);

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
        $compiler = Mockery::mock(QueryCompiler::class);
        $compiler->shouldReceive('compile')
            ->once()
            ->andReturn('1');

        $driver = Mockery::mock(StubDriver::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $driver->__construct();
        $driver->shouldReceive('newCompiler')
            ->once()
            ->andReturn($compiler);

        $query = Mockery::mock(Query::class)->shouldIgnoreMissing();
        $query->shouldReceive('type')->andReturn('select');

        $driver->shouldReceive('transformQuery')
            ->once()
            ->andReturn($query);

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
        $statement = Mockery::mock(StatementInterface::class);
        $statement->shouldReceive('queryString')->andReturn('SELECT bar FROM foo');
        $statement->shouldReceive('rowCount')->andReturn(3);
        $statement->shouldReceive('execute')->andReturn(true);
        $statement->shouldReceive('getBoundParams')->andReturn([]);

        $this->driver->shouldReceive('prepare')
            ->once()
            ->andReturn($statement);
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
        $boundParams = [];
        $statement = Mockery::mock(StatementInterface::class);
        $statement->shouldReceive('rowCount')->andReturn(3);
        $statement->shouldReceive('execute')->andReturn(true);
        $statement->shouldReceive('queryString')->andReturn('SELECT bar FROM foo WHERE a=:a AND b=:b');
        $statement->shouldReceive('bind')
            ->once()
            ->andReturnUsing(function (array $params, array $types) use (&$boundParams): void {
                $boundParams = [
                    'a' => (string)$params['a'],
                    'b' => $params['b']->format('Y-m-d'),
                ];
            });
        $statement->shouldReceive('getBoundParams')
            ->andReturnUsing(function () use (&$boundParams): array {
                return $boundParams;
            });

        $this->driver->setLogger(new QueryLogger(['connection' => 'test']));
        $this->driver->shouldReceive('prepare')
            ->once()
            ->andReturn($statement);

        $this->driver->execute(
            'SELECT bar FROM foo WHERE a=:a AND b=:b',
            [
                'a' => 1,
                'b' => new DateTime('2013-01-01'),
            ],
            ['b' => 'date'],
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
        $statement = Mockery::mock(StatementInterface::class);
        $statement->shouldReceive('queryString')->andReturn('SELECT bar FROM foo');
        $statement->shouldReceive('rowCount')->andReturn(0);
        $statement->shouldReceive('execute')->andThrow(new PDOException());
        $statement->shouldReceive('getBoundParams')->andReturn([]);

        $this->driver->setLogger(new QueryLogger(['connection' => 'test']));
        $this->driver->shouldReceive('prepare')
            ->once()
            ->andReturn($statement);

        try {
            $this->driver->execute('SELECT foo FROM bar');
        } catch (PDOException) {
        }

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test role=write duration=\d+ rows=0 SELECT bar FROM foo$/', $messages[0]);
    }

    public function testGetLoggerDefault(): void
    {
        $driver = Mockery::mock(StubDriver::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $driver->__construct();
        $this->assertNull($driver->getLogger());

        $driver = Mockery::mock(StubDriver::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $driver->__construct(['log' => true]);

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
        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('beginTransaction')->andReturn(true);
        $pdo->shouldReceive('commit')->andReturn(true);
        $pdo->shouldReceive('rollBack')->andReturn(true);
        $pdo->shouldReceive('inTransaction')
            ->times(5)
            ->andReturn(false, true, true, false, true);

        $driver = Mockery::mock(StubDriver::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $driver->__construct(['log' => true]);
        $driver->shouldReceive('getPdo')
            ->andReturn($pdo);

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

    public function testQueryException(): void
    {
        $this->expectException(QueryException::class);

        ConnectionManager::get('default')->execute('SELECT * FROM non_existent_table');
    }

    public function testQueryExceptionStatementExecute(): void
    {
        $this->expectException(QueryException::class);

        ConnectionManager::get('default')->getDriver()
            ->execute('SELECT * FROM :foo', ['foo' => 'bar']);
    }

    /**
     * Tests that queries are logged when executed without params
     */
    public function testDisableQueryLogging(): void
    {
        $statement = Mockery::mock(StatementInterface::class);
        $statement->shouldReceive('queryString')->andReturn('SELECT bar FROM foo');
        $statement->shouldReceive('rowCount')->andReturn(3);
        $statement->shouldReceive('execute')->andReturn(true);
        $statement->shouldReceive('getBoundParams')->andReturn([]);

        $this->driver->shouldReceive('prepare')
            ->twice()
            ->andReturn($statement);
        $this->driver->setLogger(new QueryLogger(['connection' => 'test']));

        $this->driver->execute('SELECT bar FROM foo');

        $messages = Log::engine('queries')->read();
        $this->driver->disableQueryLogging();

        $this->driver->execute('SELECT bar FROM foo');
        $this->assertCount(1, $messages);
        $this->assertMatchesRegularExpression('/^debug: connection=test role=write duration=[\d\.]+ rows=3 SELECT bar FROM foo$/', $messages[0]);
    }
}
