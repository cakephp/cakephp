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
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\OrderClauseExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\WindowExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests WindowExpression class
 */
class WindowExpressionTest extends TestCase
{
    /**
     * Tests an empty window expression
     *
     * @return void
     */
    public function testEmptyWindow()
    {
        $w = new WindowExpression();
        $this->assertSame('', $w->sql(new ValueBinder()));

        $w->partition('')->order([]);
        $this->assertSame('', $w->sql(new ValueBinder()));
    }

    /**
     * Tests windows with partitions
     *
     * @return void
     */
    public function testPartitions()
    {
        $w = (new WindowExpression())->partition('test');
        $this->assertEqualsSql(
            'PARTITION BY test',
            $w->sql(new ValueBinder())
        );

        $w->partition(new IdentifierExpression('identifier'));
        $this->assertEqualsSql(
            'PARTITION BY test, identifier',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->partition(new AggregateExpression('MyAggregate', ['param']));
        $this->assertEqualsSql(
            'PARTITION BY MyAggregate(:param0)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->partition(function (QueryExpression $expr) {
            return $expr->add(new AggregateExpression('MyAggregate', ['param']));
        });
        $this->assertEqualsSql(
            'PARTITION BY MyAggregate(:param0)',
            $w->sql(new ValueBinder())
        );
    }

    /**
     * Tests windows with order by
     *
     * @return void
     */
    public function testOrder()
    {
        $w = (new WindowExpression())->order('test');
        $this->assertEqualsSql(
            'ORDER BY test',
            $w->sql(new ValueBinder())
        );

        $w->order(['test2' => 'DESC']);
        $this->assertEqualsSql(
            'ORDER BY test, test2 DESC',
            $w->sql(new ValueBinder())
        );

        $w->partition('test');
        $this->assertEqualsSql(
            'PARTITION BY test ORDER BY test, test2 DESC',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())
            ->order(function () {
                return 'test';
            })
            ->order(function (QueryExpression $expr) {
                return [$expr->add('test2'), new OrderClauseExpression(new IdentifierExpression('test3'), 'DESC')];
            });
        $this->assertEqualsSql(
            'ORDER BY test, test2, test3 DESC',
            $w->sql(new ValueBinder())
        );
    }

    /**
     * Tests windows with range frames
     *
     * @return void
     */
    public function testRange()
    {
        $w = (new WindowExpression())->range(null);
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(0);
        $this->assertEqualsSql(
            'RANGE BETWEEN CURRENT ROW AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(2);
        $this->assertEqualsSql(
            'RANGE BETWEEN 2 PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null, null);
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(0, null);
        $this->assertEqualsSql(
            'RANGE BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(0, 0);
        $this->assertEqualsSql(
            'RANGE BETWEEN CURRENT ROW AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(1, 2);
        $this->assertEqualsSql(
            'RANGE BETWEEN 1 PRECEDING AND 2 FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range("'1 day'", "'10 days'");
        $this->assertRegExpSql(
            "RANGE BETWEEN '1 day' PRECEDING AND '10 days' FOLLOWING",
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(new QueryExpression("'1 day'"), new QueryExpression("'10 days'"));
        $this->assertRegExpSql(
            "RANGE BETWEEN '1 day' PRECEDING AND '10 days' FOLLOWING",
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->frame(
            WindowExpression::RANGE,
            2,
            WindowExpression::PRECEDING,
            1,
            WindowExpression::PRECEDING
        );
        $this->assertEqualsSql(
            'RANGE BETWEEN 2 PRECEDING AND 1 PRECEDING',
            $w->sql(new ValueBinder())
        );
    }

    /**
     * Tests windows with rows frames
     *
     * @return void
     */
    public function testRows()
    {
        $w = (new WindowExpression())->rows(null);
        $this->assertEqualsSql(
            'ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0);
        $this->assertEqualsSql(
            'ROWS BETWEEN CURRENT ROW AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(2);
        $this->assertEqualsSql(
            'ROWS BETWEEN 2 PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(null, null);
        $this->assertEqualsSql(
            'ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0, null);
        $this->assertEqualsSql(
            'ROWS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0, 0);
        $this->assertEqualsSql(
            'ROWS BETWEEN CURRENT ROW AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(1, 2);
        $this->assertEqualsSql(
            'ROWS BETWEEN 1 PRECEDING AND 2 FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->frame(
            WindowExpression::ROWS,
            2,
            WindowExpression::PRECEDING,
            1,
            WindowExpression::PRECEDING
        );
        $b = new ValueBinder();
        $this->assertEqualsSql(
            'ROWS BETWEEN 2 PRECEDING AND 1 PRECEDING',
            $w->sql($b)
        );
    }

    /**
     * Tests windows with groups frames
     *
     * @return void
     */
    public function testGroups()
    {
        $w = (new WindowExpression())->groups(null);
        $this->assertEqualsSql(
            'GROUPS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0);
        $this->assertEqualsSql(
            'GROUPS BETWEEN CURRENT ROW AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(2);
        $this->assertEqualsSql(
            'GROUPS BETWEEN 2 PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(null, null);
        $this->assertEqualsSql(
            'GROUPS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0, null);
        $this->assertEqualsSql(
            'GROUPS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0, 0);
        $this->assertEqualsSql(
            'GROUPS BETWEEN CURRENT ROW AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(1, 2);
        $this->assertEqualsSql(
            'GROUPS BETWEEN 1 PRECEDING AND 2 FOLLOWING',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->frame(
            WindowExpression::GROUPS,
            2,
            WindowExpression::PRECEDING,
            1,
            WindowExpression::PRECEDING
        );
        $b = new ValueBinder();
        $this->assertEqualsSql(
            'GROUPS BETWEEN 2 PRECEDING AND 1 PRECEDING',
            $w->sql($b)
        );
    }

    /**
     * Tests windows with frame exclusion
     *
     * @return void
     */
    public function testExclusion()
    {
        $w = (new WindowExpression())->excludeCurrent();
        $this->assertEqualsSql(
            '',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeGroup();
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE GROUP',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeTies();
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE TIES',
            $w->sql(new ValueBinder())
        );
    }

    /**
     * Tests windows with partition, order and frames
     *
     * @return void
     */
    public function testCombined()
    {
        $w = (new WindowExpression())->partition('test')->range(null);
        $this->assertEqualsSql(
            'PARTITION BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->order('test')->range(null);
        $this->assertEqualsSql(
            'ORDER BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->partition('test')->order('test')->range(null);
        $this->assertEqualsSql(
            'PARTITION BY test ORDER BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->partition('test')->order('test')->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'PARTITION BY test ORDER BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE CURRENT ROW',
            $w->sql(new ValueBinder())
        );
    }

    /**
     * Tests named windows
     *
     * @return void
     */
    public function testNamedWindow()
    {
        $w = new WindowExpression();
        $this->assertFalse($w->isNamedOnly());

        $w->name('name');
        $this->assertTrue($w->isNamedOnly());
        $this->assertEqualsSql(
            'name',
            $w->sql(new ValueBinder())
        );

        $w->name('new_name');
        $this->assertEqualsSql(
            'new_name',
            $w->sql(new ValueBinder())
        );

        $w->order('test');
        $this->assertFalse($w->isNamedOnly());
        $this->assertEqualsSql(
            'new_name ORDER BY test',
            $w->sql(new ValueBinder())
        );
    }

    /**
     * Tests traversing window expressions.
     *
     * @return void
     */
    public function testTraverse()
    {
        $w = (new WindowExpression('test1'))
            ->partition('test2')
            ->order('test3')
            ->range(new QueryExpression("'1 day'"));

        $expressions = [];
        $w->traverse(function ($expression) use (&$expressions) {
            $expressions[] = $expression;
        });

        $this->assertEquals(new IdentifierExpression('test1'), $expressions[0]);
        $this->assertEquals(new IdentifierExpression('test2'), $expressions[1]);
        $this->assertEquals((new OrderByExpression())->add('test3'), $expressions[2]);
        $this->assertEquals(new QueryExpression("'1 day'"), $expressions[3]);

        $w->range(new QueryExpression("'1 day'"), new QueryExpression("'10 days'"));

        $expressions = [];
        $w->traverse(function ($expression) use (&$expressions) {
            $expressions[] = $expression;
        });

        $this->assertEquals(new QueryExpression("'1 day'"), $expressions[3]);
        $this->assertEquals(new QueryExpression("'10 days'"), $expressions[4]);
    }

    /**
     * Tests cloning window expressions
     *
     * @return void
     */
    public function testCloning()
    {
        $w1 = (new WindowExpression())->name('test');
        $w2 = (clone $w1)->name('test2');
        $this->assertNotSame($w1->sql(new ValueBinder()), $w2->sql(new ValueBinder()));

        $w1 = (new WindowExpression())->partition('test');
        $w2 = (clone $w1)->partition('new');
        $this->assertNotSame($w1->sql(new ValueBinder()), $w2->sql(new ValueBinder()));

        $w1 = (new WindowExpression())->order('test');
        $w2 = (clone $w1)->order('new');
        $this->assertNotSame($w1->sql(new ValueBinder()), $w2->sql(new ValueBinder()));

        $w1 = (new WindowExpression())->rows(0, null);
        $w2 = (clone $w1)->rows(0, 0);
        $this->assertNotSame($w1->sql(new ValueBinder()), $w2->sql(new ValueBinder()));
    }
}
