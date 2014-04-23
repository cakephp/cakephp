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
namespace Cake\Collection;

use ArrayIterator;
use Cake\Collection\CollectionTrait;
use InvalidArgumentException;
use IteratorIterator;
use JsonSerializable;

/**
 * A collection is an immutable list of elements with a handful of functions to
 * iterate, group, transform and extract information from it.
 */
class Collection extends IteratorIterator implements JsonSerializable {

	use CollectionTrait;

/**
 * Constructor. You can provide an array or any traversable object
 *
 * @param array|\Traversable $items
 * @throws InvalidArgumentException if passed incorrect type for items.
 */
	public function __construct($items) {
		if (is_array($items)) {
			$items = new ArrayIterator($items);
		}

		if (!($items instanceof \Traversable)) {
			$msg = 'Only array or \Traversable are allowed for Collection';
			throw new InvalidArgumentException($msg);
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
