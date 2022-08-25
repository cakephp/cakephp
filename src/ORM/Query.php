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
namespace Cake\ORM;

use ArrayObject;
use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query as DatabaseQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\QueryInterface as DatabaseQueryInterface;
use Cake\Database\StatementInterface;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\QueryTrait;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Closure;
use JsonSerializable;

/**
 * Extends the base Query class to provide new methods related to association
 * loading, automatic fields selection, automatic type casting and to wrap results
 * into a specific iterator that will be responsible for hydrating results if
 * required.
 *
 * @property \Cake\ORM\Table $_repository Instance of a table object this query is bound to.
 * @method mixed clause(string $name)
 * @method \Cake\Database\Expression\QueryExpression newExpr(\Cake\Database\ExpressionInterface|array|string|null $rawExpression = null)
 * @method \Cake\Database\FunctionsBuilder func()
 * @method $this setValueBinder(?\Cake\Database\ValueBinder $binder)
 * @method \Cake\Database\ValueBinder getValueBinder()
 * @method \Cake\Database\TypeMap getTypeMap()
 * @method \Cake\Database\TypeMap getSelectTypeMap()
 * @method array<string, string> getDefaultTypes()
 * @method $this from(array|string $tables = [], bool $overwrite = false)
 * @method $this select(\Cake\Database\ExpressionInterface|\Closure|array|string|float|int $fields = [], bool $overwrite = false)
 * @method $this group(\Cake\Database\ExpressionInterface|array|string $fields, bool $overwrite = false)
 * @method $this union(\Cake\Database\QueryInterface|string $query, bool $overwrite = false)
 * @method $this unionAll(\Cake\Database\QueryInterface|string $query, bool $overwrite = false)
 * @method $this order(\Cake\Database\ExpressionInterface|\Closure|array|string $fields, bool $overwrite = false)
 * @method $this orderAsc(\Cake\Database\ExpressionInterface|\Closure|array|string $fields, bool $overwrite = false)
 * @method $this orderDesc(\Cake\Database\ExpressionInterface|\Closure|array|string $fields, bool $overwrite = false)
 * @method $this setSelectTypeMap(\Cake\Database\TypeMap $typeMap)
 * @method $this enableResultsCasting()
 * @method $this disableResultsCasting()
 * @method $this decorateResults(?\Closure $callback, bool $overwrite = false)
 * @method $this delete(?string $table = null)
 * @method $this join(array|string $tables, array $types = [], bool $overwrite = false)
 * @method $this innerJoin(array|string $table, \Cake\Database\ExpressionInterface|\Closure|array|string $conditions = [], array $types = [])
 * @method $this update(\Cake\Database\ExpressionInterface|string $table)
 * @method $this set(\Cake\Database\Expression\QueryExpression|\Closure|array|string $key, mixed $value = null, array|string $types = [])
 * @method $this values(\Cake\Database\Expression\ValuesExpression|\Cake\Database\QueryInterface|array $data)
 * @method $this insert(array $columns, array $types = [])
 * @method $this into(string $table)
 */
class Query implements DatabaseQueryInterface, JsonSerializable, QueryInterface
{
    use QueryTrait {
        cache as private _cache;
        all as private _all;
    }

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

    protected Connection $connection;

    protected DatabaseQuery $dbQuery;

    /**
     * Indicates whether internal state of this query was changed, this is used to
     * discard internal cached objects such as the transformed query or the reference
     * to the executed statement.
     *
     * @var bool
     */
    protected bool $_dirty = true;

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
     * @var \Cake\ORM\ResultSetFactory
     */
    protected ResultSetFactory $resultSetFactory;

    /**
     * Constructor
     *
     * @param \Cake\Database\Connection $connection The connection object
     * @param \Cake\ORM\Table $table The table this query is starting on
     */
    public function __construct(Connection $connection, Table $table)
    {
        $this->connection = $connection;
        $this->repository($table);
    }

    /**
     * Sets the connection instance to be used for executing and transforming this query.
     *
     * @param \Cake\Database\Connection $connection Connection instance
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->_dirty();
        $this->connection = $connection;

        return $this;
    }

    /**
     * Gets the connection instance to be used for executing and transforming this query.
     *
     * @return \Cake\Database\Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get database query instance.
     *
     * @param string|null $type Query type.
     * @return \Cake\Database\Query
     * @template T of string|null
     * @psalm-param T $type
     * @psalm-return (
     *     T is \Cake\Database\Query::TYPE_UPDATE
     *     ? \Cake\Database\Query\UpdateQuery
     *     : (
     *          T is \Cake\Database\Query::TYPE_INSERT
     *          ? \Cake\Database\Query\InsertQuery
     *          : (
     *              T is \Cake\Database\Query::TYPE_DELETE
     *              ? \Cake\Database\Query\DeleteQuery
     *              : \Cake\Database\Query\SelectQuery
     *          )
     *     )
     * )
     */
    protected function dbQuery(?string $type = null): DatabaseQuery
    {
        if (!isset($this->dbQuery)) {
            $this->dbQuery = match ($type) {
                DatabaseQuery::TYPE_SELECT => $this->getConnection()->newSelectQuery(),
                DatabaseQuery::TYPE_UPDATE => $this->getConnection()->newUpdateQuery(),
                DatabaseQuery::TYPE_INSERT => $this->getConnection()->newInsertQuery(),
                DatabaseQuery::TYPE_DELETE => $this->getConnection()->newDeleteQuery(),
                default => $this->getConnection()->newSelectQuery(),
            };

            $this->addDefaultTypes($this->getRepository());

            return $this->dbQuery;
        }

        if ($type && $this->dbQuery->type() !== $type) {
            throw new CakeException('Query type conversion is pending');
        }

        /** @phpstan-ignore-next-line */
        return $this->dbQuery;
    }

    /**
     * Set the default Table object that will be used by this query
     * and form the `FROM` clause.
     *
     * @param \Cake\ORM\Table $repository The default table object to use.
     * @return $this
     */
    public function repository(RepositoryInterface $repository)
    {
        assert(
            $repository instanceof Table,
            '$repository must be an instance of Cake\ORM\Table.'
        );

        $this->_repository = $repository;

        return $this;
    }

    /**
     * Returns the default table object that will be used by this query,
     * that is, the table that will appear in the from clause.
     *
     * @return \Cake\ORM\Table
     */
    public function getRepository(): Table
    {
        return $this->_repository;
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
        if ($fields instanceof Closure) {
            $fields = $fields($this);
        } else {
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
        }

        $this->dbQuery(DatabaseQuery::TYPE_SELECT)->select($fields, $overwrite);
        $this->_dirty();

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
     * @param array<string> $excludedFields The un-aliased column names you do not want selected from $table
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
     * Adds a condition or set of conditions to be used in the WHERE clause for this
     * query. Conditions can be expressed as an array of fields as keys with
     * comparison operators in it, the values for the array will be used for comparing
     * the field to such literal. Finally, conditions can be expressed as a single
     * string or an array of strings.
     *
     * When using arrays, each entry will be joined to the rest of the conditions using
     * an `AND` operator. Consecutive calls to this function will also join the new
     * conditions specified using the AND operator. Additionally, values can be
     * expressed using expression objects which can include other query objects.
     *
     * Any conditions created with this methods can be used with any `SELECT`, `UPDATE`
     * and `DELETE` type of queries.
     *
     * ### Conditions using operators:
     *
     * ```
     * $query->where([
     *     'posted >=' => new DateTime('3 days ago'),
     *     'title LIKE' => 'Hello W%',
     *     'author_id' => 1,
     * ], ['posted' => 'datetime']);
     * ```
     *
     * The previous example produces:
     *
     * `WHERE posted >= 2012-01-27 AND title LIKE 'Hello W%' AND author_id = 1`
     *
     * Second parameter is used to specify what type is expected for each passed
     * key. Valid types can be used from the mapped with Database\Type class.
     *
     * ### Nesting conditions with conjunctions:
     *
     * ```
     * $query->where([
     *     'author_id !=' => 1,
     *     'OR' => ['published' => true, 'posted <' => new DateTime('now')],
     *     'NOT' => ['title' => 'Hello']
     * ], ['published' => boolean, 'posted' => 'datetime']
     * ```
     *
     * The previous example produces:
     *
     * `WHERE author_id = 1 AND (published = 1 OR posted < '2012-02-01') AND NOT (title = 'Hello')`
     *
     * You can nest conditions using conjunctions as much as you like. Sometimes, you
     * may want to define 2 different options for the same key, in that case, you can
     * wrap each condition inside a new array:
     *
     * `$query->where(['OR' => [['published' => false], ['published' => true]])`
     *
     * Would result in:
     *
     * `WHERE (published = false) OR (published = true)`
     *
     * Keep in mind that every time you call where() with the third param set to false
     * (default), it will join the passed conditions to the previous stored list using
     * the `AND` operator. Also, using the same array key twice in consecutive calls to
     * this method will not override the previous value.
     *
     * ### Using expressions objects:
     *
     * ```
     * $exp = $query->newExpr()->add(['id !=' => 100, 'author_id' != 1])->tieWith('OR');
     * $query->where(['published' => true], ['published' => 'boolean'])->where($exp);
     * ```
     *
     * The previous example produces:
     *
     * `WHERE (id != 100 OR author_id != 1) AND published = 1`
     *
     * Other Query objects that be used as conditions for any field.
     *
     * ### Adding conditions in multiple steps:
     *
     * You can use callbacks to construct complex expressions, functions
     * receive as first argument a new QueryExpression object and this query instance
     * as second argument. Functions must return an expression object, that will be
     * added the list of conditions for the query using the `AND` operator.
     *
     * ```
     * $query
     *   ->where(['title !=' => 'Hello World'])
     *   ->where(function ($exp, $query) {
     *     $or = $exp->or(['id' => 1]);
     *     $and = $exp->and(['id >' => 2, 'id <' => 10]);
     *    return $or->add($and);
     *   });
     * ```
     *
     * * The previous example produces:
     *
     * `WHERE title != 'Hello World' AND (id = 1 OR (id > 2 AND id < 10))`
     *
     * ### Conditions as strings:
     *
     * ```
     * $query->where(['articles.author_id = authors.id', 'modified IS NULL']);
     * ```
     *
     * The previous example produces:
     *
     * `WHERE articles.author_id = authors.id AND modified IS NULL`
     *
     * Please note that when using the array notation or the expression objects, all
     * *values* will be correctly quoted and transformed to the correspondent database
     * data type automatically for you, thus securing your application from SQL injections.
     * The keys however, are not treated as unsafe input, and should be validated/sanitized.
     *
     * If you use string conditions make sure that your values are correctly quoted.
     * The safest thing you can do is to never use string conditions.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|null $conditions The conditions to filter on.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     * @param bool $overwrite whether to reset conditions with passed list or not
     * @see \Cake\Database\TypeFactory
     * @see \Cake\Database\Expression\QueryExpression
     * @return $this
     */
    public function where(
        ExpressionInterface|Closure|array|string|null $conditions = null,
        array $types = [],
        bool $overwrite = false
    ) {
        if ($conditions instanceof Closure) {
            $conditions = $conditions($this->newExpr(), $this);
        }

        $this->dbQuery()->where($conditions, $types, $overwrite);
        $this->_dirty();

        return $this;
    }

    /**
     * Connects any previously defined set of conditions to the provided list
     * using the AND operator. This function accepts the conditions list in the same
     * format as the method `where` does, hence you can use arrays, expression objects
     * callback functions or strings.
     *
     * It is important to notice that when calling this function, any previous set
     * of conditions defined for this query will be treated as a single argument for
     * the AND operator. This function will not only operate the most recently defined
     * condition, but all the conditions as a whole.
     *
     * When using an array for defining conditions, creating constraints form each
     * array entry will use the same logic as with the `where()` function. This means
     * that each array entry will be joined to the other using the AND operator, unless
     * you nest the conditions in the array using other operator.
     *
     * ### Examples:
     *
     * ```
     * $query->where(['title' => 'Hello World')->andWhere(['author_id' => 1]);
     * ```
     *
     * Will produce:
     *
     * `WHERE title = 'Hello World' AND author_id = 1`
     *
     * ```
     * $query
     *   ->where(['OR' => ['published' => false, 'published is NULL']])
     *   ->andWhere(['author_id' => 1, 'comments_count >' => 10])
     * ```
     *
     * Produces:
     *
     * `WHERE (published = 0 OR published IS NULL) AND author_id = 1 AND comments_count > 10`
     *
     * ```
     * $query
     *   ->where(['title' => 'Foo'])
     *   ->andWhere(function ($exp, $query) {
     *     return $exp
     *       ->or(['author_id' => 1])
     *       ->add(['author_id' => 2]);
     *   });
     * ```
     *
     * Generates the following conditions:
     *
     * `WHERE (title = 'Foo') AND (author_id = 1 OR author_id = 2)`
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string $conditions The conditions to add with AND.
     * @param array<string, string> $types Associative array of type names used to bind values to query
     * @see \Cake\Database\Query::where()
     * @see \Cake\Database\TypeFactory
     * @return $this
     */
    public function andWhere(ExpressionInterface|Closure|array|string $conditions, array $types = [])
    {
        $this->dbQuery()->andWhere($conditions, $types);
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
        $this->dbQuery()->page($num, $limit);
        $this->_dirty();

        return $this;
    }

    /**
     * Sets the number of records that should be retrieved from database,
     * accepts an integer or an expression object that evaluates to an integer.
     * In some databases, this operation might not be supported or will require
     * the query to be transformed in order to limit the result set size.
     *
     * ### Examples
     *
     * ```
     * $query->limit(10) // generates LIMIT 10
     * $query->limit($query->newExpr()->add(['1 + 1'])); // LIMIT (1 + 1)
     * ```
     *
     * @param \Cake\Database\ExpressionInterface|int|null $limit number of records to be returned
     * @return $this
     */
    public function limit(ExpressionInterface|int|null $limit)
    {
        $this->dbQuery()->limit($limit);
        $this->_dirty();

        return $this;
    }

    /**
     * Sets the number of records that should be skipped from the original result set
     * This is commonly used for paginating large results. Accepts an integer or an
     * expression object that evaluates to an integer.
     *
     * In some databases, this operation might not be supported or will require
     * the query to be transformed in order to limit the result set size.
     *
     * ### Examples
     *
     * ```
     * $query->offset(10) // generates OFFSET 10
     * $query->offset($query->newExpr()->add(['1 + 1'])); // OFFSET (1 + 1)
     * ```
     *
     * @param \Cake\Database\ExpressionInterface|int|null $offset number of records to be skipped
     * @return $this
     */
    public function offset(ExpressionInterface|int|null $offset)
    {
        $this->dbQuery()->offset($offset);
        $this->_dirty();

        return $this;
    }

    /**
     * Adds a single or multiple fields to be used in the ORDER clause for this query.
     * Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used as the field itself and the value will
     * represent the order in which such field should be ordered. When called multiple
     * times with the same fields as key, the last order definition will prevail over
     * the others.
     *
     * By default this function will append any passed argument to the list of fields
     * to be selected, unless the second argument is set to true.
     *
     * ### Examples:
     *
     * ```
     * $query->order(['title' => 'DESC', 'author_id' => 'ASC']);
     * ```
     *
     * Produces:
     *
     * `ORDER BY title DESC, author_id ASC`
     *
     * ```
     * $query
     *     ->order(['title' => $query->newExpr('DESC NULLS FIRST')])
     *     ->order('author_id');
     * ```
     *
     * Will generate:
     *
     * `ORDER BY title DESC NULLS FIRST, author_id`
     *
     * ```
     * $expression = $query->newExpr()->add(['id % 2 = 0']);
     * $query->order($expression)->order(['title' => 'ASC']);
     * ```
     *
     * and
     *
     * ```
     * $query->order(function ($exp, $query) {
     *     return [$exp->add(['id % 2 = 0']), 'title' => 'ASC'];
     * });
     * ```
     *
     * Will both become:
     *
     * `ORDER BY (id %2 = 0), title ASC`
     *
     * Order fields/directions are not sanitized by the query builder.
     * You should use an allowed list of fields/directions when passing
     * in user-supplied data to `order()`.
     *
     * If you need to set complex expressions as order conditions, you
     * should use `orderAsc()` or `orderDesc()`.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string $fields fields to be added to the list
     * @param bool $overwrite whether to reset order with field list or not
     * @return $this
     */
    public function order(ExpressionInterface|Closure|array|string $fields, bool $overwrite = false)
    {
        $this->dbQuery()->order($fields, $overwrite);
        $this->_dirty();

        return $this;
    }

    /**
     * Hints this object to associate the correct types when casting conditions
     * for the database. This is done by extracting the field types from the schema
     * associated to the passed table object. This prevents the user from repeating
     * themselves when specifying conditions.
     *
     * This method returns the same query object for chaining.
     *
     * @param \Cake\ORM\Table $table The table to pull types from
     * @return $this
     */
    public function addDefaultTypes(Table $table)
    {
        $alias = $table->getAlias();
        $map = $table->getSchema()->typeMap();
        $fields = [];
        foreach ($map as $f => $type) {
            $fields[$f] = $fields[$alias . '.' . $f] = $fields[$alias . '__' . $f] = $type;
        }
        $this->getTypeMap()->addDefaults($fields);

        return $this;
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
     *   This typemap is indirectly mutated via {@link \Cake\ORM\Query::addDefaultTypes()}
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
            if (empty($primary) || $typeMap->type($target->aliasField($primary[0])) === null) {
                $this->addDefaultTypes($target);
            }
            if (!empty($nested)) {
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
     *     ->group(['Users.id'])
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
     *     ->group(['Users.id'])
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
     *    ->group(['Users.id']);
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
                'joinType' => DatabaseQuery::JOIN_TYPE_LEFT,
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
     * @see \Cake\ORM\Query::matching()
     */
    public function innerJoinWith(string $assoc, ?Closure $builder = null)
    {
        $result = $this->getEagerLoader()
            ->setMatching($assoc, $builder, [
                'joinType' => DatabaseQuery::JOIN_TYPE_INNER,
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
                'joinType' => DatabaseQuery::JOIN_TYPE_LEFT,
                'fields' => false,
                'negateMatch' => true,
            ])
            ->getMatching();
        $this->_addAssociationsToTypeMap($this->getRepository(), $this->getTypeMap(), $result);
        $this->_dirty();

        return $this;
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
        $clone->order([], true);
        $clone->offset(null);
        $clone->mapReduce(null, null, true);
        $clone->formatResults(null, self::OVERWRITE);
        if (isset($clone->dbQuery) && $clone->dbQuery instanceof SelectQuery) {
            $clone->setSelectTypeMap(new TypeMap());
            $clone->decorateResults(null, true);
        }

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
        if (isset($this->dbQuery)) {
            $this->dbQuery = clone $this->dbQuery;
        }

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

        if (!$complex && $this->getValueBinder()->bindings()) {
            /** @var \Cake\Database\Expression\QueryExpression|null $order */
            $order = $this->clause('order');
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
            $statement = $this->getConnection()->newSelectQuery($count, ['count_source' => $query])
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
     * {@inheritDoc}
     *
     * @param \Closure|string|false $key Either the cache key or a function to generate the cache key.
     *   When using a function, this query instance will be supplied as an argument.
     * @param \Cake\Cache\CacheEngine|string $config Either the name of the cache config to use, or
     *   a cache config instance.
     * @return $this
     * @throws \Cake\Database\Exception\DatabaseException When you attempt to cache a non-select query.
     */
    public function cache($key, $config = 'default')
    {
        if ($this->dbQuery()->type() !== DatabaseQuery::TYPE_SELECT) {
            throw new DatabaseException('You cannot cache the results of non-select queries.');
        }

        return $this->_cache($key, $config);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Datasource\ResultSetInterface
     * @throws \Cake\Database\Exception\DatabaseException if this method is called on a non-select Query.
     */
    public function all(): ResultSetInterface
    {
        if ($this->dbQuery()->type() !== DatabaseQuery::TYPE_SELECT) {
            throw new DatabaseException(
                'You cannot call all() on a non-select query. Use execute() instead.'
            );
        }

        return $this->_all();
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
        if (!$this->_beforeFindFired && $this->dbQuery()->type() === DatabaseQuery::TYPE_SELECT) {
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
        if ($this->_dirty) {
            $this->triggerBeforeFind();
            $this->_transformQuery();
        }

        return $this->dbQuery()->sql($binder);
    }

    /**
     * Compiles the SQL representation of this query and executes it using the
     * configured connection object. Returns the resulting statement object.
     *
     * Executing a query internally executes several steps, the first one is
     * letting the connection transform this object to fit its particular dialect,
     * this might result in generating a different Query object that will be the one
     * to actually be executed. Immediately after, literal values are passed to the
     * connection so they are bound to the query in a safe way. Finally, the resulting
     * statement is decorated with custom objects to execute callbacks for each row
     * retrieved if necessary.
     *
     * Resulting statement is traversable, so it can be used in any loop as you would
     * with an array.
     *
     * This method can be overridden in query subclasses to decorate behavior
     * around query execution.
     *
     * @return \Cake\Database\StatementInterface
     */
    public function execute(): StatementInterface
    {
        $this->_transformQuery();
        $this->_dirty = false;

        return $this->dbQuery()->execute();
    }

    /**
     * Executes this query and returns an iterable containing the results.
     *
     * @return iterable
     */
    protected function executeSelect(): iterable
    {
        if ($this->_results) {
            return $this->_results;
        }

        $this->triggerBeforeFind();
        if ($this->_results) {
            return $this->_results;
        }

        $this->_transformQuery();

        $results = $this->dbQuery()->all();
        if (!is_array($results)) {
            $results = iterator_to_array($results);
        }
        $results = $this->getEagerLoader()->loadExternal($this, $results);

        $this->_dirty = false;

        return $this->resultSetFactory()->createResultSet($this, $results);
    }

    /**
     * Get resultset factory.
     *
     * @return \Cake\ORM\ResultSetFactory
     */
    protected function resultSetFactory(): ResultSetFactory
    {
        if (isset($this->resultSetFactory)) {
            return $this->resultSetFactory;
        }

        return $this->resultSetFactory = new ResultSetFactory();
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
        if (!$this->_dirty || $this->dbQuery()->type() !== DatabaseQuery::TYPE_SELECT) {
            return;
        }

        $repository = $this->getRepository();

        if (!$this->dbQuery()->clause('from')) {
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
     * @param array<string, mixed> $options The options for the finder.
     * @return static Returns a modified query.
     * @psalm-suppress MoreSpecificReturnType
     */
    public function find(string $finder, array $options = []): static
    {
        $table = $this->getRepository();

        /** @psalm-suppress LessSpecificReturnStatement */
        return $table->callFinder($finder, $this, $options);
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
        $this->_dirty = true;
    }

    /**
     * Create an update query.
     *
     * This changes the query type to be 'update'.
     * Can be combined with set() and where() methods to create update queries.
     *
     * @return $this
     */
    public function update()
    {
        $repository = $this->getRepository();

        $this->dbQuery(DatabaseQuery::TYPE_UPDATE)->update($repository->getTable());
        $this->_dirty();

        return $this;
    }

    /**
     * Create a delete query.
     *
     * This changes the query type to be 'delete'.
     * Can be combined with the where() method to create delete queries.
     *
     * @return $this
     */
    public function delete()
    {
        $repository = $this->getRepository();
        $table = [$repository->getAlias() => $repository->getTable()];

        $this->dbQuery(DatabaseQuery::TYPE_DELETE)
            ->from($table);
        $this->_dirty();

        return $this;
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
     * @param array<string, string> $types A map between columns & their datatypes.
     * @return $this
     */
    public function insert(array $columns, array $types = [])
    {
        $repository = $this->getRepository();
        $table = $repository->getTable();
        $this->into($table);

        $this->dbQuery(DatabaseQuery::TYPE_INSERT)->insert($columns, $types);
        $this->_dirty();

        return $this;
    }

    /**
     * Returns a new Query that has automatic field aliasing disabled.
     *
     * @param \Cake\ORM\Table $table The table this query is starting on
     * @return static
     */
    public static function subquery(Table $table): static
    {
        $query = new static($table->getConnection(), $table);
        $query->aliasingEnabled = false;

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function __debugInfo(): array
    {
        $eagerLoader = $this->getEagerLoader();

        return $this->dbQuery()->__debugInfo() + [
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
     * @return \Cake\Datasource\ResultSetInterface The data to convert to JSON.
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

    /**
     * Will iterate over every specified part. Traversing functions can aggregate
     * results using variables in the closure or instance variables. This function
     * is commonly used as a way for traversing all query parts that
     * are going to be used for constructing a query.
     *
     * The callback will receive 2 parameters, the first one is the value of the query
     * part that is being iterated and the second the name of such part.
     *
     * ### Example
     * ```
     * $query->select(['title'])->from('articles')->traverse(function ($value, $clause) {
     *     if ($clause === 'select') {
     *         var_dump($value);
     *     }
     * });
     * ```
     *
     * @param \Closure $callback Callback to be executed for each part
     * @return $this
     */
    public function traverse(Closure $callback)
    {
        $this->dbQuery()->traverse($callback);

        return $this;
    }

    /**
     * Magic method for proxing calls to internal database query instance.
     *
     * @param string $name Method name.
     * @param array $arguments Arguments.
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        $type = match ($name) {
            'update', 'set' => DatabaseQuery::TYPE_UPDATE,
            'insert', 'into', 'values' => DatabaseQuery::TYPE_INSERT,
            'delete' => DatabaseQuery::TYPE_DELETE,
            default => null,
        };

        if (
            in_array($name, ['andWhere', 'having', 'andHaving', 'order'])
            && isset($arguments[0])
            && $arguments[0] instanceof Closure
        ) {
            $arguments[0] = $arguments[0]($this->newExpr(), $this);
            $this->_dirty();
        }

        $return = call_user_func_array([$this->dbQuery($type), $name], $arguments);

        if ($return instanceof DatabaseQuery) {
            return $this;
        }

        return $return;
    }
}
