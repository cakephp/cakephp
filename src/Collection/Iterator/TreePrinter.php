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
use Cake\Collection\CollectionTrait;
use Cake\Collection\Iterator\TreePrinter;
use RecursiveIteratorIterator;

/**
 * Iterator for flattening elements in a tree structure while adding some
 * visual markers for their relative position in the tree
 *
 */
class TreePrinter extends RecursiveIteratorIterator {

	use CollectionTrait;

	protected $_key;

	protected $_value;

	protected $_current;

	public function __construct($items, $valuePath, $keyPath, $spacer, $mode = RecursiveIteratorIterator::SELF_FIRST) {
		parent::__construct($items, $mode);
		$this->_value = $this->_propertyExtractor($valuePath);
		$this->_key = $this->_propertyExtractor($keyPath);
		$this->_spacer = $spacer;
	}

	public function key() {
		$extractor = $this->_key;
		return $extractor($this->_fetchCurrent(), parent::key(), $this);
	}

	public function current() {
		$extractor = $this->_value;
		$current = $this->_fetchCurrent();
		$spacer = str_repeat($this->_spacer, $this->getDepth());
		return $spacer . $extractor($current, parent::key(), $this);
	}

	public function next() {
		parent::next();
		$this->_current = null;
	}

	protected function _fetchCurrent() {
		if ($this->_current !== null) {
			return $this->_current;
		}
		return $this->_current = parent::current();
	}

}
