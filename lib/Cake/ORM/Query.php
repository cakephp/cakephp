<?php

namespace Cake\ORM;

use Cake\Database\Query as DatabaseQuery;

class Query extends DatabaseQuery {

	protected $_table;

	public function repository(Table $table = null) {
		if ($table === null) {
			return $this->_table;
		}
		$this->_table = $table;
		$this->_addDefaultTypes($table);
		return $this;
	}

	public function execute() {
		return new ResultSet($this, parent::execute());
	}

	public function toArray() {
		return $this->execute()->toArray();
	}

	public function aliasField($field) {
		if (strpos($field, '.') !== false) {
			list($alias, $field) = explode('.', $field);
		} else {
			$alias = $this->repository()->alias();
		}
		return sprintf('%s__%s', $alias, $field);
	}

	public function aliasedTable($alias) {
		return $this->repository();
	}

	protected function _transformQuery() {
		if (!$this->_dirty) {
			return parent::_transformQuery();
		}

		$this->_aliasFields();
		return parent::_transformQuery();
	}

	protected function _aliasFields() {
		$select = $this->clause('select');
		$schema = $this->repository()->schema();

		if (!count($select)) {
			$this->select(array_keys($schema));
			$select = $this->clause('select');
		}

		$aliased = [];
		foreach ($select as $alias => $field) {
			if (is_numeric($alias) && is_string($field)) {
				$alias = $this->aliasField($field);
			}
			$aliased[$alias] = $field;
		}
		$this->select($aliased, true);
	}

	protected function _addDefaultTypes(Table $table) {
		$alias = $table->alias();
		$fields = [];
		foreach ($table->schema() as $f => $meta) {
			$fields[$f] = $fields[$alias . '.' . $f] = $meta['type'];
		}
		$this->defaultTypes($this->defaultTypes() + $fields);
	}

}
