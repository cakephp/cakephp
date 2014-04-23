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
namespace Cake\Test\TestCase\Collection\Iterator;

use ArrayObject;
use Cake\Collection\Iterator\SortIterator;
use Cake\TestSuite\TestCase;

/**
 * SortIterator Test
 *
 */
class SortIteratorTest extends TestCase {

/**
 * Tests sorting numbers with an identity callbacks
 *
 * @return void
 */
	public function testSortNumbersIdentity() {
		$items = new ArrayObject([3, 5, 1, 2, 4]);
		$identity = function($a) {
			return $a;
		};
		$sorted = new SortIterator($items, $identity);
		$expected = array_combine(range(4, 0), range(5, 1));
		$this->assertEquals($expected, iterator_to_array($sorted));

		$sorted = new SortIterator($items, $identity, SORT_ASC);
		$expected = array_combine(range(4, 0), range(1, 5));
		$this->assertEquals($expected, iterator_to_array($sorted));
	}

/**
 * Tests sorting numbers with custom callback
 *
 * @return void
 */
	public function testSortNumbersCustom() {
		$items = new ArrayObject([3, 5, 1, 2, 4]);
		$callback = function($a) {
			return $a * -1;
		};
		$sorted = new SortIterator($items, $callback);
		$expected = array_combine(range(4, 0), [1, 2, 3, 4, 5]);
		$this->assertEquals($expected, iterator_to_array($sorted));

		$sorted = new SortIterator($items, $callback, SORT_ASC);
		$expected = array_combine(range(4, 0), [5, 4, 3, 2, 1]);
		$this->assertEquals($expected, iterator_to_array($sorted));
	}

/**
 * Tests sorting a complex structure with numeric sort
 *
 * @return void
 */
	public function testSortComplexNumeric() {
		$items = new ArrayObject([
			['foo' => 1, 'bar' => 'a'],
			['foo' => 10, 'bar' => 'a'],
			['foo' => 2, 'bar' => 'a'],
			['foo' => 13, 'bar' => 'a'],
		]);
		$callback = function($a) {
			return $a['foo'];
		};
		$sorted = new SortIterator($items, $callback, SORT_DESC, SORT_NUMERIC);
		$expected = [
			3 => ['foo' => 13, 'bar' => 'a'],
			2 => ['foo' => 10, 'bar' => 'a'],
			1 => ['foo' => 2, 'bar' => 'a'],
			0 => ['foo' => 1, 'bar' => 'a'],
		];
		$this->assertEquals($expected, iterator_to_array($sorted));

		$sorted = new SortIterator($items, $callback, SORT_ASC, SORT_NUMERIC);
		$expected = [
			3 => ['foo' => 1, 'bar' => 'a'],
			2 => ['foo' => 2, 'bar' => 'a'],
			1 => ['foo' => 10, 'bar' => 'a'],
			0 => ['foo' => 13, 'bar' => 'a'],
		];
		$this->assertEquals($expected, iterator_to_array($sorted));
	}

/**
 * Tests sorting a complex structure with natural sort
 *
 * @return void
 */
	public function testSortComplexNatural() {
		$items = new ArrayObject([
			['foo' => 'foo_1', 'bar' => 'a'],
			['foo' => 'foo_10', 'bar' => 'a'],
			['foo' => 'foo_2', 'bar' => 'a'],
			['foo' => 'foo_13', 'bar' => 'a'],
		]);
		$callback = function($a) {
			return $a['foo'];
		};
		$sorted = new SortIterator($items, $callback, SORT_DESC, SORT_NATURAL);
		$expected = [
			3 => ['foo' => 'foo_13', 'bar' => 'a'],
			2 => ['foo' => 'foo_10', 'bar' => 'a'],
			1 => ['foo' => 'foo_2', 'bar' => 'a'],
			0 => ['foo' => 'foo_1', 'bar' => 'a'],
		];
		$this->assertEquals($expected, iterator_to_array($sorted));

		$sorted = new SortIterator($items, $callback, SORT_ASC, SORT_NATURAL);
		$expected = [
			3 => ['foo' => 'foo_1', 'bar' => 'a'],
			2 => ['foo' => 'foo_2', 'bar' => 'a'],
			1 => ['foo' => 'foo_10', 'bar' => 'a'],
			0 => ['foo' => 'foo_13', 'bar' => 'a'],
		];
		$this->assertEquals($expected, iterator_to_array($sorted));
		$this->assertEquals($expected, iterator_to_array($sorted), 'Iterator should rewind');
	}

/**
 * Tests sorting a complex structure with natural sort with string callback
 *
 * @return void
 */
	public function testSortComplexNaturalWithPath() {
		$items = new ArrayObject([
			['foo' => 'foo_1', 'bar' => 'a'],
			['foo' => 'foo_10', 'bar' => 'a'],
			['foo' => 'foo_2', 'bar' => 'a'],
			['foo' => 'foo_13', 'bar' => 'a'],
		]);
		$sorted = new SortIterator($items, 'foo', SORT_DESC, SORT_NATURAL);
		$expected = [
			3 => ['foo' => 'foo_13', 'bar' => 'a'],
			2 => ['foo' => 'foo_10', 'bar' => 'a'],
			1 => ['foo' => 'foo_2', 'bar' => 'a'],
			0 => ['foo' => 'foo_1', 'bar' => 'a'],
		];
		$this->assertEquals($expected, iterator_to_array($sorted));

		$sorted = new SortIterator($items, 'foo', SORT_ASC, SORT_NATURAL);
		$expected = [
			3 => ['foo' => 'foo_1', 'bar' => 'a'],
			2 => ['foo' => 'foo_2', 'bar' => 'a'],
			1 => ['foo' => 'foo_10', 'bar' => 'a'],
			0 => ['foo' => 'foo_13', 'bar' => 'a'],
		];
		$this->assertEquals($expected, iterator_to_array($sorted));
		$this->assertEquals($expected, iterator_to_array($sorted), 'Iterator should rewind');
	}

/**
 * Tests sorting a complex structure with a deep path
 *
 * @return void
 */
	public function testSortComplexDeepPath() {
		$items = new ArrayObject([
			['foo' => ['bar' => 1], 'bar' => 'a'],
			['foo' => ['bar' => 12], 'bar' => 'a'],
			['foo' => ['bar' => 10], 'bar' => 'a'],
			['foo' => ['bar' => 2], 'bar' => 'a'],
		]);
		$sorted = new SortIterator($items, 'foo.bar', SORT_ASC, SORT_NUMERIC);
		$expected = [
			3 => ['foo' => ['bar' => 1], 'bar' => 'a'],
			2 => ['foo' => ['bar' => 2], 'bar' => 'a'],
			1 => ['foo' => ['bar' => 10], 'bar' => 'a'],
			0 => ['foo' => ['bar' => 12], 'bar' => 'a'],
		];
		$this->assertEquals($expected, iterator_to_array($sorted));
	}

/**
 * Tests top
 *
 * @return void
 */
	public function testTop() {
		$items = new ArrayObject([3, 5, 1, 2, 4]);
		$identity = function($a) {
			return $a;
		};
		$sorted = new SortIterator($items, $identity);
		$this->assertEquals(5, $sorted->top());

		$sorted = new SortIterator([], $identity);
		$this->assertNull($sorted->top());
	}
}
