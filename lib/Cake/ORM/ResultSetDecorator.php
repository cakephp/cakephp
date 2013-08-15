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
namespace Cake\ORM;

use \IteratorIterator;
use \Iterator;

class ResultSetDecorator implements Iterator {

	protected $_keyCallback;

	protected $_currentCallback;

	protected $_results;

	protected $_current;

	public function __construct(\Traversable $results, $callbacks = []) {
		if (!empty($callbacks['key'])) {
			$this->_keyCallback = $callbacks['key'];
		}
		if (!empty($callbacks['current'])) {
			$this->_currentCallback = $callbacks['current'];
		}
		$this->_results = $results;

		if ($results instanceOf \IteratorAggregate) {
			$this->_results = $results->getIterator();
		}
	}

	public function key() {
		$key = $this->_results->key();
		if ($this->_keyCallback) {
			$current = $this->_results->current();
			$key = $this->_keyCallback->__invoke($current, $key);
		}
		return $key;
	}

	public function current() {
		if ($this->_current) {
			return $this->_current;
		}
		$current = $this->_results->current();
		if ($current && $this->_currentCallback) {
			$current = $this->_currentCallback->__invoke($current);
		}
		return $current;
	}

	public function next() {
		$this->_current = null;
		$this->_results->next();
	}

	public function rewind() {
		$this->_current = null;
		$this->_results->rewind();
	}

	public function valid() {
		return $this->_results->valid();
	}

	public function toArray() {
		return iterator_to_array($this);
	}
}
