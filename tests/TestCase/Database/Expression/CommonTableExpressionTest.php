<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Expression;

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
    public function testGetSetName(): void
    {
        $expression = new CommonTableExpression('cte', 'SELECT 1');
        $this->assertEquals('cte', $expression->getName());
    }

    public function testGetSetFields(): void
    {
        $expression = new CommonTableExpression('cte', 'SELECT 1');
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

        $expression = new CommonTableExpression('cte', 'SELECT 1');
        $expression->setFields(['col1', 123]);
    }

    public function testGetSetModifiers(): void
    {
        $expression = new CommonTableExpression('cte', 'SELECT 1');
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

        $expression = new CommonTableExpression('cte', 'SELECT 1');
        $expression->setModifiers(['FOO', 123]);
    }

    public function testGetSetQuery(): void
    {
        $connection = ConnectionManager::get('test');

        $expression = new CommonTableExpression('cte', 'SELECT 1');
        $this->assertEquals('SELECT 1', $expression->getQuery());

        $expression->setQuery('SELECT 1, 2');
        $this->assertEquals('SELECT 1, 2', $expression->getQuery());

        $query = $connection->newQuery()->select([1, 2]);
        $expression->setQuery($query);
        $this->assertSame($query, $expression->getQuery());
    }

    public function testSetQueryWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$query` argument must be either an instance of `Cake\Database\ExpressionInterface`, ' .
            'or a string, `integer` given.'
        );

        $expression = new CommonTableExpression('cte', 'SELECT 1');
        $expression->setQuery(123);
    }

    public function testGetSetRecursive(): void
    {
        $expression = new CommonTableExpression('cte', 'SELECT 1');
        $this->assertFalse($expression->isRecursive());

        $expression->setRecursive(true);
        $this->assertTrue($expression->isRecursive());
    }

    public function testConstructWithInvalidQueryType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$query` argument must be either an instance of `Cake\Database\ExpressionInterface`, ' .
            'or a string, `integer` given.'
        );

        new CommonTableExpression('cte', 123);
    }

    public function testSqlWithQueryAsExpression(): void
    {
        $connection = ConnectionManager::get('test');

        $expression = new CommonTableExpression('cte', $connection->newQuery()->select(['col']));

        $this->assertEqualsSql(
            'cte AS (SELECT col)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithQueryAsString(): void
    {
        $expression = new CommonTableExpression('cte', 'SELECT col');

        $this->assertEquals(
            'cte AS (SELECT col)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithFieldsAsStrings(): void
    {
        $expression = (new CommonTableExpression('cte', 'SELECT 1, 2'))
            ->setFields(['col1', 'col2']);

        $this->assertEquals(
            'cte(col1, col2) AS (SELECT 1, 2)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithFieldsAsExpressions(): void
    {
        $expression = (new CommonTableExpression('cte', 'SELECT 1, 2'))
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
        $expression = (new CommonTableExpression('cte', 'SELECT col'))
            ->setModifiers(['NOT MATERIALIZED']);

        $this->assertEquals(
            'cte AS NOT MATERIALIZED (SELECT col)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithModifiersAsExpressions(): void
    {
        $expression = (new CommonTableExpression('cte', 'SELECT col'))
            ->setModifiers([new QueryExpression('NOT MATERIALIZED')]);

        $this->assertEquals(
            'cte AS NOT MATERIALIZED (SELECT col)',
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
