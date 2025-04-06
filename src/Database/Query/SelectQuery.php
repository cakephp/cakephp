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
namespace Cake\Database\Query;

use ArrayIterator;
use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\WindowExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\StatementInterface;
use Cake\Database\TypeMap;
use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use function Cake\Core\deprecationWarning;

/**
 * This class is used to generate SELECT queries for the relational database.
 *
 * @template T of mixed
 * @implements \IteratorAggregate<T>
 */
class SelectQuery extends Query implements IteratorAggregate
{
    /**
     * Type of this query.
     *
     * @var string
     */
    protected string $_type = self::TYPE_SELECT;

    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $_parts = [
        'comment' => null,
        'modifier' => [],
        'with' => [],
        'select' => [],
        'distinct' => false,
        'from' => [],
        'join' => [],
        'where' => null,
        'group' => [],
        'having' => null,
        'window' => [],
        'order' => null,
        'limit' => null,
        'offset' => null,
        'union' => [],
        'epilog' => null,
        'intersect' => [],
    ];

    /**
     * A list of callbacks to be called to alter each row from resulting
     * statement upon retrieval. Each one of the callback function will receive
     * the row array as first argument.
     *
     * @var array<\Closure>
     */
    protected array $_resultDecorators = [];

    /**
     * Result set from executed SELECT query.
     *
     * @var iterable|null
     */
    protected ?iterable $_results = null;

    /**
     * Boolean for tracking whether buffered results
     * are enabled.
     *
     * @var bool
     */
    protected bool $bufferedResults = true;

    /**
     * The Type map for fields in the select clause
     *
     * @var \Cake\Database\TypeMap|null
     */
    protected ?TypeMap $_selectTypeMap = null;

    /**
     * Tracking flag to disable casting
     *
     * @var bool
     */
    protected bool $typeCastEnabled = true;

    /**
     * Executes query and returns set of decorated results.
     *
     * The results are cached until the query is modified and marked dirty.
     *
     * @return iterable
     * @throws \Cake\Core\Exception\CakeException When query is not a SELECT query.
     */
    public function all(): iterable
    {
        if ($this->_results === null || $this->_dirty) {
            $this->_results = $this->execute()->fetchAll(StatementInterface::FETCH_TYPE_ASSOC);
        }

        return $this->_results;
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
     *     return ['article_id', 'total' => $query->func()->count('*')];
     * })
     * ```
     *
     * By default no fields are selected, if you have an instance of `Cake\ORM\Query` and try to append
     * fields you should also call `Cake\ORM\Query::enableAutoFields()` to select the default fields
     * from the table.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|float|int $fields fields to be added to the list.
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     */
    public function select(ExpressionInterface|Closure|array|string|float|int $fields = [], bool $overwrite = false)
    {
        if (!is_string($fields) && $fields instanceof Closure) {
            $fields = $fields($this);
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        if ($overwrite) {
            $this->_parts['select'] = $fields;
        } else {
            $this->_parts['select'] = array_merge($this->_parts['select'], $fields);
        }

        $this->_dirty();

        return $this;
    }

    /**
     * Adds a `DISTINCT` clause to the query to remove duplicates from the result set.
     * This clause can only be used for select statements.
     *
     * If you wish to filter duplicates based of those rows sharing a particular field
     * or set of fields, you may pass an array of fields to filter on. Beware that
     * this option might not be fully supported in all database systems.
     *
     * ### Examples:
     *
     * ```
     * // Filters products with the same name and city
     * $query->select(['name', 'city'])->from('products')->distinct();
     *
     * // Filters products in the same city
     * $query->distinct(['city']);
     * $query->distinct('city');
     *
     * // Filter products with the same name
     * $query->distinct(['name'], true);
     * $query->distinct('name', true);
     * ```
     *
     * @param \Cake\Database\ExpressionInterface|array|string|bool $on Enable/disable distinct class
     * or list of fields to be filtered on
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     */
    public function distinct(ExpressionInterface|array|string|bool $on = [], bool $overwrite = false)
    {
        if ($on === []) {
            $on = true;
        } elseif (is_string($on)) {
            $on = [$on];
        }

        if (is_array($on)) {
            $merge = [];
            if (is_array($this->_parts['distinct'])) {
                $merge = $this->_parts['distinct'];
            }
            $on = $overwrite ? array_values($on) : array_merge($merge, array_values($on));
        }

        $this->_parts['distinct'] = $on;
        $this->_dirty();

        return $this;
    }

    /**
     * Adds a single or multiple fields to be used in the GROUP BY clause for this query.
     * Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * By default this function will append any passed argument to the list of fields
     * to be grouped, unless the second argument is set to true.
     *
     * ### Examples:
     *
     * ```
     * // Produces GROUP BY id, title
     * $query->groupBy(['id', 'title']);
     *
     * // Produces GROUP BY title
     * $query->groupBy('title');
     * ```
     *
     * Group fields are not suitable for use with user supplied data as they are
     * not sanitized by the query builder.
     *
     * @param \Cake\Database\ExpressionInterface|array|string $fields fields to be added to the list
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     * @deprecated 5.0.0 Use groupBy() instead now that CollectionInterface methods are no longer proxied.
     */
    public function group(ExpressionInterface|array|string $fields, bool $overwrite = false)
    {
        deprecationWarning('5.0.0', 'SelectQuery::group() is deprecated. Use SelectQuery::groupBy() instead.');

        return $this->groupBy($fields, $overwrite);
    }

    /**
     * Adds a single or multiple fields to be used in the GROUP BY clause for this query.
     * Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * By default this function will append any passed argument to the list of fields
     * to be grouped, unless the second argument is set to true.
     *
     * ### Examples:
     *
     * ```
     * // Produces GROUP BY id, title
     * $query->groupBy(['id', 'title']);
     *
     * // Produces GROUP BY title
     * $query->groupBy('title');
     * ```
     *
     * Group fields are not suitable for use with user supplied data as they are
     * not sanitized by the query builder.
     *
     * @param \Cake\Database\ExpressionInterface|array|string $fields fields to be added to the list
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     */
    public function groupBy(ExpressionInterface|array|string $fields, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['group'] = [];
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $this->_parts['group'] = array_merge($this->_parts['group'], array_values($fields));
        $this->_dirty();

        return $this;
    }

    /**
     * Adds a condition or set of conditions to be used in the `HAVING` clause for this
     * query. This method operates in exactly the same way as the method `where()`
     * does. Please refer to its documentation for an insight on how to using each
     * parameter.
     *
     * Having fields are not suitable for use with user supplied data as they are
     * not sanitized by the query builder.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|null $conditions The having conditions.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     * @param bool $overwrite whether to reset conditions with passed list or not
     * @see \Cake\Database\Query::where()
     * @return $this
     */
    public function having(
        ExpressionInterface|Closure|array|string|null $conditions = null,
        array $types = [],
        bool $overwrite = false,
    ) {
        if ($overwrite) {
            $this->_parts['having'] = $this->newExpr();
        }
        $this->_conjugate('having', $conditions, 'AND', $types);

        return $this;
    }

    /**
     * Connects any previously defined set of conditions to the provided list
     * using the AND operator in the HAVING clause. This method operates in exactly
     * the same way as the method `andWhere()` does. Please refer to its
     * documentation for an insight on how to using each parameter.
     *
     * Having fields are not suitable for use with user supplied data as they are
     * not sanitized by the query builder.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string $conditions The AND conditions for HAVING.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     * @see \Cake\Database\Query::andWhere()
     * @return $this
     */
    public function andHaving(ExpressionInterface|Closure|array|string $conditions, array $types = [])
    {
        $this->_conjugate('having', $conditions, 'AND', $types);

        return $this;
    }

    /**
     * Adds a named window expression.
     *
     * You are responsible for adding windows in the order your database requires.
     *
     * @param string $name Window name
     * @param \Cake\Database\Expression\WindowExpression|\Closure $window Window expression
     * @param bool $overwrite Clear all previous query window expressions
     * @return $this
     */
    public function window(string $name, WindowExpression|Closure $window, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['window'] = [];
        }

        if ($window instanceof Closure) {
            $window = $window(new WindowExpression(), $this);
            if (!($window instanceof WindowExpression)) {
                throw new CakeException('You must return a `WindowExpression` from a Closure passed to `window()`.');
            }
        }

        $this->_parts['window'][] = ['name' => new IdentifierExpression($name), 'window' => $window];
        $this->_dirty();

        return $this;
    }

    /**
     * Set the page of results you want.
     *
     * This method provides an easier to use interface to set the limit + offset
     * in the record set you want as results. If empty the limit will default to
     * the existing limit clause, and if that too is empty, then `25` will be used.
     *
     * Pages must start at 1.
     *
     * @param int $num The page number you want.
     * @param int|null $limit The number of rows you want in the page. If null
     *  the current limit clause will be used.
     * @return $this
     * @throws \InvalidArgumentException If page number < 1.
     */
    public function page(int $num, ?int $limit = null)
    {
        if ($num < 1) {
            throw new InvalidArgumentException('Pages must start at 1.');
        }
        if ($limit !== null) {
            $this->limit($limit);
        }
        $limit = $this->clause('limit');
        if ($limit === null) {
            $limit = 25;
            $this->limit($limit);
        }
        $offset = ($num - 1) * $limit;
        if (PHP_INT_MAX <= $offset) {
            $offset = PHP_INT_MAX;
        }
        $this->offset((int)$offset);

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with an UNION operator with
     * this query. This is used to combine the result set of this query with the one
     * that will be returned by the passed query. You can add as many queries as you
     * required by calling multiple times this method with different queries.
     *
     * By default, the UNION operator will remove duplicate rows, if you wish to include
     * every row for all queries, use unionAll().
     *
     * ### Examples
     *
     * ```
     * $union = (new SelectQuery($conn))->select(['id', 'title'])->from(['a' => 'articles']);
     * $query->select(['id', 'name'])->from(['d' => 'things'])->union($union);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM things d UNION SELECT id, title FROM articles a`
     *
     * @param \Cake\Database\Query|string $query full SQL query to be used in UNION operator
     * @param bool $overwrite whether to reset the list of queries to be operated or not
     * @return $this
     */
    public function union(Query|string $query, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['union'] = [];
        }
        $this->_parts['union'][] = [
            'all' => false,
            'query' => $query,
        ];
        $this->_dirty();

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with the UNION ALL operator with
     * this query. This is used to combine the result set of this query with the one
     * that will be returned by the passed query. You can add as many queries as you
     * required by calling multiple times this method with different queries.
     *
     * Unlike UNION, UNION ALL will not remove duplicate rows.
     *
     * ```
     * $union = (new SelectQuery($conn))->select(['id', 'title'])->from(['a' => 'articles']);
     * $query->select(['id', 'name'])->from(['d' => 'things'])->unionAll($union);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM things d UNION ALL SELECT id, title FROM articles a`
     *
     * @param \Cake\Database\Query|string $query full SQL query to be used in UNION operator
     * @param bool $overwrite whether to reset the list of queries to be operated or not
     * @return $this
     */
    public function unionAll(Query|string $query, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['union'] = [];
        }
        $this->_parts['union'][] = [
            'all' => true,
            'query' => $query,
        ];
        $this->_dirty();

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with an INTERSECT operator with
     * this query. This is used to combine the result set of this query with the one
     * that will be returned by the passed query. You can add as many queries as you
     * required by calling multiple times this method with different queries.
     *
     * By default, the INTERSECT operator will remove duplicate rows, if you wish to include
     * every row for all queries, use intersectAll().
     *
     * ### Examples
     *
     * ```
     * $intersect = (new SelectQuery($conn))->select(['id', 'title'])->from(['a' => 'articles']);
     * $query->select(['id', 'name'])->from(['d' => 'things'])->intersect($intersect);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM things d INTERSECT SELECT id, title FROM articles a`
     *
     * @param \Cake\Database\Query|string $query full SQL query to be used in INTERSECT operator
     * @param bool $overwrite whether to reset the list of queries to be operated or not
     * @return $this
     */
    public function intersect(Query|string $query, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['intersect'] = [];
        }
        $this->_parts['intersect'][] = [
            'all' => false,
            'query' => $query,
        ];
        $this->_dirty();

        return $this;
    }

    /**
     * Adds a complete query to be used in conjunction with the INTERSECT ALL operator with
     * this query. This is used to combine the result set of this query with the one
     * that will be returned by the passed query. You can add as many queries as you
     * required by calling multiple times this method with different queries.
     *
     * Unlike INTERSECT, INTERSECT ALL will not remove duplicate rows.
     *
     * ```
     * $intersect = (new SelectQuery($conn))->select(['id', 'title'])->from(['a' => 'articles']);
     * $query->select(['id', 'name'])->from(['d' => 'things'])->intersectAll($intersect);
     * ```
     *
     * Will produce:
     *
     * `SELECT id, name FROM things d INTERSECT ALL SELECT id, title FROM articles a`
     *
     * @param \Cake\Database\Query|string $query full SQL query to be used in INTERSECT operator
     * @param bool $overwrite whether to reset the list of queries to be operated or not
     * @return $this
     */
    public function intersectAll(Query|string $query, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['intersect'] = [];
        }
        $this->_parts['intersect'][] = [
            'all' => true,
            'query' => $query,
        ];
        $this->_dirty();

        return $this;
    }

    /**
     * Executes this query and returns a results iterator. This function is required
     * for implementing the IteratorAggregate interface and allows the query to be
     * iterated without having to call all() manually, thus making it look like
     * a result set instead of the query itself.
     *
     * @return \Traversable
     */
    public function getIterator(): Traversable
    {
        if ($this->bufferedResults) {
            /** @var \Traversable|array $results */
            $results = $this->all();
            if (is_array($results)) {
                return new ArrayIterator($results);
            }

            return $results;
        }

        return $this->execute();
    }

    /**
     * Registers a callback to be executed for each result that is fetched from the
     * result set, the callback function will receive as first parameter an array with
     * the raw data from the database for every row that is fetched and must return the
     * row with any possible modifications.
     *
     * Callbacks will be executed lazily, if only 3 rows are fetched for database it will
     * be called 3 times, event though there might be more rows to be fetched in the cursor.
     *
     * Callbacks are stacked in the order they are registered, if you wish to reset the stack
     * the call this function with the second parameter set to true.
     *
     * If you wish to remove all decorators from the stack, set the first parameter
     * to null and the second to true.
     *
     * ### Example
     *
     * ```
     * $query->decorateResults(function ($row) {
     *   $row['order_total'] = $row['subtotal'] + ($row['subtotal'] * $row['tax']);
     *    return $row;
     * });
     * ```
     *
     * @param \Closure|null $callback The callback to invoke when results are fetched.
     * @param bool $overwrite Whether this should append or replace all existing decorators.
     * @return $this
     */
    public function decorateResults(?Closure $callback, bool $overwrite = false)
    {
        $this->_dirty();
        if ($overwrite) {
            $this->_resultDecorators = [];
        }

        if ($callback !== null) {
            $this->_resultDecorators[] = $callback;
        }

        return $this;
    }

    /**
     * Get result decorators.
     *
     * @return array
     */
    public function getResultDecorators(): array
    {
        return $this->_resultDecorators;
    }

    /**
     * Enables buffered results.
     *
     * When enabled the results returned by this query will be
     * buffered. This enables you to iterate a result set multiple times, or
     * both cache and iterate it.
     *
     * When disabled it will consume less memory as fetched results are not
     * remembered for future iterations.
     *
     * @return $this
     */
    public function enableBufferedResults()
    {
        $this->_dirty();
        $this->bufferedResults = true;

        return $this;
    }

    /**
     * Disables buffered results.
     *
     * Disabling buffering will consume less memory as fetched results are not
     * remembered for future iterations.
     *
     * @return $this
     */
    public function disableBufferedResults()
    {
        $this->_dirty();
        $this->bufferedResults = false;

        return $this;
    }

    /**
     * Returns whether buffered results are enabled/disabled.
     *
     * When enabled the results returned by this query will be
     * buffered. This enables you to iterate a result set multiple times, or
     * both cache and iterate it.
     *
     * When disabled it will consume less memory as fetched results are not
     * remembered for future iterations.
     *
     * @return bool
     */
    public function isBufferedResultsEnabled(): bool
    {
        return $this->bufferedResults;
    }

    /**
     * Sets the TypeMap class where the types for each of the fields in the
     * select clause are stored.
     *
     * @param \Cake\Database\TypeMap|array $typeMap Creates a TypeMap if array, otherwise sets the given TypeMap.
     * @return $this
     */
    public function setSelectTypeMap(TypeMap|array $typeMap)
    {
        $this->_selectTypeMap = is_array($typeMap) ? new TypeMap($typeMap) : $typeMap;
        $this->_dirty();

        return $this;
    }

    /**
     * Gets the TypeMap class where the types for each of the fields in the
     * select clause are stored.
     *
     * @return \Cake\Database\TypeMap
     */
    public function getSelectTypeMap(): TypeMap
    {
        return $this->_selectTypeMap ??= new TypeMap();
    }

    /**
     * Disables result casting.
     *
     * When disabled, the fields will be returned as received from the database
     * driver (which in most environments means they are being returned as
     * strings), which can improve performance with larger datasets.
     *
     * @return $this
     */
    public function disableResultsCasting()
    {
        $this->typeCastEnabled = false;

        return $this;
    }

    /**
     * Enables result casting.
     *
     * When enabled, the fields in the results returned by this Query will be
     * cast to their corresponding PHP data type.
     *
     * @return $this
     */
    public function enableResultsCasting()
    {
        $this->typeCastEnabled = true;

        return $this;
    }

    /**
     * Returns whether result casting is enabled/disabled.
     *
     * When enabled, the fields in the results returned by this Query will be
     * casted to their corresponding PHP data type.
     *
     * When disabled, the fields will be returned as received from the database
     * driver (which in most environments means they are being returned as
     * strings), which can improve performance with larger datasets.
     *
     * @return bool
     */
    public function isResultsCastingEnabled(): bool
    {
        return $this->typeCastEnabled;
    }

    /**
     * Handles clearing iterator and cloning all expressions and value binders.
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();

        $this->_results = null;
        if ($this->_selectTypeMap !== null) {
            $this->_selectTypeMap = clone $this->_selectTypeMap;
        }
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $return = parent::__debugInfo();
        $return['decorators'] = count($this->_resultDecorators);

        return $return;
    }

    /**
     * Sets the connection role.
     *
     * @param string $role Connection role ('read' or 'write')
     * @return $this
     */
    public function setConnectionRole(string $role)
    {
        assert($role === Connection::ROLE_READ || $role === Connection::ROLE_WRITE);
        $this->connectionRole = $role;

        return $this;
    }

    /**
     * Sets the connection role to read.
     *
     * @return $this
     */
    public function useReadRole()
    {
        return $this->setConnectionRole(Connection::ROLE_READ);
    }

    /**
     * Sets the connection role to write.
     *
     * @return $this
     */
    public function useWriteRole()
    {
        return $this->setConnectionRole(Connection::ROLE_WRITE);
    }
}
