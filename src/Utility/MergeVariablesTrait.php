<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Utility;

use Cake\Utility\Hash;

/**
 * Provides features for merging object properties recursively with
 * parent classes.
 *
 */
trait MergeVariablesTrait {

/**
 * Merge the list of $properties with all parent classes of the current class.
 *
 * ### Options:
 *
 * - `associative` - A list of properties that should be treated as associative arrays.
 *   Properties in this list will be passed through Hash::normalize() before merging.
 *
 * @param array $properties An array of properties and the merge strategy for them.
 * @param array $options The options to use when merging properties.
 * @return void
 */
	protected function _mergeVars($properties, $options = []) {
		$class = get_class($this);
		$parents = [];
		while (true) {
			$parent = get_parent_class($class);
			if (!$parent) {
				break;
			}
			$parents[] = $parent;
			$class = $parent;
		}
		foreach ($properties as $property) {
			if (!property_exists($this, $property)) {
				continue;
			}
			$thisValue = $this->{$property};
			if ($thisValue === null || $thisValue === false) {
				continue;
			}
			$this->_mergeProperty($property, $parents, $options);
		}
	}

/**
 * Merge a single property with the values declared in all parent classes.
 *
 * @param string $property The name of the property being merged.
 * @param array $parentClasses An array of classes you want to merge with.
 * @param array $options Options for merging the property, see _mergeVars()
 * @return void
 */
	protected function _mergeProperty($property, $parentClasses, $options) {
		$thisValue = $this->{$property};
		$isAssoc = false;
		if (
			isset($options['associative']) &&
			in_array($property, (array)$options['associative'])
		) {
			$isAssoc = true;
		}

		if ($isAssoc) {
			$thisValue = Hash::normalize($thisValue);
		}
		foreach ($parentClasses as $class) {
			$parentProperties = get_class_vars($class);
			if (!isset($parentProperties[$property])) {
				continue;
			}
			$parentProperty = $parentProperties[$property];
			if (empty($parentProperty) || $parentProperty === true) {
				continue;
			}
			if ($isAssoc) {
				$parentProperty = Hash::normalize($parentProperty);
			}
			$thisValue = Hash::merge($parentProperty, $thisValue);
		}
		$this->{$property} = $thisValue;
	}

}
