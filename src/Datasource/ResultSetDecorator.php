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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Datasource;

use ArrayIterator;
use Cake\Collection\Collection;
use Countable;
use IteratorIterator;
use JsonSerializable;
use Serializable;
use Traversable;

/**
 * Generic ResultSet decorator. This will make any traversable object appear to
 * be a database result
 *
 * @return void
 */
class ResultSetDecorator extends Collection implements Countable, Serializable, JsonSerializable {

/**
 * Make this object countable.
 *
 * Part of the Countable interface. Calling this method
 * will convert the underlying traversable object into an array and
 * get the count of the underlying data.
 *
 * @return integer
 */
	public function count() {
		return count($this->toArray());
	}

/**
 * Serialize a resultset.
 *
 * Part of Serializable interface.
 *
 * @return string Serialized object
 */
	public function serialize() {
		return serialize($this->toArray());
	}

/**
 * Unserialize a resultset.
 *
 * Part of Serializable interface.
 *
 * @param string $serialized Serialized object
 */
	public function unserialize($serialized) {
		parent::__construct(unserialize($serialized));
	}

}
