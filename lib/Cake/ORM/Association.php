<?php
/**
 * PHP Version 5.4
 *
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

abstract class Association {

	protected $_name;

	protected $_canBeJoined = false;

	protected $_className;

	protected $_foreignKey;

	protected $_conditions = [];

	protected $_dependent = false;

	protected $_table;

	public function __construct($name, array $options = []) {
		$defaults = ['className', 'foreignKey', 'conditions',  'dependent'];
		foreach ($defaults as $property) {
			if (isset($options[$property])) {
				$this->{'_' . $property} = $options[$property];
			}
		}

		$this->_name = $name;
		$this->_options($options);

		if (empty($this->_className)) {
			$this->_className = $this->_name;
		}
	}

	public function name($name = null) {
		if ($name !== null) {
			$this->_name = $name;
		}
		return $this->_name;
	}

	public function repository(Table $table = null) {
		if ($table === null && $this->_table) {
			return $this->_table;
		}

		if ($table !== null) {
			return $this->_table = $table;
		}

		if ($table === null && $this->_className !== null) {
			$this->_table = Table::build(
				$this->_name,
				['className' => $this->_className]
			);
		}
		return $this->_table;
	}

	public function conditions($conditions = null) {
		if ($conditions !== null) {
			$this->_conditions = $conditions;
		}
		return $this->_conditions;
	}

	public function foreignKey($key = null) {
		if ($key !== null) {
			$this->_foreignKey = $key;
		}
		return $this->_foreignKey;
	}

	public function dependent($dependent = null) {
		if ($dependent !== null) {
			$this->_dependent = $dependent;
		}
		return $this->_dependent;
	}

	public function canBeJoined() {
		return $this->_canBeJoined;
	}

	protected  function _options(array $options) {
	}

	public abstract function attachTo(Query $query, array $options = []);

}
