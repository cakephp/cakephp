<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
 * @package Cake.Utility
 */
trait MergeVariablesTrait {

/**
 * Merge the list of $properties with all parent classes of the current class.
 *
 * @param array $properties An array of properties and the merge strategy for them.
 * @return void
 */
	protected function _mergeVars($properties) {
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
		foreach ($properties as $property => $strategy) {
			if (!property_exists($this, $property)) {
				continue;
			}
			$thisValue = $this->{$property};
			if ($thisValue === null || $thisValue === false) {
				continue;
			}
			$this->_mergeProperty($property, $parents, $strategy);
		}
	}

/**
 * Merge a single property with the values declared in all parent classes.
 *
 * @param string $property The name of the property being merged.
 * @param array $parentClasses An array of classes you want to merge with.
 * @param bool $asAssoc Set true for merging as assoc, false for list.
 * @return void
 */
	protected function _mergeProperty($property, $parentClasses, $asAssoc) {
		$thisValue = $this->{$property};
		if ($asAssoc) {
			$thisValue = Hash::normalize($thisValue);
		}
		foreach ($parentClasses as $class) {
			$parentProperties = get_class_vars($class);
			if (!isset($parentProperties[$property])) {
				continue;
			}
			$parentProperty = $parentProperties[$property];
			if ($asAssoc) {
				$parentProperty = Hash::normalize($parentProperty);
			}
			if (is_array($parentProperty)) {
				$thisValue = Hash::merge($parentProperty, $thisValue);
			}
		}
		$this->{$property} = $thisValue;
	}

}
