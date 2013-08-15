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

use \IteratorAggregate;
use \ArrayIterator;

class MapReduce implements IteratorAggregate {

	protected $_intermediate = [];

	protected $_result = [];

	protected $_executed = false;

	protected $_data;

	protected $_mapper;

	protected $_reducer;

	protected $_counter = 0;

	public function __construct($data, callable $mapper, callable $reducer) {
		$this->_data = $data;
		$this->_mapper = $mapper;
		$this->_reducer = $reducer;
	}

	public function getIterator() {
		if (!$this->_executed) {
			$this->_execute();
		}
		return new ArrayIterator($this->_result);
	}

	public function emitIntermediate($key, $value) {
		$this->_intermediate[$key][] = $value;
	}

	public function emit($value, $slot = null) {
		$this->_result[$slot === null ? $this->_counter : $slot] = $value;
	}

	protected function _execute() {
		foreach ($this->_data as $key => $value) {
			$this->_mapper->__invoke($key, $value, $this);
		}

		foreach ($this->_intermediate as $key => $list) {
			$this->_reducer->__invoke($key, $list, $this);
			$this->_counter++;
		}
		$this->_execute = true;
	}

}
