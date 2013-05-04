<?php

namespace Cake\ORM;

use Cake\Database\Query as DatabaseQuery;
use Cake\Utility\Inflector;

class Query extends DatabaseQuery {

	protected $_table;

	protected $_containments;

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

		$this->_addContainments();
		$this->_aliasFields();
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
		$options = array_intersect_key($options, $joinOptions);
		$this->join([$alias => $options]);
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
