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

use Iterator;
use IteratorAggregate;
use Cake\Model\Datasource\Database\Expression\QueryExpression;
use Cake\Model\Datasource\Database\Expression\OrderByExpression;
use Cake\Model\Datasource\Database\Expression\Comparisson;
use Cake\Model\Datasource\Database\Statement\CallbackStatement;

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
		'select' => [],
		'distinct' => false,
		'from' => [],
		'join' => [],
		'set' => [],
		'where' => null,
		'group' => [],
		'having' => null,
		'order' => [],
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
 * @param callback $builder a function or callable to be executed for each part
 * @return void
 */
	protected function _buildSelect($builder) {
		$parts = ['select', 'from', 'join', 'where', 'group', 'having', 'order', 'limit', 'offset', 'union'];
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
 * {{
 *	$query->select(['id', 'title']); // Produces SELECT id, title
 *	$query->select(['author' => 'author_id']); // Appends author: SELECT id, title, author_id as author
 *	$query->select('id', true); // Resets the list: SELECT id
 *	$query->select(['total' => $countQuery]); // SELECT id, (SELECT ...) AS total
 * }}
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
 * {{
 *  // Filters products with the same name and city
 *	$query->select(['name', 'city'])->from('products')->distinct();
 *
 *  // Filters products in the same city
 *	$query->distinct(['city']);
 *
 *  // Filter products with the same name
 *	$query->distinct(['name'], true);
 * }}
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
 * {{
 *	$query->from(['p' => 'posts']); // Produces FROM posts p
 *	$query->from('authors'); // Appends authors: FROM posts p, authors
 *	$query->select(['products'], true); // Resets the list: FROM products
 *	$query->select(['sub' => $countQuery]); // FROM (SELECT ...) sub
 * }}
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
 * {{
 *	$query->join([
 *		'a' => [
 *			'table' => 'authors', 'type' => 'LEFT', 'conditions' => 'a.id = b.author_id'
 *		]
 *	]);
 *  // Produces LEFT JOIN authors a ON (a.id = b.author_id)
 * }}
 *
 * You can even specify multiple joins in an array, including the full description:
 *
 * {{
 *	$query->join([
 *		'a' => [
 *			'table' => 'authors', 'type' => 'LEFT', 'conditions' => 'a.id = b.author_id'
 *		],
 *		'p' => [
 *			'table' => 'products', 'type' => 'INNER', 'conditions' => 'a.owner_id = p.id
 *		]
 *	]);
 *
 * ## Using conditions and types
 *
 * @todo
 *
 * @param array|string $tables list of tables to be joined in the query
 * @param array $types associative array of type names used to bind values to query
 * @param boolean $overwrite whether to reset joins with passed list or not
 * @return Query
 */
	public function join($tables = null, $types = [], $overwrite = false) {
		if ($tables === null) {
			return $this->_parts['join'];
		}

		if (is_string($tables) || isset($tables['table'])) {
			$tables = [$tables];
		}

		$joins = array();
		foreach ($tables as $alias => $t) {
			if (!is_array($t)) {
				$t = array('table' => $t, 'conditions' => $this->newExpr());
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


	public function where($conditions = null, $types = [], $overwrite = false) {
		if ($overwrite) {
			$this->_parts['where'] = $this->newExpr();
		}
		$this->_conjugate('where', $conditions, 'AND', $types);
		return $this;
	}

	public function andWhere($conditions, $types = []) {
		$this->_conjugate('where', $conditions, 'AND', $types);
		return $this;
	}

	public function orWhere($conditions, $types = []) {
		$this->_conjugate('where', $conditions, 'OR', $types);
		return $this;
	}

	public function order($clause, $overwrite = false) {
		if ($overwrite || !$this->_parts['order']) {
			$this->_parts['order'] = new OrderByExpression;
		}
		$this->_conjugate('order', $clause, '', []);
		return $this;
	}

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

	public function having($conditions = null, $types = [], $overwrite = false) {
		if ($overwrite) {
			$this->_parts['having'] = $this->newExpr();
		}
		$this->_conjugate('having', $conditions, 'AND', $types);
		return $this;
	}

	public function andHaving($conditions, $types = []) {
		$this->_conjugate('having', $conditions, 'AND', $types);
		return $this;
	}

	public function orHaving($conditions, $types = []) {
		$this->_conjugate('having', $conditions, 'OR', $types);
		return $this;
	}

	public function limit($num) {
		$this->_parts['limit'] = $num;
		return $this;
	}

	public function offset($num) {
		$this->_parts['offset'] = $num;
		return $this;
	}

	public function union($query, $overwrite = false) {
		if ($overwrite) {
			$this->_parts['union'] = [];
		}
		$this->_parts['union'][] = $query;
		$this->_dirty = true;
		return $this;
	}

	protected function _buildUnionPart($parts) {
		$parts = array_map(function($p) {
			$p =(string)$p;
			return $p[0] === '(' ? trim($p, '()') : $p;
		}, $parts);
		return sprintf("\nUNION %s", implode("\nUNION ", $parts));
	}

	public function insert() {
		return $this;
	}

	public function update() {
		return $this;
	}

	public function delete() {
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

	public function newExpr() {
		return new QueryExpression;
	}

/**
 * Executes this query and returns a results iterator
 *
 * @return Iterator
 */
	public function getIterator() {
		if (empty($this->_iterator)) {
			$this->_iterator = $this->execute();
		}
		return $this->_iterator;
	}

	public function clause($name) {
		return $this->_parts[$name];
	}

	public function decorateResults($callback, $overwrite = false) {
		if ($overwrite) {
			$this->_resultDecorators = [];
		}
		$this->_resultDecorators[] = $callback;

		return $this;
	}

	protected function _decorateResults($statement) {
		foreach ($this->_resultDecorators as $f) {
			$statement = new CallbackStatement($statement, $this->connection()->driver(), $f);
		}
		return $statement;
	}

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

	protected function _bindParams($statement) {
		$visitor = function($expression) use ($statement) {
			$params = $types = [];

			if ($expression instanceof Comparisson) {
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
 * @return void
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
