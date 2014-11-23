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
namespace Cake\ORM;

use Cake\Datasource\EntityInterface;

/**
 * Contains logic for storing and checking domain rules on entities
 *
 */
class DomainChecker {

	protected $_rules = [];

	protected $_createRules = [];

	protected $_updateRules = [];

	public function addAlways(callable $rule) {
	}

	public function addCreate(callable $rule) {
	}

	public function addUpdate(callable $rule) {
	}

	public function checkCreate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_createRules) as $rule) {
			$success = $success && $rule($entity);
		}
		return $succcess;
	}

	public function checkUpdate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_updateRules) as $rule) {
			$success = $success && $rule($entity);
		}
		return $succcess;
	}

}
