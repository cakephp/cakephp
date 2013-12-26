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

use ArrayIterator;
use Cake\Utility\CollectionTrait;
use Cake\Utility\Iterator\ExtractIterator;
use Cake\Utility\Iterator\FilterIterator;
use Cake\Utility\Iterator\ReplaceIterator;
use Cake\Utility\Iterator\SortIterator;
use Cake\Utility\MapReduce;
use InvalidArgumentException;
use IteratorIterator;
use LimitIterator;

/**
 * A collection is an immutable list of elements with a handful of functions to
 * iterate, group, transform and extract information from it.
 */
class Collection extends IteratorIterator {

	use CollectionTrait;

/**
 * Constructor. You can provide an array or any traversable object
 *
 * @param array|\Traversable $items
 * @return void
 */
	public function __construct($items) {
		if (is_array($items)) {
			$items = new ArrayIterator($items);
		}

		if (!($items instanceof \Traversable)) {
			throw new InvalidArgumentException;
		}
		parent::__construct($items);
	}

/**
 * Returns the iterator with this collection's elements
 *
 * @return \Traversable
 */
	public function getIterator() {
		return $this->_iterator;
	}

}
