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
namespace Cake\Collection\Iterator;

use Cake\Collection\Collection;
use RecursiveIterator;

class NestIterator extends Collection implements RecursiveIterator{

	protected $_nestKey;

	public function __construct($items, $nestKey) {
		parent::__construct($items);
		$this->_nestKey = $nestKey;
	}

	public function getChildren() {
		$property = $this->_propertyExtractor($this->_nestKey);
		return new self($property($this->current()), $this->_nestKey);
	}

	public function hasChildren () {
		$property = $this->_propertyExtractor($this->_nestKey);
		$children = $property($this->current());

		if (is_array($children)) {
			return !empty($children);
		}

		if ($children instanceof \Traversable) {
			return true;
		}

		return false;
	}
}
