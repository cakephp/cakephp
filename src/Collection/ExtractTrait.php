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

/**
 * Provides utility protected methods for extracting a property or column
 * from an array or object.
 */
trait ExtractTrait {

/**
 * Returns a callable that can be used to extract a property or column from
 * an array or object based on a dot separated path.
 *
 * @param string|callable $callback A dot separated path of column to follow
 * so that the final one can be returned or a callable that will take care
 * of doing that.
 * @return callable
 */
	protected function _propertyExtractor($callback) {
		if (is_string($callback)) {
			$path = explode('.', $callback);
			$callback = function($element) use ($path) {
				return $this->_extract($element, $path);
			};
		}

		return $callback;
	}

/**
 * Returns a column from $data that can be extracted
 * by iterating over the column names contained in $path
 *
 * @param array|\ArrayAccess $data
 * @param array $path
 * @return mixed
 */
	protected function _extract($data, $path) {
		$value = null;
		foreach ($path as $column) {
			if (!isset($data[$column])) {
				return null;
			}
			$value = $data[$column];
			$data = $value;
		}
		return $value;
	}

}
