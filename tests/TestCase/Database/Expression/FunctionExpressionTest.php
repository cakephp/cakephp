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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests FunctionExpression class
 */
class FunctionExpressionTest extends TestCase
{
    /**
     * @var string The expression class to test with
     */
    protected $expressionClass = FunctionExpression::class;

    /**
     * Tests generating a function with no arguments
     *
     * @return void
     */
    public function testArityZero()
    {
        $f = new $this->expressionClass('MyFunction');
        $this->assertSame('MyFunction()', $f->sql(new ValueBinder()));
    }

    /**
     * Tests generating a function one or multiple arguments and make sure
     * they are correctly replaced by placeholders
     *
     * @return void
     */
    public function testArityMultiplePlainValues()
    {
        $f = new $this->expressionClass('MyFunction', ['foo', 'bar']);
        $binder = new ValueBinder();
        $this->assertSame('MyFunction(:param0, :param1)', $f->sql($binder));

        $this->assertSame('foo', $binder->bindings()[':param0']['value']);
        $this->assertSame('bar', $binder->bindings()[':param1']['value']);

        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['bar']);
        $this->assertSame('MyFunction(:param0)', $f->sql($binder));
        $this->assertSame('bar', $binder->bindings()[':param0']['value']);
    }

    /**
     * Tests that it is possible to use literal strings as arguments
     *
     * @return void
     */
    public function testLiteralParams()
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['foo' => 'literal', 'bar']);
        $this->assertSame('MyFunction(foo, :param0)', $f->sql($binder));
    }

    /**
     * Tests that it is possible to nest expression objects and pass them as arguments
     * In particular nesting multiple FunctionExpression
     *
     * @return void
     */
    public function testFunctionNesting()
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['foo', 'bar']);
        $g = new $this->expressionClass('Wrapper', ['bar' => 'literal', $f]);
        $this->assertSame('Wrapper(bar, MyFunction(:param0, :param1))', $g->sql($binder));
    }

    /**
     * Tests to avoid regression, prevents double parenthesis
     * In particular nesting with QueryExpression
     *
     * @return void
     */
    public function testFunctionNestingQueryExpression()
    {
        $binder = new ValueBinder();
        $q = new QueryExpression('a');
        $f = new $this->expressionClass('MyFunction', [$q]);
        $this->assertSame('MyFunction(a)', $f->sql($binder));
    }

    /**
     * Tests that passing a database query as an argument wraps the query SQL into parentheses.
     *
     * @return void
     */
    public function testFunctionWithDatabaseQuery()
    {
        $query = ConnectionManager::get('test')
            ->newQuery()
            ->select(['column']);

        $binder = new ValueBinder();
        $function = new $this->expressionClass('MyFunction', [$query]);
        $this->assertSame(
            'MyFunction((SELECT column))',
            preg_replace('/[`"\[\]]/', '', $function->sql($binder))
        );
    }

    /**
     * Tests that passing a ORM query as an argument wraps the query SQL into parentheses.
     *
     * @return void
     */
    public function testFunctionWithOrmQuery()
    {
        $query = $this->getTableLocator()->get('Articles')
            ->setSchema(['column' => 'integer'])
            ->find()
            ->select(['column']);

        $binder = new ValueBinder();
        $function = new $this->expressionClass('MyFunction', [$query]);
        $this->assertSame(
            'MyFunction((SELECT Articles.column AS Articles__column FROM articles Articles))',
            preg_replace('/[`"\[\]]/', '', $function->sql($binder))
        );
    }

    /**
     * Tests that it is possible to use a number as a literal in a function.
     *
     * @return void
     */
    public function testNumericLiteral()
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['a_field' => 'literal', '32' => 'literal']);
        $this->assertSame('MyFunction(a_field, 32)', $f->sql($binder));

        $f = new $this->expressionClass('MyFunction', ['a_field' => 'literal', 32 => 'literal']);
        $this->assertSame('MyFunction(a_field, 32)', $f->sql($binder));
    }

    /**
     * Tests setReturnType() and getReturnType()
     *
     * @return void
     */
    public function testGetSetReturnType()
    {
        $f = new $this->expressionClass('MyFunction');
        $f = $f->setReturnType('foo');
        $this->assertInstanceOf($this->expressionClass, $f);
        $this->assertSame('foo', $f->getReturnType());
    }
}
