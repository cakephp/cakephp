<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Connection;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
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
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $autoQuote;

    protected Query $query;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->autoQuote = $this->connection->getDriver()->isAutoQuotingEnabled();
        $this->query = $this->newQuery();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->connection->getDriver()->enableAutoQuoting($this->autoQuote);
        unset($this->query);
        unset($this->connection);
    }

    public function testConnectionRoles(): void
    {
        // Defaults to write role
        $this->assertSame(Connection::ROLE_WRITE, $this->connection->insertQuery()->getConnectionRole());

        $selectQuery = $this->connection->selectQuery();
        $this->assertSame(Connection::ROLE_WRITE, $selectQuery->getConnectionRole());

        // Can set read role for select queries
        $this->assertSame(Connection::ROLE_READ, $selectQuery->setConnectionRole(Connection::ROLE_READ)->getConnectionRole());

        // Can set read role for select queries
        $this->assertSame(Connection::ROLE_READ, $selectQuery->useReadRole()->getConnectionRole());

        // Can set write role for select queries
        $this->assertSame(Connection::ROLE_WRITE, $selectQuery->useWriteRole()->getConnectionRole());
    }

    protected function newQuery()
    {
        return new class ($this->connection) extends Query
        {
        };
    }

    /**
     * Tests that empty values don't set where clauses.
     */
    public function testWhereEmptyValues(): void
    {
        $this->query->from('comments')
            ->where('');

        $this->assertCount(0, $this->query->clause('where'));

        $this->query->where([]);
        $this->assertCount(0, $this->query->clause('where'));
    }

    /**
     * Tests that the identifier method creates an expression object.
     */
    public function testIdentifierExpression(): void
    {
        /** @var \Cake\Database\Expression\IdentifierExpression $identifier */
        $identifier = $this->query->identifier('foo');

        $this->assertInstanceOf(IdentifierExpression::class, $identifier);
        $this->assertSame('foo', $identifier->getIdentifier());
    }

    /**
     * Tests the interface contract of identifier
     */
    public function testIdentifierInterface(): void
    {
        $identifier = $this->query->identifier('description');

        $this->assertInstanceOf(ExpressionInterface::class, $identifier);
        $this->assertSame('description', $identifier->getIdentifier());

        $identifier->setIdentifier('title');
        $this->assertSame('title', $identifier->getIdentifier());
    }

    /**
     * Tests __debugInfo on incomplete query
     */
    public function testDebugInfoIncompleteQuery(): void
    {
        $this->query = $this->newQuery()
            ->from(['articles']);
        $result = $this->query->__debugInfo();
        $this->assertStringContainsString('incomplete', $result['sql']);
        $this->assertSame([], $result['params']);
    }

    public function testCloneWithExpression(): void
    {
        $this->query
            ->with(
                new CommonTableExpression(
                    'cte',
                    $this->newQuery()
                )
            )
            ->with(function (CommonTableExpression $cte, Query $query) {
                return $cte
                    ->name('cte2')
                    ->query($query);
            });

        $clause = $this->query->clause('with');
        $clauseClone = (clone $this->query)->clause('with');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneModifierExpression(): void
    {
        $this->query->modifier($this->query->newExpr('modifier'));

        $clause = $this->query->clause('modifier');
        $clauseClone = (clone $this->query)->clause('modifier');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneFromExpression(): void
    {
        $this->query->from(['alias' => $this->newQuery()]);

        $clause = $this->query->clause('from');
        $clauseClone = (clone $this->query)->clause('from');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneJoinExpression(): void
    {
        $this->query
            ->innerJoin(
                ['alias_inner' => $this->newQuery()],
                ['alias_inner.fk = parent.pk']
            )
            ->leftJoin(
                ['alias_left' => $this->newQuery()],
                ['alias_left.fk = parent.pk']
            )
            ->rightJoin(
                ['alias_right' => $this->newQuery()],
                ['alias_right.fk = parent.pk']
            );

        $clause = $this->query->clause('join');
        $clauseClone = (clone $this->query)->clause('join');

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
        $this->query
            ->where($this->query->newExpr('where'))
            ->where(['field' => $this->query->newExpr('where')]);

        $clause = $this->query->clause('where');
        $clauseClone = (clone $this->query)->clause('where');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOrderExpression(): void
    {
        $this->query
            ->orderBy($this->query->newExpr('order'))
            ->orderByAsc($this->query->newExpr('order_asc'))
            ->orderByDesc($this->query->newExpr('order_desc'));

        $clause = $this->query->clause('order');
        $clauseClone = (clone $this->query)->clause('order');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneLimitExpression(): void
    {
        $this->query->limit($this->query->newExpr('1'));

        $clause = $this->query->clause('limit');
        $clauseClone = (clone $this->query)->clause('limit');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOffsetExpression(): void
    {
        $this->query->offset($this->query->newExpr('1'));

        $clause = $this->query->clause('offset');
        $clauseClone = (clone $this->query)->clause('offset');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneEpilogExpression(): void
    {
        $this->query->epilog($this->query->newExpr('epilog'));

        $clause = $this->query->clause('epilog');
        $clauseClone = (clone $this->query)->clause('epilog');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test getValueBinder()
     */
    public function testGetValueBinder(): void
    {
        $this->assertInstanceOf('Cake\Database\ValueBinder', $this->query->getValueBinder());
    }

    /**
     * Test that reading an undefined clause does not emit an error.
     */
    public function testClauseUndefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `nope` clause is not defined. Valid clauses are: `comment`, `delete`, `update`');

        $this->assertEmpty($this->query->clause('where'));
        $this->query->clause('nope');
    }
}
