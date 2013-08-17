<?php
/**
 * PHP Version 5.4
 *
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

use Cake\Database\Query as DatabaseQuery;
use Cake\Database\Statement\BufferedStatement;
use Cake\Database\Statement\CallbackStatement;

/**
 * Extends the base Query class to provide new methods related to association
 * loading, automatic fields selection, automatic type casting and to wrap results
 * into an specific iterator that will be responsible for hydrating results if
 * required.
 *
 */
class Query extends DatabaseQuery {

/**
 * Instance of a table object this query is bound to
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

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
 * Whether the user select any fields before being executed, this is used
 * to determined if any fields should be automatically be selected.
 *
 * @var boolean
 */
	protected $_hasFields;

/**
 * A list of associations that should be eagerly loaded
 *
 * @var array
 */
	protected $_loadEagerly = [];

	protected $_mapReduce = [];

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
		'matching' => 1
	];

/**
 * A ResultSet.
 *
 * When set, query execution will be bypassed.
 *
 * @var Cake\ORM\ResultSet
 * @see setResult()
 */
	protected $_results;

/**
 * Boolean for tracking whether or not buffered results
 * are enabled.
 *
 * @var boolean
 */
	protected $_useBufferedResults = false;

	protected $_formatters = [];

/**
 * @param Cake\Database\Connection $connection
 * @param Cake\ORM\Table $table
 */
	public function __construct($connection, $table) {
		$this->connection($connection);
		$this->repository($table);
	}

/**
 * Returns the default table object that will be used by this query,
 * that is, the table that will appear in the from clause.
 *
 * When called with a Table argument, the default table object will be set
 * and this query object will be returned for chaining.
 *
 * @param \Cake\ORM\Table $table The default table object to use
 * @var \Cake\ORM\Table|Query
 */
	public function repository(Table $table = null) {
		if ($table === null) {
			return $this->_table;
		}
		$this->_table = $table;
		$this->addDefaultTypes($table);
		return $this;
	}

/**
 * Hints this object to associate the correct types when casting conditions
 * for the database. This is done by extracting the field types from the schema
 * associated to the passed table object. This prevents the user from repeating
 * himself when specifying conditions.
 *
 * This method returns the same query object for chaining.
 *
 * @param \Cake\ORM\Table $table
 * @return Query
 */
	public function addDefaultTypes(Table $table) {
		$alias = $table->alias();
		$schema = $table->schema();
		$fields = [];
		foreach ($schema->columns() as $f) {
			$fields[$f] = $fields[$alias . '.' . $f] = $schema->columnType($f);
		}
		$this->defaultTypes($this->defaultTypes() + $fields);

		return $this;
	}

/**
 * Sets the list of associations that should be eagerly loaded along with this
 * query. The list of associated tables passed must have been previously set as
 * associations using the Table API.
 *
 * ### Example:
 *
 * {{{
 *	// Bring articles' author information
 *	$query->contain('Author');
 *
 *	// Also bring the category and tags associated to each article
 *	$query->contain(['Category', 'Tag']);
 * }}}
 *
 * Associations can be arbitrarily nested using arrays, this allows this object to
 * calculate joins or any additional queries that must be executed to bring the
 * required associated data.
 *
 * ### Example:
 *
 * {{{
 *	// Eager load the product info, and for each product load other 2 associations
 *	$query->contain(['Product' => ['Manufacturer', 'Distributor']);
 *
 *	// For an author query, load his region, state and country
 *	$query->contain(['Region' => ['State' => 'Country']);
 * }}}
 *
 * Each association might define special options when eager loaded, the allowed
 * options that can be set per association are:
 *
 * - foreignKey: Used to set a different field to match both tables, if set to false
 *   no join conditions will be generated automatically
 * - conditions: An array of conditions that will be passed to either a query or
 *   join conditions. See `where` method for the valid format.
 * - fields: An array with the fields that should be fetched from the association
 * - sort: for associations that are not joined directly, the order they should
 *   appear in the resulting set
 * - matching: A boolean indicating if the parent association records should be
 *   filtered by those matching the conditions in the target association.
 *
 * ### Example:
 *
 * {{{
 *  // Set options for the articles that will be eagerly loaded for an author
 *	$query->contain([
 *		'Article' => [
 *			'field' => ['title'],
 *			'conditions' => ['read_count >' => 100],
 *			'sort' => ['published' => 'DESC']
 *		]
 *	]);
 *
 *	// Use special join conditions for getting an article author's 'likes'
 *	$query->contain([
 *		'Like' => [
 *			'foreignKey' => false,
 *			'conditions' => ['Article.author_id = Like.user_id']
 *		]
 *	]);
 *
 *	// Bring only articles that were tagged with 'cake'
 *	$query->contain([
 *		'Tag' => [
 *			'matching' => true,
 *			'conditions' => ['Tag.name' => 'cake']
 *		]
 *	]);
 * }}}
 *
 * If called with no arguments, this function will return an ArrayObject with
 * with the list of previously configured associations to be contained in the
 * result. This object can be modified directly as the reference is kept inside
 * the query.
 *
 * The resulting ArrayObject will always have association aliases as keys, and
 * options as values, if no options are passed, the values will be set to an empty
 * array
 *
 * ### Example:
 *
 * {{{
 *	// Set some associations
 *	$query->contain(['Article', 'Author' => ['fields' => ['Author.name']);
 *
 *  // Let's now add another field to Author
 *	$query->contain()['Author']['fields'][] = 'Author.email';
 *
 *	// Let's also add Article's tags
 *	$query->contain()['Article']['Tag'] = [];
 * }}}
 *
 * Please note that when modifying directly the containments array, you are
 * required to maintain the structure. That is, association names as keys
 * having array values. Failing to do so will result in an error
 *
 * If called with an empty first argument and $override is set to true, the
 * previous list will be emptied.
 *
 * @param array|string $associations list of table aliases to be queried
 * @param boolean $override whether override previous list with the one passed
 * defaults to merging previous list with the new one.
 * @return \ArrayObject|Query
 */
	public function contain($associations = null, $override = false) {
		if ($this->_containments === null || $override) {
			$this->_dirty();
			$this->_containments = new \ArrayObject;
		}

		if ($associations === null) {
			return $this->_containments;
		}

		$associations = (array)$associations;
		if (isset(current($associations)['instance'])) {
			$this->_containments = $this->_normalizedContainments = $associations;
			return $this;
		}

		$old = $this->_containments->getArrayCopy();
		$associations = array_merge($old, $this->_reformatContain($associations));
		$this->_containments->exchangeArray($associations);
		$this->_normalizedContainments = null;
		$this->_dirty();
		return $this;
	}

/**
 * Formats the containments array so that associations are always set as keys
 * in the array.
 *
 * @param array $associations user provided containments array
 * @return array
 */
	protected function _reformatContain($associations) {
		$result = [];
		foreach ((array)$associations as $table => $options) {
			if (is_int($table)) {
				$table = $options;
				$options = [];
			} elseif (is_array($options) && !isset($this->_containOptions[$table])) {
				$options = $this->_reformatContain($options);
			}
			$result[$table] = $options;
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
 * Enable buffered results.
 *
 * When enabled the ResultSet returned by this Query will be
 * buffered. This enables you to iterate a ResultSet multiple times, or
 * both cache and iterate the ResultSet.
 *
 * This mode will consume more memory as the result set will stay in memory
 * until the ResultSet if freed.
 *
 * @return Query The query instance;
 */
	public function bufferResults() {
		$this->_useBufferedResults = true;
		return $this;
	}

	public function formatResults($current = null, $key = null) {
		$this->_formatters[] = compact('current', 'key');
		return $this;
	}

/**
 * Set the result set for a query.
 *
 * Setting the resultset of a query will make execute() a no-op. Instead
 * of executing the SQL query and fetching results, the ResultSet provided to this
 * method will be returned.
 *
 * This method is most useful when combined with results stored in a persistent cache.
 *
 * @param Cake\ORM\ResultSet $results The results this query should return.
 * @return Query The query instance.
 */
	public function setResult($results) {
		$this->_results = $results;
		return $this;
	}

/**
 * Compiles the SQL representation of this query and executes it using the
 * provided connection object. Returns a ResultSet iterator object.
 *
 * If a result set was set using setResult() that ResultSet will be returned.
 *
 * Resulting object is traversable, so it can be used in any loop as you would
 * with an array.
 *
 * @return Cake\ORM\ResultSet
 */
	public function execute() {
		if (isset($this->_results)) {
			return $this->_results;
		}
		if ($this->_useBufferedResults) {
			return $this->_applyFormatters(new BufferedResultSet($this, parent::execute()));
		}
		return  $this->_applyFormatters(new ResultSet($this, parent::execute()));
	}

/**
 * Compiles the SQL representation of this query ane executes it using
 * the provided connection object.
 *
 * @return Cake\Database\StatementInterface
 */
	public function executeStatement() {
		return parent::execute();
	}

/**
 * Returns an array representation of the results after executing the query.
 *
 * @return array
 */
	public function toArray() {
		return $this->execute()->toArray();
	}

/**
 * Returns a key => value array representing a single aliased field
 * that can be passed directly to the select() method.
 * The key will contain the alias and the value the actual field name.
 *
 * If the field is already aliased, then it will not be changed.
 * If no $alias is passed, the default table for this query will be used.
 *
 * @param string $field
 * @param string $alias the alias used to prefix the field
 * @return array
 */
	public function aliasField($field, $alias = null) {
		$namespaced = strpos($field, '.') !== false;
		$aliasedField = $field;

		if ($namespaced) {
			list($alias, $field) = explode('.', $field);
		}

		if (!$alias) {
			$alias = $this->repository()->alias();
		}

		$key = sprintf('%s__%s', $alias, $field);
		if (!$namespaced) {
			$aliasedField = $alias . '.' . $field;
		}

		return [$key => $aliasedField];
	}

/**
 * Runs `aliasfield()` for each field in the provided list and returns
 * the result under a single array.
 *
 * @param array $fields
 * @param string $defaultAlias
 * @return array
 */
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
 * Populates or adds parts to current query clauses using an array.
 * This is handy for passing all query clauses at once.
 *
 * ## Example:
 *
 * {{{
 *	$query->applyOptions([
 *		'fields' => ['id', 'name'],
 *		'conditions' => [
 *			'created >=' => '2013-01-01'
 *		],
 *		'limit' => 10
 *	]);
 * }}}
 *
 * Is equivalent to:
 *
 * {{{
 *	$query
 *	->select(['id', 'name'])
 *	->where(['created >=' => '2013-01-01'])
 *	->limit(10)
 * }}}
 *
 * @param array $options list of query clauses to apply new parts to. Accepts:
 * - fields: Maps to the select method
 * - conditions: Maps to the where method
 * - limit: Maps to the limit method
 * - order: Maps to the order method
 * - offset: Maps to the offset method
 * - group: Maps to the group method
 * - having: Maps to the having method
 * - contain: Maps to the contain options for eager loading
 * - join: Maps to the join method
 * @return Cake\ORM\Query
 */
	public function applyOptions(array $options) {
		$valid = [
			'fields' => 'select',
			'conditions' => 'where',
			'limit' => 'limit',
			'order' => 'order',
			'offset' => 'offset',
			'group' => 'group',
			'having' => 'having',
			'contain' => 'contain',
			'join' => 'join'
		];

		foreach ($options as $option => $values) {
			if (isset($valid[$option])) {
				$this->{$valid[$option]}($values);
			}
		}

		return $this;
	}

	public function mapReduce(callable $mapper, callable $reducer = null) {
		$this->_mapReduce[] = compact('mapper', 'reducer');
		return $this;
	}

	protected function _applyFormatters($result) {
		foreach ($this->_mapReduce as $mappers) {
			$result = new MapReduce($result, $mappers);
		}

		if (!empty($this->_mapReduce)) {
			$result = new ResultSetDecorator($result);
		}
		return $result;
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

/**
 * Applies some defaults to the query object before it is executed.
 * Specifically add the FROM clause, adds default table fields if none is
 * specified and applies the joins required to eager load associations defined
 * using `contain`
 *
 * @return Query
 */
	protected function _transformQuery() {
		if (!$this->_dirty) {
			return parent::_transformQuery();
		}
		if ($this->_type === 'select') {
			if (empty($this->_parts['from'])) {
				$this->from([$this->_table->alias() => $this->_table->table()]);
			}
			$this->_addDefaultFields();
			$this->_addContainments();
		}
		return parent::_transformQuery();
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
			$alias = $table->alias();
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
 * Adds a join based on a particular association and some custom options
 *
 * @param Association $association
 * @param array $options
 * @return void
 */
	protected function _addJoin($association, $options) {
		$association->attachTo($this, $options + ['includeFields' => !$this->_hasFields]);
	}

/**
 * Helper method that will calculate those associations that cannot be joined
 * directly in this query and will setup the required extra queries for fetching
 * the extra data.
 *
 * @param Statement $statement original query statement
 * @return CallbackStatement $statement modified statement with extra loaders
 */
	protected function _eagerLoad($statement) {
		$collectKeys = [];
		foreach ($this->_loadEagerly as $association => $meta) {
			$source = $meta['instance']->source();
			if ($meta['instance']->requiresKeys($meta['config'])) {
				$alias = $source->alias();
				$pkField = key($this->aliasField($source->primaryKey(), $alias));
				$collectKeys[] = [$alias, $pkField];
			}
		}

		$keys = [];
		if (!empty($collectKeys)) {
			while ($result = $statement->fetch('assoc')) {
				foreach ($collectKeys as $parts) {
					$keys[$parts[0]][] = $result[$parts[1]];
				}
			}
			$statement->rewind();
		}

		foreach ($this->_loadEagerly as $association => $meta) {
			$contain = $meta['associations'];
			$alias = $meta['instance']->source()->alias();
			$keys = isset($keys[$alias]) ? $keys[$alias] : null;
			$f = $meta['instance']->eagerLoader(
				$meta['config'] + ['query' => $this, 'contain' => $contain, 'keys' => $keys]
			);
			$statement = new CallbackStatement($statement, $this->connection()->driver(), $f);
		}

		return $statement;
	}

/**
 * Inspects if there are any set fields for selecting, otherwise adds all
 * the fields for the default table.
 *
 * @return void
 */
	protected function _addDefaultFields() {
		$select = $this->clause('select');
		$this->_hasFields = true;

		if (!count($select)) {
			$this->_hasFields = false;
			$this->select($this->repository()->schema()->columns());
			$select = $this->clause('select');
		}

		$aliased = $this->aliasFields($select, $this->repository()->alias());
		$this->select($aliased, true);
	}

}
