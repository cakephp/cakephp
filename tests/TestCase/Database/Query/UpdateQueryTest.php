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

use Cake\Database\Driver\Mysql;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\UpdateQuery;
use Cake\Database\Statement\Statement;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\Test\TestCase\Database\QueryAssertsTrait;
use Cake\TestSuite\TestCase;
use DateTime;
use PDOStatement;

/**
 * Tests UpdateQuery class
 */
class UpdateQueryTest extends TestCase
{
    use QueryAssertsTrait;

    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
        'core.Comments',
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
     * Test a simple update.
     */
    public function testUpdateSimple(): void
    {
        $query = new UpdateQuery($this->connection);
        $query->update('authors')
            ->set('name', 'mark')
            ->where(['id' => 1]);
        $result = $query->sql();
        $this->assertQuotedQuery('UPDATE <authors> SET <name> = :', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertSame(1, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Test update with multiple fields.
     */
    public function testUpdateMultipleFields(): void
    {
        $query = new UpdateQuery($this->connection);
        $query->update('articles')
            ->set('title', 'mark', 'string')
            ->set('body', 'some text', 'string')
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <articles> SET <title> = :c0 , <body> = :c1',
            $result,
            !$this->autoQuote
        );

        $this->assertQuotedQuery(' WHERE <id> = :c2$', $result, !$this->autoQuote);
        $result = $query->execute();
        $this->assertSame(1, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Test updating multiple fields with an array.
     */
    public function testUpdateMultipleFieldsArray(): void
    {
        $query = new UpdateQuery($this->connection);
        $query->update('articles')
            ->set([
                'title' => 'mark',
                'body' => 'some text',
            ], ['title' => 'string', 'body' => 'string'])
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <articles> SET <title> = :c0 , <body> = :c1',
            $result,
            !$this->autoQuote
        );
        $this->assertQuotedQuery('WHERE <id> = :', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertSame(1, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Test updates with an expression.
     */
    public function testUpdateWithExpression(): void
    {
        $query = new UpdateQuery($this->connection);

        $expr = $query->newExpr()->equalFields('article_id', 'user_id');

        $query->update('comments')
            ->set($expr)
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <comments> SET <article_id> = <user_id> WHERE <id> = :',
            $result,
            !$this->autoQuote
        );

        $result = $query->execute();
        $this->assertSame(1, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Tests update with subquery that references itself
     */
    public function testUpdateSubquery(): void
    {
        $this->skipIf($this->connection->getDriver() instanceof Mysql);

        $subquery = new SelectQuery($this->connection);
        $subquery
            ->select('created')
            ->from(['c' => 'comments'])
            ->where(['c.id' => new IdentifierExpression('comments.id')]);

        $query = new UpdateQuery($this->connection);
        $query->update('comments')
            ->set('updated', $subquery);

        $this->assertEqualsSql(
            'UPDATE comments SET updated = (SELECT created FROM comments c WHERE c.id = comments.id)',
            $query->sql(new ValueBinder())
        );

        $result = $query->execute();
        $this->assertSame(6, $result->rowCount());
        $result->closeCursor();

        $result = (new SelectQuery($this->connection))->select(['created', 'updated'])->from('comments')->execute();
        foreach ($result->fetchAll('assoc') as $row) {
            $this->assertSame($row['created'], $row['updated']);
        }
        $result->closeCursor();
    }

    /**
     * Test update with array fields and types.
     */
    public function testUpdateArrayFields(): void
    {
        $query = new UpdateQuery($this->connection);
        $date = new DateTime();
        $query->update('comments')
            ->set(['comment' => 'mark', 'created' => $date], ['created' => 'date'])
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <comments> SET <comment> = :c0 , <created> = :c1',
            $result,
            !$this->autoQuote
        );

        $this->assertQuotedQuery(' WHERE <id> = :c2$', $result, !$this->autoQuote);
        $result = $query->execute();
        $this->assertSame(1, $result->rowCount());

        $query = new SelectQuery($this->connection);
        $result = $query->select('created')->from('comments')->where(['id' => 1])->execute();
        $result = $result->fetchAll('assoc')[0]['created'];
        $this->assertStringStartsWith($date->format('Y-m-d'), $result);
    }

    /**
     * Test update with callable in set
     */
    public function testUpdateSetCallable(): void
    {
        $query = new UpdateQuery($this->connection);
        $date = new DateTime();
        $query->update('comments')
            ->set(function ($exp) use ($date) {
                return $exp
                    ->eq('comment', 'mark')
                    ->eq('created', $date, 'date');
            })
            ->where(['id' => 1]);
        $result = $query->sql();

        $this->assertQuotedQuery(
            'UPDATE <comments> SET <comment> = :c0 , <created> = :c1',
            $result,
            !$this->autoQuote
        );

        $this->assertQuotedQuery(' WHERE <id> = :c2$', $result, !$this->autoQuote);
        $result = $query->execute();
        $this->assertSame(1, $result->rowCount());
    }

    /**
     * Tests that aliases are stripped from update query conditions
     * where possible.
     */
    public function testUpdateStripAliasesFromConditions(): void
    {
        $query = new UpdateQuery($this->connection);

        $query
            ->update('authors')
            ->set(['name' => 'name'])
            ->where([
                'OR' => [
                    'a.id' => 1,
                    'a.name IS' => null,
                    'a.email IS NOT' => null,
                    'AND' => [
                        'b.name NOT IN' => ['foo', 'bar'],
                        'OR' => [
                            $query->newExpr()->eq(new IdentifierExpression('c.name'), 'zap'),
                            'd.name' => 'baz',
                            (new SelectQuery($this->connection))->select(['e.name'])->where(['e.name' => 'oof']),
                        ],
                    ],
                ],
            ]);

        $this->assertQuotedQuery(
            'UPDATE <authors> SET <name> = :c0 WHERE \(' .
                '<id> = :c1 OR \(<name>\) IS NULL OR \(<email>\) IS NOT NULL OR \(' .
                    '<name> NOT IN \(:c2,:c3\) AND \(' .
                        '<name> = :c4 OR <name> = :c5 OR \(SELECT <e>\.<name> WHERE <e>\.<name> = :c6\)' .
                    '\)' .
                '\)' .
            '\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Tests that update queries that contain joins do trigger a notice,
     * warning about possible incompatibilities with aliases being removed
     * from the conditions.
     */
    public function testUpdateRemovingAliasesCanBreakJoins(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.');
        $query = new UpdateQuery($this->connection);

        $query
            ->update('authors')
            ->set(['name' => 'name'])
            ->innerJoin('articles')
            ->where(['a.id' => 1]);

        $query->sql();
    }

    /**
     * Test that epilog() will actually append a string to an update query
     */
    public function testAppendUpdate(): void
    {
        $query = new UpdateQuery($this->connection);
        $sql = $query
            ->update('articles')
            ->set(['title' => 'foo'])
            ->where(['id' => 1])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertStringContainsString('UPDATE', $sql);
        $this->assertStringContainsString('SET', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    /**
     * Performs the simple update query and verifies the row count.
     */
    public function testRowCountAndClose(): void
    {
        $inner = $this->getMockBuilder(PDOStatement::class)->getMock();

        $statementMock = $this->getMockBuilder(Statement::class)
            ->setConstructorArgs([$inner, $this->connection->getDriver()])
            ->onlyMethods(['rowCount', 'closeCursor'])
            ->getMock();

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(500);

        $statementMock->expects($this->once())
            ->method('closeCursor');

        $queryMock = $this->getMockBuilder(UpdateQuery::class)
            ->onlyMethods(['execute'])
            ->setConstructorArgs([$this->connection])
            ->getMock();

        $queryMock->expects($this->once())
            ->method('execute')
            ->willReturn($statementMock);

        $rowCount = $queryMock->update('authors')
            ->set('name', 'mark')
            ->where(['id' => 1])
            ->rowCountAndClose();

        $this->assertEquals(500, $rowCount);
    }

    public function testCloneUpdateExpression(): void
    {
        $query = new UpdateQuery($this->connection);
        $query->update($query->newExpr('update'));

        $clause = $query->clause('update');
        $clauseClone = (clone $query)->clause('update');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneSetExpression(): void
    {
        $query = new UpdateQuery($this->connection);
        $query
            ->update('table')
            ->set(['column' => $query->newExpr('value')]);

        $clause = $query->clause('set');
        $clauseClone = (clone $query)->clause('set');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test use of modifiers in a UPDATE query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testUpdateModifiers(): void
    {
        $query = new UpdateQuery($this->connection);
        $result = $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier('TOP 10 PERCENT');
        $this->assertQuotedQuery(
            'UPDATE TOP 10 PERCENT <authors> SET <name> = :c0',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new UpdateQuery($this->connection);
        $result = $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier(['TOP 10 PERCENT', 'FOO']);
        $this->assertQuotedQuery(
            'UPDATE TOP 10 PERCENT FOO <authors> SET <name> = :c0',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new UpdateQuery($this->connection);
        $result = $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier([$query->newExpr('TOP 10 PERCENT')]);
        $this->assertQuotedQuery(
            'UPDATE TOP 10 PERCENT <authors> SET <name> = :c0',
            $result->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Tests that the JSON type can save and get data symmetrically
     */
    public function testSymmetricJsonType(): void
    {
        $query = new UpdateQuery($this->connection);
        $query
            ->update('comments')
            ->set('comment', ['a' => 'b', 'c' => true], 'json')
            ->where(['id' => 1]);
        $query->execute()->closeCursor();

        $query = new SelectQuery($this->connection);
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id' => 1])
            ->getSelectTypeMap()->setTypes(['comment' => 'json']);

        $result = $query->execute();
        $comment = $result->fetchAll('assoc')[0]['comment'];
        $result->closeCursor();
        $this->assertSame(['a' => 'b', 'c' => true], $comment);
    }
}
