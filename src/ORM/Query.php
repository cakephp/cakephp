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

use Cake\Collection\Iterator\MapReduce;
use Cake\Database\Query as DatabaseQuery;
use Cake\Event\Event;
use Cake\ORM\EagerLoader;
use Cake\ORM\QueryCacher;
use Cake\ORM\Table;

/**
 * Extends the base Query class to provide new methods related to association
 * loading, automatic fields selection, automatic type casting and to wrap results
 * into an specific iterator that will be responsible for hydrating results if
 * required.
 *
 */
class Query extends DatabaseQuery {

/**
 * Indicates that the operation should append to the list
 *
 * @var integer
 */
	const APPEND = 0;

/**
 * Indicates that the operation should prepend to the list
 *
 * @var integer
 */
	const PREPEND = 1;

/**
 * Indicates that the operation should overwrite the list
 *
 * @var boolean
 */
	const OVERWRITE = true;

/**
 * Instance of a table object this query is bound to
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * Whether the user select any fields before being executed, this is used
 * to determined if any fields should be automatically be selected.
 *
 * @var boolean
 */
	protected $_hasFields;

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
	protected $_useBufferedResults = true;

/**
 * List of map-reduce routines that should be applied over the query
 * result
 *
 * @var array
 */
	protected $_mapReduce = [];

/**
 * List of formatter classes or callbacks that will post-process the
 * results when fetched
 *
 * @var array
 */
	protected $_formatters = [];

/**
 * Holds any custom options passed using applyOptions that could not be processed
 * by any method in this class.
 *
 * @var array
 */
	protected $_options = [];

/**
 * Whether to hydrate results into entity objects
 *
 * @var boolean
 */
	protected $_hydrate = true;

/**
 * A query cacher instance if this query has caching enabled.
 *
 * @var Cake\ORM\QueryCacher
 */
	protected $_cache;

/**
 * A callable function that can be used to calculate the total amount of
 * records this query will match when not using `limit`
 *
 * @var callable
 */
	protected $_counter;

/**
 * Instance of a class responsible for storing association containments and
 * for eager loading them when this query is executed
 *
 * @var \Cake\ORM\EagerLoader
 */
	protected $_eagerLoader;

/**
 * Constuctor
 *
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
 * @return \Cake\ORM\Table|Query
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
 * Sets the instance of the eager loader class to use for loading associations
 * and storing containments. If called with no arguments, it will return the
 * currently configured instance.
 *
 * @param \Cake\ORM\EagerLoader $instance
 * @return \Cake\ORM\Eager|\Cake\ORM\Query
 */
	public function eagerLoader(EagerLoader $instance = null) {
		if ($instance === null) {
			if ($this->_eagerLoader === null) {
				$this->_eagerLoader = new EagerLoader;
			}
			return $this->_eagerLoader;
		}
		$this->_eagerLoader = $instance;
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
 * Associations can be arbitrarily nested using dot notation or nested arrays,
 * this allows this object to calculate joins or any additional queries that
 * must be executed to bring the required associated data.
 *
 * ### Example:
 *
 * {{{
 *	// Eager load the product info, and for each product load other 2 associations
 *	$query->contain(['Product' => ['Manufacturer', 'Distributor']);
 *
 *	// Which is equivalent to calling
 *	$query->contain(['Products.Manufactures', 'Products.Distributors']);
 *
 *	// For an author query, load his region, state and country
 *	$query->contain('Regions.States.Countries');
 * }}}
 *
 * It is possible to control the conditions and fields selected for each of the
 * contained associations:
 *
 * ### Example:
 *
 * {{{
 *	$query->contain(['Tags' => function($q) {
 *		return $q->where(['Tags.is_popular' => true]);
 *	}]);
 *
 *	$query->contain(['Products.Manufactures' => function($q) {
 *		return $q->select(['name'])->where(['Manufactures.active' => true]);
 *	}]);
 * }}}
 *
 * Each association might define special options when eager loaded, the allowed
 * options that can be set per association are:
 *
 * - foreignKey: Used to set a different field to match both tables, if set to false
 *   no join conditions will be generated automatically
 * - fields: An array with the fields that should be fetched from the association
 * - queryBuilder: Equivalent to passing a callable instead of an options array
 *
 * ### Example:
 *
 * {{{
 *  // Set options for the articles that will be eagerly loaded for an author
 *	$query->contain([
 *		'Articles' => [
 *			'fields' => ['title']
 *		]
 *	]);
 *
 *	// Use special join conditions for getting an article author's 'likes'
 *	$query->contain([
 *		'Likes' => [
 *			'foreignKey' => false,
 *			'queryBuilder' => function($q) {
 *				return $q->where(...); // Add full filtering conditions
 *			}
 *		]
 *	]);
 *
 * If called with no arguments, this function will return an array with
 * with the list of previously configured associations to be contained in the
 * result.
 *
 * If called with an empty first argument and $override is set to true, the
 * previous list will be emptied.
 *
 * @param array|string $associations list of table aliases to be queried
 * @param boolean $override whether override previous list with the one passed
 * defaults to merging previous list with the new one.
 * @return array|\Cake\ORM\Query
 */
	public function contain($associations = null, $override = false) {
		if (empty($associations) && $override) {
			$this->_eagerLoader = null;
		}

		$result = $this->eagerLoader()->contain($associations);
		if ($associations !== null || $override) {
			$this->_dirty();
		}
		if ($associations === null) {
			return $result;
		}

		return $this;
	}

/**
 * Adds filtering conditions to this query to only bring rows that have a relation
 * to another from an associated table, based on conditions in the associated table.
 *
 * This function will add entries in the ``contain`` graph.
 *
 * ### Example:
 *
 * {{{
 *  // Bring only articles that were tagged with 'cake'
 *	$query->matching('Tags', function($q) {
 *		return $q->where(['name' => 'cake']);
 *	);
 * }}}
 *
 * It is possible to filter by deep associations by using dot notation:
 *
 * ### Example:
 *
 * {{{
 *  // Bring only articles that were commented by 'markstory'
 *	$query->matching('Comments.Users', function($q) {
 *		return $q->where(['username' => 'markstory']);
 *	);
 * }}}
 *
 * As this function will create ``INNER JOIN``, you might want to consider
 * calling ``distinct`` on this query as you might get duplicate rows if
 * your conditions don't filter them already. This might be the case, for example,
 * of the same user commenting more than once in the same article.
 *
 * ### Example:
 *
 * {{{
 *  // Bring unique articles that were commented by 'markstory'
 *	$query->distinct(['Articles.id'])
 *	->matching('Comments.Users', function($q) {
 *		return $q->where(['username' => 'markstory']);
 *	);
 * }}}
 *
 * Please note that the query passed to the closure will only accept calling
 * ``select``, ``where``, ``andWhere`` and ``orWhere`` on it. If you wish to
 * add more complex clauses you can do it directly in the main query.
 *
 * @param string $assoc The association to filter by
 * @param callable $builder a function that will receive a pre-made query object
 * that can be used to add custom conditions or selecting some fields
 * @return Query
 */
	public function matching($assoc, callable $builder = null) {
		$this->eagerLoader()->matching($assoc, $builder);
		$this->_dirty();
		return $this;
	}

/**
 * Enable/Disable buffered results.
 *
 * When enabled the ResultSet returned by this Query will be
 * buffered. This enables you to iterate a ResultSet multiple times, or
 * both cache and iterate the ResultSet.
 *
 * When disabled it will consume less memory as fetched results are not
 * remembered in the ResultSet.
 *
 * If called with no arguments, it will return whether or not buffering is
 * enabled.
 *
 * @param boolean $enable whether or not to enable buffering
 * @return boolean|Query
 */
	public function bufferResults($enable = null) {
		if ($enable === null) {
			return $this->_useBufferedResults;
		}

		$this->_dirty();
		$this->_useBufferedResults = (bool)$enable;
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
 * Enable result caching for this query.
 *
 * If a query has caching enabled, it will do the following when executed:
 *
 * - Check the cache for $key. If there are results no SQL will be executed.
 *   Instead the cached results will be returned.
 * - When the cached data is stale/missing the result set will be cached as the query
 *   is executed.
 *
 * ## Usage
 *
 * {{{
 * // Simple string key + config
 * $query->cache('my_key', 'db_results');
 *
 * // Function to generate key.
 * $query->cache(function($q) {
 *   $key = serialize($q->clause('select'));
 *   $key .= serialize($q->clause('where'));
 *   return md5($key);
 * });
 *
 * // Using a pre-built cache engine.
 * $query->cache('my_key', $engine);
 *
 *
 * // Disable caching
 * $query->cache(false);
 * }}}
 *
 * @param false|string|Closure $key Either the cache key or a function to generate the cache key.
 *   When using a function, this query instance will be supplied as an argument.
 * @param string|CacheEngine $config Either the name of the cache config to use, or
 *   a cache config instance.
 * @return Query The query instance.
 * @throws \RuntimeException When you attempt to cache a non-select query.
 */
	public function cache($key, $config = 'default') {
		if ($this->_type !== 'select' && $this->_type !== null) {
			throw new \RuntimeException('You cannot cache the results of non-select queries.');
		}
		if ($key === false) {
			$this->_cache = null;
			return $this;
		}
		$this->_cache = new QueryCacher($key, $config);
		return $this;
	}

/**
 * Executes this query and returns a results iterator. This function is required
 * for implementing the IteratorAggregate interface and allows the query to be
 * iterated without having to call execute() manually, thus making it look like
 * a result set instead of the query itself.
 *
 * @return Iterator
 */
	public function getIterator() {
		if (empty($this->_iterator) || $this->_dirty) {
			$this->_iterator = $this->all();
		}
		return $this->_iterator;
	}

/**
 * Fetch the results for this query.
 *
 * Compiles the SQL representation of this query and executes it using the
 * provided connection object. Returns a ResultSet iterator object.
 *
 * ResultSet is a travesable object that implements the methods found
 * on Cake\Collection\Collection.
 *
 * @return Cake\ORM\ResultCollectionTrait
 * @throws RuntimeException if this method is called on a non-select Query.
 */
	public function all() {
		if ($this->_type !== 'select' && $this->_type !== null) {
			throw new \RuntimeException(
				'You cannot call all() on a non-select query. Use execute() instead.'
			);
		}
		return $this->getResults();
	}

/**
 * Get the result set for this query.
 *
 * Will return either the results set through setResult(), or execute the underlying statement
 * and return the ResultSet object ready for streaming of results.
 *
 * @return Cake\ORM\ResultCollectionTrait
 */
	public function getResults() {
		if (isset($this->_results)) {
			return $this->_results;
		}

		$table = $this->repository();
		$event = new Event('Model.beforeFind', $table, [$this, $this->_options, true]);
		$table->getEventManager()->dispatch($event);

		if (isset($this->_results)) {
			return $this->_results;
		}

		if ($this->_cache) {
			$results = $this->_cache->fetch($this);
		}
		if (!isset($results)) {
			$results = $this->_decorateResults(
				new ResultSet($this, $this->execute())
			);
			if ($this->_cache) {
				$this->_cache->store($this, $results);
			}
		}
		$this->_results = $results;
		return $this->_results;
	}

/**
 * Returns an array representation of the results after executing the query.
 *
 * @return array
 */
	public function toArray() {
		return $this->all()->toArray();
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
 * $query->applyOptions([
 *   'fields' => ['id', 'name'],
 *   'conditions' => [
 *     'created >=' => '2013-01-01'
 *   ],
 *   'limit' => 10
 * ]);
 * }}}
 *
 * Is equivalent to:
 *
 * {{{
 *  $query
 *  ->select(['id', 'name'])
 *  ->where(['created >=' => '2013-01-01'])
 *  ->limit(10)
 * }}}
 *
 * @param array $options list of query clauses to apply new parts to. Accepts:
 *
 * - fields: Maps to the select method
 * - conditions: Maps to the where method
 * - limit: Maps to the limit method
 * - order: Maps to the order method
 * - offset: Maps to the offset method
 * - group: Maps to the group method
 * - having: Maps to the having method
 * - contain: Maps to the contain options for eager loading
 * - join: Maps to the join method
 * - join: Maps to the page method
 *
 * @return Cake\ORM\Query
 */
	public function applyOptions(array $options) {
		$valid = [
			'fields' => 'select',
			'conditions' => 'where',
			'join' => 'join',
			'order' => 'order',
			'limit' => 'limit',
			'offset' => 'offset',
			'group' => 'group',
			'having' => 'having',
			'contain' => 'contain',
			'page' => 'page',
		];

		foreach ($options as $option => $values) {
			if (isset($valid[$option]) && isset($values)) {
				$this->{$valid[$option]}($values);
			} else {
				$this->_options[$option] = $values;
			}
		}

		return $this;
	}

/**
 * Returns an array with the custom options that were applied to this query
 * and that were not already processed by another method in this class.
 *
 * ###Example:
 *
 * {{{
 *	$query->applyOptions(['doABarrelRoll' => true, 'fields' => ['id', 'name']);
 *	$query->getOptions(); // Returns ['doABarrelRoll' => true]
 * }}}
 *
 * @see \Cake\ORM\Query::applyOptions() to read about the options that will
 * be processed by this class and not returned by this function
 * @return array
 */
	public function getOptions() {
		return $this->_options;
	}

/**
 * Register a new MapReduce routine to be executed on top of the database results
 * Both the mapper and caller callable should be invokable objects.
 *
 * The MapReduce routing will only be run when the query is executed and the first
 * result is attempted to be fetched.
 *
 * If the first argument is set to null, it will return the list of previously
 * registered map reduce routines.
 *
 * If the third argument is set to true, it will erase previous map reducers
 * and replace it with the arguments passed.
 *
 * @param callable $mapper
 * @param callable $reducer
 * @param boolean $overwrite
 * @return Cake\ORM\Query|array
 * @see Cake\Collection\Iterator\MapReduce for details on how to use emit data to the map reducer.
 */
	public function mapReduce(callable $mapper = null, callable $reducer = null, $overwrite = false) {
		if ($overwrite) {
			$this->_mapReduce = [];
		}
		if ($mapper === null) {
			return $this->_mapReduce;
		}
		$this->_mapReduce[] = compact('mapper', 'reducer');
		return $this;
	}

/**
 * Registers a new formatter callback function that is to be executed when the results
 * are tried to be fetched from the database.
 *
 * Formatting callbacks will get as first parameter a `ResultSetDecorator` that
 * can be traversed and modified at will. As the second parameter, the formatting
 * callback will receive this query instance.
 *
 * Callbacks are required to return an iterator object, which will be used as
 * the return value for this query's result. Formatter functions are applied
 * after all the `MapReduce` routines for this query have been executed.
 *
 * If the first argument is set to null, it will return the list of previously
 * registered map reduce routines.
 *
 * If the second argument is set to true, it will erase previous formatters
 * and replace them with the passed first argument.
 *
 * ### Example:
 *
 * {{{
 * //Return all results from the table indexed by id
 * $query->select(['id', 'name'])->formatResults(function($results, $query) {
 *	return $results->indexBy('id');
 * });
 *
 * //Add a new column to the ResultSet
 * $query->select(['name', 'birth_date'])->formatResults(function($results, $query) {
 *	return $results->map(function($row) {
 *		$row['age'] = $row['birth_date']->diff(new DateTime)->y;
 *		return $row;
 *	});
 * });
 * }}}
 *
 * @param callable $formatter
 * @param boolean|integer $mode
 * @return Cake\ORM\Query|array
 */
	public function formatResults(callable $formatter = null, $mode = self::APPEND) {
		if ($mode === self::OVERWRITE) {
			$this->_formatters = [];
		}
		if ($formatter === null) {
			return $this->_formatters;
		}

		if ($mode === self::PREPEND) {
			array_unshift($this->_formatters, $formatter);
			return $this;
		}

		$this->_formatters[] = $formatter;
		return $this;
	}

/**
 * Returns the first result out of executing this query, if the query has not been
 * executed before, it will set the limit clause to 1 for performance reasons.
 *
 * ### Example:
 *
 * `$singleUser = $query->select(['id', 'username'])->first();`
 *
 * @return mixed the first result from the ResultSet
 */
	public function first() {
		if ($this->_dirty) {
			$this->limit(1);
		}
		return $this->all()->first();
	}

/**
 * Return the COUNT(*) for for the query.
 *
 * @return integer
 */
	public function count() {
		$query = clone $this;
		$query->limit(null);
		$query->offset(null);
		$query->mapReduce(null, null, true);
		$query->formatResults(null, true);
		$counter = $this->_counter;

		if ($counter) {
			$query->counter(null);
			return (int)$counter($query);
		}

		$count = ['count' => $query->func()->count('*')];
		if (!count($query->clause('group')) && !$query->clause('distinct')) {
			$statement = $query
				->select($count, true)
				->execute();
		} else {
			// Forcing at least one field to be selected
			$query->select($query->newExpr()->add('1'));
			$statement = $this->connection()->newQuery()
				->select($count)
				->from(['count_source' => $query])
				->execute();
		}

		$result = $statement->fetch('assoc')['count'];
		$statement->closeCursor();
		return (int)$result;
	}

/**
 * Registers a callable function that will be executed when the `count` method in
 * this query is called. The return value for the function will be set as the
 * return value of the `count` method.
 *
 * This is particularly useful when you need to optimize a query for returning the
 * count, for example removing unnecessary joins, removing group by or just return
 * an estimated number of rows.
 *
 * The callback will receive as first argument a clone of this query and not this
 * query itself.
 *
 * @param callable $counter
 * @return Cake\ORM\Query
 */
	public function counter($counter) {
		$this->_counter = $counter;
		return $this;
	}

/**
 * Toggle hydrating entites.
 *
 * If set to false array results will be returned
 *
 * @param boolean|null $enable Use a boolean to set the hydration mode.
 *   Null will fetch the current hydration mode.
 * @return boolean|Query A boolean when reading, and $this when setting the mode.
 */
	public function hydrate($enable = null) {
		if ($enable === null) {
			return $this->_hydrate;
		}

		$this->_dirty();
		$this->_hydrate = (bool)$enable;
		return $this;
	}

/**
 * Decorates the ResultSet iterator with MapReduce routines
 *
 * @param Cake\ORM\ResultCollectionTrait $result Original results
 * @return Cake\ORM\ResultCollectionTrait
 */
	protected function _decorateResults($result) {
		foreach ($this->_mapReduce as $functions) {
			$result = new MapReduce($result, $functions['mapper'], $functions['reducer']);
		}

		if (!empty($this->_mapReduce)) {
			$result = new ResultSetDecorator($result);
		}

		foreach ($this->_formatters as $formatter) {
			$result = $formatter($result, $this);
		}

		if (!empty($this->_formatters) && !($result instanceof ResultSetDecorator)) {
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
	protected function _decorateStatement($statement) {
		$statement = parent::_decorateStatement($statement);
		return $this->eagerLoader()->loadExternal($this, $statement);
	}

/**
 * Applies some defaults to the query object before it is executed.
 *
 * Specifically add the FROM clause, adds default table fields if none are
 * specified and applies the joins required to eager load associations defined
 * using `contain`
 *
 * @see Cake\Database\Query::execute()
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
			$this->eagerLoader()->attachAssociations($this, $this->_table, !$this->_hasFields);
		}
		return parent::_transformQuery();
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

/**
 * Apply custom finds to against an existing query object.
 *
 * Allows custom find methods to be combined and applied to each other.
 *
 * {{{
 * $table->find('all')->find('recent');
 * }}}
 *
 * The above is an example of stacking multiple finder methods onto
 * a single query.
 *
 * @param string $finder The finder method to use.
 * @param array $options The options for the finder.
 * @return Cake\ORM\Query Returns a modified query.
 * @see Cake\ORM\Table::find()
 */
	public function find($finder, $options = []) {
		return $this->repository()->callFinder($finder, $this, $options);
	}

/**
 * Marks a query as dirty, removing any preprocessed information
 * from in memory caching such as previous results
 *
 * @return void
 */
	protected function _dirty() {
		$this->_results = null;
		parent::_dirty();
	}

/**
 * Create an update query.
 *
 * This changes the query type to be 'update'.
 * Can be combined with set() and where() methods to create update queries.
 *
 * @param string $table Unused parameter.
 * @return Query
 */
	public function update($table = null) {
		$table = $this->repository()->table();
		return parent::update($table);
	}

/**
 * Create a delete query.
 *
 * This changes the query type to be 'delete'.
 * Can be combined with the where() method to create delete queries.
 *
 * @param string $table Unused parameter.
 * @return Query
 */
	public function delete($table = null) {
		$table = $this->repository()->table();
		return parent::delete($table);
	}

/**
 * Create an insert query.
 *
 * This changes the query type to be 'insert'.
 * Note calling this method will reset any data previously set
 * with Query::values()
 *
 * Can be combined with the where() method to create delete queries.
 *
 * @param array $columns The columns to insert into.
 * @param array $types A map between columns & their datatypes.
 * @return Query
 */
	public function insert($columns, $types = []) {
		$table = $this->repository()->table();
		$this->into($table);
		return parent::insert($columns, $types);
	}

/**
 * Enables calling methods from the ResultSet as if they were from this class
 *
 * @param string $method the method to call
 * @param array $arguments list of arguments for the method to call
 * @return mixed
 * @throws \BadMethodCallException if no such method exists in ResultSet
 */
	public function __call($method, $arguments) {
		if ($this->type() === 'select') {
			$resultSetClass = __NAMESPACE__ . '\ResultSetDecorator';
			if (in_array($method, get_class_methods($resultSetClass))) {
				$results = $this->all();
				return call_user_func_array([$results, $method], $arguments);
			}
		}

		throw new \BadMethodCallException(
			sprintf('Unknown method "%s"', $method)
		);
	}

}
