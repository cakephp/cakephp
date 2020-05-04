<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Exception as DatabaseException;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

class CommonTableExpressionTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->connection);
    }

    public function testConstructWithNoArguments()
    {
        $expression = new CommonTableExpression();

        $this->assertNull($expression->getName());
        $this->assertEmpty($expression->getFields());
        $this->assertEmpty($expression->getModifiers());
        $this->assertNull($expression->getQuery());
    }

    public function testGetSetName(): void
    {
        $expression = new CommonTableExpression('cte', $this->connection->newQuery()->select(1));
        $this->assertEquals('cte', $expression->getName());

        $expression->setName('other');
        $this->assertEquals('other', $expression->getName());
    }

    public function testGetSetFields(): void
    {
        $expression = new CommonTableExpression('cte', $this->connection->newQuery()->select(1));
        $this->assertEmpty($expression->getFields());

        $expression->setFields(['col1', 'col2']);
        $this->assertEquals(
            [new IdentifierExpression('col1'), new IdentifierExpression('col2')],
            $expression->getFields()
        );
    }

    public function testSetFieldsWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$fields` argument must contain only instances of `Cake\Database\ExpressionInterface`, ' .
            'or strings, `integer` given at index `1`.'
        );

        $expression = new CommonTableExpression('cte', $this->connection->newQuery()->select(1));
        $expression->setFields(['col1', 123]);
    }

    public function testGetSetModifiers(): void
    {
        $expression = new CommonTableExpression('cte', $this->connection->newQuery()->select(1));
        $this->assertEmpty($expression->getModifiers());

        $expression->setModifiers(['FOO', 'BAR']);
        $this->assertEquals(['FOO', 'BAR'], $expression->getModifiers());
    }

    public function testModifiersFieldsWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$modifiers` argument must contain only instances of `Cake\Database\ExpressionInterface`, ' .
            'or strings, `integer` given at index `1`.'
        );

        $expression = new CommonTableExpression('cte', $this->connection->newQuery()->select(1));
        $expression->setModifiers(['FOO', 123]);
    }

    public function testGetSetQuery(): void
    {
        $connection = ConnectionManager::get('test');

        $query = $this->connection->newQuery()->select(1);
        $expression = new CommonTableExpression('cte', $query);
        $this->assertSame($query, $expression->getQuery());

        $query = $connection->newQuery()->select([1, 2]);
        $expression->setQuery($query);
        $this->assertSame($query, $expression->getQuery());
    }

    public function testGetSetRecursive(): void
    {
        $expression = new CommonTableExpression('cte', $this->connection->newQuery()->select(1));
        $this->assertFalse($expression->isRecursive());

        $expression->setRecursive(true);
        $this->assertTrue($expression->isRecursive());
    }

    public function testSqlWithNoName()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot generate SQL for common table expressions that have no name.');

        $expression = new CommonTableExpression();
        $expression->sql(new ValueBinder());
    }

    public function testSqlWithNoQuery()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot generate SQL for common table expressions that have no query.');

        $expression = new CommonTableExpression('cte');
        $expression->sql(new ValueBinder());
    }

    public function testSqlWithQueryAsExpression(): void
    {
        $expression = new CommonTableExpression('cte', $this->connection->newQuery()->select(1));

        $this->assertEqualsSql(
            'cte AS (SELECT 1)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithQueryAsCustomExpression(): void
    {
        $expression = new CommonTableExpression('cte', new QueryExpression('SELECT 1'));

        $this->assertEqualsSql(
            'cte AS (SELECT 1)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithFieldsAsStrings(): void
    {
        $expression = (new CommonTableExpression('cte', $this->connection->newQuery()->select([1, 2])))
            ->setFields(['col1', 'col2']);

        $this->assertEquals(
            'cte(col1, col2) AS (SELECT 1, 2)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithFieldsAsExpressions(): void
    {
        $expression = (new CommonTableExpression('cte', $this->connection->newQuery()->select([1, 2])))
            ->setFields([
                new IdentifierExpression('col1'),
                new IdentifierExpression('col2'),
            ]);

        $this->assertEquals(
            'cte(col1, col2) AS (SELECT 1, 2)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithModifiersAsStrings(): void
    {
        $expression = (new CommonTableExpression('cte', $this->connection->newQuery()->select(1)))
            ->setModifiers(['NOT MATERIALIZED']);

        $this->assertEquals(
            'cte AS NOT MATERIALIZED (SELECT 1)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithModifiersAsExpressions(): void
    {
        $expression = (new CommonTableExpression('cte', $this->connection->newQuery()->select(1)))
            ->setModifiers([new QueryExpression('NOT MATERIALIZED')]);

        $this->assertEquals(
            'cte AS NOT MATERIALIZED (SELECT 1)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testTraverse(): void
    {
        $query = new QueryExpression('SELECT 1');
        $identifier = new IdentifierExpression('col');
        $modifier = new QueryExpression('NOT MATERIALIZED');
        $modifierWrapper = new QueryExpression($modifier);

        $expression = (new CommonTableExpression('cte', $query))
            ->setFields([$identifier])
            ->setModifiers([$modifierWrapper]);

        $expressions = [];
        $expression->traverse(function ($expression) use (&$expressions) {
            $expressions[] = $expression;
        });

        $this->assertSame(
            [$identifier, $modifierWrapper, $modifier, $query],
            $expressions
        );
    }

    public function testClone(): void
    {
        $connection = ConnectionManager::get('test');

        $query = $connection->newQuery()->select(1);
        $fieldExpression = new IdentifierExpression('col2');
        $modifierExpression = new QueryExpression('BAR');

        $expression = (new CommonTableExpression('cte', $query))
            ->setFields([
                'col1',
                $fieldExpression,
            ])
            ->setModifiers([
                'FOO',
                $modifierExpression,
            ])
            ->setRecursive(true);

        $clone = clone $expression;

        $this->assertInstanceOf(CommonTableExpression::class, $clone);
        $this->assertNotSame($clone, $expression);

        $this->assertEquals('cte', $clone->getName());

        $this->assertCount(2, $clone->getFields());
        $this->assertInstanceOf(IdentifierExpression::class, $clone->getFields()[0]);
        $this->assertEquals('col1', $clone->getFields()[0]->getIdentifier());
        $this->assertInstanceOf(IdentifierExpression::class, $clone->getFields()[1]);
        $this->assertNotSame($fieldExpression, $clone->getFields()[1]);
        $this->assertEquals('col2', $clone->getFields()[1]->getIdentifier());

        $this->assertCount(2, $clone->getModifiers());
        $this->assertEquals('FOO', $clone->getModifiers()[0]);
        $this->assertInstanceOf(QueryExpression::class, $clone->getModifiers()[1]);
        $this->assertNotSame($fieldExpression, $clone->getModifiers()[1]);
        $this->assertEquals('BAR', $clone->getModifiers()[1]->sql(new ValueBinder()));

        $this->assertInstanceOf(Query::class, $clone->getQuery());
        $this->assertNotSame($query, $clone->getQuery());
        $this->assertEquals('SELECT 1', $clone->getQuery()->sql(new ValueBinder()));
    }
}
