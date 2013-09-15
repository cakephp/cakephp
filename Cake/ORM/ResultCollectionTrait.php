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

use \Iterator;
use \JsonSerializable;
use \Serializable;

/**
 * Common set of methods that any specific ResultSet implementation
 * should contain
 */
trait ResultCollectionTrait {

/**
 * Returns an array representation of the results
 *
 * @return array
 */
	public function toArray() {
		return iterator_to_array($this);
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
 * @param string Serialized object
 * @return ResultSet The hydrated result set.
 */
	public function unserialize($serialized) {
		$this->_results = unserialize($serialized);
	}

/**
 * Convert a result set into JSON.
 *
 * Part of JsonSerializable interface.
 *
 * @return array The data to convert to JSON
 */
	public function jsonSerialize() {
		return $this->toArray();
	}

}
