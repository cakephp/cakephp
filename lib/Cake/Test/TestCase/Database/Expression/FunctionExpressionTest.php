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
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Datasource\Database\Expression;

use Cake\Database\Expression\FunctionExpression;

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
		$this->assertEquals('MyFunction()', $f->sql());
	}

/**
 * Tests generating a function one or multiple arguments and make sure
 * they are correctly replaced by placeholders
 *
 * @return void
 */
	public function testArityMultiplePlainValues() {
		$f = new FunctionExpression('MyFunction', ['foo', 'bar']);
		$foo = $f->id() . '0';
		$bar = $f->id() . '1';
		$this->assertEquals("MyFunction(:c$foo, :c$bar)", $f->sql());

		$this->assertEquals('foo', $f->bindings()[1]['value']);
		$this->assertEquals('bar', $f->bindings()[2]['value']);

		$f = new FunctionExpression('MyFunction', ['bar']);
		$bar = $f->id() . '0';
		$this->assertEquals("MyFunction(:c$bar)", $f->sql());
		$this->assertEquals('bar', $f->bindings()[1]['value']);
	}

/**
 * Tests that it is possible to use literal strings as arguments
 *
 * @return void
 */
	public function testLiteralParams() {
		$f = new FunctionExpression('MyFunction', ['foo' => 'literal', 'bar']);
		$bar = $f->id() . '0';
		$this->assertEquals("MyFunction(foo, :c$bar)", $f->sql());
	}

/**
 * Tests that it is possible to nest expression objects and pass them as arguments
 * In particular nesting multiple FunctionExpression
 *
 * @return void
 */
	public function testFunctionNesting() {
		$f = new FunctionExpression('MyFunction', ['foo', 'bar']);
		$foo = ':c' . $f->id() . '0';
		$bar = ':c' . $f->id() . '1';
		$g = new FunctionExpression('Wrapper', ['bar' => 'literal', $f]);
		$this->assertEquals("Wrapper(bar, MyFunction($foo, $bar))", $g->sql());
	}
}
