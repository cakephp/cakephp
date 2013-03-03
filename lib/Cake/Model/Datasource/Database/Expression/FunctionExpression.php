<?php

namespace Cake\Model\Datasource\Database\Expression;
use Cake\Model\Datasource\Database\Expression;

class FunctionExpression extends QueryExpression {

	protected $_name;

	public function __construct($name, $params = [], $types = []) {
		$this->_name = $name;
		parent::__construct($params, $types, ',');
	}

	public function add($params, $types = []) {
		foreach ($params as $k => $p) {
			if (!is_numeric($k) && $p === 'literal') {
				$this->_conditions[] = $k;
				continue;
			}

			if ($p instanceof Expression) {
				$this->_conditions[] = $p;
				continue;
			}

			$type = isset($types[$k]) ? $types[$k] : null;
			$this->_conditions[] = $this->_bindValue('param', $p, $type);
		}
	}

	public function sql() {
		return $this->_name . sprintf('(%s)', implode(
			$this->_conjunction. ' ',
			$this->_conditions
		));
	}

}
