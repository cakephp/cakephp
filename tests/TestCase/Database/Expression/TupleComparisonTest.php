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
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Tests TupleComparison class
 */
class TupleComparisonTest extends TestCase
{
    /**
     * Tests generating a function with no arguments
     */
    public function testsSimpleTuple(): void
    {
        $f = new TupleComparison(['field1', 'field2'], [1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) = (:tuple0, :tuple1)', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':tuple0']['value']);
        $this->assertSame(2, $binder->bindings()[':tuple1']['value']);
        $this->assertSame('integer', $binder->bindings()[':tuple0']['type']);
        $this->assertSame('integer', $binder->bindings()[':tuple1']['type']);
    }

    /**
     * Tests generating tuples in the fields side containing expressions
     */
    public function testTupleWithExpressionFields(): void
    {
        $field1 = new QueryExpression(['a' => 1]);
        $f = new TupleComparison([$field1, 'field2'], [4, 5], ['integer', 'integer'], '>');
        $binder = new ValueBinder();
        $this->assertSame('(a = :c0, field2) > (:tuple1, :tuple2)', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':c0']['value']);
        $this->assertSame(4, $binder->bindings()[':tuple1']['value']);
        $this->assertSame(5, $binder->bindings()[':tuple2']['value']);
    }

    /**
     * Tests generating tuples in the values side containing expressions
     */
    public function testTupleWithExpressionValues(): void
    {
        $value1 = new QueryExpression(['a' => 1]);
        $f = new TupleComparison(['field1', 'field2'], [$value1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) = (a = :c0, :tuple1)', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':c0']['value']);
        $this->assertSame(2, $binder->bindings()[':tuple1']['value']);
    }

    /**
     * Tests generating tuples using the IN conjunction
     */
    public function testTupleWithInComparison(): void
    {
        $f = new TupleComparison(
            ['field1', 'field2'],
            [[1, 2], [3, 4]],
            ['integer', 'integer'],
            'IN'
        );
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) IN ((:tuple0,:tuple1), (:tuple2,:tuple3))', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':tuple0']['value']);
        $this->assertSame(2, $binder->bindings()[':tuple1']['value']);
        $this->assertSame(3, $binder->bindings()[':tuple2']['value']);
        $this->assertSame(4, $binder->bindings()[':tuple3']['value']);
    }

    /**
     * Tests traversing
     */
    public function testTraverse(): void
    {
        $value1 = new QueryExpression(['a' => 1]);
        $field2 = new QueryExpression(['b' => 2]);
        $f = new TupleComparison(['field1', $field2], [$value1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder();
        $expressions = [];

        $collector = function ($e) use (&$expressions): void {
            $expressions[] = $e;
        };

        $f->traverse($collector);
        $this->assertCount(4, $expressions);
        $this->assertSame($field2, $expressions[0]);
        $this->assertSame($value1, $expressions[2]);

        $f = new TupleComparison(
            ['field1', $field2],
            [[1, 2], [3, $value1]],
            ['integer', 'integer'],
            'IN'
        );
        $expressions = [];
        $f->traverse($collector);
        $this->assertCount(4, $expressions);
        $this->assertSame($field2, $expressions[0]);
        $this->assertSame($value1, $expressions[2]);
    }

    /**
     * Tests that a single ExpressionInterface can be used as the value for
     * comparison
     */
    public function testValueAsSingleExpression(): void
    {
        $value = new QueryExpression('SELECT 1, 1');
        $f = new TupleComparison(['field1', 'field2'], $value);
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) = (SELECT 1, 1)', $f->sql($binder));
    }

    /**
     * Tests that a single ExpressionInterface can be used as the field for
     * comparison
     */
    public function testFieldAsSingleExpression(): void
    {
        $value = [1, 1];
        $f = new TupleComparison(new QueryExpression('a, b'), $value);
        $binder = new ValueBinder();
        $this->assertSame('(a, b) = (:tuple0, :tuple1)', $f->sql($binder));
    }

    public function testMultiTupleComparisonRequiresMultiTupleValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multi-tuple comparisons require a multi-tuple value, single-tuple given.');

        new TupleComparison(
            ['field1', 'field2'],
            [1, 1],
            ['integer', 'integer'],
            'IN'
        );
    }

    public function testSingleTupleComparisonRequiresSingleTupleValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Single-tuple comparisons require a single-tuple value, multi-tuple given.');

        new TupleComparison(
            ['field1', 'field2'],
            [[1, 1], [2, 2]],
            ['integer', 'integer'],
            '='
        );
    }
}
