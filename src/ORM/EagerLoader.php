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

use Closure;

/**
 *
 */
class EagerLoader {

/**
 * Nested array describing the association to be fetched
 * and the options to apply for each of them, if any
 *
 * @var \ArrayObject
 */
	protected $_containments;

/**
 * Contains a nested array with the compiled containments tree
 * This is a normalized version of the user provided containments array.
 *
 * @var array
 */
	protected $_normalizedContainments;

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
 * A list of associations that should be eagerly loaded
 *
 * @var array
 */
	protected $_loadEagerly = [];

	public function contain($associations = null, $override = false) {
		if ($this->_containments === null || $override) {
			$this->_containments = new \ArrayObject;
		}

		if ($associations === null) {
			return $this->_containments;
		}

		$associations = (array)$associations;
		$current = current($associations);
		if (is_array($current) && isset($current['instance'])) {
			$this->_containments = $this->_normalizedContainments = $associations;
			return;
		}

		$old = $this->_containments->getArrayCopy();
		$associations = $this->_reformatContain($associations, $old);
		$this->_containments->exchangeArray($associations);
		$this->_normalizedContainments = null;
	}

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
 * Returns the fully normalized array of associations that should be eagerly
 * loaded. The normalized array will restructure the original one by sorting
 * all associations under one key and special options under another.
 *
 * Additionally it will set an 'instance' key per association containing the
 * association instance from the corresponding source table
 *
 * @return array
 */
	public function normalizedContainments() {
		if ($this->_normalizedContainments !== null || empty($this->_containments)) {
			return $this->_normalizedContainments;
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

		return $this->_normalizedContainments = $contain;
	}

/**
 * Helper function used to add the required joins for associations defined using
 * `contain()`
 *
 * @return void
 */
	protected function _addContainments() {
		$this->_loadEagerly = [];
		if (empty($this->_containments)) {
			return;
		}

		$contain = $this->normalizedContainments();
		foreach ($contain as $relation => $meta) {
			if ($meta['instance'] && !$meta['canBeJoined']) {
				$this->_loadEagerly[$relation] = $meta;
			}
		}

		foreach ($this->_resolveJoins($this->_table, $contain) as $options) {
			$table = $options['instance']->target();
			$this->_addJoin($options['instance'], $options['config']);
			foreach ($options['associations'] as $relation => $meta) {
				if ($meta['instance'] && !$meta['canBeJoined']) {
					$this->_loadEagerly[$relation] = $meta;
				}
			}
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
		return $config;
	}

/**
 * Helper function used to compile a list of all associations that can be
 * joined in this query.
 *
 * @param Table $source the owning side of the association
 * @param array $associations list of associations for $source
 * @return array
 */
	protected function _resolveJoins($source, $associations) {
		$result = [];
		foreach ($associations as $table => $options) {
			$associated = $options['instance'];
			if ($options['canBeJoined']) {
				$result[$table] = $options;
				$result += $this->_resolveJoins($associated->target(), $options['associations']);
			}
		}
		return $result;
	}

/**
 * Helper function used to return the keys from the query records that will be used
 * to eagerly load associations.
 *
 *
 * @param BufferedStatement $statement
 * @return array
 */
	protected function _collectKeys($statement) {
		$collectKeys = [];
		foreach ($this->_loadEagerly as $meta) {
			$source = $meta['instance']->source();
			if ($meta['instance']->requiresKeys($meta['config'])) {
				$alias = $source->alias();
				$pkFields = [];
				foreach ((array)$source->primaryKey() as $key) {
					$pkFields[] = key($this->aliasField($key, $alias));
				}
				$collectKeys[$alias] = [$alias, $pkFields, count($pkFields) === 1];
			}
		}

		$keys = [];
		if (!empty($collectKeys)) {
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
		}

		return $keys;
	}

}
