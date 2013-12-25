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
namespace Cake\ORM;

/**
 * Buffered ResultSets differ from un-buffered ResultSets in a few ways.
 *
 * - They can be iterated multiple times.
 * - They can be both cached and iterated using the same object.
 */
class BufferedResultSet extends ResultSet {

/**
 * Rewind the ResultSet
 *
 * @return void
 */
	public function rewind() {
		$this->_index = 0;
		$this->_lastIndex = -1;
	}

/**
 * Fetch a result and buffer the fetched row.
 *
 * @return mixed
 */
	public function valid() {
		$result = parent::valid();
		if (!isset($this->_results[$this->_index])) {
			$this->_results[] = $this->_current;
		}
		return $result;
	}

}
