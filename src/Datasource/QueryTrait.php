<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use BadMethodCallException;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\MapReduce;
use Cake\Datasource\Exception\RecordNotFoundException;
use InvalidArgumentException;
use Traversable;
use function Cake\Core\deprecationWarning;

/**
 * Contains the characteristics for an object that is attached to a repository and
 * can retrieve results based on any criteria.
 */
trait QueryTrait
{
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
     * @var iterable|null
     * @see \Cake\Datasource\QueryTrait::setResult()
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
     * @var array<callable>
     */
    protected $_formatters = [];

    /**
     * A query cacher instance if this query has caching enabled.
     *
     * @var \Cake\Datasource\QueryCacher|null
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
     * Set the default Table object that will be used by this query
     * and form the `FROM` clause.
     *
     * @param \Cake\Datasource\RepositoryInterface|\Cake\ORM\Table $repository The default table object to use
     * @return $this
     * @deprecated 4.5.0 Use `setRepository()` instead.
     */
    public function repository(RepositoryInterface $repository)
    {
        deprecationWarning('`repository() method is deprecated. Use `setRepository()` instead.');

        return $this->setRepository($repository);
    }

    /**
     * Set the default Table object that will be used by this query
     * and form the `FROM` clause.
     *
     * @param \Cake\Datasource\RepositoryInterface|\Cake\ORM\Table $repository The default table object to use
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository)
    {
        $this->_repository = $repository;

        return $this;
    }

    /**
     * Returns the default table object that will be used by this query,
     * that is, the table that will appear in the from clause.
     *
     * @return \Cake\Datasource\RepositoryInterface
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->_repository;
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
     * @param iterable $results The results this query should return.
     * @return $this
     */
    public function setResult(iterable $results)
    {
        $this->_results = $results;

        return $this;
    }

    /**
     * Executes this query and returns a results iterator. This function is required
     * for implementing the IteratorAggregate interface and allows the query to be
     * iterated without having to call execute() manually, thus making it look like
     * a result set instead of the query itself.
     *
     * @return \Cake\Datasource\ResultSetInterface
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
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
     * ### Usage
     *
     * ```
     * // Simple string key + config
     * $query->cache('my_key', 'db_results');
     *
     * // Function to generate key.
     * $query->cache(function ($q) {
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
     * ```
     *
     * @param \Closure|string|false $key Either the cache key or a function to generate the cache key.
     *   When using a function, this query instance will be supplied as an argument.
     * @param \Psr\SimpleCache\CacheInterface|string $config Either the name of the cache config to use, or
     *   a cache engine instance.
     * @return $this
     */
    public function cache($key, $config = 'default')
    {
        if ($key === false) {
            $this->_cache = null;

            return $this;
        }
        $this->_cache = new QueryCacher($key, $config);

        return $this;
    }

    /**
     * Returns the current configured query `_eagerLoaded` value
     *
     * @return bool
     */
    public function isEagerLoaded(): bool
    {
        return $this->_eagerLoaded;
    }

    /**
     * Sets the query instance to be an eager loaded query. If no argument is
     * passed, the current configured query `_eagerLoaded` value is returned.
     *
     * @param bool $value Whether to eager load.
     * @return $this
     */
    public function eagerLoaded(bool $value)
    {
        $this->_eagerLoaded = $value;

        return $this;
    }

    /**
     * Returns a key => value array representing a single aliased field
     * that can be passed directly to the select() method.
     * The key will contain the alias and the value the actual field name.
     *
     * If the field is already aliased, then it will not be changed.
     * If no $alias is passed, the default table for this query will be used.
     *
     * @param string $field The field to alias
     * @param string|null $alias the alias used to prefix the field
     * @return array<string, string>
     */
    public function aliasField(string $field, ?string $alias = null): array
    {
        if (strpos($field, '.') === false) {
            $alias = $alias ?: $this->getRepository()->getAlias();
            $aliasedField = $alias . '.' . $field;
        } else {
            $aliasedField = $field;
            [$alias, $field] = explode('.', $field);
        }

        $key = sprintf('%s__%s', $alias, $field);

        return [$key => $aliasedField];
    }

    /**
     * Runs `aliasField()` for each field in the provided list and returns
     * the result under a single array.
     *
     * @param array $fields The fields to alias
     * @param string|null $defaultAlias The default alias
     * @return array<string, string>
     */
    public function aliasFields(array $fields, ?string $defaultAlias = null): array
    {
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
     * Fetch the results for this query.
     *
     * Will return either the results set through setResult(), or execute this query
     * and return the ResultSetDecorator object ready for streaming of results.
     *
     * ResultSetDecorator is a traversable object that implements the methods found
     * on Cake\Collection\Collection.
     *
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function all(): ResultSetInterface
    {
        if ($this->_results !== null) {
            return $this->_results;
        }

        $results = null;
        if ($this->_cache) {
            $results = $this->_cache->fetch($this);
        }
        if ($results === null) {
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
    public function toArray(): array
    {
        return $this->all()->toArray();
    }

    /**
     * Register a new MapReduce routine to be executed on top of the database results
     * Both the mapper and caller callable should be invokable objects.
     *
     * The MapReduce routing will only be run when the query is executed and the first
     * result is attempted to be fetched.
     *
     * If the third argument is set to true, it will erase previous map reducers
     * and replace it with the arguments passed.
     *
     * @param callable|null $mapper The mapper callable.
     * @param callable|null $reducer The reducing function.
     * @param bool $overwrite Set to true to overwrite existing map + reduce functions.
     * @return $this
     * @see \Cake\Collection\Iterator\MapReduce for details on how to use emit data to the map reducer.
     */
    public function mapReduce(?callable $mapper = null, ?callable $reducer = null, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->_mapReduce = [];
        }
        if ($mapper === null) {
            if (!$overwrite) {
                throw new InvalidArgumentException('$mapper can be null only when $overwrite is true.');
            }

            return $this;
        }
        $this->_mapReduce[] = compact('mapper', 'reducer');

        return $this;
    }

    /**
     * Returns the list of previously registered map reduce routines.
     *
     * @return array
     */
    public function getMapReducers(): array
    {
        return $this->_mapReduce;
    }

    /**
     * Registers a new formatter callback function that is to be executed when trying
     * to fetch the results from the database.
     *
     * If the second argument is set to true, it will erase previous formatters
     * and replace them with the passed first argument.
     *
     * Callbacks are required to return an iterator object, which will be used as
     * the return value for this query's result. Formatter functions are applied
     * after all the `MapReduce` routines for this query have been executed.
     *
     * Formatting callbacks will receive two arguments, the first one being an object
     * implementing `\Cake\Collection\CollectionInterface`, that can be traversed and
     * modified at will. The second one being the query instance on which the formatter
     * callback is being applied.
     *
     * Usually the query instance received by the formatter callback is the same query
     * instance on which the callback was attached to, except for in a joined
     * association, in that case the callback will be invoked on the association source
     * side query, and it will receive that query instance instead of the one on which
     * the callback was originally attached to - see the examples below!
     *
     * ### Examples:
     *
     * Return all results from the table indexed by id:
     *
     * ```
     * $query->select(['id', 'name'])->formatResults(function ($results) {
     *     return $results->indexBy('id');
     * });
     * ```
     *
     * Add a new column to the ResultSet:
     *
     * ```
     * $query->select(['name', 'birth_date'])->formatResults(function ($results) {
     *     return $results->map(function ($row) {
     *         $row['age'] = $row['birth_date']->diff(new DateTime)->y;
     *
     *         return $row;
     *     });
     * });
     * ```
     *
     * Add a new column to the results with respect to the query's hydration configuration:
     *
     * ```
     * $query->formatResults(function ($results, $query) {
     *     return $results->map(function ($row) use ($query) {
     *         $data = [
     *             'bar' => 'baz',
     *         ];
     *
     *         if ($query->isHydrationEnabled()) {
     *             $row['foo'] = new Foo($data)
     *         } else {
     *             $row['foo'] = $data;
     *         }
     *
     *         return $row;
     *     });
     * });
     * ```
     *
     * Retaining access to the association target query instance of joined associations,
     * by inheriting the contain callback's query argument:
     *
     * ```
     * // Assuming a `Articles belongsTo Authors` association that uses the join strategy
     *
     * $articlesQuery->contain('Authors', function ($authorsQuery) {
     *     return $authorsQuery->formatResults(function ($results, $query) use ($authorsQuery) {
     *         // Here `$authorsQuery` will always be the instance
     *         // where the callback was attached to.
     *
     *         // The instance passed to the callback in the second
     *         // argument (`$query`), will be the one where the
     *         // callback is actually being applied to, in this
     *         // example that would be `$articlesQuery`.
     *
     *         // ...
     *
     *         return $results;
     *     });
     * });
     * ```
     *
     * @param callable|null $formatter The formatting callable.
     * @param int|bool $mode Whether to overwrite, append or prepend the formatter.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function formatResults(?callable $formatter = null, $mode = self::APPEND)
    {
        if ($mode === self::OVERWRITE) {
            $this->_formatters = [];
        }
        if ($formatter === null) {
            if ($mode !== self::OVERWRITE) {
                throw new InvalidArgumentException('$formatter can be null only when $mode is overwrite.');
            }

            return $this;
        }

        if ($mode === self::PREPEND) {
            array_unshift($this->_formatters, $formatter);

            return $this;
        }

        $this->_formatters[] = $formatter;

        return $this;
    }

    /**
     * Returns the list of previously registered format routines.
     *
     * @return array<callable>
     */
    public function getResultFormatters(): array
    {
        return $this->_formatters;
    }

    /**
     * Returns the first result out of executing this query, if the query has not been
     * executed before, it will set the limit clause to 1 for performance reasons.
     *
     * ### Example:
     *
     * ```
     * $singleUser = $query->select(['id', 'username'])->first();
     * ```
     *
     * @return \Cake\Datasource\EntityInterface|array|null The first result from the ResultSet.
     */
    public function first()
    {
        if ($this->_dirty) {
            $this->limit(1);
        }

        return $this->all()->first();
    }

    /**
     * Get the first result from the executing query or raise an exception.
     *
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When there is no first record.
     * @return \Cake\Datasource\EntityInterface|array The first result from the ResultSet.
     */
    public function firstOrFail()
    {
        $entity = $this->first();
        if (!$entity) {
            $table = $this->getRepository();
            throw new RecordNotFoundException(sprintf(
                'Record not found in table "%s"',
                $table->getTable()
            ));
        }

        return $entity;
    }

    /**
     * Returns an array with the custom options that were applied to this query
     * and that were not already processed by another method in this class.
     *
     * ### Example:
     *
     * ```
     *  $query->applyOptions(['doABarrelRoll' => true, 'fields' => ['id', 'name']);
     *  $query->getOptions(); // Returns ['doABarrelRoll' => true]
     * ```
     *
     * @see \Cake\Datasource\QueryInterface::applyOptions() to read about the options that will
     * be processed by this class and not returned by this function
     * @return array
     * @see applyOptions()
     */
    public function getOptions(): array
    {
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
    public function __call(string $method, array $arguments)
    {
        $resultSetClass = $this->_decoratorClass();
        if (in_array($method, get_class_methods($resultSetClass), true)) {
            deprecationWarning(sprintf(
                'Calling `%s` methods, such as `%s()`, on queries is deprecated. ' .
                'You must call `all()` first (for example, `all()->%s()`).',
                ResultSetInterface::class,
                $method,
                $method,
            ), 2);
            $results = $this->all();

            return $results->$method(...$arguments);
        }
        throw new BadMethodCallException(
            sprintf('Unknown method "%s"', $method)
        );
    }

    /**
     * @param callable $callback The callback to apply
     * @see \Cake\Collection\CollectionInterface::each()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function each(callable $callback): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling each() on a Query is deprecated. ' .
            'Instead call `$query->all()->each(...)` instead.'
        );

        return $this->all()->each($callback);
    }

    /**
     * @param ?callable $callback The callback to apply
     * @see \Cake\Collection\CollectionInterface::filter()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function filter(?callable $callback = null): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling filter() on a Query is deprecated. ' .
            'Instead call `$query->all()->filter(...)` instead.'
        );

        return $this->all()->filter($callback);
    }

    /**
     * @param callable $callback The callback to apply
     * @see \Cake\Collection\CollectionInterface::reject()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function reject(callable $callback): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling reject() on a Query is deprecated. ' .
            'Instead call `$query->all()->reject(...)` instead.'
        );

        return $this->all()->reject($callback);
    }

    /**
     * @param callable $callback The callback to apply
     * @see \Cake\Collection\CollectionInterface::every()
     * @return bool
     * @deprecated
     */
    public function every(callable $callback): bool
    {
        deprecationWarning(
            '4.3.0 - Calling every() on a Query is deprecated. ' .
            'Instead call `$query->all()->every(...)` instead.'
        );

        return $this->all()->every($callback);
    }

    /**
     * @param callable $callback The callback to apply
     * @see \Cake\Collection\CollectionInterface::some()
     * @return bool
     * @deprecated
     */
    public function some(callable $callback): bool
    {
        deprecationWarning(
            '4.3.0 - Calling some() on a Query is deprecated. ' .
            'Instead call `$query->all()->some(...)` instead.'
        );

        return $this->all()->some($callback);
    }

    /**
     * @param mixed $value The value to check.
     * @see \Cake\Collection\CollectionInterface::contains()
     * @return bool
     * @deprecated
     */
    public function contains($value): bool
    {
        deprecationWarning(
            '4.3.0 - Calling contains() on a Query is deprecated. ' .
            'Instead call `$query->all()->contains(...)` instead.'
        );

        return $this->all()->contains($value);
    }

    /**
     * @param callable $callback The callback to apply
     * @see \Cake\Collection\CollectionInterface::map()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function map(callable $callback): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling map() on a Query is deprecated. ' .
            'Instead call `$query->all()->map(...)` instead.'
        );

        return $this->all()->map($callback);
    }

    /**
     * @param callable $callback The callback to apply
     * @param mixed $initial The initial value
     * @see \Cake\Collection\CollectionInterface::reduce()
     * @return mixed
     * @deprecated
     */
    public function reduce(callable $callback, $initial = null)
    {
        deprecationWarning(
            '4.3.0 - Calling reduce() on a Query is deprecated. ' .
            'Instead call `$query->all()->reduce(...)` instead.'
        );

        return $this->all()->reduce($callback, $initial);
    }

    /**
     * @param callable|string $path The path to extract
     * @see \Cake\Collection\CollectionInterface::extract()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function extract($path): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling extract() on a Query is deprecated. ' .
            'Instead call `$query->all()->extract(...)` instead.'
        );

        return $this->all()->extract($path);
    }

    /**
     * @param callable|string $path The path to max
     * @param int $sort The SORT_ constant to order by.
     * @see \Cake\Collection\CollectionInterface::max()
     * @return mixed
     * @deprecated
     */
    public function max($path, int $sort = \SORT_NUMERIC)
    {
        deprecationWarning(
            '4.3.0 - Calling max() on a Query is deprecated. ' .
            'Instead call `$query->all()->max(...)` instead.'
        );

        return $this->all()->max($path, $sort);
    }

    /**
     * @param callable|string $path The path to max
     * @param int $sort The SORT_ constant to order by.
     * @see \Cake\Collection\CollectionInterface::min()
     * @return mixed
     * @deprecated
     */
    public function min($path, int $sort = \SORT_NUMERIC)
    {
        deprecationWarning(
            '4.3.0 - Calling min() on a Query is deprecated. ' .
            'Instead call `$query->all()->min(...)` instead.'
        );

        return $this->all()->min($path, $sort);
    }

    /**
     * @param callable|string|null $path the path to average
     * @see \Cake\Collection\CollectionInterface::avg()
     * @return float|int|null
     * @deprecated
     */
    public function avg($path = null)
    {
        deprecationwarning(
            '4.3.0 - calling avg() on a query is deprecated. ' .
            'instead call `$query->all()->avg(...)` instead.'
        );

        return $this->all()->avg($path);
    }

    /**
     * @param callable|string|null $path the path to average
     * @see \Cake\Collection\CollectionInterface::median()
     * @return float|int|null
     * @deprecated
     */
    public function median($path = null)
    {
        deprecationwarning(
            '4.3.0 - calling median() on a query is deprecated. ' .
            'instead call `$query->all()->median(...)` instead.'
        );

        return $this->all()->median($path);
    }

    /**
     * @param callable|string $path the path to average
     * @param int $order The \SORT_ constant for the direction you want results in.
     * @param int $sort The \SORT_ method to use.
     * @see \Cake\Collection\CollectionInterface::sortBy()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function sortBy($path, int $order = SORT_DESC, int $sort = \SORT_NUMERIC): CollectionInterface
    {
        deprecationwarning(
            '4.3.0 - calling sortBy() on a query is deprecated. ' .
            'instead call `$query->all()->sortBy(...)` instead.'
        );

        return $this->all()->sortBy($path, $order, $sort);
    }

    /**
     * @param callable|string $path The path to group by
     * @see \Cake\Collection\CollectionInterface::groupBy()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function groupBy($path): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling groupBy() on a Query is deprecated. ' .
            'Instead call `$query->all()->groupBy(...)` instead.'
        );

        return $this->all()->groupBy($path);
    }

    /**
     * @param string|callable $path The path to extract
     * @see \Cake\Collection\CollectionInterface::indexBy()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function indexBy($path): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling indexBy() on a Query is deprecated. ' .
            'Instead call `$query->all()->indexBy(...)` instead.'
        );

        return $this->all()->indexBy($path);
    }

    /**
     * @param string|callable $path The path to count by
     * @see \Cake\Collection\CollectionInterface::countBy()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function countBy($path): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling countBy() on a Query is deprecated. ' .
            'Instead call `$query->all()->countBy(...)` instead.'
        );

        return $this->all()->countBy($path);
    }

    /**
     * @param string|callable $path The path to sum
     * @see \Cake\Collection\CollectionInterface::sumOf()
     * @return int|float
     * @deprecated
     */
    public function sumOf($path = null)
    {
        deprecationWarning(
            '4.3.0 - Calling sumOf() on a Query is deprecated. ' .
                'Instead call `$query->all()->sumOf(...)` instead.'
        );

        return $this->all()->sumOf($path);
    }

    /**
     * @see \Cake\Collection\CollectionInterface::shuffle()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function shuffle(): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling shuffle() on a Query is deprecated. ' .
            'Instead call `$query->all()->shuffle(...)` instead.'
        );

        return $this->all()->shuffle();
    }

    /**
     * @param int $length The number of samples to select
     * @see \Cake\Collection\CollectionInterface::sample()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function sample(int $length = 10): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling sample() on a Query is deprecated. ' .
            'Instead call `$query->all()->sample(...)` instead.'
        );

        return $this->all()->sample($length);
    }

    /**
     * @param int $length The number of elements to take
     * @param int $offset The offset of the first element to take.
     * @see \Cake\Collection\CollectionInterface::take()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function take(int $length = 1, int $offset = 0): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling take() on a Query is deprecated. ' .
            'Instead call `$query->all()->take(...)` instead.'
        );

        return $this->all()->take($length, $offset);
    }

    /**
     * @param int $length The number of items to take.
     * @see \Cake\Collection\CollectionInterface::takeLast()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function takeLast(int $length): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling takeLast() on a Query is deprecated. ' .
            'Instead call `$query->all()->takeLast(...)` instead.'
        );

        return $this->all()->takeLast($length);
    }

    /**
     * @param int $length The number of items to skip
     * @see \Cake\Collection\CollectionInterface::skip()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function skip(int $length): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling skip() on a Query is deprecated. ' .
            'Instead call `$query->all()->skip(...)` instead.'
        );

        return $this->all()->skip($length);
    }

    /**
     * @param array $conditions The conditions to use.
     * @see \Cake\Collection\CollectionInterface::match()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function match(array $conditions): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling match() on a Query is deprecated. ' .
            'Instead call `$query->all()->match(...)` instead.'
        );

        return $this->all()->match($conditions);
    }

    /**
     * @param array $conditions The conditions to apply
     * @see \Cake\Collection\CollectionInterface::firstMatch()
     * @return mixed
     * @deprecated
     */
    public function firstMatch(array $conditions)
    {
        deprecationWarning(
            '4.3.0 - Calling firstMatch() on a Query is deprecated. ' .
            'Instead call `$query->all()->firstMatch(...)` instead.'
        );

        return $this->all()->firstMatch($conditions);
    }

    /**
     * @see \Cake\Collection\CollectionInterface::last()
     * @deprecated
     * @return mixed
     */
    public function last()
    {
        deprecationWarning(
            '4.3.0 - Calling last() on a Query is deprecated. ' .
            'Instead call `$query->all()->last(...)` instead.'
        );

        return $this->all()->last();
    }

    /**
     * @param mixed $items The items to append
     * @see \Cake\Collection\CollectionInterface::append()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function append($items): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling append() on a Query is deprecated. ' .
            'Instead call `$query->all()->append(...)` instead.'
        );

        return $this->all()->append($items);
    }

    /**
     * @param mixed $item The item to apply
     * @param mixed $key The key to append with
     * @see \Cake\Collection\CollectionInterface::appendItem()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function appendItem($item, $key = null): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling appendItem() on a Query is deprecated. ' .
            'Instead call `$query->all()->appendItem(...)` instead.'
        );

        return $this->all()->appendItem($item, $key);
    }

    /**
     * @param mixed $items The items to prepend.
     * @see \Cake\Collection\CollectionInterface::prepend()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function prepend($items): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling prepend() on a Query is deprecated. ' .
            'Instead call `$query->all()->prepend(...)` instead.'
        );

        return $this->all()->prepend($items);
    }

    /**
     * @param mixed $item The item to prepend
     * @param mixed $key The key to use.
     * @see \Cake\Collection\CollectionInterface::prependItem()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function prependItem($item, $key = null): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling prependItem() on a Query is deprecated. ' .
            'Instead call `$query->all()->prependItem(...)` instead.'
        );

        return $this->all()->prependItem($item, $key);
    }

    /**
     * @param callable|string $keyPath The path for keys
     * @param callable|string $valuePath The path for values
     * @param callable|string|null $groupPath The path for grouping
     * @see \Cake\Collection\CollectionInterface::combine()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function combine($keyPath, $valuePath, $groupPath = null): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling combine() on a Query is deprecated. ' .
            'Instead call `$query->all()->combine(...)` instead.'
        );

        return $this->all()->combine($keyPath, $valuePath, $groupPath);
    }

    /**
     * @param callable|string $idPath The path to ids
     * @param callable|string $parentPath The path to parents
     * @param string $nestingKey Key used for nesting children.
     * @see \Cake\Collection\CollectionInterface::nest()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function nest($idPath, $parentPath, string $nestingKey = 'children'): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling nest() on a Query is deprecated. ' .
            'Instead call `$query->all()->nest(...)` instead.'
        );

        return $this->all()->nest($idPath, $parentPath, $nestingKey);
    }

    /**
     * @param string $path The path to insert on
     * @param mixed $values The values to insert.
     * @see \Cake\Collection\CollectionInterface::insert()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function insert(string $path, $values): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling insert() on a Query is deprecated. ' .
            'Instead call `$query->all()->insert(...)` instead.'
        );

        return $this->all()->insert($path, $values);
    }

    /**
     * @see \Cake\Collection\CollectionInterface::toList()
     * @return array
     * @deprecated
     */
    public function toList(): array
    {
        deprecationWarning(
            '4.3.0 - Calling toList() on a Query is deprecated. ' .
            'Instead call `$query->all()->toList(...)` instead.'
        );

        return $this->all()->toList();
    }

    /**
     * @param bool $keepKeys Whether or not keys should be kept
     * @see \Cake\Collection\CollectionInterface::compile()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function compile(bool $keepKeys = true): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling compile() on a Query is deprecated. ' .
            'Instead call `$query->all()->compile(...)` instead.'
        );

        return $this->all()->compile($keepKeys);
    }

    /**
     * @see \Cake\Collection\CollectionInterface::lazy()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function lazy(): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling lazy() on a Query is deprecated. ' .
            'Instead call `$query->all()->lazy(...)` instead.'
        );

        return $this->all()->lazy();
    }

    /**
     * @see \Cake\Collection\CollectionInterface::buffered()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function buffered(): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling buffered() on a Query is deprecated. ' .
            'Instead call `$query->all()->buffered(...)` instead.'
        );

        return $this->all()->buffered();
    }

    /**
     * @param string|int $order The order in which to return the elements
     * @param callable|string $nestingKey The key name under which children are nested
     * @see \Cake\Collection\CollectionInterface::listNested()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function listNested($order = 'desc', $nestingKey = 'children'): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling listNested() on a Query is deprecated. ' .
            'Instead call `$query->all()->listNested(...)` instead.'
        );

        return $this->all()->listNested($order, $nestingKey);
    }

    /**
     * @param callable|array $condition the method that will receive each of the elements and
     *   returns true when the iteration should be stopped.
     * @see \Cake\Collection\CollectionInterface::stopWhen()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function stopWhen($condition): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling stopWhen() on a Query is deprecated. ' .
            'Instead call `$query->all()->stopWhen(...)` instead.'
        );

        return $this->all()->stopWhen($condition);
    }

    /**
     * @param callable|null $callback A callable function that will receive each of
     *  items in the collection.
     * @see \Cake\Collection\CollectionInterface::unfold()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function unfold(?callable $callback = null): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling unfold() on a Query is deprecated. ' .
            'Instead call `$query->all()->unfold(...)` instead.'
        );

        return $this->all()->unfold($callback);
    }

    /**
     * @param callable $callback A callable function that will receive each of
     *  items in the collection.
     * @see \Cake\Collection\CollectionInterface::through()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function through(callable $callback): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling through() on a Query is deprecated. ' .
            'Instead call `$query->all()->through(...)` instead.'
        );

        return $this->all()->through($callback);
    }

    /**
     * @param iterable ...$items The collections to zip.
     * @see \Cake\Collection\CollectionInterface::zip()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function zip(iterable $items): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling zip() on a Query is deprecated. ' .
            'Instead call `$query->all()->zip(...)` instead.'
        );

        return $this->all()->zip($items);
    }

    /**
     * @param iterable ...$items The collections to zip.
     * @param callable $callback The function to use for zipping the elements together.
     * @see \Cake\Collection\CollectionInterface::zipWith()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function zipWith(iterable $items, $callback): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling zipWith() on a Query is deprecated. ' .
            'Instead call `$query->all()->zipWith(...)` instead.'
        );

        return $this->all()->zipWith($items, $callback);
    }

    /**
     * @param int $chunkSize The maximum size for each chunk
     * @see \Cake\Collection\CollectionInterface::chunk()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function chunk(int $chunkSize): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling chunk() on a Query is deprecated. ' .
            'Instead call `$query->all()->chunk(...)` instead.'
        );

        return $this->all()->chunk($chunkSize);
    }

    /**
     * @param int $chunkSize The maximum size for each chunk
     * @param bool $keepKeys If the keys of the array should be kept
     * @see \Cake\Collection\CollectionInterface::chunkWithKeys()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function chunkWithKeys(int $chunkSize, bool $keepKeys = true): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling chunkWithKeys() on a Query is deprecated. ' .
            'Instead call `$query->all()->chunkWithKeys(...)` instead.'
        );

        return $this->all()->chunkWithKeys($chunkSize, $keepKeys);
    }

    /**
     * @see \Cake\Collection\CollectionInterface::isEmpty()
     * @return bool
     * @deprecated
     */
    public function isEmpty(): bool
    {
        deprecationWarning(
            '4.3.0 - Calling isEmpty() on a Query is deprecated. ' .
            'Instead call `$query->all()->isEmpty(...)` instead.'
        );

        return $this->all()->isEmpty();
    }

    /**
     * @see \Cake\Collection\CollectionInterface::unwrap()
     * @return \Traversable
     * @deprecated
     */
    public function unwrap(): Traversable
    {
        deprecationWarning(
            '4.3.0 - Calling unwrap() on a Query is deprecated. ' .
            'Instead call `$query->all()->unwrap(...)` instead.'
        );

        return $this->all()->unwrap();
    }

    /**
     * @see \Cake\Collection\CollectionInterface::transpose()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function transpose(): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling transpose() on a Query is deprecated. ' .
            'Instead call `$query->all()->transpose(...)` instead.'
        );

        return $this->all()->transpose();
    }

    /**
     * @see \Cake\Collection\CollectionInterface::count()
     * @return int
     * @deprecated
     */
    public function count(): int
    {
        deprecationWarning(
            '4.3.0 - Calling count() on a Query is deprecated. ' .
            'Instead call `$query->all()->count(...)` instead.'
        );

        return $this->all()->count();
    }

    /**
     * @see \Cake\Collection\CollectionInterface::countKeys()
     * @return int
     * @deprecated
     */
    public function countKeys(): int
    {
        deprecationWarning(
            '4.3.0 - Calling countKeys() on a Query is deprecated. ' .
            'Instead call `$query->all()->countKeys(...)` instead.'
        );

        return $this->all()->countKeys();
    }

    /**
     * @param callable|null $operation A callable that allows you to customize the product result.
     * @param callable|null $filter A filtering callback that must return true for a result to be part
     *   of the final results.
     * @see \Cake\Collection\CollectionInterface::cartesianProduct()
     * @return \Cake\Collection\CollectionInterface
     * @deprecated
     */
    public function cartesianProduct(?callable $operation = null, ?callable $filter = null): CollectionInterface
    {
        deprecationWarning(
            '4.3.0 - Calling cartesianProduct() on a Query is deprecated. ' .
            'Instead call `$query->all()->cartesianProduct(...)` instead.'
        );

        return $this->all()->cartesianProduct($operation, $filter);
    }

    /**
     * Populates or adds parts to current query clauses using an array.
     * This is handy for passing all query clauses at once.
     *
     * @param array<string, mixed> $options the options to be applied
     * @return $this
     */
    abstract public function applyOptions(array $options);

    /**
     * Executes this query and returns a traversable object containing the results
     *
     * @return \Cake\Datasource\ResultSetInterface
     */
    abstract protected function _execute(): ResultSetInterface;

    /**
     * Decorates the results iterator with MapReduce routines and formatters
     *
     * @param \Traversable $result Original results
     * @return \Cake\Datasource\ResultSetInterface
     */
    protected function _decorateResults(Traversable $result): ResultSetInterface
    {
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
     * @psalm-return class-string<\Cake\Datasource\ResultSetInterface>
     */
    protected function _decoratorClass(): string
    {
        return ResultSetDecorator::class;
    }
}
