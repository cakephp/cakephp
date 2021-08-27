<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\AggregateExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\WindowExpression;
use Cake\Database\ValueBinder;

/**
 * Tests FunctionExpression class
 */
class AggregateExpressionTest extends FunctionExpressionTest
{
    /**
     * @var string The expression class to test with
     */
    protected $expressionClass = AggregateExpression::class;

    /**
     * Tests annotating an aggregate with an empty window expression
     */
    public function testEmptyWindow(): void
    {
        $f = (new AggregateExpression('MyFunction'))->over();
        $this->assertSame('MyFunction() OVER ()', $f->sql(new ValueBinder()));

        $f = (new AggregateExpression('MyFunction'))->over('name');
        $this->assertEqualsSql(
            'MyFunction() OVER name',
            $f->sql(new ValueBinder())
        );
    }

    /**
     * Tests filter() clauses.
     */
    public function testFilter(): void
    {
        $f = (new AggregateExpression('MyFunction'))->filter(['this' => new IdentifierExpression('that')]);
        $this->assertEqualsSql(
            'MyFunction() FILTER (WHERE this = that)',
            $f->sql(new ValueBinder())
        );

        $f->filter(function (QueryExpression $q) {
            return $q->add(['this2' => new IdentifierExpression('that2')]);
        });
        $this->assertEqualsSql(
            'MyFunction() FILTER (WHERE (this = that AND this2 = that2))',
            $f->sql(new ValueBinder())
        );

        $f->over();
        $this->assertEqualsSql(
            'MyFunction() FILTER (WHERE (this = that AND this2 = that2)) OVER ()',
            $f->sql(new ValueBinder())
        );
    }

    /**
     * Tests WindowInterface calls are passed to the WindowExpression
     */
    public function testWindowInterface(): void
    {
        $f = (new AggregateExpression('MyFunction'))->partition('test');
        $this->assertEqualsSql(
            'MyFunction() OVER (PARTITION BY test)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->order('test');
        $this->assertEqualsSql(
            'MyFunction() OVER (ORDER BY test)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->range(null);
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->range(null, null);
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->rows(null);
        $this->assertEqualsSql(
            'MyFunction() OVER (ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->rows(null, null);
        $this->assertEqualsSql(
            'MyFunction() OVER (ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->groups(null);
        $this->assertEqualsSql(
            'MyFunction() OVER (GROUPS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->groups(null, null);
        $this->assertEqualsSql(
            'MyFunction() OVER (GROUPS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->frame(
            AggregateExpression::RANGE,
            2,
            AggregateExpression::PRECEDING,
            1,
            AggregateExpression::PRECEDING
        );
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN 2 PRECEDING AND 1 PRECEDING)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->excludeCurrent();
        $this->assertEqualsSql(
            'MyFunction() OVER ()',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE CURRENT ROW)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->range(null)->excludeGroup();
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE GROUP)',
            $f->sql(new ValueBinder())
        );

        $f = (new AggregateExpression('MyFunction'))->range(null)->excludeTies();
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE TIES)',
            $f->sql(new ValueBinder())
        );
    }

    /**
     * Tests traversing aggregate expressions.
     */
    public function testTraverse(): void
    {
        $w = (new AggregateExpression('MyFunction'))
            ->filter(['this' => true])
            ->over();

        $expressions = [];
        $w->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals(new QueryExpression(['this' => true]), $expressions[0]);
        $this->assertEquals(new WindowExpression(), $expressions[2]);
    }

    /**
     * Tests cloning aggregate expressions
     */
    public function testCloning(): void
    {
        $a1 = (new AggregateExpression('MyFunction'))->partition('test');
        $a2 = (clone $a1)->partition('new');
        $this->assertNotSame($a1->sql(new ValueBinder()), $a2->sql(new ValueBinder()));
    }
}
