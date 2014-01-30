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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM;

use Cake\Database\Statement\BufferedStatement;
use Cake\Database\Statement\CallbackStatement;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Closure;

/**
 * Exposes the methods for storing the associations that should be eager loaded
 * for a table once a query is provided and delegates the job of creating the
 * required joins and decorating the results so that those associations can be
 * part of the result set.
 */
class EagerLoader {

/**
 * Nested array describing the association to be fetched
 * and the options to apply for each of them, if any
 *
 * @var array
 */
	protected $_containments = [];

/**
 * Contains a nested array with the compiled containments tree
 * This is a normalized version of the user provided containments array.
 *
 * @var array
 */
	protected $_normalized;

/**
 * List of options accepted by associations in contain()
 * index by key for faster access
 *
 * @var array
 */
	protected $_containOptions = [
		'associations' => 1,
		'foreignKey' => 1,
		'conditions' => 1,
		'fields' => 1,
		'sort' => 1,
		'matching' => 1,
		'queryBuilder' => 1
	];

/**
 * A list of associations that should be loaded with a separate query
 *
 * @var array
 */
	protected $_loadExternal = [];


/**
 * Sets the list of associations that should be eagerly loaded along for a
 * specific table using when a query is provided. The list of associated tables
 * passed to this method must have been previously set as associations using the
 * Table API.
 *
 * Associations can be arbitrarily nested using dot notation or nested arrays,
 * this allows this object to calculate joins or any additional queries that
 * must be executed to bring the required associated data.
 *
 * Accepted options per passed association:
 *
 * - foreignKey: Used to set a different field to match both tables, if set to false
 *   no join conditions will be generated automatically
 * - fields: An array with the fields that should be fetched from the association
 * - queryBuilder: Equivalent to passing a callable instead of an options array
 * - matching: Whether to inform the association class that it should filter the
 *  main query by the results fetched by that class.
 *
 * @param array|string $associations list of table aliases to be queried.
 * When this method is called multiple times it will merge previous list with
 * the new one.
 * @return array
 */
	public function contain($associations = []) {
		if (empty($associations)) {
			return $this->_containments;
		}

		$associations = (array)$associations;
		$current = current($associations);
		if (is_array($current) && isset($current['instance'])) {
			$this->_containments = $this->_normalized = $associations;
			return;
		}

		$associations = $this->_reformatContain($associations, $this->_containments);
		$this->_normalized = null;
		return $this->_containments = $associations;
	}

/**
 * Adds a new association to the list that will be used to filter the results of
 * any given query based on the results of finding records for that association.
 * You can pass a dot separated path of associations to this method as its first
 * parameter, this will translate in setting all those associations with the
 * `matching` option.
 *
 * @param string A single association or a dot separated path of associations.
 * @param callable $builder the callback function to be used for setting extra
 * options to the filtering query
 * @return array The resulting containments array
 */
	public function matching($assoc, callable $builder = null) {
		$assocs = explode('.', $assoc);
		$last = array_pop($assocs);
		$containments = [];
		$pointer =& $containments;

		foreach ($assocs as $name) {
			$pointer[$name] = ['matching' => true];
			$pointer =& $pointer[$name];
		}

		$pointer[$last] = ['queryBuilder' => $builder, 'matching' => true];
		return $this->contain($containments);
	}

/**
 * Returns the fully normalized array of associations that should be eagerly
 * loaded for a table. The normalized array will restructure the original array
 * by sorting all associations under one key and special options under another.
 *
 * Additionally it will set an 'instance' key per association containing the
 * association instance from the corresponding source table
 *
 * @param \Cake\ORM\Table $repository The table containing the association that
 * will be normalized
 * @return array
 */
	public function normalized(Table $repository) {
		if ($this->_normalized !== null || empty($this->_containments)) {
			return $this->_normalized;
		}

		$contain = [];
		foreach ($this->_containments as $table => $options) {
			if (!empty($options['instance'])) {
				$contain = (array)$this->_containments;
				break;
			}
			$contain[$table] = $this->_normalizeContain($repository, $table, $options);
		}

		return $this->_normalized = $contain;
	}

/**
 * Returns whether or not there are associations that need to be loaded by
 * decorating results from a query and executing a separate one for ijecting
 * them.
 *
 * @param \Cake\ORM\Table The table containing the associations described in
 * the `contain` array
 * @return boolean
 */
	protected function _hasExternal(Table $repository) {
		$this->normalized($repository);
		return !empty($this->_loadExternal);
	}

/**
 * Formats the containments array so that associations are always set as keys
 * in the array. This function merges the original associations array with
 * the new associations provided
 *
 * @param array $associations user provided containments array
 * @param array $original The original containments array to merge
 * with the new one
 * @return array
 */
	protected function _reformatContain($associations, $original) {
		$result = $original;

		foreach ((array)$associations as $table => $options) {
			$pointer =& $result;
			if (is_int($table)) {
				$table = $options;
				$options = [];
			}

			if (isset($this->_containOptions[$table])) {
				$pointer[$table] = $options;
				continue;
			}

			if (strpos($table, '.')) {
				$path = explode('.', $table);
				$table = array_pop($path);
				foreach ($path as $t) {
					$pointer += [$t => []];
					$pointer =& $pointer[$t];
				}
			}

			if (is_array($options)) {
				$options = $this->_reformatContain($options, []);
			}

			if ($options instanceof Closure) {
				$options = ['queryBuilder' => $options];
			}

			$pointer += [$table => []];
			$pointer[$table] = $options + $pointer[$table];
		}

		return $result;
	}

/**
 * Modifies the passed query to apply joins or any other transformation required
 * in order to eager load the associations described in the `contain` array.
 * This method will not modify the query for loading external associations, i.e.
 * those that cannot be loaded without executing a separate query.
 *
 * @param \Cake\ORM\Query The query to be modified
 * @param boolean $includeFields whether to append all fields from the associations
 * to the passed query. This can be overridden according to the settings defined
 * per association in the containments array
 * @return void
 */
	public function attachAssociations(Query $query, $includeFields) {
		if (empty($this->_containments)) {
			return;
		}

		$contain = $this->normalized($query->repository());
		foreach ($this->_resolveJoins($contain) as $options) {
			$config = $options['config'] + ['includeFields' => $includeFields];
			$options['instance']->attachTo($query, $config);
		}
	}

/**
 * Auxiliary function responsible for fully normalizing deep associations defined
 * using `contain()`
 *
 * @param Table $parent owning side of the association
 * @param string $alias name of the association to be loaded
 * @param array $options list of extra options to use for this association
 * @return array normalized associations
 * @throws \InvalidArgumentException When containments refer to associations that do not exist.
 */
	protected function _normalizeContain(Table $parent, $alias, $options) {
		$defaults = $this->_containOptions;
		$instance = $parent->association($alias);
		if (!$instance) {
			throw new \InvalidArgumentException(
				sprintf('%s is not associated with %s', $parent->alias(), $alias)
			);
		}

		$table = $instance->target();

		$extra = array_diff_key($options, $defaults);
		$config = [
			'associations' => [],
			'instance' => $instance,
			'config' => array_diff_key($options, $extra)
		];
		$config['canBeJoined'] = $instance->canBeJoined($config['config']);

		foreach ($extra as $t => $assoc) {
			$config['associations'][$t] = $this->_normalizeContain($table, $t, $assoc);
		}

		if (!$config['canBeJoined']) {
			$this->_loadExternal[$alias] = $config;
		}

		return $config;
	}

/**
 * Helper function used to compile a list of all associations that can be
 * joined in the query.
 *
 * @param array $associations list of associations for $source
 * @return array
 */
	protected function _resolveJoins($associations) {
		$result = [];
		foreach ($associations as $table => $options) {
			if ($options['canBeJoined']) {
				$result[$table] = $options;
				$result += $this->_resolveJoins($options['associations']);
			}
		}
		return $result;
	}

/**
 * Decorates the passed statement object in order to inject data form associations
 * that cannot be joined directly.
 *
 * @param \Cake\ORM\Query $query The query for which to eager load external
 * associations
 * @param Statement $statement The statement created after executing the $query
 * @return CallbackStatement $statement modified statement with extra loaders
 */
	public function loadExternal($query, $statement) {
		if (!$this->_hasExternal($query->repository())) {
			return $statement;
		}

		$driver = $query->connection()->driver();
		list($collected, $statement) = $this->_collectKeys($query, $statement);
		foreach ($this->_loadExternal as $meta) {
			$contain = $meta['associations'];
			$alias = $meta['instance']->source()->alias();
			$keys = isset($collected[$alias]) ? $collected[$alias] : null;
			$f = $meta['instance']->eagerLoader(
				$meta['config'] + ['query' => $query, 'contain' => $contain, 'keys' => $keys]
			);
			$statement = new CallbackStatement($statement, $driver, $f);
		}

		return $statement;
	}

/**
 * Helper function used to return the keys from the query records that will be used
 * to eagerly load associations.
 *
 *
 * @param BufferedStatement $statement
 * @return array
 */
	protected function _collectKeys($query, $statement) {
		$collectKeys = [];
		foreach ($this->_loadExternal as $meta) {
			$source = $meta['instance']->source();
			if ($meta['instance']->requiresKeys($meta['config'])) {
				$alias = $source->alias();
				$pkFields = [];
				foreach ((array)$source->primaryKey() as $key) {
					$pkFields[] = key($query->aliasField($key, $alias));
				}
				$collectKeys[$alias] = [$alias, $pkFields, count($pkFields) === 1];
			}
		}

		if (empty($collectKeys)) {
			return [[], $statement];
		}

		if (!($statement instanceof BufferedStatement)) {
			$statement = new BufferedStatement($statement, $query->connection()->driver());
		}

		return [$this->_groupKeys($statement, $collectKeys), $statement];
	}

/**
 * Helper function used to iterate an statement and extract the columns
 * defined in $collectKeys
 *
 * @param \Cake\Database\StatementInterface $statement
 * @param array $collectKeys
 * @return array
 */
	protected function _groupKeys($statement, $collectKeys) {
		$keys = [];
		while ($result = $statement->fetch('assoc')) {
			foreach ($collectKeys as $parts) {
				if ($parts[2]) {
					$keys[$parts[0]][] = $result[$parts[1][0]];
					continue;
				}

				$collected = [];
				foreach ($parts[1] as $key) {
					$collected[] = $result[$key];
				}
				$keys[$parts[0]][] = $collected;
			}
		}

		$statement->rewind();
		return $keys;
	}

}
