<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\WithExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;

class WithExpressionTest extends TestCase
{
    public function testGetSetKeywords(): void
    {
        $expression = new WithExpression();
        $this->assertTrue($expression->isKeywordsEnabled());

        $this->assertSame($expression, $expression->enableKeywords(false));
        $this->assertFalse($expression->isKeywordsEnabled());
        $this->assertSame($expression, $expression->enableKeywords(true));
        $this->assertTrue($expression->isKeywordsEnabled());

        $expression->disableKeywords();
        $this->assertFalse($expression->isKeywordsEnabled());
    }

    public function testGetSetExpressions(): void
    {
        $expression = new WithExpression();
        $this->assertEmpty($expression->getExpressions());

        $expressions = [
            new CommonTableExpression('cte1', 'SELECT col'),
            new CommonTableExpression('cte2', 'SELECT col'),
        ];
        $expression->setExpressions($expressions);

        $this->assertSame($expressions, $expression->getExpressions());
    }

    public function testSetExpressionsWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$expressions` argument must contain only instances of ' .
            '`Cake\Database\Expression\CommonTableExpression`, `integer` given at index `1`.'
        );

        $expression = new WithExpression();
        $this->assertEmpty($expression->getExpressions());

        $expressions = [
            new CommonTableExpression('cte1', 'SELECT col'),
            123,
        ];
        $expression->setExpressions($expressions);

        $this->assertSame($expressions, $expression->getExpressions());
    }

    public function testConstructWithExpressions(): void
    {
        $expression = new WithExpression([
            new CommonTableExpression('cte1', 'SELECT col'),
            new CommonTableExpression('cte2', 'SELECT col'),
        ]);

        $this->assertEquals(
            'WITH cte1 AS (SELECT col), cte2 AS (SELECT col)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testConstructExpressionAliasesMustBeUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A common table expression with the name `cte` already exists.');

        new WithExpression([
            new CommonTableExpression('cte', 'SELECT col'),
            new CommonTableExpression('cte', 'SELECT col'),
        ]);
    }

    public function testConstructWithInvalidExpressionsType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$expressions` argument must contain only instances of ' .
            '`Cake\Database\Expression\CommonTableExpression`, `integer` given at index `1`.'
        );

        new WithExpression([
            new CommonTableExpression('cte1', 'SELECT col'),
            123,
        ]);
    }

    public function testHasRecursiveExpressions(): void
    {
        $expression = new WithExpression();
        $expression->addExpression(
            new CommonTableExpression('cte1', 'SELECT col')
        );

        $this->assertFalse($expression->hasRecursiveExpressions());

        $expression->addExpression(
            (new CommonTableExpression('cte2', 'SELECT col'))
                ->setFields(['field'])
                ->setRecursive(true)
        );

        $this->assertTrue($expression->hasRecursiveExpressions());
    }

    public function testAddExpressionAliasesMustBeUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A common table expression with the name `cte` already exists.');

        $expression = new WithExpression();
        $expression
            ->addExpression(
                new CommonTableExpression('cte', 'SELECT col')
            )
            ->addExpression(
                new CommonTableExpression('cte', 'SELECT col')
            );
    }

    public function testSqlWithNoExpressions(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot compile WITH clause with no expressions.');

        $expression = new WithExpression();
        $expression->sql(new ValueBinder());
    }

    public function testSqlWithNonRecursiveExpressions(): void
    {
        $expression = new WithExpression();
        $expression
            ->addExpression(
                new CommonTableExpression('cte1', 'SELECT col')
            )
            ->addExpression(
                new CommonTableExpression('cte2', 'SELECT col')
            );

        $this->assertEquals(
            'WITH cte1 AS (SELECT col), cte2 AS (SELECT col)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithRecursiveExpressions(): void
    {
        $expression = new WithExpression();
        $expression
            ->addExpression(
                (new CommonTableExpression('cte1', 'SELECT col'))
                    ->setFields(['field'])
                    ->setRecursive(true)
            )
            ->addExpression(
                (new CommonTableExpression('cte2', 'SELECT col'))
                    ->setFields(['field'])
                    ->setRecursive(true)
            );

        $this->assertEquals(
            'WITH RECURSIVE cte1(field) AS (SELECT col), cte2(field) AS (SELECT col)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testSqlWithRecursiveAndNonRecursiveExpressions(): void
    {
        $expression = new WithExpression();
        $expression
            ->addExpression(
                (new CommonTableExpression('cte1', 'SELECT col'))
                    ->setFields(['field'])
                    ->setRecursive(true)
            )
            ->addExpression(
                new CommonTableExpression('cte2', 'SELECT col')
            );

        $this->assertEquals(
            'WITH RECURSIVE cte1(field) AS (SELECT col), cte2 AS (SELECT col)',
            $expression->sql(new ValueBinder())
        );
    }

    public function testTraverse(): void
    {
        $cte1Identifier = new IdentifierExpression('col');
        $cte2Identifier = new IdentifierExpression('col');

        $cte1 = (new CommonTableExpression('cte1', 'SELECT col'))
            ->setFields([$cte1Identifier]);
        $cte2 = (new CommonTableExpression('cte2', 'SELECT col'))
            ->setFields([$cte2Identifier]);

        $expression = new WithExpression([$cte1, $cte2]);

        $expressions = [];
        $expression->traverse(function ($expression) use (&$expressions) {
            $expressions[] = $expression;
        });

        $this->assertSame([$cte1, $cte1Identifier, $cte2, $cte2Identifier], $expressions);
    }

    public function testClone(): void
    {
        $cte1 = (new CommonTableExpression('cte1', 'SELECT 1'));
        $cte2 = (new CommonTableExpression('cte2', 'SELECT 1'))->setRecursive(true);
        $expression = new WithExpression([$cte1, $cte2]);

        $clone = clone $expression;

        $this->assertInstanceOf(WithExpression::class, $clone);
        $this->assertTrue($clone->isKeywordsEnabled());
        $this->assertTrue($clone->hasRecursiveExpressions());

        $this->assertCount(2, $clone->getExpressions());

        $this->assertInstanceOf(CommonTableExpression::class, $clone->getExpressions()[0]);
        $this->assertNotSame($cte1, $clone->getExpressions()[0]);
        $this->assertEquals($cte1->getName(), $clone->getExpressions()[0]->getName());
        $this->assertEquals($cte1->getQuery(), $clone->getExpressions()[0]->getQuery());

        $this->assertInstanceOf(CommonTableExpression::class, $clone->getExpressions()[1]);
        $this->assertNotSame($cte2, $clone->getExpressions()[1]);
        $this->assertEquals($cte2->getName(), $clone->getExpressions()[1]->getName());
        $this->assertEquals($cte2->getQuery(), $clone->getExpressions()[1]->getQuery());
    }
}
