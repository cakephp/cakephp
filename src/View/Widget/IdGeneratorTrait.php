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
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\Utility\Inflector;

/**
 * A trait that provides id generating methods to be
 * used in various widget classes.
 */
trait IdGeneratorTrait {

/**
 * A list of id suffixes used in the current rendering.
 *
 * @var array
 */
	protected $_idSuffixes = [];

/**
 * Clear the stored ID suffixes.
 *
 * @return void
 */
	protected function _clearIds() {
		$this->_idSuffixes = [];
	}

/**
 * Generate an ID attribute for a radio button.
 *
 * Ensures that id's for a given set of fields are unique.
 *
 * @param array $radio The radio properties.
 * @return string Generated id.
 */
	protected function _id($name, $val) {
		$name = mb_strtolower(Inflector::slug($name, '-'));
		$idSuffix = mb_strtolower(str_replace(array('@', '<', '>', ' ', '"', '\''), '-', $val));
		$count = 1;
		$check = $idSuffix;
		while (in_array($check, $this->_idSuffixes)) {
			$check = $idSuffix . $count++;
		}
		$this->_idSuffixes[] = $check;
		return trim($name . '-' . $check, '-');
	}
}
