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
namespace Cake\Collection\Iterator;

use Cake\Collection\Collection;
use SplDoublyLinkedList;

/**
 * Creates an iterator from another iterator that will keep in memory the results
 * from the inner iterator so they don't have to be calculated again.
 */
class BufferedIterator extends Collection {

/**
 * The in-memory cache containing results from previous iterators
 *
 * @var callable
 */
	protected $_buffer;

/**
 * Points to the next record number that should be fetched
 *
 * @var int
 */
	protected $_index = 0;

/**
 * Last record fetched from the inner iterator
 *
 * @var array
 */
	protected $_current;

/**
 * Last key obtained from the inner iterator
 *
 * @var array
 */
	protected $_key;

	protected $_started = false;

/**
 * Maintains an in-memory cache of the results yielded by the internal
 * iterator.
 *
 * @param array|\Traversable $items The items to be filtered.
 */
	public function __construct($items) {
		$this->_buffer = new SplDoublyLinkedList;
		parent::__construct($items);
	}

	public function key() {
		return $this->_key;
	}

/**
 * Returns the current record in the result iterator
 *
 * Part of Iterator interface.
 *
 * @return array|object
 */
	public function current() {
		return $this->_current;
	}

/**
 * Rewinds the collection
 *
 *
 * @return void
 */
	public function rewind() {
		if ($this->_index === 0 && !$this->_started) {
			$this->_started = true;
			parent::rewind();
			return;
		}

		$this->_index = 0;
	}

/**
 *
 * @return mixed
 */
	public function valid() {
		if ($this->_buffer->offsetExists($this->_index)) {
			$current = $this->_buffer->offsetGet($this->_index);
			$this->_current = $current['value'];
			$this->_key = $current['key'];
			return true;
		}

		$valid = parent::valid();

		if ($valid) {
			$this->_current = parent::current();
			$this->_key = parent::key();
			$this->_buffer->add($this->_index, [
				'key' => $this->_index,
				'value' => $this->_current
			]);
		}

		return $valid;
	}

	public function next() {
		$this->_index++;
		parent::next();
	}

}
