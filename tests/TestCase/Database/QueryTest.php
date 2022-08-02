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
namespace Cake\Test\TestCase\Database;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\Query\SelectQuery;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Tests Query class
 */
class QueryTest extends TestCase
{
    use QueryAssertsTrait;

    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
        'core.Comments',
        'core.Profiles',
        'core.MenuLinkTrees',
    ];

    /**
     * @var int
     */
    public const ARTICLE_COUNT = 3;
    /**
     * @var int
     */
    public const AUTHOR_COUNT = 4;
    /**
     * @var int
     */
    public const COMMENT_COUNT = 6;

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
     * Tests that empty values don't set where clauses.
     */
    public function testWhereEmptyValues(): void
    {
        $query = new Query($this->connection);
        $query->from('comments')
            ->where('');

        $this->assertCount(0, $query->clause('where'));

        $query->where([]);
        $this->assertCount(0, $query->clause('where'));
    }

    /**
     * Test a basic delete using from()
     */
    public function testDeleteWithFrom(): void
    {
        $query = new Query($this->connection);

        $query->delete()
            ->from('authors')
            ->where('1 = 1');

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
        $this->assertSame(self::AUTHOR_COUNT, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Test delete with from and alias.
     */
    public function testDeleteWithAliasedFrom(): void
    {
        $query = new Query($this->connection);

        $query->delete()
            ->from(['a ' => 'authors'])
            ->where(['a.id !=' => 99]);

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors> WHERE <id> != :c0', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
        $this->assertSame(self::AUTHOR_COUNT, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Test a basic delete with no from() call.
     */
    public function testDeleteNoFrom(): void
    {
        $query = new Query($this->connection);

        $query->delete('authors')
            ->where('1 = 1');

        $result = $query->sql();
        $this->assertQuotedQuery('DELETE FROM <authors>', $result, !$this->autoQuote);

        $result = $query->execute();
        $this->assertInstanceOf(StatementInterface::class, $result);
        $this->assertSame(self::AUTHOR_COUNT, $result->rowCount());
        $result->closeCursor();
    }

    /**
     * Tests that delete queries that contain joins do trigger a notice,
     * warning about possible incompatibilities with aliases being removed
     * from the conditions.
     */
    public function testDeleteRemovingAliasesCanBreakJoins(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.');
        $query = new Query($this->connection);

        $query
            ->delete('authors')
            ->from(['a ' => 'authors'])
            ->innerJoin('articles')
            ->where(['a.id' => 1]);

        $query->sql();
    }

    /**
     * Tests that aliases are stripped from delete query conditions
     * where possible.
     */
    public function testDeleteStripAliasesFromConditions(): void
    {
        $query = new Query($this->connection);

        $query
            ->delete()
            ->from(['a' => 'authors'])
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
            'DELETE FROM <authors> WHERE \(' .
                '<id> = :c0 OR \(<name>\) IS NULL OR \(<email>\) IS NOT NULL OR \(' .
                    '<name> NOT IN \(:c1,:c2\) AND \(' .
                        '<name> = :c3 OR <name> = :c4 OR \(SELECT <e>\.<name> WHERE <e>\.<name> = :c5\)' .
                    '\)' .
                '\)' .
            '\)',
            $query->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Tests that the identifier method creates an expression object.
     */
    public function testIdentifierExpression(): void
    {
        $query = new Query($this->connection);
        /** @var \Cake\Database\Expression\IdentifierExpression $identifier */
        $identifier = $query->identifier('foo');

        $this->assertInstanceOf(IdentifierExpression::class, $identifier);
        $this->assertSame('foo', $identifier->getIdentifier());
    }

    /**
     * Tests the interface contract of identifier
     */
    public function testIdentifierInterface(): void
    {
        $query = new Query($this->connection);
        $identifier = $query->identifier('description');

        $this->assertInstanceOf(ExpressionInterface::class, $identifier);
        $this->assertSame('description', $identifier->getIdentifier());

        $identifier->setIdentifier('title');
        $this->assertSame('title', $identifier->getIdentifier());
    }

    /**
     * Test that epilog() will actually append a string to a delete query
     */
    public function testAppendDelete(): void
    {
        $query = new Query($this->connection);
        $sql = $query
            ->delete('articles')
            ->where(['id' => 1])
            ->epilog('RETURNING id')
            ->sql();
        $this->assertStringContainsString('DELETE FROM', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    /**
     * Tests __debugInfo on incomplete query
     */
    public function testDebugInfoIncompleteQuery(): void
    {
        $query = (new Query($this->connection))
            ->from(['articles']);
        $result = $query->__debugInfo();
        $this->assertStringContainsString('incomplete', $result['sql']);
        $this->assertSame([], $result['params']);
    }

    public function testCloneWithExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->with(
                new CommonTableExpression(
                    'cte',
                    new Query($this->connection)
                )
            )
            ->with(function (CommonTableExpression $cte, Query $query) {
                return $cte
                    ->name('cte2')
                    ->query($query);
            });

        $clause = $query->clause('with');
        $clauseClone = (clone $query)->clause('with');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneModifierExpression(): void
    {
        $query = new Query($this->connection);
        $query->modifier($query->newExpr('modifier'));

        $clause = $query->clause('modifier');
        $clauseClone = (clone $query)->clause('modifier');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneFromExpression(): void
    {
        $query = new Query($this->connection);
        $query->from(['alias' => new Query($this->connection)]);

        $clause = $query->clause('from');
        $clauseClone = (clone $query)->clause('from');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneJoinExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->innerJoin(
                ['alias_inner' => new Query($this->connection)],
                ['alias_inner.fk = parent.pk']
            )
            ->leftJoin(
                ['alias_left' => new Query($this->connection)],
                ['alias_left.fk = parent.pk']
            )
            ->rightJoin(
                ['alias_right' => new Query($this->connection)],
                ['alias_right.fk = parent.pk']
            );

        $clause = $query->clause('join');
        $clauseClone = (clone $query)->clause('join');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value['table'], $clauseClone[$key]['table']);
            $this->assertNotSame($value['table'], $clauseClone[$key]['table']);

            $this->assertEquals($value['conditions'], $clauseClone[$key]['conditions']);
            $this->assertNotSame($value['conditions'], $clauseClone[$key]['conditions']);
        }
    }

    public function testCloneWhereExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->where($query->newExpr('where'))
            ->where(['field' => $query->newExpr('where')]);

        $clause = $query->clause('where');
        $clauseClone = (clone $query)->clause('where');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOrderExpression(): void
    {
        $query = new Query($this->connection);
        $query
            ->order($query->newExpr('order'))
            ->orderAsc($query->newExpr('order_asc'))
            ->orderDesc($query->newExpr('order_desc'));

        $clause = $query->clause('order');
        $clauseClone = (clone $query)->clause('order');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneLimitExpression(): void
    {
        $query = new Query($this->connection);
        $query->limit($query->newExpr('1'));

        $clause = $query->clause('limit');
        $clauseClone = (clone $query)->clause('limit');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOffsetExpression(): void
    {
        $query = new Query($this->connection);
        $query->offset($query->newExpr('1'));

        $clause = $query->clause('offset');
        $clauseClone = (clone $query)->clause('offset');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneEpilogExpression(): void
    {
        $query = new Query($this->connection);
        $query->epilog($query->newExpr('epilog'));

        $clause = $query->clause('epilog');
        $clauseClone = (clone $query)->clause('epilog');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test use of modifiers in a DELETE query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testDeleteModifiers(): void
    {
        $query = new Query($this->connection);
        $result = $query->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier('IGNORE');
        $this->assertQuotedQuery(
            'DELETE IGNORE FROM <authors> WHERE 1 = 1',
            $result->sql(),
            !$this->autoQuote
        );

        $query = new Query($this->connection);
        $result = $query->delete()
            ->from('authors')
            ->where('1 = 1')
            ->modifier(['IGNORE', 'QUICK']);
        $this->assertQuotedQuery(
            'DELETE IGNORE QUICK FROM <authors> WHERE 1 = 1',
            $result->sql(),
            !$this->autoQuote
        );
    }

    /**
     * Test getValueBinder()
     */
    public function testGetValueBinder(): void
    {
        $query = new Query($this->connection);

        $this->assertInstanceOf('Cake\Database\ValueBinder', $query->getValueBinder());
    }

    /**
     * Test that reading an undefined clause does not emit an error.
     */
    public function testClauseUndefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'nope\' clause is not defined. Valid clauses are: delete, update');
        $query = new Query($this->connection);
        $this->assertEmpty($query->clause('where'));
        $query->clause('nope');
    }
}
