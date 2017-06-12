<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Expression\BetweenExpression;
use Cake\Database\Expression\CaseExpression;
use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\ValuesExpression;
use Cake\Database\Type;
use Cake\Database\Type\ExpressionTypeInterface;
use Cake\Database\Type\StringType;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

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
        $this->assertEquals('field = (CONCAT(:param0, :param1))', $sql);
        $this->assertEquals('the thing', $binder->bindings()[':param0']['value']);

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
        $this->assertEquals('field IN (CONCAT(:param0, :param1),CONCAT(:param2, :param3))', $sql);
        $this->assertEquals('2', $binder->bindings()[':param0']['value']);
        $this->assertEquals('3', $binder->bindings()[':param2']['value']);

        $found = false;
        $comparison->traverse(function ($exp) use (&$found) {
            $found = $exp instanceof FunctionExpression;
        });
        $this->assertTrue($found, 'The expression is not included in the tree');
    }

    /**
     * Tests that the Between expression casts values to expressions correctly
     *
     * @return void
     */
    public function testBetween()
    {
        $between = new BetweenExpression('field', 'from', 'to', 'test');
        $binder = new ValueBinder;
        $sql = $between->sql($binder);
        $this->assertEquals('field BETWEEN CONCAT(:param0, :param1) AND CONCAT(:param2, :param3)', $sql);
        $this->assertEquals('from', $binder->bindings()[':param0']['value']);
        $this->assertEquals('to', $binder->bindings()[':param2']['value']);

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
            ['test', 'test']
        );

        $binder = new ValueBinder;
        $sql = $case->sql($binder);
        $this->assertEquals('CASE WHEN foo = :c0 THEN CONCAT(:param1, :param2) ELSE CONCAT(:param3, :param4) END', $sql);

        $this->assertEquals('1', $binder->bindings()[':c0']['value']);
        $this->assertEquals('value1', $binder->bindings()[':param1']['value']);
        $this->assertEquals('value2', $binder->bindings()[':param3']['value']);

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
        $this->assertEquals('DATE((CONCAT(:param0, :param1)))', $sql);
        $this->assertEquals('2016-01', $binder->bindings()[':param0']['value']);

        $expressions = [];
        $function->traverse(function ($exp) use (&$expressions) {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertEquals(1, $result, 'Missing expressions in the tree');
    }

    /**
     * Tests that values in ValuesExpression are converted to expressions correctly
     *
     * @return void
     */
    public function testValuesExpression()
    {
        $values = new ValuesExpression(['title'], ['title' => 'test']);
        $values->add(['title' => 'foo']);
        $values->add(['title' => 'bar']);

        $binder = new ValueBinder;
        $sql = $values->sql($binder);
        $this->assertEquals(
            ' VALUES ((CONCAT(:param0, :param1))), ((CONCAT(:param2, :param3)))',
            $sql
        );
        $this->assertEquals('foo', $binder->bindings()[':param0']['value']);
        $this->assertEquals('bar', $binder->bindings()[':param2']['value']);

        $expressions = [];
        $values->traverse(function ($exp) use (&$expressions) {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertEquals(2, $result, 'Missing expressions in the tree');
    }
}
