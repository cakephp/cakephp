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
namespace Cake\Datasource;

use Cake\Collection\Iterator\MapReduce;
use Cake\Datasource\QueryCacher;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\Event\Event;

/**
 * Contains the characteristics for an object that is attached to a repository and
 * can retrieve results based on any criteria.
 *
 */
trait QueryTrait {

/**
 * Instance of a table object this query is bound to
 *
 * @var \Cake\Datasource\RepositoryInterface
 */
	protected $_repository;

/**
 * A ResultSet.
 *
 * When set, query execution will be bypassed.
 *
 * @var \Cake\Datasource\ResultSetDecorator
 * @see setResult()
 */
	protected $_results;

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
 * A query cacher instance if this query has caching enabled.
 *
 * @var \Cake\Datasource\QueryCacher
 */
	protected $_cache;

/**
 * Holds any custom options passed using applyOptions that could not be processed
 * by any method in this class.
 *
 * @var array
 */
	protected $_options = [];

/**
 * Whether the query is standalone or the product of an eager load operation.
 *
 * @var bool
 */
	protected $_eagerLoaded = false;

/**
 * Returns the default table object that will be used by this query,
 * that is, the table that will appear in the from clause.
 *
 * When called with a Table argument, the default table object will be set
 * and this query object will be returned for chaining.
 *
 * @param \Cake\Datasource\RepositoryInterface $table The default table object to use
 * @return \Cake\Datasource\RepositoryInterface|\Cake\ORM\Query
 */
	public function repository(RepositoryInterface $table = null) {
		if ($table === null) {
			return $this->_repository;
		}
		$this->_repository = $table;
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
 * @param \Cake\ORM\ResultSet $results The results this query should return.
 * @return \Cake\ORM\Query The query instance.
 */
	public function setResult($results) {
		$this->_results = $results;
		return $this;
	}

/**
 * Executes this query and returns a results iterator. This function is required
 * for implementing the IteratorAggregate interface and allows the query to be
 * iterated without having to call execute() manually, thus making it look like
 * a result set instead of the query itself.
 *
 * @return \Iterator
 */
	public function getIterator() {
		return $this->all();
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
 * // Disable caching
 * $query->cache(false);
 * }}}
 *
 * @param false|string|\Closure $key Either the cache key or a function to generate the cache key.
 *   When using a function, this query instance will be supplied as an argument.
 * @param string|\Cake\Cache\CacheEngine $config Either the name of the cache config to use, or
 *   a cache config instance.
 * @return \Cake\Datasource\QueryTrait This same object
 */
	public function cache($key, $config = 'default') {
		if ($key === false) {
			$this->_cache = null;
			return $this;
		}
		$this->_cache = new QueryCacher($key, $config);
		return $this;
	}

/**
 * Sets the query instance to be the eager loaded query. If no argument is
 * passed, the current configured query `_eagerLoaded` value is returned.
 *
 * @param bool $value Whether or not to eager load.
 * @return \Cake\ORM\Query
 */
	public function eagerLoaded($value = null) {
		if ($value === null) {
			return $this->_eagerLoaded;
		}
		$this->_eagerLoaded = $value;
		return $this;
	}

/**
 * Fetch the results for this query.
 *
 * Will return either the results set through setResult(), or execute this query
 * and return the ResultSetDecorator object ready for streaming of results.
 *
 * ResultSetDecorator is a travesable object that implements the methods found
 * on Cake\Collection\Collection.
 *
 * @return \Cake\DataSource\ResultSetDecorator
 */
	public function all() {
		if (isset($this->_results)) {
			return $this->_results;
		}

		$table = $this->repository();
		$event = new Event('Model.beforeFind', $table, [$this, $this->_options, !$this->eagerLoaded()]);
		$table->eventManager()->dispatch($event);

		if (isset($this->_results)) {
			return $this->_results;
		}

		if ($this->_cache) {
			$results = $this->_cache->fetch($this);
		}
		if (!isset($results)) {
			$results = $this->_decorateResults($this->_execute());
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
 * @param callable $mapper The mapper callable.
 * @param callable $reducer The reducing function.
 * @param bool $overwrite Set to true to overwrite existing map + reduce functions.
 * @return \Cake\Datasource\QueryTrait|array
 * @see \Cake\Collection\Iterator\MapReduce for details on how to use emit data to the map reducer.
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
 * Registers a new formatter callback function that is to be executed when trying
 * to fetch the results from the database.
 *
 * Formatting callbacks will get a first parameter, a `ResultSetDecorator`, that
 * can be traversed and modified at will. As for the second parameter, the
 * formatting callback will receive this query instance.
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
 * // Return all results from the table indexed by id
 * $query->select(['id', 'name'])->formatResults(function($results, $query) {
 *   return $results->indexBy('id');
 * });
 *
 * // Add a new column to the ResultSet
 * $query->select(['name', 'birth_date'])->formatResults(function($results, $query) {
 *   return $results->map(function($row) {
 *     $row['age'] = $row['birth_date']->diff(new DateTime)->y;
 *     return $row;
 *   });
 * });
 * }}}
 *
 * @param callable $formatter The formatting callable.
 * @param bool|int $mode Whether or not to overwrite, append or prepend the formatter.
 * @return \Cake\Datasource\QueryTrait|array
 */
	public function formatResults(callable $formatter = null, $mode = 0) {
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
 * Enables calling methods from the result set as if they were from this class
 *
 * @param string $method the method to call
 * @param array $arguments list of arguments for the method to call
 * @return mixed
 * @throws \BadMethodCallException if no such method exists in result set
 */
	public function __call($method, $arguments) {
		$resultSetClass = $this->_decoratorClass();
		if (in_array($method, get_class_methods($resultSetClass))) {
			$results = $this->all();
			return call_user_func_array([$results, $method], $arguments);
		}
		throw new \BadMethodCallException(
			sprintf('Unknown method "%s"', $method)
		);
	}

/**
 * Populates or adds parts to current query clauses using an array.
 * This is handy for passing all query clauses at once.
 *
 * @param array $options the options to be applied
 * @return \Cake\Datasource\QueryTrait this object
 */
	abstract public function applyOptions(array $options);

/**
 * Executes this query and returns a traversable object containing the results
 *
 * @return \Traversable
 */
	abstract protected function _execute();

/**
 * Decorates the results iterator with MapReduce routines and formatters
 *
 * @param \Traversable $result Original results
 * @return \Cake\Datasource\ResultSetDecorator
 */
	protected function _decorateResults($result) {
		$decorator = $this->_decoratorClass();
		foreach ($this->_mapReduce as $functions) {
			$result = new MapReduce($result, $functions['mapper'], $functions['reducer']);
		}

		if (!empty($this->_mapReduce)) {
			$result = new $decorator($result);
		}

		foreach ($this->_formatters as $formatter) {
			$result = $formatter($result, $this);
		}

		if (!empty($this->_formatters) && !($result instanceof $decorator)) {
			$result = new $decorator($result);
		}

		return $result;
	}

/**
 * Returns the name of the class to be used for decorating results
 *
 * @return string
 */
	protected function _decoratorClass() {
		return 'Cake\Datasource\ResultSetDecorator';
	}

}
