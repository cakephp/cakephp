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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Query;

use ArrayObject;
use Cake\Collection\Iterator\MapReduce;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query\SelectQuery as DbSelectQuery;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\QueryCacher;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Association;
use Cake\ORM\EagerLoader;
use Cake\ORM\ResultSetFactory;
use Cake\ORM\Table;
use Closure;
use InvalidArgumentException;
use JsonSerializable;
use Psr\SimpleCache\CacheInterface;

/**
 * Extends the Cake\Database\Query\SelectQuery class to provide new methods related to association
 * loading, automatic fields selection, automatic type casting and to wrap results
 * into a specific iterator that will be responsible for hydrating results if
 * required.
 *
 * @template TSubject of \Cake\Datasource\EntityInterface|array
 * @extends \Cake\Database\Query\SelectQuery<TSubject>
 */
class SelectQuery extends DbSelectQuery implements JsonSerializable, QueryInterface
{
    use CommonQueryTrait;

    /**
     * Indicates that the operation should append to the list
     *
     * @var int
     */
    public const APPEND = 0;

    /**
     * Indicates that the operation should prepend to the list
     *
     * @var int
     */
    public const PREPEND = 1;

    /**
     * Indicates that the operation should overwrite the list
     *
     * @var bool
     */
    public const OVERWRITE = true;

    /**
     * Whether the user select any fields before being executed, this is used
     * to determined if any fields should be automatically be selected.
     *
     * @var bool|null
     */
    protected ?bool $_hasFields = null;

    /**
     * Tracks whether the original query should include
     * fields from the top level table.
     *
     * @var bool|null
     */
    protected ?bool $_autoFields = null;

    /**
     * Whether to hydrate results into entity objects
     *
     * @var bool
     */
    protected bool $_hydrate = true;

    /**
     * Whether aliases are generated for fields.
     *
     * @var bool
     */
    protected bool $aliasingEnabled = true;

    /**
     * A callback used to calculate the total amount of
     * records this query will match when not using `limit`
     *
     * @var \Closure|null
     */
    protected ?Closure $_counter = null;

    /**
     * Instance of a class responsible for storing association containments and
     * for eager loading them when this query is executed
     *
     * @var \Cake\ORM\EagerLoader|null
     */
    protected ?EagerLoader $_eagerLoader = null;

    /**
     * Whether the query is standalone or the product of an eager load operation.
     *
     * @var bool
     */
    protected bool $_eagerLoaded = false;

    /**
     * True if the beforeFind event has already been triggered for this query
     *
     * @var bool
     */
    protected bool $_beforeFindFired = false;

    /**
     * The COUNT(*) for the query.
     *
     * When set, count query execution will be bypassed.
     *
     * @var int|null
     */
    protected ?int $_resultsCount = null;

    /**
     * Resultset factory
     *
     * @var \Cake\ORM\ResultSetFactory<\Cake\Datasource\EntityInterface|array>
     */
    protected ResultSetFactory $resultSetFactory;

    /**
     * A ResultSet.
     *
     * When set, SelectQuery execution will be bypassed.
     *
     * @var iterable|null
     * @see \Cake\Datasource\QueryTrait::setResult()
     */
    protected ?iterable $_results = null;

    /**
     * List of map-reduce routines that should be applied over the query
     * result
     *
     * @var array
     */
    protected array $_mapReduce = [];

    /**
     * List of formatter classes or callbacks that will post-process the
     * results when fetched
     *
     * @var array<\Closure>
     */
    protected array $_formatters = [];

    /**
     * A query cacher instance if this query has caching enabled.
     *
     * @var \Cake\Datasource\QueryCacher|null
     */
    protected ?QueryCacher $_cache = null;

    /**
     * Holds any custom options passed using applyOptions that could not be processed
     * by any method in this class.
     *
     * @var array
     */
    protected array $_options = [];

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table $table The table this query is starting on
     */
    public function __construct(Table $table)
    {
        parent::__construct($table->getConnection());

        $this->setRepository($table);
        $this->addDefaultTypes($table);
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
     * @return \Cake\Datasource\ResultSetInterface<\Cake\Datasource\EntityInterface|array>
     */
    public function getIterator(): ResultSetInterface
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
    public function cache(Closure|string|false $key, CacheInterface|string $config = 'default')
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
        if (str_contains($field, '.')) {
            $aliasedField = $field;
            [$alias, $field] = explode('.', $field);
        } else {
            $alias = $alias ?: $this->getRepository()->getAlias();
            $aliasedField = $alias . '.' . $field;
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
     * @return \Cake\Datasource\ResultSetInterface<mixed>
     */
    public function all(): ResultSetInterface
    {
        if ($this->_results !== null) {
            if (!($this->_results instanceof ResultSetInterface)) {
                $this->_results = $this->_decorateResults($this->_results);
            }

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
     *
     * The MapReduce routing will only be run when the query is executed and the first
     * result is attempted to be fetched.
     *
     * If the third argument is set to true, it will erase previous map reducers
     * and replace it with the arguments passed.
     *
     * @param \Closure|null $mapper The mapper function
     * @param \Closure|null $reducer The reducing function
     * @param bool $overwrite Set to true to overwrite existing map + reduce functions.
     * @return $this
     * @see \Cake\Collection\Iterator\MapReduce for details on how to use emit data to the map reducer.
     */
    public function mapReduce(?Closure $mapper = null, ?Closure $reducer = null, bool $overwrite = false)
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
     * @param \Closure|null $formatter The formatting function
     * @param int|bool $mode Whether to overwrite, append or prepend the formatter.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function formatResults(?Closure $formatter = null, int|bool $mode = self::APPEND)
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
     * @return array<\Closure>
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
     * @return mixed The first result from the ResultSet.
     */
    public function first(): mixed
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
     * @return mixed The first result from the ResultSet.
     */
    public function firstOrFail(): mixed
    {
        $entity = $this->first();
        if (!$entity) {
            $table = $this->getRepository();
            throw new RecordNotFoundException(sprintf(
                'Record not found in table `%s`.',
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
     * Populates or adds parts to current query clauses using an array.
     * This is handy for passing all query clauses at once.
     *
     * The method accepts the following query clause related options:
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
     * - page: Maps to the page method
     *
     * All other options will not affect the query, but will be stored
     * as custom options that can be read via `getOptions()`. Furthermore
     * they are automatically passed to `Model.beforeFind`.
     *
     * ### Example:
     *
     * ```
     * $query->applyOptions([
     *   'fields' => ['id', 'name'],
     *   'conditions' => [
     *     'created >=' => '2013-01-01'
     *   ],
     *   'limit' => 10,
     * ]);
     * ```
     *
     * Is equivalent to:
     *
     * ```
     * $query
     *   ->select(['id', 'name'])
     *   ->where(['created >=' => '2013-01-01'])
     *   ->limit(10)
     * ```
     *
     * Custom options can be read via `getOptions()`:
     *
     * ```
     * $query->applyOptions([
     *   'fields' => ['id', 'name'],
     *   'custom' => 'value',
     * ]);
     * ```
     *
     * Here `$options` will hold `['custom' => 'value']` (the `fields`
     * option will be applied to the query instead of being stored, as
     * it's a query clause related option):
     *
     * ```
     * $options = $query->getOptions();
     * ```
     *
     * @param array<string, mixed> $options The options to be applied
     * @return $this
     * @see getOptions()
     */
    public function applyOptions(array $options)
    {
        $valid = [
            'select' => 'select',
            'fields' => 'select',
            'conditions' => 'where',
            'where' => 'where',
            'join' => 'join',
            'order' => 'orderBy',
            'orderBy' => 'orderBy',
            'limit' => 'limit',
            'offset' => 'offset',
            'group' => 'groupBy',
            'groupBy' => 'groupBy',
            'having' => 'having',
            'contain' => 'contain',
            'page' => 'page',
        ];

        ksort($options);
        foreach ($options as $option => $values) {
            if (isset($valid[$option], $values)) {
                $this->{$valid[$option]}($values);
            } else {
                $this->_options[$option] = $values;
            }
        }

        return $this;
    }

    /**
     * Decorates the results iterator with MapReduce routines and formatters
     *
     * @param iterable $result Original results
     * @return \Cake\Datasource\ResultSetInterface<\Cake\Datasource\EntityInterface|mixed>
     */
    protected function _decorateResults(iterable $result): ResultSetInterface
    {
        $decorator = $this->_decoratorClass();

        if ($this->_mapReduce) {
            foreach ($this->_mapReduce as $functions) {
                $result = new MapReduce($result, $functions['mapper'], $functions['reducer']);
            }
            $result = new $decorator($result);
        }

        if (!($result instanceof ResultSetInterface)) {
            $result = new $decorator($result);
        }

        if ($this->_formatters) {
            foreach ($this->_formatters as $formatter) {
                $result = $formatter($result, $this);
            }

            if (!($result instanceof ResultSetInterface)) {
                $result = new $decorator($result);
            }
        }

        return $result;
    }

    /**
     * Returns the name of the class to be used for decorating results
     *
     * @return class-string<\Cake\Datasource\ResultSetInterface>
     */
    protected function _decoratorClass(): string
    {
        return ResultSetDecorator::class;
    }

    /**
     * Adds new fields to be returned by a `SELECT` statement when this query is
     * executed. Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias fields using the value as the
     * real field to be aliased. It is possible to alias strings, Expression objects or
     * even other Query objects.
     *
     * If a callback is passed, the returning array of the function will
     * be used as the list of fields.
     *
     * By default this function will append any passed argument to the list of fields
     * to be selected, unless the second argument is set to true.
     *
     * ### Examples:
     *
     * ```
     * $query->select(['id', 'title']); // Produces SELECT id, title
     * $query->select(['author' => 'author_id']); // Appends author: SELECT id, title, author_id as author
     * $query->select('id', true); // Resets the list: SELECT id
     * $query->select(['total' => $countQuery]); // SELECT id, (SELECT ...) AS total
     * $query->select(function ($query) {
     *     return ['article_id', 'total' => $query->count('*')];
     * })
     * ```
     *
     * By default no fields are selected, if you have an instance of `Cake\ORM\Query` and try to append
     * fields you should also call `Cake\ORM\Query::enableAutoFields()` to select the default fields
     * from the table.
     *
     * If you pass an instance of a `Cake\ORM\Table` or `Cake\ORM\Association` class,
     * all the fields in the schema of the table or the association will be added to
     * the select clause.
     *
     * @param \Cake\Database\ExpressionInterface|\Cake\ORM\Table|\Cake\ORM\Association|\Closure|array|string|float|int $fields Fields
     * to be added to the list.
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     */
    public function select(
        ExpressionInterface|Table|Association|Closure|array|string|float|int $fields = [],
        bool $overwrite = false
    ) {
        if ($fields instanceof Association) {
            $fields = $fields->getTarget();
        }

        if ($fields instanceof Table) {
            if ($this->aliasingEnabled) {
                $fields = $this->aliasFields($fields->getSchema()->columns(), $fields->getAlias());
            } else {
                $fields = $fields->getSchema()->columns();
            }
        }

        return parent::select($fields, $overwrite);
    }

    /**
     * Behaves the exact same as `select()` except adds the field to the list of fields selected and
     * does not disable auto-selecting fields for Associations.
     *
     * Use this instead of calling `select()` then `enableAutoFields()` to re-enable auto-fields.
     *
     * @param \Cake\Database\ExpressionInterface|\Cake\ORM\Table|\Cake\ORM\Association|\Closure|array|string|float|int $fields Fields
     * to be added to the list.
     * @return $this
     */
    public function selectAlso(
        ExpressionInterface|Table|Association|Closure|array|string|float|int $fields
    ) {
        $this->select($fields);
        $this->_autoFields = true;

        return $this;
    }

    /**
     * All the fields associated with the passed table except the excluded
     * fields will be added to the select clause of the query. Passed excluded fields should not be aliased.
     * After the first call to this method, a second call cannot be used to remove fields that have already
     * been added to the query by the first. If you need to change the list after the first call,
     * pass overwrite boolean true which will reset the select clause removing all previous additions.
     *
     * @param \Cake\ORM\Table|\Cake\ORM\Association $table The table to use to get an array of columns
     * @param list<string> $excludedFields The un-aliased column names you do not want selected from $table
     * @param bool $overwrite Whether to reset/remove previous selected fields
     * @return $this
     */
    public function selectAllExcept(Table|Association $table, array $excludedFields, bool $overwrite = false)
    {
        if ($table instanceof Association) {
            $table = $table->getTarget();
        }

        $fields = array_diff($table->getSchema()->columns(), $excludedFields);
        if ($this->aliasingEnabled) {
            $fields = $this->aliasFields($fields);
        }

        return $this->select($fields, $overwrite);
    }

    /**
     * Sets the instance of the eager loader class to use for loading associations
     * and storing containments.
     *
     * @param \Cake\ORM\EagerLoader $instance The eager loader to use.
     * @return $this
     */
    public function setEagerLoader(EagerLoader $instance)
    {
        $this->_eagerLoader = $instance;

        return $this;
    }

    /**
     * Returns the currently configured instance.
     *
     * @return \Cake\ORM\EagerLoader
     */
    public function getEagerLoader(): EagerLoader
    {
        return $this->_eagerLoader ??= new EagerLoader();
    }

    /**
     * Sets the list of associations that should be eagerly loaded along with this
     * query. The list of associated tables passed must have been previously set as
     * associations using the Table API.
     *
     * ### Example:
     *
     * ```
     * // Bring articles' author information
     * $query->contain('Author');
     *
     * // Also bring the category and tags associated to each article
     * $query->contain(['Category', 'Tag']);
     * ```
     *
     * Associations can be arbitrarily nested using dot notation or nested arrays,
     * this allows this object to calculate joins or any additional queries that
     * must be executed to bring the required associated data.
     *
     * ### Example:
     *
     * ```
     * // Eager load the product info, and for each product load other 2 associations
     * $query->contain(['Product' => ['Manufacturer', 'Distributor']);
     *
     * // Which is equivalent to calling
     * $query->contain(['Products.Manufactures', 'Products.Distributors']);
     *
     * // For an author query, load his region, state and country
     * $query->contain('Regions.States.Countries');
     * ```
     *
     * It is possible to control the conditions and fields selected for each of the
     * contained associations:
     *
     * ### Example:
     *
     * ```
     * $query->contain(['Tags' => function ($q) {
     *     return $q->where(['Tags.is_popular' => true]);
     * }]);
     *
     * $query->contain(['Products.Manufactures' => function ($q) {
     *     return $q->select(['name'])->where(['Manufactures.active' => true]);
     * }]);
     * ```
     *
     * Each association might define special options when eager loaded, the allowed
     * options that can be set per association are:
     *
     * - `foreignKey`: Used to set a different field to match both tables, if set to false
     *   no join conditions will be generated automatically. `false` can only be used on
     *   joinable associations and cannot be used with hasMany or belongsToMany associations.
     * - `fields`: An array with the fields that should be fetched from the association.
     * - `finder`: The finder to use when loading associated records. Either the name of the
     *   finder as a string, or an array to define options to pass to the finder.
     * - `queryBuilder`: Equivalent to passing a callback instead of an options array.
     *
     * ### Example:
     *
     * ```
     * // Set options for the hasMany articles that will be eagerly loaded for an author
     * $query->contain([
     *     'Articles' => [
     *         'fields' => ['title', 'author_id']
     *     ]
     * ]);
     * ```
     *
     * Finders can be configured to use options.
     *
     * ```
     * // Retrieve translations for the articles, but only those for the `en` and `es` locales
     * $query->contain([
     *     'Articles' => [
     *         'finder' => [
     *             'translations' => [
     *                 'locales' => ['en', 'es']
     *             ]
     *         ]
     *     ]
     * ]);
     * ```
     *
     * When containing associations, it is important to include foreign key columns.
     * Failing to do so will trigger exceptions.
     *
     * ```
     * // Use a query builder to add conditions to the containment
     * $query->contain('Authors', function ($q) {
     *     return $q->where(...); // add conditions
     * });
     * // Use special join conditions for multiple containments in the same method call
     * $query->contain([
     *     'Authors' => [
     *         'foreignKey' => false,
     *         'queryBuilder' => function ($q) {
     *             return $q->where(...); // Add full filtering conditions
     *         }
     *     ],
     *     'Tags' => function ($q) {
     *         return $q->where(...); // add conditions
     *     }
     * ]);
     * ```
     *
     * If called with an empty first argument and `$override` is set to true, the
     * previous list will be emptied.
     *
     * @param array|string $associations List of table aliases to be queried.
     * @param \Closure|bool $override The query builder for the association, or
     *   if associations is an array, a bool on whether to override previous list
     *   with the one passed
     * defaults to merging previous list with the new one.
     * @return $this
     */
    public function contain(array|string $associations, Closure|bool $override = false)
    {
        $loader = $this->getEagerLoader();
        if ($override === true) {
            $this->clearContain();
        }

        $queryBuilder = null;
        if ($override instanceof Closure) {
            $queryBuilder = $override;
        }

        if ($associations) {
            $loader->contain($associations, $queryBuilder);
        }
        $this->_addAssociationsToTypeMap(
            $this->getRepository(),
            $this->getTypeMap(),
            $loader->getContain()
        );

        return $this;
    }

    /**
     * @return array
     */
    public function getContain(): array
    {
        return $this->getEagerLoader()->getContain();
    }

    /**
     * Clears the contained associations from the current query.
     *
     * @return $this
     */
    public function clearContain()
    {
        $this->getEagerLoader()->clearContain();
        $this->_dirty();

        return $this;
    }

    /**
     * Used to recursively add contained association column types to
     * the query.
     *
     * @param \Cake\ORM\Table $table The table instance to pluck associations from.
     * @param \Cake\Database\TypeMap $typeMap The typemap to check for columns in.
     *   This typemap is indirectly mutated via {@link \Cake\ORM\Query\SelectQuery::addDefaultTypes()}
     * @param array<string, array> $associations The nested tree of associations to walk.
     * @return void
     */
    protected function _addAssociationsToTypeMap(Table $table, TypeMap $typeMap, array $associations): void
    {
        foreach ($associations as $name => $nested) {
            if (!$table->hasAssociation($name)) {
                continue;
            }
            $association = $table->getAssociation($name);
            $target = $association->getTarget();
            $primary = (array)$target->getPrimaryKey();
            if (!$primary || $typeMap->type($target->aliasField($primary[0])) === null) {
                $this->addDefaultTypes($target);
            }
            if ($nested) {
                $this->_addAssociationsToTypeMap($target, $typeMap, $nested);
            }
        }
    }

    /**
     * Adds filtering conditions to this query to only bring rows that have a relation
     * to another from an associated table, based on conditions in the associated table.
     *
     * This function will add entries in the `contain` graph.
     *
     * ### Example:
     *
     * ```
     * // Bring only articles that were tagged with 'cake'
     * $query->matching('Tags', function ($q) {
     *     return $q->where(['name' => 'cake']);
     * });
     * ```
     *
     * It is possible to filter by deep associations by using dot notation:
     *
     * ### Example:
     *
     * ```
     * // Bring only articles that were commented by 'markstory'
     * $query->matching('Comments.Users', function ($q) {
     *     return $q->where(['username' => 'markstory']);
     * });
     * ```
     *
     * As this function will create `INNER JOIN`, you might want to consider
     * calling `distinct` on this query as you might get duplicate rows if
     * your conditions don't filter them already. This might be the case, for example,
     * of the same user commenting more than once in the same article.
     *
     * ### Example:
     *
     * ```
     * // Bring unique articles that were commented by 'markstory'
     * $query->distinct(['Articles.id'])
     *     ->matching('Comments.Users', function ($q) {
     *         return $q->where(['username' => 'markstory']);
     *     });
     * ```
     *
     * Please note that the query passed to the closure will only accept calling
     * `select`, `where`, `andWhere` and `orWhere` on it. If you wish to
     * add more complex clauses you can do it directly in the main query.
     *
     * @param string $assoc The association to filter by
     * @param \Closure|null $builder a function that will receive a pre-made query object
     * that can be used to add custom conditions or selecting some fields
     * @return $this
     */
    public function matching(string $assoc, ?Closure $builder = null)
    {
        $result = $this->getEagerLoader()->setMatching($assoc, $builder)->getMatching();
        $this->_addAssociationsToTypeMap($this->getRepository(), $this->getTypeMap(), $result);
        $this->_dirty();

        return $this;
    }

    /**
     * Creates a LEFT JOIN with the passed association table while preserving
     * the foreign key matching and the custom conditions that were originally set
     * for it.
     *
     * This function will add entries in the `contain` graph.
     *
     * ### Example:
     *
     * ```
     * // Get the count of articles per user
     * $usersQuery
     *     ->select(['total_articles' => $query->func()->count('Articles.id')])
     *     ->leftJoinWith('Articles')
     *     ->groupBy(['Users.id'])
     *     ->enableAutoFields();
     * ```
     *
     * You can also customize the conditions passed to the LEFT JOIN:
     *
     * ```
     * // Get the count of articles per user with at least 5 votes
     * $usersQuery
     *     ->select(['total_articles' => $query->func()->count('Articles.id')])
     *     ->leftJoinWith('Articles', function ($q) {
     *         return $q->where(['Articles.votes >=' => 5]);
     *     })
     *     ->groupBy(['Users.id'])
     *     ->enableAutoFields();
     * ```
     *
     * This will create the following SQL:
     *
     * ```
     * SELECT COUNT(Articles.id) AS total_articles, Users.*
     * FROM users Users
     * LEFT JOIN articles Articles ON Articles.user_id = Users.id AND Articles.votes >= 5
     * GROUP BY USers.id
     * ```
     *
     * It is possible to left join deep associations by using dot notation
     *
     * ### Example:
     *
     * ```
     * // Total comments in articles by 'markstory'
     * $query
     *     ->select(['total_comments' => $query->func()->count('Comments.id')])
     *     ->leftJoinWith('Comments.Users', function ($q) {
     *         return $q->where(['username' => 'markstory']);
     *     })
     *    ->groupBy(['Users.id']);
     * ```
     *
     * Please note that the query passed to the closure will only accept calling
     * `select`, `where`, `andWhere` and `orWhere` on it. If you wish to
     * add more complex clauses you can do it directly in the main query.
     *
     * @param string $assoc The association to join with
     * @param \Closure|null $builder a function that will receive a pre-made query object
     * that can be used to add custom conditions or selecting some fields
     * @return $this
     */
    public function leftJoinWith(string $assoc, ?Closure $builder = null)
    {
        $result = $this->getEagerLoader()
            ->setMatching($assoc, $builder, [
                'joinType' => static::JOIN_TYPE_LEFT,
                'fields' => false,
            ])
            ->getMatching();
        $this->_addAssociationsToTypeMap($this->getRepository(), $this->getTypeMap(), $result);
        $this->_dirty();

        return $this;
    }

    /**
     * Creates an INNER JOIN with the passed association table while preserving
     * the foreign key matching and the custom conditions that were originally set
     * for it.
     *
     * This function will add entries in the `contain` graph.
     *
     * ### Example:
     *
     * ```
     * // Bring only articles that were tagged with 'cake'
     * $query->innerJoinWith('Tags', function ($q) {
     *     return $q->where(['name' => 'cake']);
     * });
     * ```
     *
     * This will create the following SQL:
     *
     * ```
     * SELECT Articles.*
     * FROM articles Articles
     * INNER JOIN tags Tags ON Tags.name = 'cake'
     * INNER JOIN articles_tags ArticlesTags ON ArticlesTags.tag_id = Tags.id
     *   AND ArticlesTags.articles_id = Articles.id
     * ```
     *
     * This function works the same as `matching()` with the difference that it
     * will select no fields from the association.
     *
     * @param string $assoc The association to join with
     * @param \Closure|null $builder a function that will receive a pre-made query object
     * that can be used to add custom conditions or selecting some fields
     * @return $this
     * @see \Cake\ORM\Query\SeletQuery::matching()
     */
    public function innerJoinWith(string $assoc, ?Closure $builder = null)
    {
        $result = $this->getEagerLoader()
            ->setMatching($assoc, $builder, [
                'joinType' => static::JOIN_TYPE_INNER,
                'fields' => false,
            ])
            ->getMatching();
        $this->_addAssociationsToTypeMap($this->getRepository(), $this->getTypeMap(), $result);
        $this->_dirty();

        return $this;
    }

    /**
     * Adds filtering conditions to this query to only bring rows that have no match
     * to another from an associated table, based on conditions in the associated table.
     *
     * This function will add entries in the `contain` graph.
     *
     * ### Example:
     *
     * ```
     * // Bring only articles that were not tagged with 'cake'
     * $query->notMatching('Tags', function ($q) {
     *     return $q->where(['name' => 'cake']);
     * });
     * ```
     *
     * It is possible to filter by deep associations by using dot notation:
     *
     * ### Example:
     *
     * ```
     * // Bring only articles that weren't commented by 'markstory'
     * $query->notMatching('Comments.Users', function ($q) {
     *     return $q->where(['username' => 'markstory']);
     * });
     * ```
     *
     * As this function will create a `LEFT JOIN`, you might want to consider
     * calling `distinct` on this query as you might get duplicate rows if
     * your conditions don't filter them already. This might be the case, for example,
     * of the same article having multiple comments.
     *
     * ### Example:
     *
     * ```
     * // Bring unique articles that were commented by 'markstory'
     * $query->distinct(['Articles.id'])
     *     ->notMatching('Comments.Users', function ($q) {
     *         return $q->where(['username' => 'markstory']);
     *     });
     * ```
     *
     * Please note that the query passed to the closure will only accept calling
     * `select`, `where`, `andWhere` and `orWhere` on it. If you wish to
     * add more complex clauses you can do it directly in the main query.
     *
     * @param string $assoc The association to filter by
     * @param \Closure|null $builder a function that will receive a pre-made query object
     * that can be used to add custom conditions or selecting some fields
     * @return $this
     */
    public function notMatching(string $assoc, ?Closure $builder = null)
    {
        $result = $this->getEagerLoader()
            ->setMatching($assoc, $builder, [
                'joinType' => static::JOIN_TYPE_LEFT,
                'fields' => false,
                'negateMatch' => true,
            ])
            ->getMatching();
        $this->_addAssociationsToTypeMap($this->getRepository(), $this->getTypeMap(), $result);
        $this->_dirty();

        return $this;
    }

    /**
     * Creates a copy of this current query, triggers beforeFind and resets some state.
     *
     * The following state will be cleared:
     *
     * - autoFields
     * - limit
     * - offset
     * - map/reduce functions
     * - result formatters
     * - order
     * - containments
     *
     * This method creates query clones that are useful when working with subqueries.
     *
     * @return static
     */
    public function cleanCopy(): static
    {
        $clone = clone $this;
        $clone->triggerBeforeFind();
        $clone->disableAutoFields();
        $clone->limit(null);
        $clone->orderBy([], true);
        $clone->offset(null);
        $clone->mapReduce(null, null, true);
        $clone->formatResults(null, self::OVERWRITE);
        $clone->setSelectTypeMap(new TypeMap());
        $clone->decorateResults(null, true);

        return $clone;
    }

    /**
     * Clears the internal result cache and the internal count value from the current
     * query object.
     *
     * @return $this
     */
    public function clearResult()
    {
        $this->_dirty();

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Handles cloning eager loaders.
     */
    public function __clone()
    {
        parent::__clone();
        if ($this->_eagerLoader !== null) {
            $this->_eagerLoader = clone $this->_eagerLoader;
        }
    }

    /**
     * {@inheritDoc}
     *
     * Returns the COUNT(*) for the query. If the query has not been
     * modified, and the count has already been performed the cached
     * value is returned
     *
     * @return int
     */
    public function count(): int
    {
        return $this->_resultsCount ??= $this->_performCount();
    }

    /**
     * Performs and returns the COUNT(*) for the query.
     *
     * @return int
     */
    protected function _performCount(): int
    {
        $query = $this->cleanCopy();
        $counter = $this->_counter;
        if ($counter !== null) {
            $query->counter(null);

            return (int)$counter($query);
        }

        $complex = (
            $query->clause('distinct') ||
            count($query->clause('group')) ||
            count($query->clause('union')) ||
            $query->clause('having')
        );

        if (!$complex) {
            // Expression fields could have bound parameters.
            foreach ($query->clause('select') as $field) {
                if ($field instanceof ExpressionInterface) {
                    $complex = true;
                    break;
                }
            }
        }

        if (!$complex && $this->_valueBinder !== null) {
            $order = $this->clause('order');
            assert($order === null || $order instanceof QueryExpression);
            $complex = $order === null ? false : $order->hasNestedExpression();
        }

        $count = ['count' => $query->func()->count('*')];

        if (!$complex) {
            $query->getEagerLoader()->disableAutoFields();
            $statement = $query
                ->select($count, true)
                ->disableAutoFields()
                ->execute();
        } else {
            $statement = $this->getConnection()->selectQuery()
                ->select($count)
                ->from(['count_source' => $query])
                ->execute();
        }

        $result = $statement->fetch('assoc');

        if ($result === false) {
            return 0;
        }

        return (int)$result['count'];
    }

    /**
     * Registers a callback that will be executed when the `count` method in
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
     * If the first param is a null value, the built-in counter function will be called
     * instead
     *
     * @param \Closure|null $counter The counter value
     * @return $this
     */
    public function counter(?Closure $counter)
    {
        $this->_counter = $counter;

        return $this;
    }

    /**
     * Toggle hydrating entities.
     *
     * If set to false array results will be returned for the query.
     *
     * @param bool $enable Use a boolean to set the hydration mode.
     * @return $this
     */
    public function enableHydration(bool $enable = true)
    {
        $this->_dirty();
        $this->_hydrate = $enable;

        return $this;
    }

    /**
     * Disable hydrating entities.
     *
     * Disabling hydration will cause array results to be returned for the query
     * instead of entities.
     *
     * @return $this
     */
    public function disableHydration()
    {
        $this->_dirty();
        $this->_hydrate = false;

        return $this;
    }

    /**
     * Returns the current hydration mode.
     *
     * @return bool
     */
    public function isHydrationEnabled(): bool
    {
        return $this->_hydrate;
    }

    /**
     * Trigger the beforeFind event on the query's repository object.
     *
     * Will not trigger more than once, and only for select queries.
     *
     * @return void
     */
    public function triggerBeforeFind(): void
    {
        if (!$this->_beforeFindFired) {
            $this->_beforeFindFired = true;

            $repository = $this->getRepository();
            $repository->dispatchEvent('Model.beforeFind', [
                $this,
                new ArrayObject($this->_options),
                !$this->isEagerLoaded(),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function sql(?ValueBinder $binder = null): string
    {
        $this->triggerBeforeFind();

        $this->_transformQuery();

        return parent::sql($binder);
    }

    /**
     * Executes this query and returns an iterable containing the results.
     *
     * @return iterable
     */
    protected function _execute(): iterable
    {
        $this->triggerBeforeFind();
        if ($this->_results !== null) {
            return $this->_results;
        }

        $results = parent::all();
        if (!is_array($results)) {
            $results = iterator_to_array($results);
        }
        $results = $this->getEagerLoader()->loadExternal($this, $results);

        return $this->resultSetFactory()->createResultSet($this, $results);
    }

    /**
     * Get resultset factory.
     *
     * @return \Cake\ORM\ResultSetFactory
     */
    protected function resultSetFactory(): ResultSetFactory
    {
        return $this->resultSetFactory ??= new ResultSetFactory();
    }

    /**
     * Applies some defaults to the query object before it is executed.
     *
     * Specifically add the FROM clause, adds default table fields if none are
     * specified and applies the joins required to eager load associations defined
     * using `contain`
     *
     * It also sets the default types for the columns in the select clause
     *
     * @see \Cake\Database\Query::execute()
     * @return void
     */
    protected function _transformQuery(): void
    {
        if (!$this->_dirty) {
            return;
        }

        $repository = $this->getRepository();

        if (empty($this->_parts['from'])) {
            $this->from([$repository->getAlias() => $repository->getTable()]);
        }
        $this->_addDefaultFields();
        $this->getEagerLoader()->attachAssociations($this, $repository, !$this->_hasFields);
        $this->_addDefaultSelectTypes();
    }

    /**
     * Inspects if there are any set fields for selecting, otherwise adds all
     * the fields for the default table.
     *
     * @return void
     */
    protected function _addDefaultFields(): void
    {
        $select = $this->clause('select');
        $this->_hasFields = true;

        $repository = $this->getRepository();

        if (!count($select) || $this->_autoFields === true) {
            $this->_hasFields = false;
            $this->select($repository->getSchema()->columns());
            $select = $this->clause('select');
        }

        if ($this->aliasingEnabled) {
            $select = $this->aliasFields($select, $repository->getAlias());
        }
        $this->select($select, true);
    }

    /**
     * Sets the default types for converting the fields in the select clause
     *
     * @return void
     */
    protected function _addDefaultSelectTypes(): void
    {
        $typeMap = $this->getTypeMap()->getDefaults();
        $select = $this->clause('select');
        $types = [];

        foreach ($select as $alias => $value) {
            if ($value instanceof TypedResultInterface) {
                $types[$alias] = $value->getReturnType();
                continue;
            }
            if (isset($typeMap[$alias])) {
                $types[$alias] = $typeMap[$alias];
                continue;
            }
            if (is_string($value) && isset($typeMap[$value])) {
                $types[$alias] = $typeMap[$value];
            }
        }
        $this->getSelectTypeMap()->addDefaults($types);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $finder The finder method to use.
     * @param mixed ...$args Arguments that match up to finder-specific parameters
     * @return static<TSubject> Returns a modified query.
     * @psalm-suppress MoreSpecificReturnType
     */
    public function find(string $finder, mixed ...$args): static
    {
        $table = $this->getRepository();

        /** @psalm-suppress LessSpecificReturnStatement */
        return $table->callFinder($finder, $this, ...$args);
    }

    /**
     * Disable auto adding table's alias to the fields of SELECT clause.
     *
     * @return $this
     */
    public function disableAutoAliasing()
    {
        $this->aliasingEnabled = false;

        return $this;
    }

    /**
     * Marks a query as dirty, removing any preprocessed information
     * from in memory caching such as previous results
     *
     * @return void
     */
    protected function _dirty(): void
    {
        $this->_results = null;
        $this->_resultsCount = null;
        parent::_dirty();
    }

    /**
     * @inheritDoc
     */
    public function __debugInfo(): array
    {
        $eagerLoader = $this->getEagerLoader();

        return parent::__debugInfo() + [
            'hydrate' => $this->_hydrate,
            'formatters' => count($this->_formatters),
            'mapReducers' => count($this->_mapReduce),
            'contain' => $eagerLoader->getContain(),
            'matching' => $eagerLoader->getMatching(),
            'extraOptions' => $this->_options,
            'repository' => $this->_repository,
        ];
    }

    /**
     * Executes the query and converts the result set into JSON.
     *
     * Part of JsonSerializable interface.
     *
     * @return \Cake\Datasource\ResultSetInterface<(\Cake\Datasource\EntityInterface|mixed)> The data to convert to JSON.
     */
    public function jsonSerialize(): ResultSetInterface
    {
        return $this->all();
    }

    /**
     * Sets whether the ORM should automatically append fields.
     *
     * By default calling select() will disable auto-fields. You can re-enable
     * auto-fields with this method.
     *
     * @param bool $value Set true to enable, false to disable.
     * @return $this
     */
    public function enableAutoFields(bool $value = true)
    {
        $this->_autoFields = $value;

        return $this;
    }

    /**
     * Disables automatically appending fields.
     *
     * @return $this
     */
    public function disableAutoFields()
    {
        $this->_autoFields = false;

        return $this;
    }

    /**
     * Gets whether the ORM should automatically append fields.
     *
     * By default calling select() will disable auto-fields. You can re-enable
     * auto-fields with enableAutoFields().
     *
     * @return bool|null The current value. Returns null if neither enabled or disabled yet.
     */
    public function isAutoFieldsEnabled(): ?bool
    {
        return $this->_autoFields;
    }
}

// phpcs:disable
class_exists('Cake\ORM\Query');
// phpcs:enable
