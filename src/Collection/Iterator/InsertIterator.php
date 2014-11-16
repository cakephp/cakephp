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

/**
 * This iterator will insert values into a property of each of the records returned.
 * The values to be inserted come out of another traversal object. This is useful
 * when you have two separate collections and want to merge them together by placing
 * each of the values from one collection into a property inside the other collection.
 */
class InsertIterator extends Collection {

/**
 * The collection from which to extract the values to be inserted
 *
 * @var \Cake\Collection\Collection
 */
	protected $_values;

/**
 * Holds whether the values collection is still valid. (has more records)
 *
 * @var bool
 */
	protected $_validValues = true;

/**
 * An array containing each of the properties to be traversed to reach the
 * point where the values should be inserted.
 *
 * @var array
 */
	protected $_path;

/**
 * The property name to which values will be assigned
 *
 * @var string
 */
	protected $_target;

/**
 * Constructs a new collection that will dynamically add properties to it out of
 * the values found in $values.
 *
 * @param array|\Traversable $into The target collection to which the values will
 * be inserted at the specified path.
 * @param string $path A dot separated list of properties that need to be traversed
 * to insert the value into the target collection.
 * @param array|\Traversable $values The source collection from which the values will
 * be inserted at the specified path.
 */
	public function __construct($into, $path, $values) {
		parent::__construct($into);

		if (!($values instanceof Collection)) {
			$values = new Collection($values);
		}

		$path = explode('.', $path);
		$target = array_pop($path);
		$this->_path = $path;
		$this->_target = $target;
		$this->_values = $values;
	}

/**
 * Advances the cursor to the next record
 *
 * @return void
 */
	public function next() {
		parent::next();
		if ($this->_validValues) {
			$this->_values->next();
		}
		$this->_validValues = $this->_values->valid();
	}

/**
 * Returns the current element in the target collection after inserting
 * the value from the source collection into the specified path.
 *
 * @return void
 */
	public function current() {
		$row = parent::current();

		if (!$this->_validValues) {
			return $row;
		}

		$pointer =& $row;
		foreach ($this->_path as $step) {
			if (!isset($pointer[$step])) {
				return $row;
			}
			$pointer =& $pointer[$step];
		}

		$pointer[$this->_target] = $this->_values->current();
		return $row;
	}

/**
 * Resets the collection pointer.
 *
 * @return void
 */
	public function rewind() {
		parent::rewind();
		$this->_values->rewind();
		$this->_validValues = $this->_values->valid();
	}

}
