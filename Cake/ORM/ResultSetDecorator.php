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
class ResultSetDecorator implements IteratorAggregate, Serializable, JsonSerializable {

	use ResultCollectionTrait;

/**
 * Holds the records after an instance of this object has been unserialized
 *
 * @var array
 */
	protected $_results;

/**
 * Internal index pointer.
 *
 * @var integer
 */
	protected $_index = 0;

/**
 * The array form of the results. Used to 
 * facilitate one().
 *
 * @var array
 */
	protected $_arrayResults;

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
 * @return mixed
 */
	public function one() {
		if (empty($this->_arrayResults) && !is_array($this->_results)) {
			$this->_arrayResults = iterator_to_array($this->_results);
		}
		$index = $this->_index;
		$this->_index++;
		if (!isset($this->_arrayResults[$index])) {
			return false;
		}
		return $this->_arrayResults[$index];
	}

}
