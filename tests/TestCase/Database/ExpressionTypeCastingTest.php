<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\TestSuite\TestCase;
use Cake\Database\Type\StringType;
use Cake\Database\Type\ExpressionTypeInterface;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ValueBinder;
use Cake\Database\Type;
use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\BetweenExpression;
use Cake\Database\Expression\CaseExpression;

class TestType extends StringType implements ExpressionTypeInterface
{

    public function toExpression($value)
    {
        return new FunctionExpression('CONCAT', [$value, ' - foo']);
    }
}

/**
 * Tests for Expression objects casting values to other expressions
 * using the type classes
 *
 */
class ExpressionTypeCastingTest extends TestCase
{

    /**
     * Setups a mock for FunctionsBuilder
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Type::map('test', new TestType);
    }

    /**
     * Tests that the Comparison expression can handle values convertible to
     * expressions
     *
     * @return void
     */
    public function testComparisonSimple()
    {
        $comparison = new Comparison('field', 'the thing', 'test', '=');
        $binder = new ValueBinder;
        $sql = $comparison->sql($binder);
        $this->assertEquals('field = (CONCAT(:c0, :c1))', $sql);
        $this->assertEquals('the thing', $binder->bindings()[':c0']['value']);

        $found = false;
        $comparison->traverse(function ($exp) use (&$found) {
            $found = $exp instanceof FunctionExpression;
        });
        $this->assertTrue($found, 'The expression is not included in the tree');
    }

    /**
     * Tests that the Comparison expression can handle values convertible to
     * expressions
     *
     * @return void
     */
    public function testComparisonMultiple()
    {
        $comparison = new Comparison('field', ['2', '3'], 'test[]', 'IN');
        $binder = new ValueBinder;
        $sql = $comparison->sql($binder);
        $this->assertEquals('field IN (CONCAT(:c0, :c1),CONCAT(:c2, :c3))', $sql);
        $this->assertEquals('2', $binder->bindings()[':c0']['value']);
        $this->assertEquals('3', $binder->bindings()[':c2']['value']);

        $found = false;
        $comparison->traverse(function ($exp) use (&$found) {
            $found = $exp instanceof FunctionExpression;
        });
        $this->assertTrue($found, 'The expression is not included in the tree');
    }

    /**
     * Tests that the Between expression casts values to expresisons correctly
     *
     * @return void
     */
    public function testBetween()
    {
        $between = new BetweenExpression('field', 'from', 'to', 'test');
        $binder = new ValueBinder;
        $sql = $between->sql($binder);
        $this->assertEquals('field BETWEEN CONCAT(:c0, :c1) AND CONCAT(:c2, :c3)', $sql);
        $this->assertEquals('from', $binder->bindings()[':c0']['value']);
        $this->assertEquals('to', $binder->bindings()[':c2']['value']);

        $expressions = [];
        $between->traverse(function ($exp) use (&$expressions) {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertEquals(2, $result, 'Missing expressions in the tree');
    }

    /**
     * Tests that the Case expressions converts values to expressions correctly
     *
     * @return void
     */
    public function testCaseExpression()
    {
        $case = new CaseExpression(
            [new Comparison('foo', '1', 'string', '=')],
            ['value1', 'value2'],
            ['test' , 'test']
        );

        $binder = new ValueBinder;
        $sql = $case->sql($binder);
        $this->assertEquals('CASE WHEN foo = :c0 THEN CONCAT(:c1, :c2) ELSE CONCAT(:c3, :c4) END', $sql);

        $this->assertEquals('1', $binder->bindings()[':c0']['value']);
        $this->assertEquals('value1', $binder->bindings()[':c1']['value']);
        $this->assertEquals('value2', $binder->bindings()[':c3']['value']);

        $expressions = [];
        $case->traverse(function ($exp) use (&$expressions) {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertEquals(2, $result, 'Missing expressions in the tree');
    }

    /**
     * Tests that values in FunctionExpressions are converted to expressions correctly
     *
     * @return void
     */
    public function testFunctionExpression()
    {
        $function = new FunctionExpression('DATE', ['2016-01'], ['test']);
        $binder = new ValueBinder;
        $sql = $function->sql($binder);
        $this->assertEquals('DATE((CONCAT(:c0, :c1)))', $sql);
        $this->assertEquals('2016-01', $binder->bindings()[':c0']['value']);

        $expressions = [];
        $function->traverse(function ($exp) use (&$expressions) {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertEquals(1, $result, 'Missing expressions in the tree');
    }
}
