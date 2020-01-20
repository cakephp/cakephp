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
        $this->assertEquals(
            'OVER (PARTITION BY test)',
            preg_replace('/[`"\[\]]/', '', $w->sql(new ValueBinder()))
        );

        $w->partition(new IdentifierExpression('identifier'));
        $this->assertEquals(
            'OVER (PARTITION BY test, identifier)',
            preg_replace('/[`"\[\]]/', '', $w->sql(new ValueBinder()))
        );

        $w = (new WindowExpression())->partition(new AggregateExpression('MyAggregate', ['param']));
        $this->assertEquals(
            'OVER (PARTITION BY MyAggregate(:param0))',
            preg_replace('/[`"\[\]]/', '', $w->sql(new ValueBinder()))
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
        $this->assertEquals(
            'OVER (ORDER BY test)',
            preg_replace('/[`"\[\]]/', '', $w->sql(new ValueBinder()))
        );

        $w->order(['test2' => 'DESC']);
        $this->assertEquals(
            'OVER (ORDER BY test, test2 DESC)',
            preg_replace('/[`"\[\]]/', '', $w->sql(new ValueBinder()))
        );

        $w->partition('test');
        $this->assertEquals(
            'OVER (PARTITION BY test ORDER BY test, test2 DESC)',
            preg_replace('/[`"\[\]]/', '', $w->sql(new ValueBinder()))
        );
    }
}
