<?php

namespace Cake\ORM;

use Cake\Database\Query as DatabaseQuery;

class Query extends DatabaseQuery {

	protected $_table;

	protected $_containments;

	protected $_hasFields;

	protected $_aliasMap = [];

	public function repository(Table $table = null) {
		if ($table === null) {
			return $this->_table;
		}
		$this->_table = $table;
		$this->addDefaultTypes($table);
		return $this;
	}

	public function addDefaultTypes(Table $table) {
		$alias = $table->alias();
		$fields = [];
		foreach ($table->schema() as $f => $meta) {
			$fields[$f] = $fields[$alias . '.' . $f] = $meta['type'];
		}
		$this->defaultTypes($this->defaultTypes() + $fields);
	}

	public function contain($associations = null) {
		if ($this->_containments === null) {
			$this->_containments = new \ArrayObject;
		}
		if ($associations === null) {
			return $this->_containments;
		}

		foreach ((array)$associations as $table => $options) {
			if (is_string($options)) {
				$table = $options;
				$options = [];
			}
			$this->_containments[$table] = $options;
		}
		return $this;
	}

	public function execute() {
		return new ResultSet($this, parent::execute());
	}

	public function toArray() {
		return $this->execute()->toArray();
	}

	public function aliasedTable($alias) {
		return $this->_aliasMap[$alias];
	}

	public function aliasField($field, $alias = null) {
		$namespaced = strpos($field, '.') !== false;
		$_field = $field;

		if ($namespaced) {
			list($alias, $field) = explode('.', $field);
		}

		if (!$alias) {
			$alias = $this->repository()->alias();
		}

		$key = sprintf('%s__%s', $alias, $field);
		if (!$namespaced) {
			$_field = $alias . '.' . $field;
		}

		return [$key => $_field];
	}

	public function aliasFields($fields, $defaultAlias = null) {
		$aliased = [];
		foreach ($fields as $alias => $field) {
			if (is_numeric($alias) && is_string($field)) {
				$aliased += $this->aliasField($field, $defaultAlias);
				continue;
			}
			$aliased[$alias] = $field;
		}

		return $aliased;
	}


	protected function _transformQuery() {
		if (!$this->_dirty) {
			return parent::_transformQuery();
		}

		$this->from([$this->_table->alias() => $this->_table->table()]);
		$this->_aliasMap[$this->_table->alias()] = $this->_table;
		$this->_addDefaultFields();
		$this->_addContainments();
		return parent::_transformQuery();
	}

	protected function _addContainments() {
		if (empty($this->_containments)) {
			return;
		}

		$contain = [];
		foreach ($this->_containments as $table => $options) {
			$contain[$table] = $this->_normalizeContain(
				$this->_table,
				$table,
				$options
			);
		}

		$firstLevelJoins = $this->_resolveFirstLevel($this->_table, $contain);
		foreach ($firstLevelJoins as $options) {
			$this->_addJoin($options['association'], $options['options']);
		}
	}

	protected function _normalizeContain(Table $parent, $alias, $options) {
		$defaults = [
			'associations' => 1,
			'foreignKey' => 1,
			'conditions' => 1,
			'fields' => 1
		];

		$table = $parent->association($alias)->target();
		$this->_aliasMap[$alias] = $table;

		$extra = array_diff_key($options, $defaults);
		$config = [
			'associations' => [],
			'config' => array_diff_key($options, $extra)
		];

		foreach ($extra as $t => $assoc) {
			if (is_numeric($t)) {
				$t = $assoc;
				$assoc = [];
			}
			$config['associations'][$t] = $this->_normalizeContain($table, $t, $assoc);
		}
		return $config;
	}

	protected function _resolveFirstLevel($source, $associations) {
		$result = [];
		foreach ($associations as $table => $options) {
			$associated = $source->association($table);
			if ($associated && $associated->canBeJoined()) {
				$result[$table] =  [
					'association' => $associated,
					'options' => $options['config']
				];
				$result += $this->_resolveFirstLevel($associated->target(), $options['associations']);
			}
			//TODO: If it is not associated assume a HasOne association (like in the popular Linkable plugin)
		}
		return $result;
	}

	protected function _addJoin($association, $options) {
		$association->attachTo($this, $options + ['includeFields' => !$this->_hasFields]);
	}

	protected function _addDefaultFields() {
		$select = $this->clause('select');
		$this->_hasFields = true;

		if (!count($select)) {
			$this->_hasFields = false;
			$this->select(array_keys($this->repository()->schema()));
			$select = $this->clause('select');
		}

		$aliased = $this->aliasFields($select, $this->repository()->alias());
		$this->select($aliased, true);
	}

}
