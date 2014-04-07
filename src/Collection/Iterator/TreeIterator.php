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

use Cake\Collection\CollectionTrait;
use Cake\Collection\Iterator\TreePrinter;
use RecursiveIteratorIterator;

/**
 * A Recursive iterator used to flatten nested structures and also exposes
 * all Collection methods
 *
 */
class TreeIterator extends RecursiveIteratorIterator {

	use CollectionTrait;

	protected $_mode;

	public function __construct($items, $mode = RecursiveIteratorIterator::LEAVES_ONLY, $flags = 0) {
		parent::__construct($items, $mode, $flags);
		$this->_mode = $mode;
	}

	public function printer($valuePath, $keyPath = null, $spacer = '__') {
		if (!$keyPath) {
			$counter = 0;
			$keyPath = function() use ($counter) {
				return $counter++;
			};
		}
		return new TreePrinter(
			$this->getInnerIterator(),
			$valuePath,
			$keyPath,
			$spacer,
			$this->_mode
		);
	}

}
