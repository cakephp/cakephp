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
namespace Cake\Test\TestCase\Database\Query;

use Cake\Database\Driver\Sqlserver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Cake\Test\TestCase\Database\QueryAssertsTrait;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Tests InsertQuery class
 */
class InsertQueryTest extends TestCase
{
    use QueryAssertsTrait;

    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
    ];

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $autoQuote;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->autoQuote = $this->connection->getDriver()->isAutoQuotingEnabled();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->connection->getDriver()->enableAutoQuoting($this->autoQuote);
        unset($this->connection);
    }

    /**
     * You cannot call values() before insert() it causes all sorts of pain.
     */
    public function testInsertValuesBeforeInsertFailure(): void
    {
        $this->expectException(DatabaseException::class);
        $query = new InsertQuery($this->connection);
        $query->values([
            'id' => 1,
            'title' => 'mark',
            'body' => 'test insert',
        ]);
    }

    /**
     * Inserting nothing should not generate an error.
     */
    public function testInsertNothing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 1 column is required to perform an insert.');
        $query = new InsertQuery($this->connection);
        $query->insert([]);
    }

    /**
     * Test insert() with no into()
     */
    public function testInsertNoInto(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Could not compile insert query. No table was specified');
        $query = new InsertQuery($this->connection);
        $query->insert(['title', 'body'])->sql();
    }

    /**
     * Test insert overwrites values
     */
    public function testInsertOverwritesValues(): void
    {
        $query = new InsertQuery($this->connection);
        $query->insert(['title', 'body'])
            ->insert(['title'])
            ->into('articles')
            ->values([
                'title' => 'mark',
            ]);

        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0\)',
            $result,
            !$this->autoQuote
        );
    }

    /**
     * Test inserting a single row.
     */
    public function testInsertSimple(): void
    {
        $query = new InsertQuery($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'title' => 'mark',
                'body' => 'test insert',
            ]);
        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>, <body>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0, :c1\)',
            $result,
            !$this->autoQuote
        );

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame(1, $result->rowCount(), '1 row should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'mark',
                'body' => 'test insert',
                'published' => 'N',
            ],
        ];
        $this->assertTable('articles', 1, $expected, ['id >=' => 4]);
    }

    /**
     * Test insert queries quote integer column names
     */
    public function testInsertQuoteColumns(): void
    {
        $query = new InsertQuery($this->connection);
        $query->insert([123])
            ->into('articles')
            ->values([
                '123' => 'mark',
            ]);
        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<123>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0\)',
            $result,
            !$this->autoQuote
        );
    }

    /**
     * Test an insert when not all the listed fields are provided.
     * Columns should be matched up where possible.
     */
    public function testInsertSparseRow(): void
    {
        $query = new InsertQuery($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'title' => 'mark',
            ]);
        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>, <body>\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c0, :c1\)',
            $result,
            !$this->autoQuote
        );

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame(1, $result->rowCount(), '1 row should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => 'mark',
                'body' => null,
                'published' => 'N',
            ],
        ];
        $this->assertTable('articles', 1, $expected, ['id >=' => 4]);
    }

    /**
     * Test inserting multiple rows with sparse data.
     */
    public function testInsertMultipleRowsSparse(): void
    {
        $query = new InsertQuery($this->connection);
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'body' => 'test insert',
            ])
            ->values([
                'title' => 'jose',
            ]);

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame(2, $result->rowCount(), '2 rows should be inserted');
        }

        $expected = [
            [
                'id' => 4,
                'author_id' => null,
                'title' => null,
                'body' => 'test insert',
                'published' => 'N',
            ],
            [
                'id' => 5,
                'author_id' => null,
                'title' => 'jose',
                'body' => null,
                'published' => 'N',
            ],
        ];
        $this->assertTable('articles', 2, $expected, ['id >=' => 4]);
    }

    /**
     * Test that INSERT INTO ... SELECT works.
     */
    public function testInsertFromSelect(): void
    {
        $select = (new SelectQuery($this->connection))->select(['name', "'some text'", 99])
            ->from('authors')
            ->where(['id' => 1]);

        $query = new InsertQuery($this->connection);
        $query->insert(
            ['title', 'body', 'author_id'],
            ['title' => 'string', 'body' => 'string', 'author_id' => 'integer']
        )
        ->into('articles')
        ->values($select);

        $result = $query->sql();
        $this->assertQuotedQuery(
            'INSERT INTO <articles> \(<title>, <body>, <author_id>\) (OUTPUT INSERTED\.\* )?SELECT',
            $result,
            !$this->autoQuote
        );
        $this->assertQuotedQuery(
            'SELECT <name>, \'some text\', 99 FROM <authors>',
            $result,
            !$this->autoQuote
        );
        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame(1, $result->rowCount());
        }

        $result = (new SelectQuery($this->connection))->select('*')
            ->from('articles')
            ->where(['author_id' => 99])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $expected = [
            'id' => 4,
            'title' => 'mariano',
            'body' => 'some text',
            'author_id' => 99,
            'published' => 'N',
        ];
        $this->assertEquals($expected, $rows[0]);
    }

    /**
     * Test that an exception is raised when mixing query + array types.
     */
    public function testInsertFailureMixingTypesArrayFirst(): void
    {
        $this->expectException(DatabaseException::class);
        $query = new InsertQuery($this->connection);
        $query->insert(['name'])
            ->into('articles')
            ->values(['name' => 'mark'])
            ->values(new InsertQuery($this->connection));
    }

    /**
     * Test that an exception is raised when mixing query + array types.
     */
    public function testInsertFailureMixingTypesQueryFirst(): void
    {
        $this->expectException(DatabaseException::class);
        $query = new InsertQuery($this->connection);
        $query->insert(['name'])
            ->into('articles')
            ->values(new InsertQuery($this->connection))
            ->values(['name' => 'mark']);
    }

    /**
     * Test that insert can use expression objects as values.
     */
    public function testInsertExpressionValues(): void
    {
        $query = new InsertQuery($this->connection);
        $query->insert(['title', 'author_id'])
            ->into('articles')
            ->values(['title' => $query->newExpr("SELECT 'jose'"), 'author_id' => 99]);

        $result = $query->execute();
        $result->closeCursor();

        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame(1, $result->rowCount());
        }

        $result = (new SelectQuery($this->connection))->select('*')
            ->from('articles')
            ->where(['author_id' => 99])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $expected = [
            'id' => 4,
            'title' => 'jose',
            'body' => null,
            'author_id' => '99',
            'published' => 'N',
        ];
        $this->assertEquals($expected, $rows[0]);

        $subquery = new SelectQuery($this->connection);
        $subquery->select(['name'])
            ->from('authors')
            ->where(['id' => 1]);

        $query = new InsertQuery($this->connection);
        $query->insert(['title', 'author_id'])
            ->into('articles')
            ->values(['title' => $subquery, 'author_id' => 100]);
        $result = $query->execute();
        //PDO_SQLSRV returns -1 for successful inserts when using INSERT ... OUTPUT
        if (!$this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame(1, $result->rowCount());
        }
        $result->closeCursor();

        $result = (new SelectQuery($this->connection))->select('*')
            ->from('articles')
            ->where(['author_id' => 100])
            ->execute();
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $expected = [
            'id' => 5,
            'title' => 'mariano',
            'body' => null,
            'author_id' => '100',
            'published' => 'N',
        ];
        $this->assertEquals($expected, $rows[0]);
    }

    /**
     * Test use of modifiers in a INSERT query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testInsertModifiers(): void
    {
        $query = new InsertQuery($this->connection);
        $result = $query
            ->insert(['title'])
            ->into('articles')
            ->values(['title' => 'foo'])
            ->modifier('IGNORE');
        $this->assertQuotedQuery(
            'INSERT IGNORE INTO <articles> \(<title>\) (OUTPUT INSERTED\.\* )?',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new InsertQuery($this->connection);
        $result = $query
            ->insert(['title'])
            ->into('articles')
            ->values(['title' => 'foo'])
            ->modifier(['IGNORE', 'LOW_PRIORITY']);
        $this->assertQuotedQuery(
            'INSERT IGNORE LOW_PRIORITY INTO <articles> \(<title>\) (OUTPUT INSERTED\.\* )?',
            $result->sql(),
            !$this->autoQuote
        );
    }

    public function testCloneValuesExpression(): void
    {
        $query = new InsertQuery($this->connection);
        $query
            ->insert(['column'])
            ->into('table')
            ->values(['column' => $query->newExpr('value')]);

        $clause = $query->clause('values');
        $clauseClone = (clone $query)->clause('values');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test that epilog() will actually append a string to an insert query
     */
    public function testAppendInsert(): void
    {
        $query = new InsertQuery($this->connection);
        $sql = $query
            ->insert(['id', 'title'])
            ->into('articles')
            ->values([1, 'a title'])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertStringContainsString('INSERT', $sql);
        $this->assertStringContainsString('INTO', $sql);
        $this->assertStringContainsString('VALUES', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    /**
     * Tests that insert query parts get quoted automatically
     */
    public function testQuotingInsert(): void
    {
        $this->connection->getDriver()->enableAutoQuoting(true);
        $query = new InsertQuery($this->connection);
        $sql = $query->insert(['bar', 'baz'])
            ->into('foo')
            ->sql();
        $this->assertQuotedQuery('INSERT INTO <foo> \(<bar>, <baz>\)', $sql);

        $query = new InsertQuery($this->connection);
        $sql = $query->insert([$query->newExpr('bar')])
            ->into('foo')
            ->sql();
        $this->assertQuotedQuery('INSERT INTO <foo> \(\(bar\)\)', $sql);
    }
}
