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
use Cake\Database\Expression\WindowExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

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
        $this->assertSame('OVER ()', $w->sql(new ValueBinder()));

        $w->partition('')->order([]);
        $this->assertSame('OVER ()', $w->sql(new ValueBinder()));
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
            'OVER (PARTITION BY test)',
            $w->sql(new ValueBinder())
        );

        $w->partition(new IdentifierExpression('identifier'));
        $this->assertEqualsSql(
            'OVER (PARTITION BY test, identifier)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->partition(new AggregateExpression('MyAggregate', ['param']));
        $this->assertEqualsSql(
            'OVER (PARTITION BY MyAggregate(:param0))',
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
            'OVER (ORDER BY test)',
            $w->sql(new ValueBinder())
        );

        $w->order(['test2' => 'DESC']);
        $this->assertEqualsSql(
            'OVER (ORDER BY test, test2 DESC)',
            $w->sql(new ValueBinder())
        );

        $w->partition('test');
        $this->assertEqualsSql(
            'OVER (PARTITION BY test ORDER BY test, test2 DESC)',
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
            'OVER (RANGE UNBOUNDED PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(0);
        $this->assertEqualsSql(
            'OVER (RANGE CURRENT ROW)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(2);
        $this->assertEqualsSql(
            'OVER (RANGE 2 PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null, null);
        $this->assertEqualsSql(
            'OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(0, null);
        $this->assertEqualsSql(
            'OVER (RANGE BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(0, 0);
        $this->assertEqualsSql(
            'OVER (RANGE BETWEEN CURRENT ROW AND CURRENT ROW)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(1, 2);
        $this->assertEqualsSql(
            'OVER (RANGE BETWEEN 1 PRECEDING AND 2 FOLLOWING)',
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
            'OVER (RANGE BETWEEN 2 PRECEDING AND 1 PRECEDING)',
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
            'OVER (ROWS UNBOUNDED PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0);
        $this->assertEqualsSql(
            'OVER (ROWS CURRENT ROW)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(2);
        $this->assertEqualsSql(
            'OVER (ROWS 2 PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(null, null);
        $this->assertEqualsSql(
            'OVER (ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0, null);
        $this->assertEqualsSql(
            'OVER (ROWS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0, 0);
        $this->assertEqualsSql(
            'OVER (ROWS BETWEEN CURRENT ROW AND CURRENT ROW)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->rows(1, 2);
        $this->assertEqualsSql(
            'OVER (ROWS BETWEEN 1 PRECEDING AND 2 FOLLOWING)',
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
            'OVER (ROWS BETWEEN 2 PRECEDING AND 1 PRECEDING)',
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
            'OVER (GROUPS UNBOUNDED PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0);
        $this->assertEqualsSql(
            'OVER (GROUPS CURRENT ROW)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(2);
        $this->assertEqualsSql(
            'OVER (GROUPS 2 PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(null, null);
        $this->assertEqualsSql(
            'OVER (GROUPS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0, null);
        $this->assertEqualsSql(
            'OVER (GROUPS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0, 0);
        $this->assertEqualsSql(
            'OVER (GROUPS BETWEEN CURRENT ROW AND CURRENT ROW)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->groups(1, 2);
        $this->assertEqualsSql(
            'OVER (GROUPS BETWEEN 1 PRECEDING AND 2 FOLLOWING)',
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
            'OVER (GROUPS BETWEEN 2 PRECEDING AND 1 PRECEDING)',
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
            'OVER ()',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'OVER (RANGE UNBOUNDED PRECEDING EXCLUDE CURRENT ROW)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeGroup();
        $this->assertEqualsSql(
            'OVER (RANGE UNBOUNDED PRECEDING EXCLUDE GROUP)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeTies();
        $this->assertEqualsSql(
            'OVER (RANGE UNBOUNDED PRECEDING EXCLUDE TIES)',
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
            'OVER (PARTITION BY test RANGE UNBOUNDED PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->order('test')->range(null);
        $this->assertEqualsSql(
            'OVER (ORDER BY test RANGE UNBOUNDED PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->partition('test')->order('test')->range(null);
        $this->assertEqualsSql(
            'OVER (PARTITION BY test ORDER BY test RANGE UNBOUNDED PRECEDING)',
            $w->sql(new ValueBinder())
        );

        $w = (new WindowExpression())->partition('test')->order('test')->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'OVER (PARTITION BY test ORDER BY test RANGE UNBOUNDED PRECEDING EXCLUDE CURRENT ROW)',
            $w->sql(new ValueBinder())
        );
    }

    /**
     * Tests windows with invalid offsets
     *
     * @return void
     */
    public function testInvalidStart()
    {
        $this->expectException(InvalidArgumentException::class);
        $w = (new WindowExpression())->range(-2, 1);
    }

    /**
     * Tests windows with invalid offsets
     *
     * @return void
     */
    public function testInvalidEnd()
    {
        $this->expectException(InvalidArgumentException::class);
        $w = (new WindowExpression())->range(0, -2);
    }
}
