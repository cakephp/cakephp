<?php
/**
 * 
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database;

use IteratorAggregate;
use Cake\Model\Datasource\Database\Expression\QueryExpression;
use Cake\Model\Datasource\Database\Expression\OrderByExpression;
use Cake\Model\Datasource\Database\Expression\Comparison;
use Cake\Model\Datasource\Database\Statement\CallbackStatement;

/**
 * This class represents a Relational database SQL Query. A query can be of
 * different types like select, update, insert and delete. Exposes the methods
 * for dynamically constructing each query part, execute it and transform it
 * to a specific SQL disalect.
 */
class Query implements Expression, IteratorAggregate {

/**
 * Connection instance to be used to execute this query
 *
 * @var \Cake\Model\Datasource\Database\Connection
 */
	protected $_connection;

/**
 * Type of this query (select, insert, update, delete)
 *
 * @var string
 */
	protected $_type;

/**
 * List of SQL parts that will be used to build this query
 *
 * @var array
 */
	protected $_parts = [
		'delete' => true,
		'select' => [],
		'distinct' => false,
		'from' => [],
		'join' => [],
		'set' => [],
		'where' => null,
		'group' => [],
		'having' => null,
		'order' => null,
		'limit' => null,
		'offset' => null,
		'union' => []
	];

/**
 * List of sprintf templates that will be used for compiling the SQL for
 * this query. There are some clauses that can be built as just as the
 * direct concatenation of the internal parts, those are listed here.
 *
 * @var array
 */
	protected $_templates = [
		'delete' => 'DELETE',
		'where' => ' WHERE %s',
		'group' => ' GROUP BY %s ',
		'having' => ' HAVING %s ',
		'order' => ' %s',
		'limit' => ' LIMIT %s',
		'offset' => ' OFFSET %s',
	];

/**
 * When compiling a query to its SQL representation, the connection being used
 * for its execution has the ability to internally change it or even create a 
 * completely different Query object to save any differences with its dialect.
 * This property holds a reference to the Query object that resulted from
 * transforming this instance.
 *
 * @var Query
 */
	protected $_transformedQuery;

/**
 * Indicates whether internal state of this query was changed, this is used to
 * discard internal cached objects such as the transformed query or the reference
 * to the executed statement
 *
 * @var boolean
 */
	protected $_dirty = false;

/**
 * A list of callback functions to be called to alter each row from resulting
 * statement upon retrieval. Each one of the callback function will receive
 * the row array as first argument
 *
 * @var array
 */
	protected $_resultDecorators = [];

/**
 * Statement object resulting from executing this query
 *
 * @var Statement
 */
	protected $_iterator;

/**
 * Constructor
 *
 * @param Cake\Model\Datasource\Database\Connection $connection The connection
 * object to be used for transforming and executing this query
 *
 * @return void
 */
	public function __construct($connection) {
		$this->connection($connection);
	}

/**
 * Sets the connection instance to be used for executing and transforming this query
 * When called with a null argument, it will return the current connection instance
 *
 * @param Cake\Model\Datasource\Database\Connection $connection instance
 * @return Query|Cake\Model\Datasource\Database\Connection
 */
	public function connection($connection = null) {
		if ($connection === null) {
			return $this->_connection;
		}
		$this->_dirty = false;
		$this->_connection = $connection;
		return $this;
	}

/**
 * Compiles the SQL representation of this query and executes it using the
 * configured connection object. Returns the resulting statement object
 *
 * Executing a query internally executes several steps, the first one is
 * letting the connection transform this object to fit its particular dialect,
 * this might result in generating a different Query object that will be the one
 * to actually be executed. Immediately after, literal values are passed to the
 * connection so they are bound to the query in a safe way. Finally, the resulting
 * statement is decorated with custom objects to execute callbacks for each row
 * is retrieved if necessary.
 *
 * Resulting statement is traversable, so it can be used in any loop as you would
 * with an array.
 *
 * @return Cake\Model\Datasource\Database\Statement
 */
	public function execute() {
		$this->_transformedQuery = null;
		$this->_dirty = false;

		$query = $this->_transformQuery();
		$statement = $this->_connection->prepare($query->sql(false));
		$query->_bindParams($statement);
		$statement->execute();

		return $query->_decorateResults($statement);
	}

/**
 * Returns the SQL representation of this object.
 *
 * By default, this function will transform this query to make it compatible
 * with the SQL dialect that is used by the connection, This process might
 * add, remove or alter any query part or internal expression to make it
 * executable in the target platform.
 *
 * Resulting query may have placeholders that will be replaced with the actual
 * values when the query is executed, hence it is most suitable to use with
 * prepared statements.
 *
 * @param boolean $transform Whether to let the connection transform the query
 * to the specific dialect or not
 * @return string
 */
	public function sql($transform = true) {
		$sql = '';
		$builder = function($parts, $name) use(&$sql) {
			if (!count($parts)) {
				return;
			}
			if ($parts instanceof Expression) {
				$parts = [$parts->sql()];
			}
			if (isset($this->_templates[$name])) {
				return $sql .= sprintf($this->_templates[$name], implode(', ', (array)$parts));
			}

			return $sql .= $this->{'_build' . ucFirst($name) . 'Part'}($parts, $sql);
		};

		$query = $transform ? $this->_transformQuery() : $this;
		$query->build($builder->bindTo($query));
		return $sql;
	}

/**
 * Will iterate over every part that should be included for an specific query
 * type and execute the passed builder function for each of them. Builder
 * functions can aggregate results using variables in the closure or instance
 * variables. This function is commonly used as a way for traversing all query parts that
 * are going to be used for constructing a query.
 * 
 * The callback will receive 2 parameters, the first one is the value of the query
 * part that is being iterated and the second the name of such part.
 *
 * ## Example:
 * {{{
 *	$query->select(['title'])->from('articles')->build(function($value, $clause) {
 *		if ($clause === 'select') {
 *			var_dump($value);
 *		}
 *	});
 * }}}
 *
 * @param callback $builder a function or callable to be executed for each part
 * @return Query
 */
	public function build($builder) {
		$this->{'_build' . ucFirst($this->_type)}($builder);
		return $this;
	}

/**
 * Helper function that will iterate over all query parts needed for a SELECT statement
 * and execute the $builder callback for each of them.
 *
 * The callback will receive 2 parameters, the first one is the value of the query
 * part that is being iterated and the second the name of such part.
 *
 * @param callable $builder a function or callable to be executed for each part
 * @return void
 */
	protected function _buildSelect(callable $builder) {
		$parts = ['select', 'from', 'join', 'where', 'group', 'having', 'order', 'limit', 'offset', 'union'];
		foreach ($parts as $name) {
			$builder($this->_parts[$name], $name);
		}
	}

/**
 * Helper function that iterates the query parts needed for DELETE statements.
 *
 * @param callable $builder A callable to execute for each part of the query.
 * @return void
 */
	protected function _buildDelete(callable $builder) {
		$parts = ['delete', 'from', 'where'];
		foreach ($parts as $name) {
			$builder($this->_parts[$name], $name);
		}
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
 * By default this function will append any passed argument to the list of fields
 * to be selected, unless the second argument is set to true.
 *
 * ##Examples:
 *
 * {{{
 *	$query->select(['id', 'title']); // Produces SELECT id, title
 *	$query->select(['author' => 'author_id']); // Appends author: SELECT id, title, author_id as author
 *	$query->select('id', true); // Resets the list: SELECT id
 *	$query->select(['total' => $countQuery]); // SELECT id, (SELECT ...) AS total
 * }}}
 *
 * @param array|Expression|string $fields fields to be added to the list
 * @param boolean $overwrite whether to reset fields with passed list or not
 * @return Query
 */
	public function select($fields = [], $overwrite = false) {
		if ($fields === null) {
			return $this->_parts['select'];
		}

		if (!is_array($fields)) {
			$fields = [$fields];
		}

		if ($overwrite) {
			$this->_parts['select'] = $fields;
		} else {
			$this->_parts['select'] = array_merge($this->_parts['select'], $fields);
		}

		$this->_dirty = true;
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
 * ##Examples:
 *
 * {{{
 *  // Filters products with the same name and city
 *	$query->select(['name', 'city'])->from('products')->distinct();
 *
 *  // Filters products in the same city
 *	$query->distinct(['city']);
 *
 *  // Filter products with the same name
 *	$query->distinct(['name'], true);
 * }}}
 *
 * @param array|Expression fields to be filtered on
 * @param boolean $overwrite whether to reset fields with passed list or not
 * @return Query
 */
	public function distinct($on = [], $overwrite = false) {
		if ($on === []) {
			$on = true;
		}

		if (is_array($on)) {
			$merge = [];
			if (is_array($this->_parts['distinct'])) {
				$merge = $this->_parts['distinct'];
			}
			$on = ($overwrite) ? array_values($on) : array_merge($merge, array_values($on));
		}

		$this->_parts['distinct'] = $on;
		$this->_dirty = true;
		return $this;
	}

/**
 * Helper function used to build the string representation of a SELECT clause,
 * it constructs the field list taking care of aliasing and
 * converting expression objects to string. This function also constructs the
 * DISTINCT clause for the query.
 *
 * @param array $parts list of fields to be transformed to string
 * @return string
 */
	protected function _buildSelectPart($parts) {
		$select = 'SELECT %s%s';
		$distinct = null;
		$normalized = [];
		foreach ($parts as $k => $p) {
			if (!is_numeric($k)) {
				$p = $p . ' AS ' . $k;
			}
			$normalized[] = $p;
		}

		if ($this->_parts['distinct'] === true) {
			$distinct = 'DISTINCT ';
		}

		if (is_array($this->_parts['distinct'])) {
			$distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $this->_parts['distinct']));
		}

		return sprintf($select, $distinct, implode(', ', $normalized));
	}

/**
 * Adds a single or multiple tables to be used in the FROM clause for this query.
 * Tables can be passed as an array of strings, array of expression
 * objects, a single expression or a single string.
 *
 * If an array is passed, keys will be used to alias tables using the value as the
 * real field to be aliased. It is possible to alias strings, Expression objects or
 * even other Query objects.
 *
 * By default this function will append any passed argument to the list of tables
 * to be selected from, unless the second argument is set to true.
 *
 * This method can be used for select, update and delete statements.
 *
 * ##Examples:
 *
 * {{{
 *	$query->from(['p' => 'posts']); // Produces FROM posts p
 *	$query->from('authors'); // Appends authors: FROM posts p, authors
 *	$query->select(['products'], true); // Resets the list: FROM products
 *	$query->select(['sub' => $countQuery]); // FROM (SELECT ...) sub
 * }}}
 *
 * @param array|Expression|string $tables tables to be added to the list
 * @param boolean $overwrite whether to reset tables with passed list or not
 * @return Query
 */
	public function from($tables = [], $overwrite = false) {
		if (empty($tables)) {
			return $this->_parts['from'];
		}

		if (is_string($tables)) {
			$tables = [$tables];
		}

		if ($overwrite) {
			$this->_parts['from'] = $tables;
		} else {
			$this->_parts['from'] =  array_merge($this->_parts['from'], $tables);
		}

		$this->_dirty = true;
		return $this;
	}

/**
 * Helper function used to build the string representation of a FROM clause,
 * it constructs the tables list taking care of aliasing and
 * converting expression objects to string.
 *
 * @param array $parts list of tables to be transformed to string
 * @return string
 */
	public function _buildFromPart($parts) {
		$select = ' FROM %s';
		$normalized = [];
		foreach ($parts as $k => $p) {
			if (!is_numeric($k)) {
				$p = $p . ' ' . $k;
			}
			$normalized[] = $p;
		}
		return sprintf($select, implode(', ', $normalized));
	}

/**
 * Adds a single or multiple tables to be used as JOIN clauses this query.
 * Tables can be passed as an array of strings, an array describing the
 * join parts, an array with multiple join descriptions, or a single string.
 *
 * By default this function will append any passed argument to the list of tables
 * to be joined, unless the third argument is set to true. 
 *
 * When no join type is specified an INNER JOIN is used by default:
 * ``$query->join(['authors'])`` Will produce INNER JOIN authors ON (1 = 1)
 *
 * It is also possible to alias joins using the array key:
 * ``$query->join(['a' => 'authors'])`` Will produce INNER JOIN authors a ON (1 = 1)
 *
 * A join can be fully described and aliased using the array notation:
 * 
 * {{{
 *	$query->join([
 *		'a' => [
 *			'table' => 'authors', 'type' => 'LEFT', 'conditions' => 'a.id = b.author_id'
 *		]
 *	]);
 *  // Produces LEFT JOIN authors a ON (a.id = b.author_id)
 * }}}
 *
 * You can even specify multiple joins in an array, including the full description:
 *
 * {{{
 *	$query->join([
 *		'a' => [
 *			'table' => 'authors', 'type' => 'LEFT', 'conditions' => 'a.id = b.author_id'
 *		],
 *		'p' => [
 *			'table' => 'products', 'type' => 'INNER', 'conditions' => 'a.owner_id = p.id
 *		]
 *	]);
 *	// LEFT JOIN authors a ON (a.id = b.author_id)
 *	// INNER JOIN products p (a.owner_id = p.id)
 * }}}
 *
 * ## Using conditions and types
 *
 * Conditions can be expressed, as in the examples above, using a string for comparing
 * columns, or string with already quoted literal values. Additionally it is
 * possible to using conditions expressed in arrays or expression objects.
 *
 * When using arrays for expressing conditions, it is often desirable to convert
 * the literal values to the correct database representation. This is achieved 
 * using the second parameter of this function.
 *
 * {{{
 *	$query->join(['a' => [
 *		'table' => 'articles',
 *		'conditions' => [
 *			'a.posted >=' => new DateTime('-3 days'),
 *			'a.published' => true
 *			'a.author_id = authors.id'
 *		]
 *	]], ['a.posted' => 'datetime', 'a.published' => 'boolean'])
 * }}}
 *
 * ## Overwriting joins
 *
 * When creating aliased joins using the array notation, you can override
 * previous join definitions by using the same alias in consequent
 * calls to this function or you can replace all previously defined joins
 * with another list if the third parameter for this function is set to true
 *
 * {{{
 *	$query->join(['alias' => 'table']); // joins table with as alias
 *	$query->join(['alias' => 'another_table']); // joins another_table with as alias
 *	$query->join(['something' => 'different_table'], [], true); // resets joins list
 * }}}
 *
 * @param array|string $tables list of tables to be joined in the query
 * @param array $types associative array of type names used to bind values to query
 * @param boolean $overwrite whether to reset joins with passed list or not
 * @see Cake\Model\Datasource\Database\Type
 * @return Query
 */
	public function join($tables = null, $types = [], $overwrite = false) {
		if ($tables === null) {
			return $this->_parts['join'];
		}

		if (is_string($tables) || isset($tables['table'])) {
			$tables = [$tables];
		}

		$joins = [];
		foreach ($tables as $alias => $t) {
			if (!is_array($t)) {
				$t = ['table' => $t, 'conditions' => $this->newExpr()];
			}
			if (!($t['conditions']) instanceof Expression) {
				$t['conditions'] = $this->newExpr()->add($t['conditions'], $types);
			}

			$joins[] = $t + ['type' => 'INNER', 'alias' => is_string($alias) ? $alias : null];
		}

		if ($overwrite) {
			$this->_parts['join'] = $joins;
		} else {
			$this->_parts['join'] = array_merge($this->_parts['join'], array_values($joins));
		}

		$this->_dirty = true;
		return $this;
	}

/**
 * Helper function used to build the string representation of multiple JOIN clauses,
 * it constructs the joins list taking care of aliasing and converting
 * expression objects to string in both the table to be joined and the conditions
 * to be used
 *
 * @param array $parts list of joins to be transformed to string
 * @return string
 */
	protected function _buildJoinPart($parts) {
		$joins = '';
		foreach ($parts as $join) {
			$joins .= sprintf(' %s JOIN %s %s', $join['type'], $join['table'], $join['alias']);
			if (isset($join['conditions']) && count($join['conditions'])) {
				$joins .= sprintf(' ON %s', $join['conditions']);
			} else {
				$joins .= ' ON 1 = 1';
			}
		}
		return $joins;
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
 * ## Conditions using operators:
 *
 * {{{
 *	$query->where([
 *		'posted >=' => new DateTime('3 days ago'),
 *		'title LIKE' => 'Hello W%',
 *		'author_id' => 1,
 *	], ['posted' => 'datetime']);
 * }}}
 *
 * The previous example produces:
 *
 * ``WHERE posted >= 2012-01-27 AND title LIKE 'Hello W%' AND author_id = 1``
 *
 * Second parameter is used to specify what type is expected for each passed
 * key. Valid types can be used from the mapped with Database\Type class.
 *
 * ## Nesting conditions with conjunctions:
 *
 * {{{
 *	$query->where([
 *		'author_id !=' => 1,
 *		'OR' => ['published' => true, 'posted <' => new DateTime('now')],
 *		'NOT' => ['title' => 'Hello']
 *	], ['published' => boolean, 'posted' => 'datetime']
 * }}}
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
 * ## Using expressions objects:
 *
 * {{{
 *	$exp = $query->newExpr()->add(['id !=' => 100, 'author_id' != 1])->type('OR');
 *	$query->where(['published' => true], ['published' => 'boolean'])->where($exp);
 * }}}
 *
 * The previous example produces:
 *
 * ``WHERE (id != 100 OR author_id != 1) AND published = 1``
 *
 * Other Query objects that be used as conditions for any field.
 *
 * ## Adding conditions in multiple steps:
 *
 * You can use callable functions to construct complex expressions, functions
 * receive as first argument a new QueryExpression object and this query instance
 * as second argument. Functions must return an expression object, that will be
 * added the list of conditions for the query using th AND operator
 *
 * {{{
 *	$query
 *	->where(['title !=' => 'Hello World'])
 *	->where(function($exp, $query) {
 *		$or = $exp->or_(['id' => 1]);
		$and = $exp->and_(['id >' => 2, 'id <' => 10]);
		return $or->add($and);
 *	});
 * }}}
 *
 * * The previous example produces:
 *
 * ``WHERE title != 'Hello World' AND (id = 1 OR (id > 2 AND id < 10))``
 *
 * ## Conditions as strings:
 *
 * {{{
 *	$query->where(['articles.author_id = authors.id', 'modified IS NULL']);
 * }}}
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
 * @param string|array|Expression|callback $conditions
 * @param array $types associative array of type names used to bind values to query
 * @param boolean $overwrite whether to reset conditions with passed list or not
 * @see Cake\Model\Datasource\Database\Type
 * @see Cake\Model\Datasource\Database\QueryExpression
 * @return Query
 */
	public function where($conditions = null, $types = [], $overwrite = false) {
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
 * ##Examples:
 *
 * {{{
 *	$query->where(['title' => 'Hello World')->andWhere(['author_id' => 1]);
 * }}}
 *
 * Will produce:
 *
 * ``WHERE title = 'Hello World' AND author_id = 1``
 *
 * {{{
 *	$query
 *		->where(['OR' => ['published' => false, 'published is NULL']])
 *		->andWhere(['author_id' => 1, 'comments_count >' => 10])
 * }}}
 *
 * Produces:
 *
 * ``WHERE (published = 0 OR published IS NULL) AND author_id = 1 AND comments_count > 10``
 *
 * {{{
 *	$query
 *		->where(['title' => 'Foo'])
 *		->andWhere(function($exp, $query) {
 *			return $exp
 *			->add(['author_id' => 1])
 *			->or_(['author_id' => 2]);
 *		});
 * }}}
 *
 * Generates the following conditions:
 *
 * ``WHERE (title = 'Foo') AND (author_id = 1 OR author_id = 2)``
 *
 * @param string|array|Expression|callback $conditions
 * @param array $types associative array of type names used to bind values to query
 * @see Cake\Model\Datasource\Database\Query::where()
 * @see Cake\Model\Datasource\Database\Type
 * @return Query
 */
	public function andWhere($conditions, $types = []) {
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
 * ##Examples:
 *
 * {{{
 *	$query->where(['title' => 'Hello World')->orWhere(['title' => 'Foo']);
 * }}}
 *
 * Will produce:
 *
 * ``WHERE title = 'Hello World' OR title = 'Foo'``
 *
 * {{{
 *	$query
 *		->where(['OR' => ['published' => false, 'published is NULL']])
 *		->orWhere(['author_id' => 1, 'comments_count >' => 10])
 * }}}
 *
 * Produces:
 *
 * ``WHERE (published = 0 OR published IS NULL) OR (author_id = 1 AND comments_count > 10)``
 *
 * {{{
 *	$query
 *		->where(['title' => 'Foo'])
 *		->orWhere(function($exp, $query) {
 *			return $exp
 *			->add(['author_id' => 1])
 *			->or_(['author_id' => 2]);
 *		});
 * }}}
 *
 * Generates the following conditions:
 *
 * ``WHERE (title = 'Foo') OR (author_id = 1 OR author_id = 2)``
 *
 * @param string|array|Expression|callback $conditions
 * @param array $types associative array of type names used to bind values to query
 * @see Cake\Model\Datasource\Database\Query::where()
 * @see Cake\Model\Datasource\Database\Type
 * @return Query
 */
	public function orWhere($conditions, $types = []) {
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
 * ##Examples:
 *
 * {{{
 *	$query->order(['title' => 'DESC', 'author_id' => 'ASC']);
 * }}}
 *
 * Produces:
 *
 * ``ORDER BY title DESC, author_id ASC``
 *
 * {{{
 *	$query->order(['title' => 'DESC NULLS FIRST'])->order('author_id');
 * }}}
 *
 * Will generate:
 *
 * ``ORDER BY title DESC NULLS FIRST, author_id``
 *
 * {{{
 *	$expression = $query->newExpr()->add(['id % 2 = 0']);
 *	$query->order($expression)->order(['title' => 'ASC']);
 * }}}
 *
 * Will become:
 *
 * ``ORDER BY (id %2 = 0), title ASC``
 *
 * @param array|Expression|string $fields fields to be added to the list
 * @param boolean $overwrite whether to reset order with field list or not
 * @return Query
 */
	public function order($fields, $overwrite = false) {
		if ($overwrite || !$this->_parts['order']) {
			$this->_parts['order'] = new OrderByExpression;
		}
		$this->_conjugate('order', $fields, '', []);
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
 * ##Examples:
 *
 * {{{
 *	$query->group(['id', 'title']); // Produces GROUP BY id, title
 *	$query->group('title'); // Produces GROUP BY title
 * }}}
 *
 * @param array|Expression|string $fields fields to be added to the list
 * @param boolean $overwrite whether to reset fields with passed list or not
 * @return Query
 */
	public function group($fields, $overwrite = false) {
		if ($overwrite) {
			$this->_parts['group'] = [];
		}

		if (!is_array($fields)) {
			$fields = [$fields];
		}

		$this->_parts['group'] = array_merge($this->_parts['group'], array_values($fields));
		$this->_dirty = true;
		return $this;
	}

/**
 * Adds a condition or set of conditions to be used in the HAVING clause for this
 * query. This method operates in exactly the same way as the method ``where()``
 * does. Please refer to its documentation for an insight on how to using each
 * parameter.
 *
 * @param string|array|Expression|callback $conditions
 * @param array $types associative array of type names used to bind values to query
 * @param boolean $overwrite whether to reset conditions with passed list or not
 * @see Cake\Model\Datasource\Database\Query::where()
 * @return Query
 */
	public function having($conditions = null, $types = [], $overwrite = false) {
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
 * @param string|array|Expression|callback $conditions
 * @param array $types associative array of type names used to bind values to query
 * @see Cake\Model\Datasource\Database\Query::andWhere()
 * @return Query
 */
	public function andHaving($conditions, $types = []) {
		$this->_conjugate('having', $conditions, 'AND', $types);
		return $this;
	}

/**
 * Connects any previously defined set of conditions to the provided list
 * using the OR operator in the HAVING clause. This method operates in exactly
 * the same way as the method ``orWhere()`` does. Please refer to its
 * documentation for an insight on how to using each parameter.
 *
 * @param string|array|Expression|callback $conditions
 * @param array $types associative array of type names used to bind values to query
 * @see Cake\Model\Datasource\Database\Query::orWhere()
 * @return Query
 */
	public function orHaving($conditions, $types = []) {
		$this->_conjugate('having', $conditions, 'OR', $types);
		return $this;
	}

/**
 * Sets the number of records that should be retrieved from database,
 * accepts an integer or an expression object that evaluates to an integer.
 * In some databases, this operation might not be supported or will require
 * the query to be transformed in order to limit the result set size.
 *
 * ## Examples
 *
 * {{{
 *	$query->limit(10) // generates LIMIT 10
 *	$query->limit($query->newExpr()->add(['1 + 1'])); // LIMIT (1 + 1)
 * }}}
 *
 * @param integer|Expression $num number of records to be returned
 * @return Query
 */
	public function limit($num) {
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
 * In some databases, this operation might not be supported or will require
 * the query to be transformed in order to limit the result set size.
 *
 * ## Examples
 *
 * {{{
 *	$query->offset(10) // generates OFFSET 10
 *	$query->limit($query->newExpr()->add(['1 + 1'])); // OFFSET (1 + 1)
 * }}}
 *
 * @param integer|Expression $num number of records to be skipped
 * @return Query
 */
	public function offset($num) {
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
 * every row for all queries, set the second argument to true.
 *
 * ## Examples
 *
 * {{{
 *	$union = (new Query($conn))->select(['id', 'title'])->from(['a' => 'articles']);
 *	$query->select(['id', 'name'])->from(['d' => 'things'])->union($union);
 * }}}
 *
 * Will produce:
 *
 * ``SELECT id, name FROM things d UNION SELECT id, title FROM articles a``
 *
 * {{{
 *	$union = (new Query($conn))->select(['id', 'title'])->from(['a' => 'articles']);
 *	$query->select(['id', 'name'])->from(['d' => 'things'])->union($union, true);
 * }}}
 *
 * Will produce:
 *
 * ``SELECT id, name FROM things d UNION ALL SELECT id, title FROM articles a``
 *
 * @param string|Query $query full SQL query to be used in UNION operator
 * @param boolean $all whether to use UNION ALL or not
 * @param boolean $overwrite whether to reset the list of queries to be operated or not
 * @return Query
 */
	public function union($query, $all = false, $overwrite = false) {
		if ($overwrite) {
			$this->_parts['union'] = [];
		}
		$this->_parts['union'][] = compact('all', 'query');
		$this->_dirty = true;
		return $this;
	}

/**
 * Builds the SQL string for all the UNION clauses in this query, when dealing
 * with query objects it will also transform them using their configured SQL
 * dialect.
 *
 * @param array $parts list of queries to be operated with UNION
 * @return string
 */
	protected function _buildUnionPart($parts) {
		$parts = array_map(function($p) {
			$p['query'] =(string)$p['query'];
			$p['query'] = $p['query'][0] === '(' ? trim($p['query'], '()') : $p['query'];
			return $p['all'] ? 'ALL ' . $p['query'] : $p['query'];
		}, $parts);
		return sprintf("\nUNION %s", implode("\nUNION ", $parts));
	}

	public function insert() {
		return $this;
	}

	public function update() {
		return $this;
	}

/**
 * Convert the query into a delete query.
 *
 * Can be combined with from(), where() and other methods to
 * create delete queries with specific conditions.
 *
 * @param string $table The table to use when deleting. This
 * @return Query
 */
	public function delete($table = null) {
		$this->_dirty = true;
		$this->_type = 'delete';
		if ($table) {
			$this->from($table);
		}
		return $this;
	}

/**
 * Returns the type of this query (select, insert, update, delete)
 *
 * @return string
 */
	public function type() {
		return $this->_type;
	}

/**
 * Returns a new QueryExpression object. This is a handy function when
 * building complex queries using a fluent interface. You can also override
 * this function in subclasses to use a more specialized QueryExpression class
 * if required.
 *
 * @return QueryExpression
 */
	public function newExpr() {
		return new QueryExpression;
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
			$this->_iterator = $this->execute();
		}
		return $this->_iterator;
	}

/**
 * Returns any data that was stored in the specified clause. This is useful for
 * modifying any internal part of the query and it is used by the SQL dialects
 * to transform the query accordingly before it is executed. The valid clauses that
 * can be retrieved are: select, distinct, from, join, set, where, group, having,
 * order, limit, offset and union.
 *
 * The return value for each of those parts may vary. Some clauses use QueryExpression
 * to internally store their state, some use arrays and others may use booleans or
 * integers. This is summary of the return types for each clause
 *
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
 *
 * @param string $name name of the clause to be returned
 * @return mixed
 */
	public function clause($name) {
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
 * ## Example
 *
 * {{{
 *	$query->decorateResults(function($row) {
 *		$row['order_total'] = $row['subtotal'] + ($row['subtotal'] * $row['tax']);
 *		return $row;
 *	});
 * }}}
 *
 * @return Query
 */
	public function decorateResults($callback, $overwrite = false) {
		if ($overwrite) {
			$this->_resultDecorators = [];
		}

		if ($callback !== null) {
			$this->_resultDecorators[] = $callback;
		}

		return $this;
	}

/**
 * Auxiliary function used to wrap the original statement from the driver with
 * any registered callbacks.
 *
 * @param Cake\Model\Datasource\Database\Statement $statement to be decorated
 * @return Cake\Model\Datasource\Database\Statement\CallbackStatement
 */
	protected function _decorateResults($statement) {
		foreach ($this->_resultDecorators as $f) {
			$statement = new CallbackStatement($statement, $this->connection()->driver(), $f);
		}
		return $statement;
	}


/**
 * Helper function used to build conditions by composing QueryExpression objects
 *
 * @param string name of the query part to append the new part to
 * @param string|array|Expression|callback $append
 * @param sttring $conjunction type of conjunction to be used to operate part
 * @param array $types associative array of type names used to bind values to query
 * @return void
 */
	protected function _conjugate($part, $append, $conjunction, $types) {
		$expression = $this->_parts[$part] ?: $this->newExpr();

		if (is_callable($append)) {
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
		$this->_dirty = true;
	}

/**
 * Traverses all QueryExpression objects stored in every relevant for this type
 * of query and binds every value to the statement object for each placeholder.
 *
 * @param Cake\Model\Datasource\Database\Statement $statement
 * @return void
 */
	protected function _bindParams($statement) {
		$visitor = function($expression) use ($statement) {
			$params = $types = [];

			if ($expression instanceof Comparison) {
				if ($expression->getValue() instanceof self) {
					$expression->getValue()->_bindParams($statement);
				}
			}

			foreach ($expression->bindings() as $b) {
				$params[$b['placeholder']] = $b['value'];
				$types[$b['placeholder']] = $b['type'];
			}
			$statement->bind($params, $types);
		};

		$binder = function($expression, $name) use ($statement, $visitor, &$binder) {
			if (is_array($expression)) {
				foreach ($expression as $e) {
					$binder($e, $name);
				}
			}

			if ($expression instanceof self) {
				return $expression->build($binder);
			}

			if ($expression instanceof QueryExpression) {
				//Visit all expressions and subexpressions to get every bound value
				$expression->traverse($visitor);
			}
		};

		$this->_transformQuery()->build($binder);
	}

/**
 * Returns a query object as returned by the connection object as a result of
 * transforming this query instance to conform to any dialect specifics
 *
 * @return Query
 */
	protected function _transformQuery() {
		if (isset($this->_transformedQuery) && !$this->_dirty) {
			return $this->_transformedQuery;
		}
		// TODO: Should Query actually get the driver or just let the connection decide where
		// to get the query translator?
		$translator = $this->connection()->driver()->queryTranslator($this->_type);
		return $this->_transformedQuery = $translator($this);
	}

/**
 * Returns string representation of this query (complete SQL statement)
 *
 * @return string
 */
	public function __toString() {
		return sprintf('(%s)', $this->sql());
	}

}
