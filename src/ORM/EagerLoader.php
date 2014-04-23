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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Database\Statement\BufferedStatement;
use Cake\Database\Statement\CallbackStatement;
use Cake\ORM\Query;
use Cake\ORM\Table;
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
 * Contains a list of the association names that are to be eagerly loaded
 *
 * @var array
 */
	protected $_aliasList = [];

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
 * @return array|void
 */
	public function contain($associations = []) {
		if (empty($associations)) {
			return $this->_containments;
		}

		$associations = (array)$associations;
		$associations = $this->_reformatContain($associations, $this->_containments);
		$this->_normalized = $this->_loadExternal = null;
		$this->_aliasList = [];
		return $this->_containments = $associations;
	}

/**
 * Adds a new association to the list that will be used to filter the results of
 * any given query based on the results of finding records for that association.
 * You can pass a dot separated path of associations to this method as its first
 * parameter, this will translate in setting all those associations with the
 * `matching` option.
 *
 * @param string $assoc A single association or a dot separated path of associations.
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
			return (array)$this->_normalized;
		}

		$contain = [];
		foreach ($this->_containments as $alias => $options) {
			if (!empty($options['instance'])) {
				$contain = (array)$this->_containments;
				break;
			}
			$contain[$alias] = $this->_normalizeContain(
				$repository,
				$alias,
				$options,
				['root' => null]
			);
		}

		return $this->_normalized = $contain;
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
				$options = isset($options['config']) ?
					$options['config'] + $options['associations'] :
					$options;
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
 * @param \Cake\ORM\Query $query The query to be modified
 * @param \Cake\ORM\Table $repository The repository containing the associations
 * @param bool $includeFields whether to append all fields from the associations
 * to the passed query. This can be overridden according to the settings defined
 * per association in the containments array
 * @return void
 */
	public function attachAssociations(Query $query, Table $repository, $includeFields) {
		if (empty($this->_containments)) {
			return;
		}

		foreach ($this->attachableAssociations($repository) as $options) {
			$config = $options['config'] + [
				'aliasPath' => $options['aliasPath'],
				'propertyPath' => $options['propertyPath'],
				'includeFields' => $includeFields
			];
			$options['instance']->attachTo($query, $config);
		}
	}

/**
 * Returns an array with the associations that can be fetched using a single query,
 * the array keys are the association aliases and the values will contain an array
 * with the following keys:
 *
 * - instance: the association object instance
 * - config: the options set for fetching such association
 *
 * @param \Cake\ORM\Table $repository The table containing the associations to be
 * attached
 * @return array
 */
	public function attachableAssociations(Table $repository) {
		$contain = $this->normalized($repository);
		return $this->_resolveJoins($contain);
	}

/**
 * Returns an array with the associations that need to be fetched using a
 * separate query, each array value will contain the following keys:
 *
 * - instance: the association object instance
 * - config: the options set for fetching such association
 *
 * @param \Cake\ORM\Table $repository The table containing the associations
 * to be loaded
 * @return array
 */
	public function externalAssociations(Table $repository) {
		if ($this->_loadExternal) {
			return $this->_loadExternal;
		}

		$contain = $this->normalized($repository);
		$this->_resolveJoins($contain);
		return $this->_loadExternal;
	}

/**
 * Auxiliary function responsible for fully normalizing deep associations defined
 * using `contain()`
 *
 * @param Table $parent owning side of the association
 * @param string $alias name of the association to be loaded
 * @param array $options list of extra options to use for this association
 * @param array $paths An array with to values, the first one is a list of dot
 * separated strings representing associations that lead to this `$alias` in the
 * chain of associaitons to be loaded. The second value is the path to follow in
 * entities' properties to fetch a record of the corresponding association.
 * @return array normalized associations
 * @throws \InvalidArgumentException When containments refer to associations that do not exist.
 */
	protected function _normalizeContain(Table $parent, $alias, $options, $paths) {
		$defaults = $this->_containOptions;
		$instance = $parent->association($alias);
		if (!$instance) {
			throw new \InvalidArgumentException(
				sprintf('%s is not associated with %s', $parent->alias(), $alias)
			);
		}

		$paths += ['aliasPath' => '', 'propertyPath' => '', 'root' => $alias];
		$paths['aliasPath'] .= '.' . $alias;
		$paths['propertyPath'] .= '.' . $instance->property();

		$table = $instance->target();

		$extra = array_diff_key($options, $defaults);
		$config = [
			'associations' => [],
			'instance' => $instance,
			'config' => array_diff_key($options, $extra),
			'aliasPath' => trim($paths['aliasPath'], '.'),
			'propertyPath' => trim($paths['propertyPath'], '.')
		];
		$config['canBeJoined'] = $instance->canBeJoined($config['config']);
		$config = $this->_correctStrategy($alias, $config, $paths['root']);

		if ($config['canBeJoined']) {
			$this->_aliasList[$paths['root']][$alias] = true;
		} else {
			$paths['root'] = $config['aliasPath'];
		}

		foreach ($extra as $t => $assoc) {
			$config['associations'][$t] = $this->_normalizeContain($table, $t, $assoc, $paths);
		}

		return $config;
	}

/**
 * Changes the association fetching strategy if required because of duplicate
 * under the same direct associations chain
 *
 * @param string $alias the name of the association to evaluate
 * @param array $config The association config
 * @param string $root An string representing the root association that started
 * the direct chain this alias is in
 * @return array The modified association config
 * @throws \RuntimeException if a duplicate association in the same chain is detected
 * but is not possible to change the strategy due to conflicting settings
 */
	protected function _correctStrategy($alias, $config, $root) {
		if (!$config['canBeJoined'] || empty($this->_aliasList[$root][$alias])) {
			return $config;
		}

		if (!empty($config['config']['matching'])) {
			throw new \RuntimeException(sprintf(
				'Cannot use "matching" on "%s" as there is another association with the same alias',
				$alias
			));
		}

		$config['canBeJoined'] = false;
		$config['config']['strategy'] = $config['instance']::STRATEGY_SELECT;

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
			} else {
				$this->_loadExternal[] = $options;
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
 * @param \Cake\Database\StatementInterface $statement The statement created after executing the $query
 * @return CallbackStatement statement modified statement with extra loaders
 */
	public function loadExternal($query, $statement) {
		$external = $this->externalAssociations($query->repository());
		if (empty($external)) {
			return $statement;
		}

		$driver = $query->connection()->driver();
		list($collected, $statement) = $this->_collectKeys($external, $query, $statement);
		foreach ($external as $meta) {
			$contain = $meta['associations'];
			$alias = $meta['instance']->source()->alias();

			$requiresKeys = $meta['instance']->requiresKeys($meta['config']);
			if ($requiresKeys && empty($collected[$alias])) {
				continue;
			}

			$keys = isset($collected[$alias]) ? $collected[$alias] : null;
			$f = $meta['instance']->eagerLoader(
				$meta['config'] + [
					'query' => $query,
					'contain' => $contain,
					'keys' => $keys,
					'nestKey' => $meta['aliasPath']
				]
			);
			$statement = new CallbackStatement($statement, $driver, $f);
		}

		return $statement;
	}

/**
 * Helper function used to return the keys from the query records that will be used
 * to eagerly load associations.
 *
 * @param array $external the list of external associations to be loaded
 * @param \Cake\ORM\Query $query The query from which the results where generated
 * @param BufferedStatement $statement
 * @return array
 */
	protected function _collectKeys($external, $query, $statement) {
		$collectKeys = [];
		foreach ($external as $meta) {
			if (!$meta['instance']->requiresKeys($meta['config'])) {
				continue;
			}

			$source = $meta['instance']->source();
			$keys = $meta['instance']->type() === $meta['instance']::MANY_TO_ONE ?
				(array)$meta['instance']->foreignKey() :
				(array)$source->primaryKey();

			$alias = $source->alias();
			$pkFields = [];
			foreach ($keys as $key) {
				$pkFields[] = key($query->aliasField($key, $alias));
			}
			$collectKeys[$alias] = [$alias, $pkFields, count($pkFields) === 1];
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
