<?php

namespace Cake\ORM;

use Cake\Database\Query as DatabaseQuery;
use Cake\Database\Statement\BufferedStatement;
use Cake\Database\Statement\CallbackStatement;
use Cake\Utility\Hash;

class Query extends DatabaseQuery {

/**
 * Instance of a table object this query is bound to
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

	protected $_containments;

	protected $_hasFields;

	protected $_aliasMap = [];

	protected $_loadEagerly = [];

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

	public function contain($associations = null, $override = false) {
		if ($this->_containments === null || $override) {
			$this->_dirty = true;
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
		$this->_dirty = true;
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

/**
 * Auxiliary function used to wrap the original statement from the driver with
 * any registered callbacks. This will also setup the correct statement class
 * in order to eager load deep associations.
 *
 * @param Cake\Database\Statement $statement to be decorated
 * @return Cake\Database\Statement
 */
	protected function _decorateResults($statement) {
		$statement = parent::_decorateResults($statement);
		if ($this->_loadEagerly) {
			if (!($statement instanceof BufferedStatement)) {
				$statement = new BufferedStatement($statement, $this->connection()->driver());
			}
			$statement = $this->_eagerLoad($statement);
		}

		return $statement;
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
		$this->_loadEagerly = [];
		if (empty($this->_containments)) {
			return;
		}

		$contain = [];
		foreach ($this->_containments as $table => $options) {
			if (!empty($options['instance'])) {
				$contain = (array)$this->_containments;
				break;
			}
			$contain[$table] = $this->_normalizeContain(
				$this->_table,
				$table,
				$options
			);
		}

		foreach ($contain as $relation => $meta) {
			if ($meta['instance'] && !$meta['instance']->canBeJoined()) {
				$this->_loadEagerly[$relation] = $meta;
			}
		}

		foreach ($this->_resolveFirstLevel($this->_table, $contain) as $options) {
			$table = $options['instance']->target();
			$alias = $table->alias();
			$this->_aliasMap[$alias] = $table;
			$this->_addJoin($options['instance'], $options['config']);
			foreach ($options['associations'] as $relation => $meta) {
				if ($meta['instance'] && !$meta['instance']->canBeJoined()) {
					$this->_loadEagerly[$relation] = $meta;
				}
			}
		}

	}

	protected function _normalizeContain(Table $parent, $alias, $options) {
		$defaults = [
			'associations' => 1,
			'foreignKey' => 1,
			'conditions' => 1,
			'fields' => 1,
			'sort' => 1
		];

		$instance = $parent->association($alias);
		$table = $instance->target();

		$extra = array_diff_key($options, $defaults);
		$config = [
			'associations' => [],
			'instance' => $instance,
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
			$associated = $options['instance'];
			if ($associated && $associated->canBeJoined()) {
				$result[$table] = $options;
				$result += $this->_resolveFirstLevel($associated->target(), $options['associations']);
			}
			//TODO: If it is not associated assume a HasOne association (like in the popular Linkable plugin)
		}
		return $result;
	}

	protected function _addJoin($association, $options) {
		$association->attachTo($this, $options + ['includeFields' => !$this->_hasFields]);
	}

	protected function _eagerLoad($statement) {
		$collectKeys = [];
		foreach ($this->_loadEagerly as $association => $meta) {
			$source = $meta['instance']->source();
			$alias = $source->alias();
			$pkField = key($this->aliasField($source->primaryKey(), $alias));
			$collectKeys[] = [$alias, $pkField];
		}

		$keys = [];
		while($result = $statement->fetch('assoc')) {
			foreach ($collectKeys as $parts) {
				$keys[$parts[0]][] = $result[$parts[1]];
			}
		}

		$statement->rewind();
		foreach ($this->_loadEagerly as $association => $meta) {
			$contain = $meta['associations'];
			$f = $meta['instance']->eagerLoader(
				$this,
				$meta['config'] + compact('contain'),
				$keys[$meta['instance']->source()->alias()]
			);
			$statement = new CallbackStatement($statement, $this->connection()->driver(), $f);
		}

		return $statement;
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
