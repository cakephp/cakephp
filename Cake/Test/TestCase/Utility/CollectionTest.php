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
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\Collection;

class CollectionTest extends TestCase {

/**
 * Tests that it is possible to convert an array into a collection
 *
 * @return void
 */
	public function testArrayIsWrapped() {
		$items = [1, 2, 3];
		$collection = new Collection($items);
		$this->assertEquals($items, iterator_to_array($collection));
	}

/**
 * Tests that it is possible to convert an iterator into a collection
 *
 * @return void
 */
	public function testIteratorIsWrapped() {
		$items = new \ArrayObject([1, 2, 3]);
		$collection = new Collection($items);
		$this->assertEquals(iterator_to_array($items), iterator_to_array($collection));
	}

/**
 * Test running a method over all elements in the collection
 *
 * @return void
 */
	public function testEeach() {
		$items = ['a' => 1, 'b' => 2, 'c' => 3];
		$collection = new Collection($items);
		$callable = $this->getMock('stdClass', ['__invoke']);
		$callable->expects($this->at(0))
			->method('__invoke')
			->with(1, 'a');
		$callable->expects($this->at(1))
			->method('__invoke')
			->with(2, 'b');
		$callable->expects($this->at(2))
			->method('__invoke')
			->with(3, 'c');
		$collection->each($callable);
	}

/**
 * Tests that it is possible to chain filter() as it returns a collection object
 *
 * @return void
 */
	public function testFilterChaining() {
		$items = ['a' => 1, 'b' => 2, 'c' => 3];
		$collection = new Collection($items);
		$callable = $this->getMock('stdClass', ['__invoke']);
		$callable->expects($this->once())
			->method('__invoke')
			->with(3, 'c');
		$filtered = $collection->filter(function ($value, $key, $iterator) {
			return $value > 2;
		});

		$this->assertInstanceOf('\Cake\Utility\Collection', $filtered);
		$filtered->each($callable);
	}

}
