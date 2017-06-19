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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests FunctionExpression class
 */
class FunctionExpressionTest extends TestCase
{

    /**
     * Tests generating a function with no arguments
     *
     * @return void
     */
    public function testArityZero()
    {
        $f = new FunctionExpression('MyFunction');
        $this->assertEquals('MyFunction()', $f->sql(new ValueBinder));
    }

    /**
     * Tests generating a function one or multiple arguments and make sure
     * they are correctly replaced by placeholders
     *
     * @return void
     */
    public function testArityMultiplePlainValues()
    {
        $f = new FunctionExpression('MyFunction', ['foo', 'bar']);
        $binder = new ValueBinder;
        $this->assertEquals('MyFunction(:param0, :param1)', $f->sql($binder));

        $this->assertEquals('foo', $binder->bindings()[':param0']['value']);
        $this->assertEquals('bar', $binder->bindings()[':param1']['value']);

        $binder = new ValueBinder;
        $f = new FunctionExpression('MyFunction', ['bar']);
        $this->assertEquals('MyFunction(:param0)', $f->sql($binder));
        $this->assertEquals('bar', $binder->bindings()[':param0']['value']);
    }

    /**
     * Tests that it is possible to use literal strings as arguments
     *
     * @return void
     */
    public function testLiteralParams()
    {
        $binder = new ValueBinder;
        $f = new FunctionExpression('MyFunction', ['foo' => 'literal', 'bar']);
        $this->assertEquals('MyFunction(foo, :param0)', $f->sql($binder));
    }

    /**
     * Tests that it is possible to nest expression objects and pass them as arguments
     * In particular nesting multiple FunctionExpression
     *
     * @return void
     */
    public function testFunctionNesting()
    {
        $binder = new ValueBinder;
        $f = new FunctionExpression('MyFunction', ['foo', 'bar']);
        $g = new FunctionExpression('Wrapper', ['bar' => 'literal', $f]);
        $this->assertEquals('Wrapper(bar, (MyFunction(:param0, :param1)))', $g->sql($binder));
    }

    /**
     * Tests that it is possible to use a number as a literal in a function.
     *
     * @return void
     */
    public function testNumericLiteral()
    {
        $binder = new ValueBinder;
        $f = new FunctionExpression('MyFunction', ['a_field' => 'literal', '32' => 'literal']);
        $this->assertEquals('MyFunction(a_field, 32)', $f->sql($binder));

        $f = new FunctionExpression('MyFunction', ['a_field' => 'literal', 32 => 'literal']);
        $this->assertEquals('MyFunction(a_field, 32)', $f->sql($binder));
    }
}
