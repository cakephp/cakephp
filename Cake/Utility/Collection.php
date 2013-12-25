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

namespace Cake\Utility;

use \ArrayIterator;
use \InvalidArgumentException;

class Collection {

	protected $_iterator;

	public function __construct($items) {
		if (is_array($items)) {
			$items = new ArrayIterator($items);
		}

		if (!($items instanceof \Traversable)) {
			throw new InvalidArgumentException;
		}

		$this->_iterator = $items;
	}

	public function each(callable $c) {
	}

	public function mapReduce(callable $map, callable $reduce) {
	}

	public function filter(callable $c) {
	}

	public function some(callable $c) {
	}

	public function every(callable $c) {
	}

	public function contains(callable $c) {
	}

	public function extract($property) {
	}

	public function max() {
	}

	public function min() {
	}

	public function sortBy($property) {
	}

	public function groupBy($property) {
	}

	public function indexBy($property) {
	}

	public function countBy($property) {
	}

	public function shuffle() {
	}

	public function sample($size) {
	}

}
