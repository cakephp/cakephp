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
use Cake\ORM\Rule\IsUnique;

/**
 * Contains logic for storing and checking domain rules on entities
 *
 */
class DomainChecker {

	protected $_rules = [];

	protected $_createRules = [];

	protected $_updateRules = [];

	protected $_options = [];

	public function __construct(array $options) {
		$this->_options = $options;
	}

	public function add(callable $rule) {
		$this->_rules[] = $rule;
		return $this;
	}

	public function addCreate(callable $rule) {
		$this->_createRules[] = $rule;
		return $this;
	}

	public function addUpdate(callable $rule) {
		$this->_updateRules[] = $rule;
		return $this;
	}

	public function checkCreate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_createRules) as $rule) {
			$success = $rule($entity, $this->_options) && $success;
		}
		return $success;
	}

	public function checkUpdate(EntityInterface $entity) {
		$success = true;
		foreach (array_merge($this->_rules, $this->_updateRules) as $rule) {
			$success = $rule($entity, $this->_options) && $success;
		}
		return $success;
	}

	public function isUnique(array $fields) {
		return new IsUnique($fields);
	}

}
