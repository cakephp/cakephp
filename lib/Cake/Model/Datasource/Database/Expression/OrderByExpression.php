<?php

namespace Cake\Model\Datasource\Database\Expression;

use Cake\Model\Datasource\Database\Expression;

class OrderByExpression extends QueryExpression {

	public function __construct($conditions = [], $types = [], $conjunction = '') {
		parent::__construct($conditions, $types, $conjunction);
	}

	public function sql() {
		$order = [];
		foreach ($this->_conditions as $k => $direction) {
			$order[] = is_numeric($k) ? $direction : sprintf('%s %s', $k, $direction);
		}
		return sprintf ('ORDER BY %s', implode(', ', $order));
	}

	protected function _addConditions(array $conditions, array $types) {
		$this->_conditions = array_merge($this->_conditions, $conditions);
	}

}
