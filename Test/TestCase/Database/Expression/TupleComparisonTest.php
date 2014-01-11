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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\TupleComparison;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests TupleComparison class
 *
 **/
class TupleComparisonTest extends TestCase {

/**
 * Tests generating a function with no arguments
 *
 * @return void
 */
	public function testsSimpleTuple() {
		$f = new TupleComparison(['field1', 'field2'], [1, 2], ['integer', 'integer'], '=');
		$binder = new ValueBinder;
		$this->assertEquals('(field1, field2) = (:c0, :c1)', $f->sql($binder));
		$this->assertSame(1, $binder->bindings()[':c0']['value']);
		$this->assertSame(2, $binder->bindings()[':c1']['value']);
		$this->assertSame('integer', $binder->bindings()[':c0']['type']);
		$this->assertSame('integer', $binder->bindings()[':c1']['type']);
	}

}
