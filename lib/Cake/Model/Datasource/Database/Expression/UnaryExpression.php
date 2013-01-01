<?php

namespace Cake\Model\Datasource\Database\Expression;

class UnaryExpression extends QueryExpression {

	public function sql() {
		return $this->_conjunction . ' (' . ((string)current($this->_conditions)) . ')';
	}

}
