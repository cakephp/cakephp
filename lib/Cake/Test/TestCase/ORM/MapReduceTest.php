<?php
/**
 * PHP Version 5.4
 *
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
namespace Cake\Test\TestCase\ORM;

use \ArrayIterator;
use Cake\ORM\MapReduce;
use Cake\TestSuite\TestCase;

/**
 * Tests MapReduce class
 *
 */
class MapReduceTest extends TestCase {

/**
 * Tests the creating an inversed index of words to documents using
 * MapReduce
 *
 * @return void
 */
	public function testInvertedIndexCreation() {
		$data = [
			'document_1' => 'Dogs are the most amazing animal in history',
			'document_2' => 'History is not only amazing but boring',
			'document_3' => 'One thing that is not boring is dogs'
		];
		$mapper = function($document, $row, $mr) {
			$words = array_map('strtolower', explode(' ', $row));
			foreach ($words as $word) {
				$mr->emitIntermediate($word, $document);
			}
		};
		$reducer = function($word, $documents, $mr) {
			$mr->emit(array_unique($documents), $word);
		};
		$results = new MapReduce(new ArrayIterator($data), compact('mapper', 'reducer'));
		$expected = [
			'dogs' => array('document_1', 'document_3'),
			'are' => array('document_1'),
			'the' => array('document_1'),
			'most' => array('document_1'),
			'amazing' => array('document_1', 'document_2'),
			'animal' => array('document_1'),
			'in' => array('document_1'),
			'history' => array('document_1', 'document_2'),
			'is' => array('document_2', 'document_3'),
			'not' => array('document_2', 'document_3'),
			'only' => array('document_2'),
			'but' => array('document_2'),
			'boring' => array('document_2', 'document_3'),
			'one' => array('document_3'),
			'thing' => array('document_3'),
			'that' => array('document_3')
		];
		$this->assertEquals($expected, iterator_to_array($results));
	}

/**
 * Tests that it is possible to use the emit function directly in the mapper
 *
 * @return void
 */
	public function testEmitFinalInMapper() {
		$data = ['a' => ['one', 'two'], 'b' => ['three', 'four']];
		$mapper = function ($key, $row, $mr) {
			foreach ($row as $number) {
				$mr->emit($number);
			}
		};
		$results = new MapReduce(new ArrayIterator($data), compact('mapper'));
		$expected = ['one', 'two', 'three', 'four'];
		$this->assertEquals($expected, iterator_to_array($results));
	}

/**
 * Tests that a mapper function is required
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage A mapper is required to run MapReduce
 * @return void
 */
	public function testMapReduceNoMapper() {
		new MapReduce(new ArrayIterator([]), ['reducer' => function() {}]);
	}

/**
 * Tests that the mapper should be invokable
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Can only pass invokable objects to MapReduce
 * @return void
 */
	public function testMapperIsInvokable() {
		new MapReduce(new ArrayIterator([]), ['mapper' => [$this, 'setUp']]);
	}

/**
 * Tests that the mapper should be invokable
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage Can only pass invokable objects to MapReduce
 * @return void
 */
	public function testReducerIsInvokable() {
		new MapReduce(new ArrayIterator([]), [
			'mapper' => function() {},
			'reducer' => 'strtolower'
		]);
	}
}
