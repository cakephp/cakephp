<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests TupleComparison class
 */
class TupleComparisonTest extends TestCase
{

    /**
     * Tests generating a function with no arguments
     *
     * @return void
     */
    public function testsSimpleTuple()
    {
        $f = new TupleComparison(['field1', 'field2'], [1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder;
        $this->assertEquals('(field1, field2) = (:c0, :c1)', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':c0']['value']);
        $this->assertSame(2, $binder->bindings()[':c1']['value']);
        $this->assertSame('integer', $binder->bindings()[':c0']['type']);
        $this->assertSame('integer', $binder->bindings()[':c1']['type']);
    }

    /**
     * Tests generating tuples in the fields side containing expressions
     *
     * @return void
     */
    public function testTupleWithExpressionFields()
    {
        $field1 = new QueryExpression(['a' => 1]);
        $f = new TupleComparison([$field1, 'field2'], [4, 5], ['integer', 'integer'], '>');
        $binder = new ValueBinder;
        $this->assertEquals('(a = :c0, field2) > (:c1, :c2)', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':c0']['value']);
        $this->assertSame(4, $binder->bindings()[':c1']['value']);
        $this->assertSame(5, $binder->bindings()[':c2']['value']);
    }

    /**
     * Tests generating tuples in the values side containing expressions
     *
     * @return void
     */
    public function testTupleWithExpressionValues()
    {
        $value1 = new QueryExpression(['a' => 1]);
        $f = new TupleComparison(['field1', 'field2'], [$value1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder;
        $this->assertEquals('(field1, field2) = (a = :c0, :c1)', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':c0']['value']);
        $this->assertSame(2, $binder->bindings()[':c1']['value']);
    }

    /**
     * Tests generating tuples using the IN conjunction
     *
     * @return void
     */
    public function testTupleWithInComparison()
    {
        $f = new TupleComparison(
            ['field1', 'field2'],
            [[1, 2], [3, 4]],
            ['integer', 'integer'],
            'IN'
        );
        $binder = new ValueBinder;
        $this->assertEquals('(field1, field2) IN ((:c0,:c1), (:c2,:c3))', $f->sql($binder));
        $this->assertSame(1, $binder->bindings()[':c0']['value']);
        $this->assertSame(2, $binder->bindings()[':c1']['value']);
        $this->assertSame(3, $binder->bindings()[':c2']['value']);
        $this->assertSame(4, $binder->bindings()[':c3']['value']);
    }

    /**
     * Tests traversing
     *
     * @return void
     */
    public function testTraverse()
    {
        $value1 = new QueryExpression(['a' => 1]);
        $field2 = new QueryExpression(['b' => 2]);
        $f = new TupleComparison(['field1', $field2], [$value1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder;
        $expressions = [];

        $collector = function ($e) use (&$expressions) {
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
     * Tests that a single ExpressionInteface can be used as the value for
     * comparison
     *
     * @return void
     */
    public function testValueAsSingleExpression()
    {
        $value = new QueryExpression('SELECT 1, 1');
        $f = new TupleComparison(['field1', 'field2'], $value);
        $binder = new ValueBinder;
        $this->assertEquals('(field1, field2) = (SELECT 1, 1)', $f->sql($binder));
    }

    /**
     * Tests that a single ExpressionInteface can be used as the field for
     * comparison
     *
     * @return void
     */
    public function testFieldAsSingleExpression()
    {
        $value = [1, 1];
        $f = new TupleComparison(new QueryExpression('a, b'), $value);
        $binder = new ValueBinder;
        $this->assertEquals('(a, b) = (:c0, :c1)', $f->sql($binder));
    }
}
