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
namespace Cake\Database;

use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\OrderClauseExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\ValuesExpression;
use Cake\Database\Statement\CallbackStatement;
use IteratorAggregate;
use RuntimeException;

/**
 * This class represents a Relational database SQL Query. A query can be of
 * different types like select, update, insert and delete. Exposes the methods
 * for dynamically constructing each query part, execute it and transform it
 * to a specific SQL dialect.
 */
class Query implements ExpressionInterface, IteratorAggregate
{

    use TypeMapTrait;

    /**
     * Connection instance to be used to execute this query.
     *
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $_connection;

    /**
     * Type of this query (select, insert, update, delete).
     *
     * @var string
     */
    protected $_type = 'select';

    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array
     */
    protected $_parts = [
        'delete' => true,
        'update' => [],
        'set' => [],
        'insert' => [],
        'values' => [],
        'select' => [],
        'distinct' => false,
        'modifier' => [],
        'from' => [],
        'join' => [],
        'where' => null,
        'group' => [],
        'having' => null,
        'order' => null,
        'limit' => null,
        'offset' => null,
        'union' => [],
        'epilog' => null
    ];

    /**
     * Indicates whether internal state of this query was changed, this is used to
     * discard internal cached objects such as the transformed query or the reference
     * to the executed statement.
     *
     * @var bool
     */
    protected $_dirty = false;

    /**
     * A list of callback functions to be called to alter each row from resulting
     * statement upon retrieval. Each one of the callback function will receive
     * the row array as first argument.
     *
     * @var array
     */
    protected $_resultDecorators = [];

    /**
     * Statement object resulting from executing this query.
     *
     * @var \Cake\Database\StatementInterface
     */
    protected $_iterator;

    /**
     * The object responsible for generating query placeholders and temporarily store values
     * associated to each of those.
     *
     * @var ValueBinder
     */
    protected $_valueBinder;

    /**
     * Instance of functions builder object used for generating arbitrary SQL functions.
     *
     * @var FunctionsBuilder
     */
    protected $_functionsBuilder;

    /**
     * Boolean for tracking whether or not buffered results
     * are enabled.
     *
     * @var bool
     */
    protected $_useBufferedResults = true;

    /**
     * Constructor.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection The connection
     * object to be used for transforming and executing this query
     */
    public function __construct($connection)
    {
        $this->connection($connection);
    }

    /**
     * Sets the connection instance to be used for executing and transforming this query
     * When called with a null argument, it will return the current connection instance.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection instance
     * @return $this|\Cake\Datasource\ConnectionInterface
     */
    public function connection($connection = null)
    {
        if ($connection === null) {
            return $this->_connection;
        }
        $this->_dirty();
        $this->_connection = $connection;
        return $this;
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
    public function execute()
    {
        $statement = $this->_connection->run($this);
        $this->_iterator = $this->_decorateStatement($statement);
        $this->_dirty = false;
        return $this->_iterator;
    }

    /**
     * Returns the SQL representation of this object.
     *
     * This function will compile this query to make it compatible
     * with the SQL dialect that is used by the connection, This process might
     * add, remove or alter any query part or internal expression to make it
     * executable in the target platform.
     *
     * The resulting query may have placeholders that will be replaced with the actual
     * values when the query is executed, hence it is most suitable to use with
     * prepared statements.
     *
     * @param ValueBinder $generator A placeholder object that will hold
     * associated values for expressions
     * @return string
     */
    public function sql(ValueBinder $generator = null)
    {
        if (!$generator) {
            $generator = $this->valueBinder();
            $generator->resetCount();
        }

        return $this->connection()->compileQuery($this, $generator);
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
     * ### Example:
     * ```
     *  $query->select(['title'])->from('articles')->traverse(function ($value, $clause) {
     *      if ($clause === 'select') {
     *          var_dump($value);
     *      }
     *  }, ['select', 'from']);
     * ```
     *
     * @param callable $visitor a function or callable to be executed for each part
     * @param array $parts the query clauses to traverse
     * @return $this
     */
    public function traverse(callable $visitor, array $parts = [])
    {
        $parts = $parts ?: array_keys($this->_parts);
        foreach ($parts as $name) {
            $visitor($this->_parts[$name], $name);
        }
        return $this;
    }

    /**
     * Adds new fields to be returned by a SELECT statement when this query is
     * executed. Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias fields using the value as the
     * real field to be aliased. It is possible to alias strings, Expression objects or
     * even other Query objects.
     *
     * If a callable function is passed, the returning array of the function will
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
     * fields you should also call `Cake\ORM\Query::autoFields()` to select the default fields
     * from the table.
     *
     * @param array|ExpressionInterface|string|callable $fields fields to be added to the list.
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     */
    public function select($fields = [], $overwrite = false)
    {
        if (!is_string($fields) && is_callable($fields)) {
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
        $this->_type = 'select';
        return $this;
    }

    /**
     * Adds a DISTINCT clause to the query to remove duplicates from the result set.
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
     * @param array|ExpressionInterface|string|bool $on Enable/disable distinct class
     * or list of fields to be filtered on
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     */
    public function distinct($on = [], $overwrite = false)
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
            $on = ($overwrite) ? array_values($on) : array_merge($merge, array_values($on));
        }

        $this->_parts['distinct'] = $on;
        $this->_dirty();
        return $this;
    }

    /**
     * Adds a single or multiple SELECT modifiers to be used in the SELECT.
     *
     * By default this function will append any passed argument to the list of modifiers
     * to be applied, unless the second argument is set to true.
     *
     * ### Example:
     *
     * ```
     * // Ignore cache query in MySQL
     * $query->select(['name', 'city'])->from('products')->modifier('SQL_NO_CACHE');
     * // It will produce the SQL: SELECT SQL_NO_CACHE name, city FROM products
     *
     * // Or with multiple modifiers
     * $query->select(['name', 'city'])->from('products')->modifier(['HIGH_PRIORITY', 'SQL_NO_CACHE']);
     * // It will produce the SQL: SELECT HIGH_PRIORITY SQL_NO_CACHE name, city FROM products
     * ```
     *
     * @param array|ExpressionInterface|string $modifiers modifiers to be applied to the query
     * @param bool $overwrite whether to reset order with field list or not
     * @return $this
     */
    public function modifier($modifiers, $overwrite = false)
    {
        $this->_dirty();
        if ($overwrite) {
            $this->_parts['modifier'] = [];
        }
        $this->_parts['modifier'] = array_merge($this->_parts['modifier'], (array)$modifiers);
        return $this;
    }

    /**
     * Adds a single or multiple tables to be used in the FROM clause for this query.
     * Tables can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias tables using the value as the
     * real field to be aliased. It is possible to alias strings, ExpressionInterface objects or
     * even other Query objects.
     *
     * By default this function will append any passed argument to the list of tables
     * to be selected from, unless the second argument is set to true.
     *
     * This method can be used for select, update and delete statements.
     *
     * ### Examples:
     *
     * ```
     *  $query->from(['p' => 'posts']); // Produces FROM posts p
     *  $query->from('authors'); // Appends authors: FROM posts p, authors
     *  $query->select(['products'], true); // Resets the list: FROM products
     *  $query->select(['sub' => $countQuery]); // FROM (SELECT ...) sub
     * ```
     *
     * @param array|ExpressionInterface|string $tables tables to be added to the list
     * @param bool $overwrite whether to reset tables with passed list or not
     * @return $this
     */
    public function from($tables = [], $overwrite = false)
    {
        if (empty($tables)) {
            return $this->_parts['from'];
        }

        if (is_string($tables)) {
            $tables = [$tables];
        }

        if ($overwrite) {
            $this->_parts['from'] = $tables;
        } else {
            $this->_parts['from'] = array_merge($this->_parts['from'], $tables);
        }

        $this->_dirty();
        return $this;
    }

    /**
     * Adds a single or multiple tables to be used as JOIN clauses to this query.
     * Tables can be passed as an array of strings, an array describing the
     * join parts, an array with multiple join descriptions, or a single string.
     *
     * By default this function will append any passed argument to the list of tables
     * to be joined, unless the third argument is set to true.
     *
     * When no join type is specified an INNER JOIN is used by default:
     * ``$query->join(['authors'])`` Will produce ``INNER JOIN authors ON 1 = 1``
     *
     * It is also possible to alias joins using the array key:
     * ``$query->join(['a' => 'authors'])`` Will produce ``INNER JOIN authors a ON 1 = 1``
     *
     * A join can be fully described and aliased using the array notation:
     *
     * ```
     *  $query->join([
     *      'a' => [
     *          'table' => 'authors',
     *          'type' => 'LEFT',
     *          'conditions' => 'a.id = b.author_id'
     *      ]
     *  ]);
     *  // Produces LEFT JOIN authors a ON a.id = b.author_id
     * ```
     *
     * You can even specify multiple joins in an array, including the full description:
     *
     * ```
     *  $query->join([
     *      'a' => [
     *          'table' => 'authors',
     *          'type' => 'LEFT',
     *          'conditions' => 'a.id = b.author_id'
     *      ],
     *      'p' => [
     *          'table' => 'publishers',
     *          'type' => 'INNER',
     *          'conditions' => 'p.id = b.publisher_id AND p.name = "Cake Software Foundation"'
     *      ]
     *  ]);
     *  // LEFT JOIN authors a ON a.id = b.author_id
     *  // INNER JOIN publishers p ON p.id = b.publisher_id AND p.name = "Cake Software Foundation"
     * ```
     *
     * ### Using conditions and types
     *
     * Conditions can be expressed, as in the examples above, using a string for comparing
     * columns, or string with already quoted literal values. Additionally it is
     * possible to use conditions expressed in arrays or expression objects.
     *
     * When using arrays for expressing conditions, it is often desirable to convert
     * the literal values to the correct database representation. This is achieved
     * using the second parameter of this function.
     *
     * ```
     *  $query->join(['a' => [
     *      'table' => 'articles',
     *      'conditions' => [
     *          'a.posted >=' => new DateTime('-3 days'),
     *          'a.published' => true,
     *          'a.author_id = authors.id'
     *      ]
     *  ]], ['a.posted' => 'datetime', 'a.published' => 'boolean'])
     * ```
     *
     * ### Overwriting joins
     *
     * When creating aliased joins using the array notation, you can override
     * previous join definitions by using the same alias in consequent
     * calls to this function or you can replace all previously defined joins
     * with another list if the third parameter for this function is set to true.
     *
     * ```
     *  $query->join(['alias' => 'table']); // joins table with as alias
     *  $query->join(['alias' => 'another_table']); // joins another_table with as alias
     *  $query->join(['something' => 'different_table'], [], true); // resets joins list
     * ```
     *
     * @param array|string|null $tables list of tables to be joined in the query
     * @param array $types associative array of type names used to bind values to query
     * @param bool $overwrite whether to reset joins with passed list or not
     * @see \Cake\Database\Type
     * @return $this
     */
    public function join($tables = null, $types = [], $overwrite = false)
    {
        if ($tables === null) {
            return $this->_parts['join'];
        }

        if (is_string($tables) || isset($tables['table'])) {
            $tables = [$tables];
        }

        $joins = [];
        $i = count($this->_parts['join']);
        foreach ($tables as $alias => $t) {
            if (!is_array($t)) {
                $t = ['table' => $t, 'conditions' => $this->newExpr()];
            }

            if (!is_string($t['conditions']) && is_callable($t['conditions'])) {
                $t['conditions'] = $t['conditions']($this->newExpr(), $this);
            }

            if (!($t['conditions'] instanceof ExpressionInterface)) {
                $t['conditions'] = $this->newExpr()->add($t['conditions'], $types);
            }
            $alias = is_string($alias) ? $alias : null;
            $joins[$alias ?: $i++] = $t + ['type' => 'INNER', 'alias' => $alias];
        }

        if ($overwrite) {
            $this->_parts['join'] = $joins;
        } else {
            $this->_parts['join'] = array_merge($this->_parts['join'], $joins);
        }

        $this->_dirty();
        return $this;
    }

    /**
     * Remove a join if it has been defined.
     *
     * Useful when you are redefining joins or want to re-order
     * the join clauses.
     *
     * @param string $name The alias/name of the join to remove.
     * @return $this
     */
    public function removeJoin($name)
    {
        unset($this->_parts['join'][$name]);
        $this->_dirty();
        return $this;
    }

    /**
     * Adds a single LEFT JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The table name can be passed as a string, or as an array in case it needs to
     * be aliased:
     *
     * ```
     * // LEFT JOIN authors ON authors.id = posts.author_id
     * $query->leftJoin('authors', 'authors.id = posts.author_id');
     *
     * // LEFT JOIN authors a ON a.id = posts.author_id
     * $query->leftJoin(['a' => 'authors'], 'a.id = posts.author_id');
     * ```
     *
     * Conditions can be passed as strings, arrays, or expression objects. When
     * using arrays it is possible to combine them with the `$types` parameter
     * in order to define how to convert the values:
     *
     * ```
     * $query->leftJoin(['a' => 'articles'], [
     *      'a.posted >=' => new DateTime('-3 days'),
     *      'a.published' => true,
     *      'a.author_id = authors.id'
     * ], ['a.posted' => 'datetime', 'a.published' => 'boolean']);
     * ```
     *
     * See `join()` for further details on conditions and types.
     *
     * @param string|array $table The table to join with
     * @param string|array|\Cake\Database\ExpressionInterface $conditions The conditions
     * to use for joining.
     * @param array $types a list of types associated to the conditions used for converting
     * values to the corresponding database representation.
     * @return $this
     */
    public function leftJoin($table, $conditions = [], $types = [])
    {
        return $this->join($this->_makeJoin($table, $conditions, 'LEFT'), $types);
    }

    /**
     * Adds a single RIGHT JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The arguments of this method are identical to the `leftJoin()` shorthand, please refer
     * to that methods description for further details.
     *
     * @param string|array $table The table to join with
     * @param string|array|\Cake\Database\ExpressionInterface $conditions The conditions
     * to use for joining.
     * @param array $types a list of types associated to the conditions used for converting
     * values to the corresponding database representation.
     * @return $this
     */
    public function rightJoin($table, $conditions = [], $types = [])
    {
        return $this->join($this->_makeJoin($table, $conditions, 'RIGHT'), $types);
    }

    /**
     * Adds a single INNER JOIN clause to the query.
     *
     * This is a shorthand method for building joins via `join()`.
     *
     * The arguments of this method are identical to the `leftJoin()` shorthand, please refer
     * to that methods description for further details.
     *
     * @param string|array $table The table to join with
     * @param string|array|\Cake\Database\ExpressionInterface $conditions The conditions
     * to use for joining.
     * @param array $types a list of types associated to the conditions used for converting
     * values to the corresponding database representation.
     * @return $this
     */
    public function innerJoin($table, $conditions = [], $types = [])
    {
        return $this->join($this->_makeJoin($table, $conditions, 'INNER'), $types);
    }

    /**
     * Returns an array that can be passed to the join method describing a single join clause
     *
     * @param string|array $table The table to join with
     * @param string|array|\Cake\Database\ExpressionInterface $conditions The conditions
     * to use for joining.
     * @param string $type the join type to use
     * @return array
     */
    protected function _makeJoin($table, $conditions, $type)
    {
        $alias = $table;

        if (is_array($table)) {
            $alias = key($table);
            $table = current($table);
        }

        return [
            $alias => [
                'table' => $table,
                'conditions' => $conditions,
                'type' => $type
            ]
        ];
    }

    /**
     * Adds a condition or set of conditions to be used in the WHERE clause for this
     * query. Conditions can be expressed as an array of fields as keys with
     * comparison operators in it, the values for the array will be used for comparing
     * the field to such literal. Finally, conditions can be expressed as a single
     * string or an array of strings.
     *
     * When using arrays, each entry will be joined to the rest of the conditions using
     * an AND operator. Consecutive calls to this function will also join the new
     * conditions specified using the AND operator. Additionally, values can be
     * expressed using expression objects which can include other query objects.
     *
     * Any conditions created with this methods can be used with any SELECT, UPDATE
     * and DELETE type of queries.
     *
     * ### Conditions using operators:
     *
     * ```
     *  $query->where([
     *      'posted >=' => new DateTime('3 days ago'),
     *      'title LIKE' => 'Hello W%',
     *      'author_id' => 1,
     *  ], ['posted' => 'datetime']);
     * ```
     *
     * The previous example produces:
     *
     * ``WHERE posted >= 2012-01-27 AND title LIKE 'Hello W%' AND author_id = 1``
     *
     * Second parameter is used to specify what type is expected for each passed
     * key. Valid types can be used from the mapped with Database\Type class.
     *
     * ### Nesting conditions with conjunctions:
     *
     * ```
     *  $query->where([
     *      'author_id !=' => 1,
     *      'OR' => ['published' => true, 'posted <' => new DateTime('now')],
     *      'NOT' => ['title' => 'Hello']
     *  ], ['published' => boolean, 'posted' => 'datetime']
     * ```
     *
     * The previous example produces:
     *
     * ``WHERE author_id = 1 AND (published = 1 OR posted < '2012-02-01') AND NOT (title = 'Hello')``
     *
     * You can nest conditions using conjunctions as much as you like. Sometimes, you
     * may want to define 2 different options for the same key, in that case, you can
     * wrap each condition inside a new array:
     *
     * ``$query->where(['OR' => [['published' => false], ['published' => true]])``
     *
     * Keep in mind that every time you call where() with the third param set to false
     * (default), it will join the passed conditions to the previous stored list using
     * the AND operator. Also, using the same array key twice in consecutive calls to
     * this method will not override the previous value.
     *
     * ### Using expressions objects:
     *
     * ```
     *  $exp = $query->newExpr()->add(['id !=' => 100, 'author_id' != 1])->type('OR');
     *  $query->where(['published' => true], ['published' => 'boolean'])->where($exp);
     * ```
     *
     * The previous example produces:
     *
     * ``WHERE (id != 100 OR author_id != 1) AND published = 1``
     *
     * Other Query objects that be used as conditions for any field.
     *
     * ### Adding conditions in multiple steps:
     *
     * You can use callable functions to construct complex expressions, functions
     * receive as first argument a new QueryExpression object and this query instance
     * as second argument. Functions must return an expression object, that will be
     * added the list of conditions for the query using the AND operator.
     *
     * ```
     *  $query
     *  ->where(['title !=' => 'Hello World'])
     *  ->where(function ($exp, $query) {
     *      $or = $exp->or_(['id' => 1]);
     *      $and = $exp->and_(['id >' => 2, 'id <' => 10]);
     *  return $or->add($and);
     *  });
     * ```
     *
     * * The previous example produces:
     *
     * ``WHERE title != 'Hello World' AND (id = 1 OR (id > 2 AND id < 10))``
     *
     * ### Conditions as strings:
     *
     * ```
     *  $query->where(['articles.author_id = authors.id', 'modified IS NULL']);
     * ```
     *
     * The previous example produces:
     *
     * ``WHERE articles.author_id = authors.id AND modified IS NULL``
     *
     * Please note that when using the array notation or the expression objects, all
     * values will be correctly quoted and transformed to the correspondent database
     * data type automatically for you, thus securing your application from SQL injections.
     * If you use string conditions make sure that your values are correctly quoted.
     * The safest thing you can do is to never use string conditions.
     *
     * @param string|array|\Cake\Database\ExpressionInterface|callback|null $conditions The conditions to filter on.
     * @param array $types associative array of type names used to bind values to query
     * @param bool $overwrite whether to reset conditions with passed list or not
     * @see \Cake\Database\Type
     * @see \Cake\Database\Expression\QueryExpression
     * @return $this
     */
    public function where($conditions = null, $types = [], $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['where'] = $this->newExpr();
        }
        $this->_conjugate('where', $conditions, 'AND', $types);
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
     * ``WHERE title = 'Hello World' AND author_id = 1``
     *
     * ```
     * $query
     *   ->where(['OR' => ['published' => false, 'published is NULL']])
     *   ->andWhere(['author_id' => 1, 'comments_count >' => 10])
     * ```
     *
     * Produces:
     *
     * ``WHERE (published = 0 OR published IS NULL) AND author_id = 1 AND comments_count > 10``
     *
     * ```
     * $query
     *   ->where(['title' => 'Foo'])
     *   ->andWhere(function ($exp, $query) {
     *     return $exp
     *       ->add(['author_id' => 1])
     *       ->or_(['author_id' => 2]);
     *   });
     * ```
     *
     * Generates the following conditions:
     *
     * ``WHERE (title = 'Foo') AND (author_id = 1 OR author_id = 2)``
     *
     * @param string|array|ExpressionInterface|callback $conditions The conditions to add with AND.
     * @param array $types associative array of type names used to bind values to query
     * @see \Cake\Database\Query::where()
     * @see \Cake\Database\Type
     * @return $this
     */
    public function andWhere($conditions, $types = [])
    {
        $this->_conjugate('where', $conditions, 'AND', $types);
        return $this;
    }

    /**
     * Connects any previously defined set of conditions to the provided list
     * using the OR operator. This function accepts the conditions list in the same
     * format as the method `where` does, hence you can use arrays, expression objects
     * callback functions or strings.
     *
     * It is important to notice that when calling this function, any previous set
     * of conditions defined for this query will be treated as a single argument for
     * the OR operator. This function will not only operate the most recently defined
     * condition, but all the conditions as a whole.
     *
     * When using an array for defining conditions, creating constraints form each
     * array entry will use the same logic as with the `where()` function. This means
     * that each array entry will be joined to the other using the OR operator, unless
     * you nest the conditions in the array using other operator.
     *
     * ### Examples:
     *
     * ```
     * $query->where(['title' => 'Hello World')->orWhere(['title' => 'Foo']);
     * ```
     *
     * Will produce:
     *
     * ``WHERE title = 'Hello World' OR title = 'Foo'``
     *
     * ```
     * $query
     *   ->where(['OR' => ['published' => false, 'published is NULL']])
     *   ->orWhere(['author_id' => 1, 'comments_count >' => 10])
     * ```
     *
     * Produces:
     *
     * ``WHERE (published = 0 OR published IS NULL) OR (author_id = 1 AND comments_count > 10)``
     *
     * ```
     * $query
     *   ->where(['title' => 'Foo'])
     *   ->orWhere(function ($exp, $query) {
     *     return $exp
     *       ->add(['author_id' => 1])
     *       ->or_(['author_id' => 2]);
     *   });
     * ```
     *
     * Generates the following conditions:
     *
     * ``WHERE (title = 'Foo') OR (author_id = 1 OR author_id = 2)``
     *
     * @param string|array|ExpressionInterface|callback $conditions The conditions to add with OR.
     * @param array $types associative array of type names used to bind values to query
     * @see \Cake\Database\Query::where()
     * @see \Cake\Database\Type
     * @return $this
     */
    public function orWhere($conditions, $types = [])
    {
        $this->_conjugate('where', $conditions, 'OR', $types);
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
     * ``ORDER BY title DESC, author_id ASC``
     *
     * ```
     * $query->order(['title' => 'DESC NULLS FIRST'])->order('author_id');
     * ```
     *
     * Will generate:
     *
     * ``ORDER BY title DESC NULLS FIRST, author_id``
     *
     * ```
     * $expression = $query->newExpr()->add(['id % 2 = 0']);
     * $query->order($expression)->order(['title' => 'ASC']);
     * ```
     *
     * Will become:
     *
     * ``ORDER BY (id %2 = 0), title ASC``
     *
     * If you need to set complex expressions as order conditions, you
     * should use `orderAsc()` or `orderDesc()`.
     *
     * @param array|\Cake\Database\ExpressionInterface|string $fields fields to be added to the list
     * @param bool $overwrite whether to reset order with field list or not
     * @return $this
     */
    public function order($fields, $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['order'] = null;
        }

        if (!$fields) {
            return $this;
        }

        if (!$this->_parts['order']) {
            $this->_parts['order'] = new OrderByExpression();
        }
        $this->_conjugate('order', $fields, '', []);
        return $this;
    }

    /**
     * Add an ORDER BY clause with an ASC direction.
     *
     * This method allows you to set complex expressions
     * as order conditions unlike order()
     *
     * @param string|\Cake\Database\Expression\QueryExpression $field The field to order on.
     * @param bool $overwrite Whether or not to reset the order clauses.
     * @return $this
     */
    public function orderAsc($field, $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['order'] = null;
        }
        if (!$field) {
            return $this;
        }

        if (!$this->_parts['order']) {
            $this->_parts['order'] = new OrderByExpression();
        }
        $this->_parts['order']->add(new OrderClauseExpression($field, 'ASC'));
        return $this;
    }

    /**
     * Add an ORDER BY clause with an ASC direction.
     *
     * This method allows you to set complex expressions
     * as order conditions unlike order()
     *
     * @param string|\Cake\Database\Expression\QueryExpression $field The field to order on.
     * @param bool $overwrite Whether or not to reset the order clauses.
     * @return $this
     */
    public function orderDesc($field, $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['order'] = null;
        }
        if (!$field) {
            return $this;
        }

        if (!$this->_parts['order']) {
            $this->_parts['order'] = new OrderByExpression();
        }
        $this->_parts['order']->add(new OrderClauseExpression($field, 'DESC'));
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
     * $query->group(['id', 'title']);
     *
     * // Produces GROUP BY title
     * $query->group('title');
     * ```
     *
     * @param array|ExpressionInterface|string $fields fields to be added to the list
     * @param bool $overwrite whether to reset fields with passed list or not
     * @return $this
     */
    public function group($fields, $overwrite = false)
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
     * Adds a condition or set of conditions to be used in the HAVING clause for this
     * query. This method operates in exactly the same way as the method `where()`
     * does. Please refer to its documentation for an insight on how to using each
     * parameter.
     *
     * @param string|array|ExpressionInterface|callback $conditions The having conditions.
     * @param array $types associative array of type names used to bind values to query
     * @param bool $overwrite whether to reset conditions with passed list or not
     * @see \Cake\Database\Query::where()
     * @return $this
     */
    public function having($conditions = null, $types = [], $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['having'] = $this->newExpr();
        }
        $this->_conjugate('having', $conditions, 'AND', $types);
        return $this;
    }

    /**
     * Connects any previously defined set of conditions to the provided list
     * using the AND operator in the HAVING clause. This method operates in exactly
     * the same way as the method ``andWhere()`` does. Please refer to its
     * documentation for an insight on how to using each parameter.
     *
     * @param string|array|ExpressionInterface|callback $conditions The AND conditions for HAVING.
     * @param array $types associative array of type names used to bind values to query
     * @see \Cake\Database\Query::andWhere()
     * @return $this
     */
    public function andHaving($conditions, $types = [])
    {
        $this->_conjugate('having', $conditions, 'AND', $types);
        return $this;
    }

    /**
     * Connects any previously defined set of conditions to the provided list
     * using the OR operator in the HAVING clause. This method operates in exactly
     * the same way as the method ``orWhere()`` does. Please refer to its
     * documentation for an insight on how to using each parameter.
     *
     * @param string|array|ExpressionInterface|callback $conditions The OR conditions for HAVING.
     * @param array $types associative array of type names used to bind values to query.
     * @see \Cake\Database\Query::orWhere()
     * @return $this
     */
    public function orHaving($conditions, $types = [])
    {
        $this->_conjugate('having', $conditions, 'OR', $types);
        return $this;
    }

    /**
     * Set the page of results you want.
     *
     * This method provides an easier to use interface to set the limit + offset
     * in the record set you want as results. If empty the limit will default to
     * the existing limit clause, and if that too is empty, then `25` will be used.
     *
     * Pages should start at 1.
     *
     * @param int $num The page number you want.
     * @param int $limit The number of rows you want in the page. If null
     *  the current limit clause will be used.
     * @return $this
     */
    public function page($num, $limit = null)
    {
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
     * @param int|ExpressionInterface $num number of records to be returned
     * @return $this
     */
    public function limit($num)
    {
        $this->_dirty();
        if ($num !== null && !is_object($num)) {
            $num = (int)$num;
        }
        $this->_parts['limit'] = $num;
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
     *  $query->offset(10) // generates OFFSET 10
     *  $query->offset($query->newExpr()->add(['1 + 1'])); // OFFSET (1 + 1)
     * ```
     *
     * @param int|ExpressionInterface $num number of records to be skipped
     * @return $this
     */
    public function offset($num)
    {
        $this->_dirty();
        if ($num !== null && !is_object($num)) {
            $num = (int)$num;
        }
        $this->_parts['offset'] = $num;
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
     *  $union = (new Query($conn))->select(['id', 'title'])->from(['a' => 'articles']);
     *  $query->select(['id', 'name'])->from(['d' => 'things'])->union($union);
     * ```
     *
     * Will produce:
     *
     * ``SELECT id, name FROM things d UNION SELECT id, title FROM articles a``
     *
     * @param string|Query $query full SQL query to be used in UNION operator
     * @param bool $overwrite whether to reset the list of queries to be operated or not
     * @return $this
     */
    public function union($query, $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['union'] = [];
        }
        $this->_parts['union'][] = [
            'all' => false,
            'query' => $query
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
     * $union = (new Query($conn))->select(['id', 'title'])->from(['a' => 'articles']);
     * $query->select(['id', 'name'])->from(['d' => 'things'])->unionAll($union);
     * ```
     *
     * Will produce:
     *
     * ``SELECT id, name FROM things d UNION ALL SELECT id, title FROM articles a``
     *
     * @param string|Query $query full SQL query to be used in UNION operator
     * @param bool $overwrite whether to reset the list of queries to be operated or not
     * @return $this
     */
    public function unionAll($query, $overwrite = false)
    {
        if ($overwrite) {
            $this->_parts['union'] = [];
        }
        $this->_parts['union'][] = [
            'all' => true,
            'query' => $query
        ];
        $this->_dirty();
        return $this;
    }

    /**
     * Create an insert query.
     *
     * Note calling this method will reset any data previously set
     * with Query::values().
     *
     * @param array $columns The columns to insert into.
     * @param array $types A map between columns & their datatypes.
     * @return $this
     * @throws \RuntimeException When there are 0 columns.
     */
    public function insert(array $columns, array $types = [])
    {
        if (empty($columns)) {
            throw new RuntimeException('At least 1 column is required to perform an insert.');
        }
        $this->_dirty();
        $this->_type = 'insert';
        $this->_parts['insert'][1] = $columns;

        if (!$this->_parts['values']) {
            $this->_parts['values'] = new ValuesExpression($columns, $this->typeMap()->types($types));
        }

        return $this;
    }

    /**
     * Set the table name for insert queries.
     *
     * @param string $table The table name to insert into.
     * @return $this
     */
    public function into($table)
    {
        $this->_dirty();
        $this->_type = 'insert';
        $this->_parts['insert'][0] = $table;
        return $this;
    }

    /**
     * Set the values for an insert query.
     *
     * Multi inserts can be performed by calling values() more than one time,
     * or by providing an array of value sets. Additionally $data can be a Query
     * instance to insert data from another SELECT statement.
     *
     * @param array|Query $data The data to insert.
     * @return $this
     * @throws \Cake\Database\Exception if you try to set values before declaring columns.
     *   Or if you try to set values on non-insert queries.
     */
    public function values($data)
    {
        if ($this->_type !== 'insert') {
            throw new Exception(
                'You cannot add values before defining columns to use.'
            );
        }
        if (empty($this->_parts['insert'])) {
            throw new Exception(
                'You cannot add values before defining columns to use.'
            );
        }

        $this->_dirty();
        if ($data instanceof ValuesExpression) {
            $this->_parts['values'] = $data;
            return $this;
        }

        $this->_parts['values']->add($data);
        return $this;
    }

    /**
     * Create an update query.
     *
     * Can be combined with set() and where() methods to create update queries.
     *
     * @param string $table The table you want to update.
     * @return $this
     */
    public function update($table)
    {
        $this->_dirty();
        $this->_type = 'update';
        $this->_parts['update'][0] = $table;
        return $this;
    }

    /**
     * Set one or many fields to update.
     *
     * @param string|array|QueryExpression $key The column name or array of keys
     *    + values to set. This can also be a QueryExpression containing a SQL fragment.
     * @param mixed $value The value to update $key to. Can be null if $key is an
     *    array or QueryExpression. When $key is an array, this parameter will be
     *    used as $types instead.
     * @param array $types The column types to treat data as.
     * @return $this
     */
    public function set($key, $value = null, $types = [])
    {
        if (empty($this->_parts['set'])) {
            $this->_parts['set'] = $this->newExpr()->type(',');
        }

        if (is_array($key) || $key instanceof ExpressionInterface) {
            $types = (array)$value;
            $this->_parts['set']->add($key, $types);
            return $this;
        }

        if (is_string($types) && is_string($key)) {
            $types = [$key => $types];
        }
        $this->_parts['set']->eq($key, $value, $types);

        return $this;
    }

    /**
     * Create a delete query.
     *
     * Can be combined with from(), where() and other methods to
     * create delete queries with specific conditions.
     *
     * @param string $table The table to use when deleting.
     * @return $this
     */
    public function delete($table = null)
    {
        $this->_dirty();
        $this->_type = 'delete';
        if ($table) {
            $this->from($table);
        }
        return $this;
    }

    /**
     * A string or expression that will be appended to the generated query
     *
     * ### Examples:
     * ```
     * $query->select('id')->where(['author_id' => 1])->epilog('FOR UPDATE');
     * $query
     *  ->insert('articles', ['title'])
     *  ->values(['author_id' => 1])
     *  ->epilog('RETURNING id');
     * ```
     *
     * @param string|\Cake\Database\Expression\QueryExpression $expression The expression to be appended
     * @return $this
     */
    public function epilog($expression = null)
    {
        $this->_dirty();
        $this->_parts['epilog'] = $expression;
        return $this;
    }

    /**
     * Returns the type of this query (select, insert, update, delete)
     *
     * @return string
     */
    public function type()
    {
        return $this->_type;
    }

    /**
     * Returns a new QueryExpression object. This is a handy function when
     * building complex queries using a fluent interface. You can also override
     * this function in subclasses to use a more specialized QueryExpression class
     * if required.
     *
     * You can optionally pass a single raw SQL string or an array or expressions in
     * any format accepted by \Cake\Database\Expression\QueryExpression:
     *
     * ```
     *
     * $expression = $query->newExpr(); // Returns an empty expression object
     * $expression = $query->newExpr('Table.column = Table2.column'); // Return a raw SQL expression
     * ```
     *
     * @param mixed $rawExpression A string, array or anything you want wrapped in an expression object
     * @return \Cake\Database\Expression\QueryExpression
     */
    public function newExpr($rawExpression = null)
    {
        $expression = new QueryExpression([], $this->typeMap());

        if ($rawExpression !== null) {
            $expression->add($rawExpression);
        }

        return $expression;
    }

    /**
     * Returns an instance of a functions builder object that can be used for
     * generating arbitrary SQL functions.
     *
     * ### Example:
     *
     * ```
     * $query->func()->count('*');
     * $query->func()->dateDiff(['2012-01-05', '2012-01-02'])
     * ```
     *
     * @return \Cake\Database\FunctionsBuilder
     */
    public function func()
    {
        if (empty($this->_functionsBuilder)) {
            $this->_functionsBuilder = new FunctionsBuilder;
        }
        return $this->_functionsBuilder;
    }

    /**
     * Executes this query and returns a results iterator. This function is required
     * for implementing the IteratorAggregate interface and allows the query to be
     * iterated without having to call execute() manually, thus making it look like
     * a result set instead of the query itself.
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        if (empty($this->_iterator) || $this->_dirty) {
            $this->_iterator = $this->execute();
        }
        return $this->_iterator;
    }

    /**
     * Returns any data that was stored in the specified clause. This is useful for
     * modifying any internal part of the query and it is used by the SQL dialects
     * to transform the query accordingly before it is executed. The valid clauses that
     * can be retrieved are: delete, update, set, insert, values, select, distinct,
     * from, join, set, where, group, having, order, limit, offset and union.
     *
     * The return value for each of those parts may vary. Some clauses use QueryExpression
     * to internally store their state, some use arrays and others may use booleans or
     * integers. This is summary of the return types for each clause.
     *
     * - update: string The name of the table to update
     * - set: QueryExpression
     * - insert: array, will return an array containing the table + columns.
     * - values: ValuesExpression
     * - select: array, will return empty array when no fields are set
     * - distinct: boolean
     * - from: array of tables
     * - join: array
     * - set: array
     * - where: QueryExpression, returns null when not set
     * - group: array
     * - having: QueryExpression, returns null when not set
     * - order: OrderByExpression, returns null when not set
     * - limit: integer or QueryExpression, null when not set
     * - offset: integer or QueryExpression, null when not set
     * - union: array
     *
     * @param string $name name of the clause to be returned
     * @return mixed
     */
    public function clause($name)
    {
        return $this->_parts[$name];
    }

    /**
     * Registers a callback to be executed for each result that is fetched from the
     * result set, the callback function will receive as first parameter an array with
     * the raw data from the database for every row that is fetched and must return the
     * row with any possible modifications.
     *
     * Callbacks will be executed lazily, if only 3 rows are fetched for database it will
     * called 3 times, event though there might be more rows to be fetched in the cursor.
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
     * @param null|callable $callback The callback to invoke when results are fetched.
     * @param bool $overwrite Whether or not this should append or replace all existing decorators.
     * @return $this
     */
    public function decorateResults($callback, $overwrite = false)
    {
        if ($overwrite) {
            $this->_resultDecorators = [];
        }

        if ($callback !== null) {
            $this->_resultDecorators[] = $callback;
        }

        return $this;
    }

    /**
     * This function works similar to the traverse() function, with the difference
     * that it does a full depth traversal of the entire expression tree. This will execute
     * the provided callback function for each ExpressionInterface object that is
     * stored inside this query at any nesting depth in any part of the query.
     *
     * Callback will receive as first parameter the currently visited expression.
     *
     * @param callable $callback the function to be executed for each ExpressionInterface
     *   found inside this query.
     * @return $this|null
     */
    public function traverseExpressions(callable $callback)
    {
        $visitor = function ($expression) use (&$visitor, $callback) {
            if (is_array($expression)) {
                foreach ($expression as $e) {
                    $visitor($e);
                }
                return null;
            }

            if ($expression instanceof ExpressionInterface) {
                $expression->traverse($visitor);

                if (!($expression instanceof self)) {
                    $callback($expression);
                }
            }
        };
        return $this->traverse($visitor);
    }

    /**
     * Associates a query placeholder to a value and a type.
     *
     * If type is expressed as "atype[]" (note braces) then it will cause the
     * placeholder to be re-written dynamically so if the value is an array, it
     * will create as many placeholders as values are in it. For example "string[]"
     * will create several placeholders of type string.
     *
     * @param string|int $param placeholder to be replaced with quoted version
     *   of $value
     * @param mixed $value The value to be bound
     * @param string|int $type the mapped type name, used for casting when sending
     *   to database
     * @return $this
     */
    public function bind($param, $value, $type = 'string')
    {
        $this->valueBinder()->bind($param, $value, $type);
        return $this;
    }

    /**
     * Returns the currently used ValueBinder instance. If a value is passed,
     * it will be set as the new instance to be used.
     *
     * A ValueBinder is responsible for generating query placeholders and temporarily
     * associate values to those placeholders so that they can be passed correctly
     * statement object.
     *
     * @param \Cake\Database\ValueBinder $binder new instance to be set. If no value is passed the
     *   default one will be returned
     * @return $this|\Cake\Database\ValueBinder
     */
    public function valueBinder($binder = null)
    {
        if ($binder === null) {
            if ($this->_valueBinder === null) {
                $this->_valueBinder = new ValueBinder;
            }
            return $this->_valueBinder;
        }
        $this->_valueBinder = $binder;
        return $this;
    }

    /**
     * Enable/Disable buffered results.
     *
     * When enabled the results returned by this Query will be
     * buffered. This enables you to iterate a result set multiple times, or
     * both cache and iterate it.
     *
     * When disabled it will consume less memory as fetched results are not
     * remembered for future iterations.
     *
     * If called with no arguments, it will return whether or not buffering is
     * enabled.
     *
     * @param bool|null $enable whether or not to enable buffering
     * @return bool|$this
     */
    public function bufferResults($enable = null)
    {
        if ($enable === null) {
            return $this->_useBufferedResults;
        }

        $this->_dirty();
        $this->_useBufferedResults = (bool)$enable;
        return $this;
    }

    /**
     * Auxiliary function used to wrap the original statement from the driver with
     * any registered callbacks.
     *
     * @param \Cake\Database\StatementInterface $statement to be decorated
     * @return \Cake\Database\Statement\CallbackStatement
     */
    protected function _decorateStatement($statement)
    {
        foreach ($this->_resultDecorators as $f) {
            $statement = new CallbackStatement($statement, $this->connection()->driver(), $f);
        }
        return $statement;
    }

    /**
     * Helper function used to build conditions by composing QueryExpression objects.
     *
     * @param string $part Name of the query part to append the new part to
     * @param string|null|array|ExpressionInterface|callback $append Expression or builder function to append.
     * @param string $conjunction type of conjunction to be used to operate part
     * @param array $types associative array of type names used to bind values to query
     * @return void
     */
    protected function _conjugate($part, $append, $conjunction, $types)
    {
        $expression = $this->_parts[$part] ?: $this->newExpr();
        if (empty($append)) {
            $this->_parts[$part] = $expression;
            return;
        }

        if ($expression->isCallable($append)) {
            $append = $append($this->newExpr(), $this);
        }

        if ($expression->type() === $conjunction) {
            $expression->add($append, $types);
        } else {
            $expression = $this->newExpr()
                ->type($conjunction)
                ->add([$append, $expression], $types);
        }

        $this->_parts[$part] = $expression;
        $this->_dirty();
    }

    /**
     * Marks a query as dirty, removing any preprocessed information
     * from in memory caching.
     *
     * @return void
     */
    protected function _dirty()
    {
        $this->_dirty = true;
        $this->_transformedQuery = null;

        if ($this->_iterator && $this->_valueBinder) {
            $this->valueBinder()->reset();
        }
    }

    /**
     * Do a deep clone on this object.
     *
     * Will clone all of the expression objects used in
     * each of the clauses, as well as the valueBinder.
     *
     * @return void
     */
    public function __clone()
    {
        $this->_iterator = null;
        if ($this->_valueBinder) {
            $this->_valueBinder = clone $this->_valueBinder;
        }
        foreach ($this->_parts as $name => $part) {
            if (empty($part)) {
                continue;
            }
            if (is_array($part)) {
                foreach ($part as $i => $piece) {
                    if ($piece instanceof ExpressionInterface) {
                        $this->_parts[$name][$i] = clone $piece;
                    }
                }
            }
            if ($part instanceof ExpressionInterface) {
                $this->_parts[$name] = clone $part;
            }
        }
    }

    /**
     * Returns string representation of this query (complete SQL statement).
     *
     * @return string
     */
    public function __toString()
    {
        return $this->sql();
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'sql' => $this->sql(),
            'params' => $this->valueBinder()->bindings(),
            'defaultTypes' => $this->defaultTypes(),
            'decorators' => count($this->_resultDecorators),
            'executed' => $this->_iterator ? true : false
        ];
    }
}
