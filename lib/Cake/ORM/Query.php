<?php

namespace Cake\ORM;

use Cake\Database\Query as DatabaseQuery;
use Cake\Utility\Inflector;

class Query extends DatabaseQuery {

	protected $_table;

	protected $_containments;

	protected $_hasFields;

	public function repository(Table $table = null) {
		if ($table === null) {
			return $this->_table;
		}
		$this->_table = $table;
		$this->_addDefaultTypes($table);
		return $this;
	}

	public function contain($associations = null) {
		if ($this->_containments === null) {
			$this->_containments = new \ArrayObject;
		}
		if ($associations === null) {
			return $this->_containments;
		}
		foreach ($associations as $table => $options) {
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

	public function aliasedTable() {
		return $this->repository();
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

	protected function _transformQuery() {
		if (!$this->_dirty) {
			return parent::_transformQuery();
		}

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
				$this->repository(),
				$table,
				$options
			);
		}

		$firstLevelJoins = $this->_resolveFirstLevel($contain);
		foreach ($firstLevelJoins as $table => $options) {
			$this->_addJoin($table, $options);
		}
	}

	protected function _normalizeContain(Table $parent, $alias, $options) {
		$defaults = [
			'table' => 1,
			'associationType' => 1,
			'associations' => 1,
			'foreignKey' => 1,
			'conditions' => 1,
			'fields' => 1
		];
		$table = Table::build($alias);

		if (is_string($options)) {
			//TODO: finish extracting 
			$options = $table->associated($options);
		}

		$extra = array_diff_key($options, $defaults);
		$config = array_diff_key($options, $extra) + [
			'associations' => [],
			'table' => $table->table(),
			'type' => 'left'
		];
		$config = $this->_resolveForeignKeyConditions($table, $parent, $config);

		if (empty($config['fields'])) {
			$f = isset($config['fields']) ? $config['fields'] : null;
			if (!$this->_hasFields && ($f === null || $f !== false)) {
				$config['fields'] = array_keys($table->schema());
			}
		}

		foreach ($extra as $t => $assoc) {
			$config['associations'][$t] = $this->_normalizeContain($table, $t, $assoc);
		}
		return $config;
	}

	protected function _resolveForeignKeyConditions(Table $table, Table $parent, array $config) {
		if (!isset($config['foreignKey']) || $config['foreignKey'] !== false) {
			$target = $config['associationType'] === 'belongsTo' ? $table : $parent;
			$config['foreignKey'] = Inflector::underscore($target->alias()) . '_id';
		}

		if (!empty($config['foreignKey'])) {
			if ($config['associationType'] === 'belongsTo') {
				$config['conditions'][] =  sprintf('%s.%s = %s.%s',
					$table->alias(),
					'id',
					$parent->alias(),
					$config['foreignKey']
				);
			}
			if ($config['associationType'] === 'hasOne') {
				$config['conditions'][] = sprintf('%s.%s = %s.%s', 
					$table->alias(),
					$config['foreignKey'],
					$parent->alias(),
					'id'
				);
			}
		}
		return $config;
	}

	protected function _resolveFirstLevel($associations) {
		$result = [];
		foreach ($associations as $table => $options) {
			foreach (['belongsTo', 'hasOne'] as $type) {
				if ($options['associationType'] === $type) {
					$result += [$table => array_diff_key($options, ['associations' => 1])];
					$result += $this->_resolveFirstLevel($options['associations']);
				}
			}
		}
		return $result;
	}

	protected function _addJoin($alias, $options) {
		$joinOptions = ['table' => 1, 'conditions' => 1, 'type' => 1];
		$this->join([$alias => array_intersect_key($options, $joinOptions)]);

		if (!empty($options['fields'])) {
			$this->select($this->_aliasFields($options['fields'], $alias));
		}
	}

	protected function _aliasFields($fields, $defaultAlias = null) {
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

	protected function _addDefaultFields() {
		$select = $this->clause('select');
		$this->_hasFields = true;

		if (!count($select)) {
			$this->_hasFields = false;
			$this->select(array_keys($this->repository()->schema()));
			$select = $this->clause('select');
		}

		$aliased = $this->_aliasFields($select, $this->repository()->alias());
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
