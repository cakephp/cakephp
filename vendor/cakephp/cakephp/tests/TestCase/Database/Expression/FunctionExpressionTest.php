<?php
/**
 *
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ValueBinder;

/**
 * Tests FunctionExpression class
 *
 **/
class FunctionExpressionTest extends \Cake\TestSuite\TestCase {

/**
 * Tests generating a function with no arguments
 *
 * @return void
 */
	public function testArityZero() {
		$f = new FunctionExpression('MyFunction');
		$this->assertEquals('MyFunction()', $f->sql(new ValueBinder));
	}

/**
 * Tests generating a function one or multiple arguments and make sure
 * they are correctly replaced by placeholders
 *
 * @return void
 */
	public function testArityMultiplePlainValues() {
		$f = new FunctionExpression('MyFunction', ['foo', 'bar']);
		$binder = new ValueBinder;
		$this->assertEquals("MyFunction(:c0, :c1)", $f->sql($binder));

		$this->assertEquals('foo', $binder->bindings()[':c0']['value']);
		$this->assertEquals('bar', $binder->bindings()[':c1']['value']);

		$binder = new ValueBinder;
		$f = new FunctionExpression('MyFunction', ['bar']);
		$this->assertEquals("MyFunction(:c0)", $f->sql($binder));
		$this->assertEquals('bar', $binder->bindings()[':c0']['value']);
	}

/**
 * Tests that it is possible to use literal strings as arguments
 *
 * @return void
 */
	public function testLiteralParams() {
		$binder = new ValueBinder;
		$f = new FunctionExpression('MyFunction', ['foo' => 'literal', 'bar']);
		$this->assertEquals("MyFunction(foo, :c0)", $f->sql($binder));
	}

/**
 * Tests that it is possible to nest expression objects and pass them as arguments
 * In particular nesting multiple FunctionExpression
 *
 * @return void
 */
	public function testFunctionNesting() {
		$binder = new ValueBinder;
		$f = new FunctionExpression('MyFunction', ['foo', 'bar']);
		$g = new FunctionExpression('Wrapper', ['bar' => 'literal', $f]);
		$this->assertEquals("Wrapper(bar, MyFunction(:c0, :c1))", $g->sql($binder));
	}
}
