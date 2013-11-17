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

use \ArrayIterator;
use \Countable;
use \IteratorAggregate;
use \JsonSerializable;
use \Serializable;
use \Traversable;

/**
 * Generic ResultSet decorator. This will make any traversable object appear to
 * be a database result
 *
 * @return void
 */
class ResultSetDecorator implements Countable, IteratorAggregate, Serializable, JsonSerializable {

	use ResultCollectionTrait;

/**
 * Holds the records after an instance of this object has been unserialized
 *
 * @var array
 */
	protected $_results;

/**
 * Constructor
 *
 * @param Traversable $results
 */
	public function __construct(Traversable $results) {
		$this->_results = $results;
	}

/**
 * Returns the inner iterator this decorator is wrapping
 *
 * @return \Iterator
 */
	public function getIterator() {
		if (is_array($this->_results)) {
			$this->_results = new ArrayIterator($this->_results);
		}
		return $this->_results;
	}

/**
 * Get a single result from the results.
 *
 * Calling this method will convert the underlying data into
 * an array and will return the first result in the data set.
 *
 * @return mixed The first value in the results will be returned.
 */
	public function one() {
		if (!is_array($this->_results)) {
			$this->_results = iterator_to_array($this->_results);
		}
		if (count($this->_results) < 1) {
			return false;
		}
		return current($this->_results);
	}

/**
 * Make this object countable.
 *
 * Part of the Countable interface. Calling this method
 * will convert the underlying traversable object into an array and
 * get the count of the underlying data.
 *
 * @return integer.
 */
	public function count() {
		if (!is_array($this->_results)) {
			$this->_results = iterator_to_array($this->_results);
		}
		return count($this->_results);
	}

}
