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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Expression\BetweenExpression;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\ValuesExpression;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\TestType;

/**
 * Tests for Expression objects casting values to other expressions
 * using the type classes
 */
class ExpressionTypeCastingTest extends TestCase
{
    /**
     * Setups a mock for FunctionsBuilder
     */
    protected function setUp(): void
    {
        parent::setUp();
        TypeFactory::set('test', new TestType());
    }

    /**
     * Tests that the Comparison expression can handle values convertible to
     * expressions
     */
    public function testComparisonSimple(): void
    {
        $comparison = new ComparisonExpression('field', 'the thing', 'test', '=');
        $binder = new ValueBinder();
        $sql = $comparison->sql($binder);
        $this->assertSame('field = (CONCAT(:param0, :param1))', $sql);
        $this->assertSame('the thing', $binder->bindings()[':param0']['value']);

        $found = false;
        $comparison->traverse(function ($exp) use (&$found): void {
            $found = $exp instanceof FunctionExpression;
        });
        $this->assertTrue($found, 'The expression is not included in the tree');
    }

    /**
     * Tests that the Comparison expression can handle values convertible to
     * expressions
     */
    public function testComparisonMultiple(): void
    {
        $comparison = new ComparisonExpression('field', ['2', '3'], 'test[]', 'IN');
        $binder = new ValueBinder();
        $sql = $comparison->sql($binder);
        $this->assertSame('field IN (CONCAT(:param0, :param1),CONCAT(:param2, :param3))', $sql);
        $this->assertSame('2', $binder->bindings()[':param0']['value']);
        $this->assertSame('3', $binder->bindings()[':param2']['value']);

        $found = false;
        $comparison->traverse(function ($exp) use (&$found): void {
            $found = $exp instanceof FunctionExpression;
        });
        $this->assertTrue($found, 'The expression is not included in the tree');
    }

    /**
     * Tests that the Between expression casts values to expressions correctly
     */
    public function testBetween(): void
    {
        $between = new BetweenExpression('field', 'from', 'to', 'test');
        $binder = new ValueBinder();
        $sql = $between->sql($binder);
        $this->assertSame('field BETWEEN CONCAT(:param0, :param1) AND CONCAT(:param2, :param3)', $sql);
        $this->assertSame('from', $binder->bindings()[':param0']['value']);
        $this->assertSame('to', $binder->bindings()[':param2']['value']);

        $expressions = [];
        $between->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertSame(2, $result, 'Missing expressions in the tree');
    }

    /**
     * Tests that values in FunctionExpressions are converted to expressions correctly
     */
    public function testFunctionExpression(): void
    {
        $function = new FunctionExpression('DATE', ['2016-01'], ['test']);
        $binder = new ValueBinder();
        $sql = $function->sql($binder);
        $this->assertSame('DATE(CONCAT(:param0, :param1))', $sql);
        $this->assertSame('2016-01', $binder->bindings()[':param0']['value']);

        $expressions = [];
        $function->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertSame(1, $result, 'Missing expressions in the tree');
    }

    /**
     * Tests that values in ValuesExpression are converted to expressions correctly
     */
    public function testValuesExpression(): void
    {
        $values = new ValuesExpression(['title'], new TypeMap(['title' => 'test']));
        $values->add(['title' => 'foo']);
        $values->add(['title' => 'bar']);

        $binder = new ValueBinder();
        $sql = $values->sql($binder);
        $this->assertSame(
            ' VALUES ((CONCAT(:param0, :param1))), ((CONCAT(:param2, :param3)))',
            $sql
        );
        $this->assertSame('foo', $binder->bindings()[':param0']['value']);
        $this->assertSame('bar', $binder->bindings()[':param2']['value']);

        $expressions = [];
        $values->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertSame(2, $result, 'Missing expressions in the tree');
    }
}
