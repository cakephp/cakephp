<?php
/**
 * Dbo Source
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.model.datasources
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('DataSource', 'Model/Datasource');
App::uses('String', 'Utility');
App::uses('View', 'View');

/**
 * DboSource
 *
 * Creates DBO-descendant objects from a given db connection configuration
 *
 * @package       cake.libs.model.datasources
 */
class DboSource extends DataSource {

/**
 * Description string for this Database Data Source.
 *
 * @var string
 * @access public
 */
	public $description = "Database Data Source";

/**
 * index definition, standard cake, primary, index, unique
 *
 * @var array
 */
	public $index = array('PRI' => 'primary', 'MUL' => 'index', 'UNI' => 'unique');

/**
 * Database keyword used to assign aliases to identifiers.
 *
 * @var string
 * @access public
 */
	public $alias = 'AS ';

/**
 * Caches result from query parsing operations.  Cached results for both DboSource::name() and
 * DboSource::conditions() will be stored here.  Method caching uses `crc32()` which is
 * fast but can collisions more easily than other hashing algorithms.  If you have problems
 * with collisions, set DboSource::$cacheMethods to false.
 *
 * @var array
 * @access public
 */
	public $methodCache = array();

/**
 * Whether or not to cache the results of DboSource::name() and DboSource::conditions()
 * into the memory cache.  Set to false to disable the use of the memory cache.
 *
 * @var boolean.
 * @access public
 */
	public $cacheMethods = true;

/**
 * Print full query debug info?
 *
 * @var boolean
 * @access public
 */
	public $fullDebug = false;

/**
 * Error description of last query
 *
 * @var unknown_type
 * @access public
 */
	public $error = null;

/**
 * String to hold how many rows were affected by the last SQL operation.
 *
 * @var string
 * @access public
 */
	public $affected = null;

/**
 * Number of rows in current resultset
 *
 * @var int
 * @access public
 */
	public $numRows = null;

/**
 * Time the last query took
 *
 * @var int
 * @access public
 */
	public $took = null;

/**
 * Result
 *
 * @var array
 * @access protected
 */
	protected $_result = null;

/**
 * Queries count.
 *
 * @var int
 * @access protected
 */
	protected $_queriesCnt = 0;

/**
 * Total duration of all queries.
 *
 * @var unknown_type
 * @access protected
 */
	protected $_queriesTime = null;

/**
 * Log of queries executed by this DataSource
 *
 * @var unknown_type
 * @access protected
 */
	protected $_queriesLog = array();

/**
 * Maximum number of items in query log
 *
 * This is to prevent query log taking over too much memory.
 *
 * @var int Maximum number of queries in the queries log.
 * @access protected
 */
	protected $_queriesLogMax = 200;

/**
 * Caches serialzed results of executed queries
 *
 * @var array Maximum number of queries in the queries log.
 * @access protected
 */
	protected $_queryCache = array();

/**
 * A reference to the physical connection of this DataSource
 *
 * @var array
 * @access public
 */
	public $connection = null;

/**
 * The DataSource configuration key name
 *
 * @var string
 * @access public
 */
	public $configKeyName = null;

/**
 * The starting character that this DataSource uses for quoted identifiers.
 *
 * @var string
 * @access public
 */
	public $startQuote = null;

/**
 * The ending character that this DataSource uses for quoted identifiers.
 *
 * @var string
 * @access public
 */
	public $endQuote = null;

/**
 * Bypass automatic adding of joined fields/associations.
 *
 * @var boolean
 * @access private
 */
	private $__bypass = false;

/**
 * The set of valid SQL operations usable in a WHERE statement
 *
 * @var array
 * @access private
 */
	private $__sqlOps = array('like', 'ilike', 'or', 'not', 'in', 'between', 'regexp', 'similar to');

/**
 * Indicates the level of nested transactions
 *
 * @var integer
 * @access protected
 */
	protected $_transactionNesting = 0;

/**
 * Index of basic SQL commands
 *
 * @var array
 * @access protected
 */
	protected $_commands = array(
		'begin' => 'BEGIN',
		'commit' => 'COMMIT',
		'rollback' => 'ROLLBACK'
	);

/**
 * Separator string for virtualField composition
 *
 * @var string
 */
	public $virtualFieldSeparator = '__';

/**
 * List of table engine specific parameters used on table creating
 *
 * @var array
 * @access public
 */
	public $tableParameters = array();

/**
 * List of engine specific additional field parameters used on table creating
 *
 * @var array
 * @access public
 */
	public $fieldParameters = array();

/**
 * Constructor
 *
 * @param array $config Array of configuration information for the Datasource.
 * @param boolean $autoConnect Whether or not the datasource should automatically connect.
 */
	public function __construct($config = null, $autoConnect = true) {
		if (!isset($config['prefix'])) {
			$config['prefix'] = '';
		}
		parent::__construct($config);
		$this->fullDebug = Configure::read('debug') > 1;
		if (!$this->enabled()) {
			return;
		}
		if ($autoConnect) {
			$this->connect();
		}
	}

/**
 * Reconnects to database server with optional new settings
 *
 * @param array $config An array defining the new configuration settings
 * @return boolean True on success, false on failure
 */
	function reconnect($config = array()) {
		$this->disconnect();
		$this->setConfig($config);
		$this->_sources = null;

		return $this->connect();
	}

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		if ($this->_result instanceof PDOStatement) {
			$this->_result->closeCursor();
		}
		unset($this->_connection);
		$this->connected = false;
		return true;
	}

/**
 * Get the underlying connection object.
 *
 * @return PDOConnection
 */
	public function getConnection() {
		return $this->_connection;
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @return string Quoted and escaped data
 */
	function value($data, $column = null) {
		if (is_array($data) && !empty($data)) {
			return array_map(
				array(&$this, 'value'),
				$data, array_fill(0, count($data), $column)
			);
		} elseif (is_object($data) && isset($data->type, $data->value)) {
			if ($data->type == 'identifier') {
				return $this->name($data->value);
			} elseif ($data->type == 'expression') {
				return $data->value;
			}
		} elseif (in_array($data, array('{$__cakeID__$}', '{$__cakeForeignKey__$}'), true)) {
			return $data;
		}

		if ($data === null || (is_array($data) && empty($data))) {
			return 'NULL';
		}

		if (empty($column)) {
			$column = $this->introspectType($data);
		}

		switch ($column) {
			case 'binary':
				return $this->_connection->quote($data, PDO::PARAM_LOB);
			break;
			case 'boolean':
				return $this->_connection->quote($this->boolean($data, true), PDO::PARAM_BOOL);
			break;
			case 'string':
			case 'text':
				return $this->_connection->quote($data, PDO::PARAM_STR);
			default:
				if ($data === '') {
					return 'NULL';
				}
				if (is_float($data)) {
					return sprintf('%F', $data);
				}
				if ((is_int($data) || $data === '0') || (
					is_numeric($data) && strpos($data, ',') === false &&
					$data[0] != '0' && strpos($data, 'e') === false)
				) {
					return $data;
				}
				return $this->_connection->quote($data);
			break;
		}
	}


/**
 * Returns an object to represent a database identifier in a query. Expression objects
 * are not sanitized or esacped.
 *
 * @param string $identifier A SQL expression to be used as an identifier
 * @return object An object representing a database identifier to be used in a query
 */
	public function identifier($identifier) {
		$obj = new stdClass();
		$obj->type = 'identifier';
		$obj->value = $identifier;
		return $obj;
	}

/**
 * Returns an object to represent a database expression in a query.  Expression objects
 * are not sanitized or esacped.
 *
 * @param string $expression An arbitrary SQL expression to be inserted into a query.
 * @return object An object representing a database expression to be used in a query
 */
	public function expression($expression) {
		$obj = new stdClass();
		$obj->type = 'expression';
		$obj->value = $expression;
		return $obj;
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @param array $params Additional options for the query.
 * @return boolean
 */
	public function rawQuery($sql, $params = array()) {
		$this->took = $this->error = $this->numRows = false;
		return $this->execute($sql, $params);
	}

/**
 * Queries the database with given SQL statement, and obtains some metadata about the result
 * (rows affected, timing, any errors, number of rows in resultset). The query is also logged.
 * If Configure::read('debug') is set, the log is shown all the time, else it is only shown on errors.
 *
 * ### Options
 *
 * - log - Whether or not the query should be logged to the memory log.
 *
 * @param string $sql
 * @param array $options
 * @param array $params values to be bided to the query
 * @return mixed Resource or object representing the result set, or false on failure
 */
	public function execute($sql, $options = array(), $params = array()) {
		$options += array('log' => $this->fullDebug);
		$this->error = null;

		$t = microtime(true);
		$this->_result = $this->_execute($sql, $params);

		if ($options['log']) {
			$this->took = round((microtime(true) - $t) * 1000, 0);
			$this->numRows = $this->affected = $this->lastAffected();
			$this->logQuery($sql);
		}

		if ($this->error) {
			$this->showQuery($sql);
			return false;
		}
		return $this->_result;
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @param array $params list of params to be bound to query
 * @return PDOStatement if query executes with no problem, true as the result of a succesfull
 * query returning no rows, suchs as a CREATE statement, false otherwise
 */
	protected function _execute($sql, $params = array()) {
		$sql = trim($sql);
		if (preg_match('/^(?:CREATE|ALTER|DROP)/i', $sql)) {
			$statements = array_filter(explode(';', $sql));
			if (count($statements) > 1) {
				$result = array_map(array($this, '_execute'), $statements);
				return array_search(false, $result) === false;
			}
		}

		try {
			$query = $this->_connection->prepare($sql);
			$query->setFetchMode(PDO::FETCH_LAZY);
			if (!$query->execute($params)) {
				$this->_results = $query;
				$this->error = $this->lastError($query);
				$query->closeCursor();
				return false;
			}
			if (!$query->columnCount()) {
				$query->closeCursor();
				return true;
			}
			return $query;
		} catch (PDOException $e) {
			$this->_results = null;
			$this->error = $e->getMessage();
			return false;
		}
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @param PDOStatement $query the query to extract the error from if any
 * @return string Error message with error number
 */
	function lastError(PDOStatement $query = null) {
		$error = $query->errorInfo();
		if (empty($error[2])) {
			return null;
		}
		return $error[1] . ': ' . $error[2];
	}

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @return integer Number of affected rows
 */
	function lastAffected() {
		if ($this->hasResult()) {
			return $this->_result->rowCount();
		}
		return null;
	}

/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return integer Number of rows in resultset
 */
	function lastNumRows() {
		return $this->lastAffected();
	}

/**
 * DataSource Query abstraction
 *
 * @return resource Result resource identifier.
 */
	public function query() {
		$args	  = func_get_args();
		$fields	  = null;
		$order	  = null;
		$limit	  = null;
		$page	  = null;
		$recursive = null;

		if (count($args) === 1) {
			return $this->fetchAll($args[0]);
		} elseif (count($args) > 1 && (strpos($args[0], 'findBy') === 0 || strpos($args[0], 'findAllBy') === 0)) {
			$params = $args[1];

			if (substr($args[0], 0, 6) === 'findBy') {
				$all = false;
				$field = Inflector::underscore(substr($args[0], 6));
			} else {
				$all = true;
				$field = Inflector::underscore(substr($args[0], 9));
			}

			$or = (strpos($field, '_or_') !== false);
			if ($or) {
				$field = explode('_or_', $field);
			} else {
				$field = explode('_and_', $field);
			}
			$off = count($field) - 1;

			if (isset($params[1 + $off])) {
				$fields = $params[1 + $off];
			}

			if (isset($params[2 + $off])) {
				$order = $params[2 + $off];
			}

			if (!array_key_exists(0, $params)) {
				return false;
			}

			$c = 0;
			$conditions = array();

			foreach ($field as $f) {
				$conditions[$args[2]->alias . '.' . $f] = $params[$c++];
			}

			if ($or) {
				$conditions = array('OR' => $conditions);
			}

			if ($all) {
				if (isset($params[3 + $off])) {
					$limit = $params[3 + $off];
				}

				if (isset($params[4 + $off])) {
					$page = $params[4 + $off];
				}

				if (isset($params[5 + $off])) {
					$recursive = $params[5 + $off];
				}
				return $args[2]->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'));
			} else {
				if (isset($params[3 + $off])) {
					$recursive = $params[3 + $off];
				}
				return $args[2]->find('first', compact('conditions', 'fields', 'order', 'recursive'));
			}
		} else {
			if (isset($args[1]) && $args[1] === true) {
				return $this->fetchAll($args[0], true);
			} else if (isset($args[1]) && !is_array($args[1]) ) {
				return $this->fetchAll($args[0], false);
			} else if (isset($args[1]) && is_array($args[1])) {
				$offset = 0;
				if (isset($args[2])) {
					$cache = $args[2];
				} else {
					$cache = true;
				}
				return $this->fetchAll($args[0], $args[1], array('cache' => $cache));
			}
		}
	}

/**
 * Returns a row from current resultset as an array
 *
 * @param string $sql Some SQL to be executed.
 * @return array The fetched row as an array
 */
	public function fetchRow($sql = null) {
		if (is_string($sql) && strlen($sql) > 5 && !$this->execute($sql)) {
			return null;
		}

		if ($this->hasResult()) {
			$this->resultSet($this->_result);
			$resultRow = $this->fetchResult();
			if (isset($resultRow[0])) {
				$this->fetchVirtualField($resultRow);
			}
			return $resultRow;
		} else {
			return null;
		}
	}

/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 *
 * ### Options
 *
 * - cache - Returns the cached version of the query, if exists and stores the result in cache
 *
 * @param string $sql SQL statement
 * @param array $params parameters to be bound as values for the SQL statement
 * @param array $options additional options for the query.
 * @return array Array of resultset rows, or false if no rows matched
 */
	public function fetchAll($sql, $params = array(), $options = array()) {
		if (is_string($options)) {
			$options = array('modelName' => $options);
		}
		if (is_bool($params)) {
			$options['cache'] = $params;
			$params = array();
		}
		$options += array('cache' => true);
		$cache = $options['cache'];
		if ($cache && ($cached = $this->getQueryCache($sql, $params)) !== false) {
			return $cached;
		}
		if ($result = $this->execute($sql, array(), $params)) {
			$out = array();

			if ($this->hasResult()) {
				$first = $this->fetchRow();
				if ($first != null) {
					$out[] = $first;
				}
				while ($item = $this->fetchResult()) {
					if (isset($item[0])) {
						$this->fetchVirtualField($item);
					}
					$out[] = $item;
				}
			}

			if (!is_bool($result) && $cache) {
				$this->_writeQueryCache($sql, $out, $params);
			}

			if (empty($out) && is_bool($this->_result)) {
				return $this->_result;
			}
			return $out;
		}
		return false;
	}

/**
 * Fetches the next row from the current result set
 *
 * @return boolean
 */
	public function fetchResult() {
		return false;
	}

/**
 * Modifies $result array to place virtual fields in model entry where they belongs to
 *
 * @param array $resut Reference to the fetched row
 * @return void
 */
	public function fetchVirtualField(&$result) {
		if (isset($result[0]) && is_array($result[0])) {
			foreach ($result[0] as $field => $value) {
				if (strpos($field, $this->virtualFieldSeparator) === false) {
					continue;
				}
				list($alias, $virtual) = explode($this->virtualFieldSeparator, $field);

				if (!ClassRegistry::isKeySet($alias)) {
					return;
				}
				$model = ClassRegistry::getObject($alias);
				if ($model->isVirtualField($virtual)) {
					$result[$alias][$virtual] = $value;
					unset($result[0][$field]);
				}
			}
			if (empty($result[0])) {
				unset($result[0]);
			}
		}
	}

/**
 * Returns a single field of the first of query results for a given SQL query, or false if empty.
 *
 * @param string $name Name of the field
 * @param string $sql SQL query
 * @return mixed Value of field read.
 */
	public function field($name, $sql) {
		$data = $this->fetchRow($sql);
		if (empty($data[$name])) {
			return false;
		}
		return $data[$name];
	}

/**
 * Empties the method caches.
 * These caches are used by DboSource::name() and DboSource::conditions()
 *
 * @return void
 */
	public function flushMethodCache() {
		$this->methodCache = array();
	}

/**
 * Cache a value into the methodCaches.  Will respect the value of DboSource::$cacheMethods.
 * Will retrieve a value from the cache if $value is null.
 *
 * If caching is disabled and a write is attempted, the $value will be returned.
 * A read will either return the value or null.
 *
 * @param string $method Name of the method being cached.
 * @param string $key The keyname for the cache operation.
 * @param mixed $value The value to cache into memory.
 * @return mixed Either null on failure, or the value if its set.
 */
	public function cacheMethod($method, $key, $value = null) {
		if ($this->cacheMethods === false) {
			return $value;
		}
		if ($value === null) {
			return (isset($this->methodCache[$method][$key])) ? $this->methodCache[$method][$key] : null;
		}
		return $this->methodCache[$method][$key] = $value;
	}

/**
 * Returns a quoted name of $data for use in an SQL statement.
 * Strips fields out of SQL functions before quoting.
 *
 * Results of this method are stored in a memory cache.  This improves performance, but
 * because the method uses a simple hashing algorithm it can infrequently have collisions.
 * Setting DboSource::$cacheMethods to false will disable the memory cache.
 *
 * @param mixed $data Either a string with a column to quote. An array of columns to quote or an
 *   object from DboSource::expression() or DboSource::identifier()
 * @return string SQL field
 */
	public function name($data) {
		if (is_object($data) && isset($data->type)) {
			return $data->value;
		}
		if ($data === '*') {
			return '*';
		}
		if (is_array($data)) {
			foreach ($data as $i => $dataItem) {
				$data[$i] = $this->name($dataItem);
			}
			return $data;
		}
		$cacheKey = crc32($this->startQuote.$data.$this->endQuote);
		if ($return = $this->cacheMethod(__FUNCTION__, $cacheKey)) {
			return $return;
		}
		$data = trim($data);
		if (preg_match('/^[\w-]+(?:\.[^ \*]*)*$/', $data)) { // string, string.string
			if (strpos($data, '.') === false) { // string
				return $this->cacheMethod(__FUNCTION__, $cacheKey, $this->startQuote . $data . $this->endQuote);
			}
			$items = explode('.', $data);
			return $this->cacheMethod(__FUNCTION__, $cacheKey,
				$this->startQuote . implode($this->endQuote . '.' . $this->startQuote, $items) . $this->endQuote
			);
		}
		if (preg_match('/^[\w-]+\.\*$/', $data)) { // string.*
			return $this->cacheMethod(__FUNCTION__, $cacheKey,
				$this->startQuote . str_replace('.*', $this->endQuote . '.*', $data)
			);
		}
		if (preg_match('/^([\w-]+)\((.*)\)$/', $data, $matches)) { // Functions
			return $this->cacheMethod(__FUNCTION__, $cacheKey,
				 $matches[1] . '(' . $this->name($matches[2]) . ')'
			);
		}
		if (
			preg_match('/^([\w-]+(\.[\w-]+|\(.*\))*)\s+' . preg_quote($this->alias) . '\s*([\w-]+)$/i', $data, $matches
		)) {
			return $this->cacheMethod(
				__FUNCTION__, $cacheKey,
				preg_replace(
					'/\s{2,}/', ' ', $this->name($matches[1]) . ' ' . $this->alias . ' ' . $this->name($matches[3])
				)
			);
		}
		if (preg_match('/^[\w-_\s]*[\w-_]+/', $data)) {
			return $this->cacheMethod(__FUNCTION__, $cacheKey, $this->startQuote . $data . $this->endQuote);
		}
		return $this->cacheMethod(__FUNCTION__, $cacheKey, $data);
	}

/**
 * Checks if the source is connected to the database.
 *
 * @return boolean True if the database is connected, else false
 */
	public function isConnected() {
		return $this->connected;
	}

/**
 * Checks if the result is valid
 *
 * @return boolean True if the result is valid else false
 */
	public function hasResult() {
		return is_a($this->_result, 'PDOStatement');
	}

/**
 * Get the query log as an array.
 *
 * @param boolean $sorted Get the queries sorted by time taken, defaults to false.
 * @param boolean $clear If True the existing log will cleared.
 * @return array Array of queries run as an array
 */
	public function getLog($sorted = false, $clear = true) {
		if ($sorted) {
			$log = sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_queriesLog;
		}
		if ($clear) {
			$this->_queriesLog = array();
		}
		return array('log' => $log, 'count' => $this->_queriesCnt, 'time' => $this->_queriesTime);
	}

/**
 * Outputs the contents of the queries log. If in a non-CLI environment the sql_log element
 * will be rendered and output.  If in a CLI environment, a plain text log is generated.
 *
 * @param boolean $sorted Get the queries sorted by time taken, defaults to false.
 * @return void
 */
	public function showLog($sorted = false) {
		$log = $this->getLog($sorted, false);
		if (empty($log['log'])) {
			return;
		}
		if (PHP_SAPI != 'cli') {
			$controller = null;
			$View = new View($controller, false);
			$View->set('logs', array($this->configKeyName => $log));
			echo $View->element('sql_dump', array('_forced_from_dbo_' => true));
		} else {
			foreach ($log['log'] as $k => $i) {
				print (($k + 1) . ". {$i['query']} {$i['error']}\n");
			}
		}
	}

/**
 * Log given SQL query.
 *
 * @param string $sql SQL statement
 * @todo: Add hook to log errors instead of returning false
 */
	public function logQuery($sql) {
		$this->_queriesCnt++;
		$this->_queriesTime += $this->took;
		$this->_queriesLog[] = array(
			'query'		=> $sql,
			'error'		=> $this->error,
			'affected'	=> $this->affected,
			'numRows'	=> $this->numRows,
			'took'		=> $this->took
		);
		if (count($this->_queriesLog) > $this->_queriesLogMax) {
			array_pop($this->_queriesLog);
		}
		if ($this->error) {
			return false;
		}
	}

/**
 * Output information about an SQL query. The SQL statement, number of rows in resultset,
 * and execution time in microseconds. If the query fails, an error is output instead.
 *
 * @param string $sql Query to show information on.
 */
	public function showQuery($sql) {
		$error = $this->error;
		if (strlen($sql) > 200 && !$this->fullDebug && Configure::read('debug') > 1) {
			$sql = substr($sql, 0, 200) . '[...]';
		}
		if (Configure::read('debug') > 0) {
			$out = null;
			if ($error) {
				trigger_error('<span style="color:Red;text-align:left"><b>' . __d('cake_dev', 'SQL Error:') . "</b> {$this->error}</span>", E_USER_WARNING);
			} else {
				$out = ('<small>[' . __d('cake_dev', 'Aff:%s Num:%s Took:%sms', $this->affected, $this->numRows, $this->took) . ']</small>');
			}
			pr(sprintf('<p style="text-align:left"><b>' . __d('cake_dev', 'Query:') . '</b> %s %s</p>', $sql, $out));
		}
	}

/**
 * Gets full table name including prefix
 *
 * @param mixed $model Either a Model object or a string table name.
 * @param boolean $quote Whether you want the table name quoted.
 * @return string Full quoted table name
 */
	public function fullTableName($model, $quote = true) {
		if (is_object($model)) {
			$table = $model->tablePrefix . $model->table;
		} elseif (isset($this->config['prefix'])) {
			$table = $this->config['prefix'] . strval($model);
		} else {
			$table = strval($model);
		}
		if ($quote) {
			return $this->name($table);
		}
		return $table;
	}

/**
 * The "C" in CRUD
 *
 * Creates new records in the database.
 *
 * @param Model $model Model object that the record is for.
 * @param array $fields An array of field names to insert. If null, $model->data will be
 *   used to generate field names.
 * @param array $values An array of values with keys matching the fields. If null, $model->data will
 *   be used to generate values.
 * @return boolean Success
 */
	public function create($model, $fields = null, $values = null) {
		$id = null;

		if ($fields == null) {
			unset($fields, $values);
			$fields = array_keys($model->data);
			$values = array_values($model->data);
		}
		$count = count($fields);

		for ($i = 0; $i < $count; $i++) {
			$valueInsert[] = $this->value($values[$i], $model->getColumnType($fields[$i]), false);
		}
		for ($i = 0; $i < $count; $i++) {
			$fieldInsert[] = $this->name($fields[$i]);
			if ($fields[$i] == $model->primaryKey) {
				$id = $values[$i];
			}
		}
		$query = array(
			'table' => $this->fullTableName($model),
			'fields' => implode(', ', $fieldInsert),
			'values' => implode(', ', $valueInsert)
		);

		if ($this->execute($this->renderStatement('create', $query))) {
			if (empty($id)) {
				$id = $this->lastInsertId($this->fullTableName($model, false), $model->primaryKey);
			}
			$model->setInsertID($id);
			$model->id = $id;
			return true;
		}
		$model->onError();
		return false;
	}

/**
 * The "R" in CRUD
 *
 * Reads record(s) from the database.
 *
 * @param Model $model A Model object that the query is for.
 * @param array $queryData An array of queryData information containing keys similar to Model::find()
 * @param integer $recursive Number of levels of association
 * @return mixed boolean false on error/failure.  An array of results on success.
 */
	public function read($model, $queryData = array(), $recursive = null) {
		$queryData = $this->__scrubQueryData($queryData);

		$null = null;
		$array = array();
		$linkedModels = array();
		$this->__bypass = false;

		if ($recursive === null && isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}

		if (!is_null($recursive)) {
			$_recursive = $model->recursive;
			$model->recursive = $recursive;
		}

		if (!empty($queryData['fields'])) {
			$this->__bypass = true;
			$queryData['fields'] = $this->fields($model, null, $queryData['fields']);
		} else {
			$queryData['fields'] = $this->fields($model);
		}

		$_associations = $model->associations();

		if ($model->recursive == -1) {
			$_associations = array();
		} elseif ($model->recursive == 0) {
			unset($_associations[2], $_associations[3]);
		}

		foreach ($_associations as $type) {
			foreach ($model->{$type} as $assoc => $assocData) {
				$linkModel = $model->{$assoc};
				$external = isset($assocData['external']);

				$linkModel->getDataSource();
				if ($model->useDbConfig === $linkModel->useDbConfig) {
					if (true === $this->generateAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null)) {
						$linkedModels[$type . '/' . $assoc] = true;
					}
				}
			}
		}

		$query = trim($this->generateAssociationQuery($model, null, null, null, null, $queryData, false, $null));

		$resultSet = $this->fetchAll($query, $model->cacheQueries);
		if ($resultSet === false) {
			$model->onError();
			return false;
		}

		$filtered = $this->_filterResults($resultSet, $model);

		if ($model->recursive > -1) {
			foreach ($_associations as $type) {
				foreach ($model->{$type} as $assoc => $assocData) {
					$linkModel = $model->{$assoc};

					if (!isset($linkedModels[$type . '/' . $assoc])) {
						if ($model->useDbConfig === $linkModel->useDbConfig) {
							$db = $this;
						} else {
							$db = ConnectionManager::getDataSource($linkModel->useDbConfig);
						}
					} elseif ($model->recursive > 1 && ($type === 'belongsTo' || $type === 'hasOne')) {
						$db = $this;
					}

					if (isset($db) && method_exists($db, 'queryAssociation')) {
						$stack = array($assoc);
						$db->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $array, true, $resultSet, $model->recursive - 1, $stack);
						unset($db);

						if ($type === 'hasMany') {
							$filtered[] = $assoc;
						}
					}
				}
			}
			$this->_filterResults($resultSet, $model, $filtered);
		}

		if (!is_null($recursive)) {
			$model->recursive = $_recursive;
		}
		return $resultSet;
	}

/**
 * Passes association results thru afterFind filters of corresponding model
 *
 * @param array $results Reference of resultset to be filtered
 * @param object $model Instance of model to operate against
 * @param array $filtered List of classes already filtered, to be skipped
 * @return array Array of results that have been filtered through $model->afterFind
 */
	protected function _filterResults(&$results, Model $model, $filtered = array()) {
		$current = current($results);
		if (!is_array($current)) {
			return array();
		}
		$keys = array_diff(array_keys($current), $filtered, array($model->alias));
		$filtering = array();
		foreach ($keys as $className) {
			if (!isset($model->{$className}) || !is_object($model->{$className})) {
				continue;
			}
			$linkedModel = $model->{$className};
			$filtering[] = $className;
			foreach ($results as &$result) {
				$data = $linkedModel->afterFind(array(array($className => $result[$className])), false);
				if (isset($data[0][$className])) {
					$result[$className] = $data[0][$className];
				}
			}
		}
		return $filtering;
	}

/**
 * Queries associations.  Used to fetch results on recursive models.
 *
 * @param Model $model Primary Model object
 * @param Model $linkModel Linked model that
 * @param string $type Association type, one of the model association types ie. hasMany
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param array $queryData
 * @param boolean $external Whether or not the association query is on an external datasource.
 * @param array $resultSet Existing results
 * @param integer $recursive Number of levels of association
 * @param array $stack
 */
	public function queryAssociation($model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive, $stack) {
		if ($query = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet)) {
			if (!is_array($resultSet)) {
				if (Configure::read('debug') > 0) {
					echo '<div style = "font: Verdana bold 12px; color: #FF0000">' . __d('cake_dev', 'SQL Error in model %s:', $model->alias) . ' ';
					if (isset($this->error) && $this->error != null) {
						echo $this->error;
					}
					echo '</div>';
				}
				return null;
			}

			if ($type === 'hasMany' && empty($assocData['limit']) && !empty($assocData['foreignKey'])) {
				$ins = $fetch = array();
				foreach ($resultSet as &$result) {
					if ($in = $this->insertQueryData('{$__cakeID__$}', $result, $association, $assocData, $model, $linkModel, $stack)) {
						$ins[] = $in;
					}
				}

				if (!empty($ins)) {
					$ins = array_unique($ins);
					$fetch = $this->fetchAssociated($model, $query, $ins);
				}

				if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {
						foreach ($linkModel->associations() as $type1) {
							foreach ($linkModel->{$type1} as $assoc1 => $assocData1) {
								$deepModel = $linkModel->{$assoc1};
								$tmpStack = $stack;
								$tmpStack[] = $assoc1;

								if ($linkModel->useDbConfig === $deepModel->useDbConfig) {
									$db = $this;
								} else {
									$db = ConnectionManager::getDataSource($deepModel->useDbConfig);
								}
								$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1, $tmpStack);
							}
						}
					}
				}
				$this->_filterResults($fetch, $model);
				return $this->__mergeHasMany($resultSet, $fetch, $association, $model, $linkModel);
			} elseif ($type === 'hasAndBelongsToMany') {
				$ins = $fetch = array();
				foreach ($resultSet as &$result) {
					if ($in = $this->insertQueryData('{$__cakeID__$}', $result, $association, $assocData, $model, $linkModel, $stack)) {
						$ins[] = $in;
					}
				}
				if (!empty($ins)) {
					$ins = array_unique($ins);
					if (count($ins) > 1) {
						$query = str_replace('{$__cakeID__$}', '(' .implode(', ', $ins) .')', $query);
						$query = str_replace('= (', 'IN (', $query);
					} else {
						$query = str_replace('{$__cakeID__$}', $ins[0], $query);
					}

					$query = str_replace(' WHERE 1 = 1', '', $query);
				}

				$foreignKey = $model->hasAndBelongsToMany[$association]['foreignKey'];
				$joinKeys = array($foreignKey, $model->hasAndBelongsToMany[$association]['associationForeignKey']);
				list($with, $habtmFields) = $model->joinModel($model->hasAndBelongsToMany[$association]['with'], $joinKeys);
				$habtmFieldsCount = count($habtmFields);
				$q = $this->insertQueryData($query, null, $association, $assocData, $model, $linkModel, $stack);

				if ($q !== false) {
					$fetch = $this->fetchAll($q, $model->cacheQueries);
				} else {
					$fetch = null;
				}
			}

			$modelAlias = $model->alias;
			$modelPK = $model->primaryKey;
			foreach ($resultSet as &$row) {
				if ($type !== 'hasAndBelongsToMany') {
					$q = $this->insertQueryData($query, $row, $association, $assocData, $model, $linkModel, $stack);
					if ($q !== false) {
						$fetch = $this->fetchAll($q, $model->cacheQueries);
					} else {
						$fetch = null;
					}
				}
				$selfJoin = $linkModel->name === $model->name;

				if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {
						foreach ($linkModel->associations() as $type1) {
							foreach ($linkModel->{$type1} as $assoc1 => $assocData1) {
								$deepModel = $linkModel->{$assoc1};

								if ($type1 === 'belongsTo' || ($deepModel->alias === $modelAlias && $type === 'belongsTo') || ($deepModel->alias !== $modelAlias)) {
									$tmpStack = $stack;
									$tmpStack[] = $assoc1;
									if ($linkModel->useDbConfig == $deepModel->useDbConfig) {
										$db = $this;
									} else {
										$db = ConnectionManager::getDataSource($deepModel->useDbConfig);
									}
									$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1, $tmpStack);
								}
							}
						}
					}
					if ($type === 'hasAndBelongsToMany') {
						$uniqueIds = $merge = array();

						foreach ($fetch as $j => $data) {
							if (isset($data[$with]) && $data[$with][$foreignKey] === $row[$modelAlias][$modelPK]) {
								if ($habtmFieldsCount <= 2) {
									unset($data[$with]);
								}
								$merge[] = $data;
							}
						}
						if (empty($merge) && !isset($row[$association])) {
							$row[$association] = $merge;
						} else {
							$this->__mergeAssociation($row, $merge, $association, $type);
						}
					} else {
						$this->__mergeAssociation($row, $fetch, $association, $type, $selfJoin);
					}
					if (isset($row[$association])) {
						$row[$association] = $linkModel->afterFind($row[$association], false);
					}
				} else {
					$tempArray[0][$association] = false;
					$this->__mergeAssociation($row, $tempArray, $association, $type, $selfJoin);
				}
			}
		}
	}

/**
 * A more efficient way to fetch associations.	Woohoo!
 *
 * @param model $model Primary model object
 * @param string $query Association query
 * @param array $ids Array of IDs of associated records
 * @return array Association results
 */
	public function fetchAssociated($model, $query, $ids) {
		$query = str_replace('{$__cakeID__$}', implode(', ', $ids), $query);
		if (count($ids) > 1) {
			$query = str_replace('= (', 'IN (', $query);
		}
		return $this->fetchAll($query, $model->cacheQueries);
	}

/**
 * mergeHasMany - Merge the results of hasMany relations.
 *
 *
 * @param array $resultSet Data to merge into
 * @param array $merge Data to merge
 * @param string $association Name of Model being Merged
 * @param object $model Model being merged onto
 * @param object $linkModel Model being merged
 * @return void
 */
	function __mergeHasMany(&$resultSet, $merge, $association, $model, $linkModel) {
		$modelAlias = $model->alias;
		$modelPK = $model->primaryKey;
		$modelFK = $model->hasMany[$association]['foreignKey'];
		foreach ($resultSet as &$result) {
			if (!isset($result[$modelAlias])) {
				continue;
			}
			$merged = array();
			foreach ($merge as $data) {
				if ($result[$modelAlias][$modelPK] === $data[$association][$modelFK]) {
					if (count($data) > 1) {
						$data = array_merge($data[$association], $data);
						unset($data[$association]);
						foreach ($data as $key => $name) {
							if (is_numeric($key)) {
								$data[$association][] = $name;
								unset($data[$key]);
							}
						}
						$merged[] = $data;
					} else {
						$merged[] = $data[$association];
					}
				}
			}
			$result = Set::pushDiff($result, array($association => $merged));
		}
	}

/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @param unknown_type $merge
 * @param unknown_type $association
 * @param unknown_type $type
 * @param boolean $selfJoin
 * @access private
 */
	function __mergeAssociation(&$data, &$merge, $association, $type, $selfJoin = false) {
		if (isset($merge[0]) && !isset($merge[0][$association])) {
			$association = Inflector::pluralize($association);
		}

		if ($type === 'belongsTo' || $type === 'hasOne') {
			if (isset($merge[$association])) {
				$data[$association] = $merge[$association][0];
			} else {
				if (count($merge[0][$association]) > 1) {
					foreach ($merge[0] as $assoc => $data2) {
						if ($assoc !== $association) {
							$merge[0][$association][$assoc] = $data2;
						}
					}
				}
				if (!isset($data[$association])) {
					if ($merge[0][$association] != null) {
						$data[$association] = $merge[0][$association];
					} else {
						$data[$association] = array();
					}
				} else {
					if (is_array($merge[0][$association])) {
						foreach ($data[$association] as $k => $v) {
							if (!is_array($v)) {
								$dataAssocTmp[$k] = $v;
							}
						}

						foreach ($merge[0][$association] as $k => $v) {
							if (!is_array($v)) {
								$mergeAssocTmp[$k] = $v;
							}
						}
						$dataKeys = array_keys($data);
						$mergeKeys = array_keys($merge[0]);

						if ($mergeKeys[0] === $dataKeys[0] || $mergeKeys === $dataKeys) {
							$data[$association][$association] = $merge[0][$association];
						} else {
							$diff = Set::diff($dataAssocTmp, $mergeAssocTmp);
							$data[$association] = array_merge($merge[0][$association], $diff);
						}
					} elseif ($selfJoin && array_key_exists($association, $merge[0])) {
						$data[$association] = array_merge($data[$association], array($association => array()));
					}
				}
			}
		} else {
			if (isset($merge[0][$association]) && $merge[0][$association] === false) {
				if (!isset($data[$association])) {
					$data[$association] = array();
				}
			} else {
				foreach ($merge as $i => $row) {
					if (count($row) === 1) {
						if (empty($data[$association]) || (isset($data[$association]) && !in_array($row[$association], $data[$association]))) {
							$data[$association][] = $row[$association];
						}
					} elseif (!empty($row)) {
						$tmp = array_merge($row[$association], $row);
						unset($tmp[$association]);
						$data[$association][] = $tmp;
					}
				}
			}
		}
	}

/**
 * Generates an array representing a query or part of a query from a single model or two associated models
 *
 * @param Model $model
 * @param Model $linkModel
 * @param string $type
 * @param string $association
 * @param array $assocData
 * @param array $queryData
 * @param boolean $external
 * @param array $resultSet
 * @return mixed
 */
	public function generateAssociationQuery($model, $linkModel, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet) {
		$queryData = $this->__scrubQueryData($queryData);
		$assocData = $this->__scrubQueryData($assocData);
		$modelAlias = $model->alias;

		if (empty($queryData['fields'])) {
			$queryData['fields'] = $this->fields($model, $modelAlias);
		} elseif (!empty($model->hasMany) && $model->recursive > -1) {
			$assocFields = $this->fields($model, $modelAlias, array("{$modelAlias}.{$model->primaryKey}"));
			$passedFields = $this->fields($model, $modelAlias, $queryData['fields']);
			if (count($passedFields) === 1) {
				if (strpos($passedFields[0], $assocFields[0]) === false && !preg_match('/^[a-z]+\(/i', $passedFields[0])) {
					$queryData['fields'] = array_merge($passedFields, $assocFields);
				} else {
					$queryData['fields'] = $passedFields;
				}
			} else {
				$queryData['fields'] = array_merge($passedFields, $assocFields);
			}
			unset($assocFields, $passedFields);
		}

		if ($linkModel === null) {
			return $this->buildStatement(
				array(
					'fields' => array_unique($queryData['fields']),
					'table' => $this->fullTableName($model),
					'alias' => $modelAlias,
					'limit' => $queryData['limit'],
					'offset' => $queryData['offset'],
					'joins' => $queryData['joins'],
					'conditions' => $queryData['conditions'],
					'order' => $queryData['order'],
					'group' => $queryData['group']
				),
				$model
			);
		}
		if ($external && !empty($assocData['finderQuery'])) {
			return $assocData['finderQuery'];
		}

		$self = $model->name === $linkModel->name;
		$fields = array();

		if ($external || (in_array($type, array('hasOne', 'belongsTo')) && $this->__bypass === false)) {
			$fields = $this->fields($linkModel, $association, $assocData['fields']);
		}
		if (empty($assocData['offset']) && !empty($assocData['page'])) {
			$assocData['offset'] = ($assocData['page'] - 1) * $assocData['limit'];
		}
		$assocData['limit'] = $this->limit($assocData['limit'], $assocData['offset']);

		switch ($type) {
			case 'hasOne':
			case 'belongsTo':
				$conditions = $this->__mergeConditions(
					$assocData['conditions'],
					$this->getConstraint($type, $model, $linkModel, $association, array_merge($assocData, compact('external', 'self')))
				);

				if (!$self && $external) {
					foreach ($conditions as $key => $condition) {
						if (is_numeric($key) && strpos($condition, $modelAlias . '.') !== false) {
							unset($conditions[$key]);
						}
					}
				}

				if ($external) {
					$query = array_merge($assocData, array(
						'conditions' => $conditions,
						'table' => $this->fullTableName($linkModel),
						'fields' => $fields,
						'alias' => $association,
						'group' => null
					));
					$query += array('order' => $assocData['order'], 'limit' => $assocData['limit']);
				} else {
					$join = array(
						'table' => $this->fullTableName($linkModel),
						'alias' => $association,
						'type' => isset($assocData['type']) ? $assocData['type'] : 'LEFT',
						'conditions' => trim($this->conditions($conditions, true, false, $model))
					);
					$queryData['fields'] = array_merge($queryData['fields'], $fields);

					if (!empty($assocData['order'])) {
						$queryData['order'][] = $assocData['order'];
					}
					if (!in_array($join, $queryData['joins'])) {
						$queryData['joins'][] = $join;
					}
					return true;
				}
			break;
			case 'hasMany':
				$assocData['fields'] = $this->fields($linkModel, $association, $assocData['fields']);
				if (!empty($assocData['foreignKey'])) {
					$assocData['fields'] = array_merge($assocData['fields'], $this->fields($linkModel, $association, array("{$association}.{$assocData['foreignKey']}")));
				}
				$query = array(
					'conditions' => $this->__mergeConditions($this->getConstraint('hasMany', $model, $linkModel, $association, $assocData), $assocData['conditions']),
					'fields' => array_unique($assocData['fields']),
					'table' => $this->fullTableName($linkModel),
					'alias' => $association,
					'order' => $assocData['order'],
					'limit' => $assocData['limit'],
					'group' => null
				);
			break;
			case 'hasAndBelongsToMany':
				$joinFields = array();
				$joinAssoc = null;

				if (isset($assocData['with']) && !empty($assocData['with'])) {
					$joinKeys = array($assocData['foreignKey'], $assocData['associationForeignKey']);
					list($with, $joinFields) = $model->joinModel($assocData['with'], $joinKeys);

					$joinTbl = $this->fullTableName($model->{$with});
					$joinAlias = $joinTbl;

					if (is_array($joinFields) && !empty($joinFields)) {
						$joinAssoc = $joinAlias = $model->{$with}->alias;
						$joinFields = $this->fields($model->{$with}, $joinAlias, $joinFields);
					} else {
						$joinFields = array();
					}
				} else {
					$joinTbl = $this->fullTableName($assocData['joinTable']);
					$joinAlias = $joinTbl;
				}
				$query = array(
					'conditions' => $assocData['conditions'],
					'limit' => $assocData['limit'],
					'table' => $this->fullTableName($linkModel),
					'alias' => $association,
					'fields' => array_merge($this->fields($linkModel, $association, $assocData['fields']), $joinFields),
					'order' => $assocData['order'],
					'group' => null,
					'joins' => array(array(
						'table' => $joinTbl,
						'alias' => $joinAssoc,
						'conditions' => $this->getConstraint('hasAndBelongsToMany', $model, $linkModel, $joinAlias, $assocData, $association)
					))
				);
			break;
		}
		if (isset($query)) {
			return $this->buildStatement($query, $model);
		}
		return null;
	}

/**
 * Returns a conditions array for the constraint between two models
 *
 * @param string $type Association type
 * @param object $model Model object
 * @param array $association Association array
 * @return array Conditions array defining the constraint between $model and $association
 */
	public function getConstraint($type, $model, $linkModel, $alias, $assoc, $alias2 = null) {
		$assoc += array('external' => false, 'self' => false);

		if (empty($assoc['foreignKey'])) {
			return array();
		}

		switch (true) {
			case ($assoc['external'] && $type === 'hasOne'):
				return array("{$alias}.{$assoc['foreignKey']}" => '{$__cakeID__$}');
			case ($assoc['external'] && $type === 'belongsTo'):
				return array("{$alias}.{$linkModel->primaryKey}" => '{$__cakeForeignKey__$}');
			case (!$assoc['external'] && $type === 'hasOne'):
				return array("{$alias}.{$assoc['foreignKey']}" => $this->identifier("{$model->alias}.{$model->primaryKey}"));
			case (!$assoc['external'] && $type === 'belongsTo'):
				return array("{$model->alias}.{$assoc['foreignKey']}" => $this->identifier("{$alias}.{$linkModel->primaryKey}"));
			case ($type === 'hasMany'):
				return array("{$alias}.{$assoc['foreignKey']}" => array('{$__cakeID__$}'));
			case ($type === 'hasAndBelongsToMany'):
				return array(
					array("{$alias}.{$assoc['foreignKey']}" => '{$__cakeID__$}'),
					array("{$alias}.{$assoc['associationForeignKey']}" => $this->identifier("{$alias2}.{$linkModel->primaryKey}"))
				);
		}
		return array();
	}

/**
 * Builds and generates a JOIN statement from an array.	 Handles final clean-up before conversion.
 *
 * @param array $join An array defining a JOIN statement in a query
 * @return string An SQL JOIN statement to be used in a query
 * @access public
 * @see DboSource::renderJoinStatement()
 * @see DboSource::buildStatement()
 */
	public function buildJoinStatement($join) {
		$data = array_merge(array(
			'type' => null,
			'alias' => null,
			'table' => 'join_table',
			'conditions' => array()
		), $join);

		if (!empty($data['alias'])) {
			$data['alias'] = $this->alias . $this->name($data['alias']);
		}
		if (!empty($data['conditions'])) {
			$data['conditions'] = trim($this->conditions($data['conditions'], true, false));
		}
		return $this->renderJoinStatement($data);
	}

/**
 * Builds and generates an SQL statement from an array.	 Handles final clean-up before conversion.
 *
 * @param array $query An array defining an SQL query
 * @param object $model The model object which initiated the query
 * @return string An executable SQL statement
 * @access public
 * @see DboSource::renderStatement()
 */
	public function buildStatement($query, $model) {
		$query = array_merge(array('offset' => null, 'joins' => array()), $query);
		if (!empty($query['joins'])) {
			$count = count($query['joins']);
			for ($i = 0; $i < $count; $i++) {
				if (is_array($query['joins'][$i])) {
					$query['joins'][$i] = $this->buildJoinStatement($query['joins'][$i]);
				}
			}
		}
		return $this->renderStatement('select', array(
			'conditions' => $this->conditions($query['conditions'], true, true, $model),
			'fields' => implode(', ', $query['fields']),
			'table' => $query['table'],
			'alias' => $this->alias . $this->name($query['alias']),
			'order' => $this->order($query['order'], 'ASC', $model),
			'limit' => $this->limit($query['limit'], $query['offset']),
			'joins' => implode(' ', $query['joins']),
			'group' => $this->group($query['group'], $model)
		));
	}

/**
 * Renders a final SQL JOIN statement
 *
 * @param array $data
 * @return string
 */
	public function renderJoinStatement($data) {
		extract($data);
		return trim("{$type} JOIN {$table} {$alias} ON ({$conditions})");
	}

/**
 * Renders a final SQL statement by putting together the component parts in the correct order
 *
 * @param string $type type of query being run.  e.g select, create, update, delete, schema, alter.
 * @param array $data Array of data to insert into the query.
 * @return string Rendered SQL expression to be run.
 */
	public function renderStatement($type, $data) {
		extract($data);
		$aliases = null;

		switch (strtolower($type)) {
			case 'select':
				return "SELECT {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order} {$limit}";
			case 'create':
				return "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
			case 'update':
				if (!empty($alias)) {
					$aliases = "{$this->alias}{$alias} {$joins} ";
				}
				return "UPDATE {$table} {$aliases}SET {$fields} {$conditions}";
			case 'delete':
				if (!empty($alias)) {
					$aliases = "{$this->alias}{$alias} {$joins} ";
				}
				return "DELETE {$alias} FROM {$table} {$aliases}{$conditions}";
			case 'schema':
				foreach (array('columns', 'indexes', 'tableParameters') as $var) {
					if (is_array(${$var})) {
						${$var} = "\t" . join(",\n\t", array_filter(${$var}));
					} else {
						${$var} = '';
					}
				}
				if (trim($indexes) !== '') {
					$columns .= ',';
				}
				return "CREATE TABLE {$table} (\n{$columns}{$indexes}){$tableParameters};";
			case 'alter':
				return;
		}
	}

/**
 * Merges a mixed set of string/array conditions
 *
 * @param mixed $query
 * @param mixed $assoc
 * @return array
 */
	private function __mergeConditions($query, $assoc) {
		if (empty($assoc)) {
			return $query;
		}

		if (is_array($query)) {
			return array_merge((array)$assoc, $query);
		}

		if (!empty($query)) {
			$query = array($query);
			if (is_array($assoc)) {
				$query = array_merge($query, $assoc);
			} else {
				$query[] = $assoc;
			}
			return $query;
		}

		return $assoc;
	}

/**
 * Generates and executes an SQL UPDATE statement for given model, fields, and values.
 * For databases that do not support aliases in UPDATE queries.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return boolean Success
 */
	public function update($model, $fields = array(), $values = null, $conditions = null) {
		if ($values == null) {
			$combined = $fields;
		} else {
			$combined = array_combine($fields, $values);
		}

		$fields = implode(', ', $this->_prepareUpdateFields($model, $combined, empty($conditions)));

		$alias = $joins = null;
		$table = $this->fullTableName($model);
		$conditions = $this->_matchRecords($model, $conditions);

		if ($conditions === false) {
			return false;
		}
		$query = compact('table', 'alias', 'joins', 'fields', 'conditions');

		if (!$this->execute($this->renderStatement('update', $query))) {
			$model->onError();
			return false;
		}
		return true;
	}

/**
 * Quotes and prepares fields and values for an SQL UPDATE statement
 *
 * @param Model $model
 * @param array $fields
 * @param boolean $quoteValues If values should be quoted, or treated as SQL snippets
 * @param boolean $alias Include the model alias in the field name
 * @return array Fields and values, quoted and preparted
 */
	protected function _prepareUpdateFields($model, $fields, $quoteValues = true, $alias = false) {
		$quotedAlias = $this->startQuote . $model->alias . $this->endQuote;

		$updates = array();
		foreach ($fields as $field => $value) {
			if ($alias && strpos($field, '.') === false) {
				$quoted = $model->escapeField($field);
			} elseif (!$alias && strpos($field, '.') !== false) {
				$quoted = $this->name(str_replace($quotedAlias . '.', '', str_replace(
					$model->alias . '.', '', $field
				)));
			} else {
				$quoted = $this->name($field);
			}

			if ($value === null) {
				$updates[] = $quoted . ' = NULL';
				continue;
			}
			$update = $quoted . ' = ';

			if ($quoteValues) {
				$update .= $this->value($value, $model->getColumnType($field), false);
			} elseif (!$alias) {
				$update .= str_replace($quotedAlias . '.', '', str_replace(
					$model->alias . '.', '', $value
				));
			} else {
				$update .= $value;
			}
			$updates[] =  $update;
		}
		return $updates;
	}

/**
 * Generates and executes an SQL DELETE statement.
 * For databases that do not support aliases in UPDATE queries.
 *
 * @param Model $model
 * @param mixed $conditions
 * @return boolean Success
 */
	public function delete($model, $conditions = null) {
		$alias = $joins = null;
		$table = $this->fullTableName($model);
		$conditions = $this->_matchRecords($model, $conditions);

		if ($conditions === false) {
			return false;
		}

		if ($this->execute($this->renderStatement('delete', compact('alias', 'table', 'joins', 'conditions'))) === false) {
			$model->onError();
			return false;
		}
		return true;
	}

/**
 * Gets a list of record IDs for the given conditions.	Used for multi-record updates and deletes
 * in databases that do not support aliases in UPDATE/DELETE queries.
 *
 * @param Model $model
 * @param mixed $conditions
 * @return array List of record IDs
 */
	protected function _matchRecords($model, $conditions = null) {
		if ($conditions === true) {
			$conditions = $this->conditions(true);
		} elseif ($conditions === null) {
			$conditions = $this->conditions($this->defaultConditions($model, $conditions, false), true, true, $model);
		} else {
			$noJoin = true;
			foreach ($conditions as $field => $value) {
				$originalField = $field;
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field);
					$field = ltrim($field, $this->startQuote);
					$field = rtrim($field, $this->endQuote);
				}
				if (!$model->hasField($field)) {
					$noJoin = false;
					break;
				}
				if ($field !== $originalField) {
					$conditions[$field] = $value;
					unset($conditions[$originalField]);
				}
			}
			if ($noJoin === true) {
				return $this->conditions($conditions);
			}
			$idList = $model->find('all', array(
				'fields' => "{$model->alias}.{$model->primaryKey}",
				'conditions' => $conditions
			));

			if (empty($idList)) {
				return false;
			}
			$conditions = $this->conditions(array(
				$model->primaryKey => Set::extract($idList, "{n}.{$model->alias}.{$model->primaryKey}")
			));
		}
		return $conditions;
	}

/**
 * Returns an array of SQL JOIN fragments from a model's associations
 *
 * @param object $model
 * @return array
 */
	protected function _getJoins($model) {
		$join = array();
		$joins = array_merge($model->getAssociated('hasOne'), $model->getAssociated('belongsTo'));

		foreach ($joins as $assoc) {
			if (isset($model->{$assoc}) && $model->useDbConfig == $model->{$assoc}->useDbConfig && $model->{$assoc}->getDataSource()) {
				$assocData = $model->getAssociated($assoc);
				$join[] = $this->buildJoinStatement(array(
					'table' => $this->fullTableName($model->{$assoc}),
					'alias' => $assoc,
					'type' => isset($assocData['type']) ? $assocData['type'] : 'LEFT',
					'conditions' => trim($this->conditions(
						$this->__mergeConditions($assocData['conditions'], $this->getConstraint($assocData['association'], $model, $model->{$assoc}, $assoc, $assocData)),
						true, false, $model
					))
				));
			}
		}
		return $join;
	}

/**
 * Returns an SQL calculation, i.e. COUNT() or MAX()
 *
 * @param model $model
 * @param string $func Lowercase name of SQL function, i.e. 'count' or 'max'
 * @param array $params Function parameters (any values must be quoted manually)
 * @return string An SQL calculation function
 */
	public function calculate($model, $func, $params = array()) {
		$params = (array)$params;

		switch (strtolower($func)) {
			case 'count':
				if (!isset($params[0])) {
					$params[0] = '*';
				}
				if (!isset($params[1])) {
					$params[1] = 'count';
				}
				if (is_object($model) && $model->isVirtualField($params[0])){
					$arg = $this->__quoteFields($model->getVirtualField($params[0]));
				} else {
					$arg = $this->name($params[0]);
				}
				return 'COUNT(' . $arg . ') AS ' . $this->name($params[1]);
			case 'max':
			case 'min':
				if (!isset($params[1])) {
					$params[1] = $params[0];
				}
				if (is_object($model) && $model->isVirtualField($params[0])) {
					$arg = $this->__quoteFields($model->getVirtualField($params[0]));
				} else {
					$arg = $this->name($params[0]);
				}
				return strtoupper($func) . '(' . $arg . ') AS ' . $this->name($params[1]);
			break;
		}
	}

/**
 * Deletes all the records in a table and resets the count of the auto-incrementing
 * primary key, where applicable.
 *
 * @param mixed $table A string or model class representing the table to be truncated
 * @return boolean	SQL TRUNCATE TABLE statement, false if not applicable.
 */
	public function truncate($table) {
		return $this->execute('TRUNCATE TABLE ' . $this->fullTableName($table));
	}

/**
 * Begin a transaction
 *
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function begin() {
		if ($this->_transactionStarted || $this->_connection->beginTransaction()) {
			$this->_transactionStarted = true;
			$this->_transactionNesting++;
			return true;
		}
		return false;
	}

/**
 * Commit a transaction
 *
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function commit() {
		if ($this->_transactionStarted) {
			$this->_transactionNesting--;
			if ($this->_transactionNesting <= 0) {
				$this->_transactionStarted = false;
				$this->_transactionNesting = 0;
				return $this->_connection->commit();
			}
			return true;
		}
		return false;
	}

/**
 * Rollback a transaction
 *
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function rollback() {
		if ($this->_transactionStarted && $this->_connection->rollBack()) {
			$this->_transactionStarted = false;
			$this->_transactionNesting = 0;
			return true;
		}
		return false;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
	function lastInsertId($source = null) {
		return $this->_connection->lastInsertId();
	}

/**
 * Creates a default set of conditions from the model if $conditions is null/empty.
 * If conditions are supplied then they will be returned.  If a model doesn't exist and no conditions
 * were provided either null or false will be returned based on what was input.
 *
 * @param object $model
 * @param mixed $conditions Array of conditions, conditions string, null or false. If an array of conditions,
 *   or string conditions those conditions will be returned.  With other values the model's existance will be checked.
 *   If the model doesn't exist a null or false will be returned depending on the input value.
 * @param boolean $useAlias Use model aliases rather than table names when generating conditions
 * @return mixed Either null, false, $conditions or an array of default conditions to use.
 * @see DboSource::update()
 * @see DboSource::conditions()
 */
	public function defaultConditions($model, $conditions, $useAlias = true) {
		if (!empty($conditions)) {
			return $conditions;
		}
		$exists = $model->exists();
		if (!$exists && $conditions !== null) {
			return false;
		} elseif (!$exists) {
			return null;
		}
		$alias = $model->alias;

		if (!$useAlias) {
			$alias = $this->fullTableName($model, false);
		}
		return array("{$alias}.{$model->primaryKey}" => $model->getID());
	}

/**
 * Returns a key formatted like a string Model.fieldname(i.e. Post.title, or Country.name)
 *
 * @param unknown_type $model
 * @param unknown_type $key
 * @param unknown_type $assoc
 * @return string
 */
	public function resolveKey($model, $key, $assoc = null) {
		if (empty($assoc)) {
			$assoc = $model->alias;
		}
		if (strpos('.', $key) !== false) {
			return $this->name($model->alias) . '.' . $this->name($key);
		}
		return $key;
	}

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data
 * @return array
 */
	public function __scrubQueryData($data) {
		static $base = null;
		if ($base === null) {
			$base = array_fill_keys(array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group'), array());
		}
		return (array)$data + $base;
	}

/**
 * Converts model virtual fields into sql expressions to be fetched later
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields virtual fields to be used on query
 * @return array
 */
	protected function _constructVirtualFields($model, $alias, $fields) {
		$virtual = array();
		foreach ($fields as $field) {
			$virtualField = $this->name($alias . $this->virtualFieldSeparator . $field);
			$expression = $this->__quoteFields($model->getVirtualField($field));
			$virtual[] = '(' . $expression . ") {$this->alias} {$virtualField}";
		}
		return $virtual;
	}

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields
 * @param boolean $quote If false, returns fields array unquoted
 * @return array
 */
	public function fields($model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}
		$cacheKey = array(
			$model->useDbConfig,
			$model->table,
			array_keys($model->schema()),
			$model->name,
			$model->getVirtualField(),
			$alias,
			$fields,
			$quote
		);
		$cacheKey = crc32(serialize($cacheKey));
		if ($return = $this->cacheMethod(__FUNCTION__, $cacheKey)) {
			return $return;
		}
		$allFields = empty($fields);
		if ($allFields) {
			$fields = array_keys($model->schema());
		} elseif (!is_array($fields)) {
			$fields = String::tokenize($fields);
		}
		$fields = array_values(array_filter($fields));
		$allFields = $allFields || in_array('*', $fields) || in_array($model->alias . '.*', $fields);

		$virtual = array();
		$virtualFields = $model->getVirtualField();
		if (!empty($virtualFields)) {
			$virtualKeys = array_keys($virtualFields);
			foreach ($virtualKeys as $field) {
				$virtualKeys[] = $model->alias . '.' . $field;
			}
			$virtual = ($allFields) ? $virtualKeys : array_intersect($virtualKeys, $fields);
			foreach ($virtual as $i => $field) {
				if (strpos($field, '.') !== false) {
					$virtual[$i] = str_replace($model->alias . '.', '', $field);
				}
				$fields = array_diff($fields, array($field));
			}
			$fields = array_values($fields);
		}

		if (!$quote) {
			if (!empty($virtual)) {
				$fields = array_merge($fields, $this->_constructVirtualFields($model, $alias, $virtual));
			}
			return $fields;
		}
		$count = count($fields);

		if ($count >= 1 && !in_array($fields[0], array('*', 'COUNT(*)'))) {
			for ($i = 0; $i < $count; $i++) {
				if (is_string($fields[$i]) && in_array($fields[$i], $virtual)) {
					unset($fields[$i]);
					continue;
				}
				if (is_object($fields[$i]) && isset($fields[$i]->type) && $fields[$i]->type === 'expression') {
					$fields[$i] = $fields[$i]->value;
				} elseif (preg_match('/^\(.*\)\s' . $this->alias . '.*/i', $fields[$i])){
					continue;
				} elseif (!preg_match('/^.+\\(.*\\)/', $fields[$i])) {
					$prepend = '';

					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend = 'DISTINCT ';
						$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
					}
					$dot = strpos($fields[$i], '.');

					if ($dot === false) {
						$prefix = !(
							strpos($fields[$i], ' ') !== false ||
							strpos($fields[$i], '(') !== false
						);
						$fields[$i] = $this->name(($prefix ? $alias . '.' : '') . $fields[$i]);
					} else {
						$value = array();
						if (strpos($fields[$i], ',') === false) {
							$build = explode('.', $fields[$i]);
							if (!Set::numeric($build)) {
								$fields[$i] = $this->name(implode('.', $build));
							}
						}
					}
					$fields[$i] = $prepend . $fields[$i];
				} elseif (preg_match('/\(([\.\w]+)\)/', $fields[$i], $field)) {
					if (isset($field[1])) {
						if (strpos($field[1], '.') === false) {
							$field[1] = $this->name($alias . '.' . $field[1]);
						} else {
							$field[0] = explode('.', $field[1]);
							if (!Set::numeric($field[0])) {
								$field[0] = implode('.', array_map(array(&$this, 'name'), $field[0]));
								$fields[$i] = preg_replace('/\(' . $field[1] . '\)/', '(' . $field[0] . ')', $fields[$i], 1);
							}
						}
					}
				}
			}
		}
		if (!empty($virtual)) {
			$fields = array_merge($fields, $this->_constructVirtualFields($model, $alias, $virtual));
		}
		return $this->cacheMethod(__FUNCTION__, $cacheKey, array_unique($fields));
	}

/**
 * Creates a WHERE clause by parsing given conditions data.  If an array or string
 * conditions are provided those conditions will be parsed and quoted.  If a boolean
 * is given it will be integer cast as condition.  Null will return 1 = 1.
 *
 * Results of this method are stored in a memory cache.  This improves performance, but
 * because the method uses a simple hashing algorithm it can infrequently have collisions.
 * Setting DboSource::$cacheMethods to false will disable the memory cache.
 *
 * @param mixed $conditions Array or string of conditions, or any value.
 * @param boolean $quoteValues If true, values should be quoted
 * @param boolean $where If true, "WHERE " will be prepended to the return value
 * @param Model $model A reference to the Model instance making the query
 * @return string SQL fragment
 */
	public function conditions($conditions, $quoteValues = true, $where = true, $model = null) {
		if (is_object($model)) {
			$cacheKey = array(
				$model->useDbConfig,
				$model->table,
				$model->schema(),
				$model->name,
				$model->getVirtualField(),
				$conditions,
				$quoteValues,
				$where
			);
		} else {
			$cacheKey = array($conditions, $quoteValues, $where);
		}
		$cacheKey = crc32(serialize($cacheKey));
		if ($return = $this->cacheMethod(__FUNCTION__, $cacheKey)) {
			return $return;
		}

		$clause = $out = '';

		if ($where) {
			$clause = ' WHERE ';
		}

		if (is_array($conditions) && !empty($conditions)) {
			$out = $this->conditionKeysToString($conditions, $quoteValues, $model);

			if (empty($out)) {
				return $this->cacheMethod(__FUNCTION__, $cacheKey, $clause . ' 1 = 1');
			}
			return $this->cacheMethod(__FUNCTION__, $cacheKey, $clause . implode(' AND ', $out));
		}
		if (is_bool($conditions)) {
			return $this->cacheMethod(__FUNCTION__, $cacheKey,  $clause . (int)$conditions . ' = 1');
		}

		if (empty($conditions) || trim($conditions) === '') {
			return $this->cacheMethod(__FUNCTION__, $cacheKey, $clause . '1 = 1');
		}
		$clauses = '/^WHERE\\x20|^GROUP\\x20BY\\x20|^HAVING\\x20|^ORDER\\x20BY\\x20/i';

		if (preg_match($clauses, $conditions, $match)) {
			$clause = '';
		}
		$conditions = $this->__quoteFields($conditions);
		return $this->cacheMethod(__FUNCTION__, $cacheKey, $clause . $conditions);
	}

/**
 * Creates a WHERE clause by parsing given conditions array.  Used by DboSource::conditions().
 *
 * @param array $conditions Array or string of conditions
 * @param boolean $quoteValues If true, values should be quoted
 * @param Model $model A reference to the Model instance making the query
 * @return string SQL fragment
 */
	public function conditionKeysToString($conditions, $quoteValues = true, $model = null) {
		$out = array();
		$data = $columnType = null;
		$bool = array('and', 'or', 'not', 'and not', 'or not', 'xor', '||', '&&');

		foreach ($conditions as $key => $value) {
			$join = ' AND ';
			$not = null;

			if (is_array($value)) {
				$valueInsert = (
					!empty($value) &&
					(substr_count($key, '?') === count($value) || substr_count($key, ':') === count($value))
				);
			}

			if (is_numeric($key) && empty($value)) {
				continue;
			} elseif (is_numeric($key) && is_string($value)) {
				$out[] = $not . $this->__quoteFields($value);
			} elseif ((is_numeric($key) && is_array($value)) || in_array(strtolower(trim($key)), $bool)) {
				if (in_array(strtolower(trim($key)), $bool)) {
					$join = ' ' . strtoupper($key) . ' ';
				} else {
					$key = $join;
				}
				$value = $this->conditionKeysToString($value, $quoteValues, $model);

				if (strpos($join, 'NOT') !== false) {
					if (strtoupper(trim($key)) === 'NOT') {
						$key = 'AND ' . trim($key);
					}
					$not = 'NOT ';
				}

				if (empty($value[1])) {
					if ($not) {
						$out[] = $not . '(' . $value[0] . ')';
					} else {
						$out[] = $value[0] ;
					}
				} else {
					$out[] = '(' . $not . '(' . implode(') ' . strtoupper($key) . ' (', $value) . '))';
				}
			} else {
				if (is_object($value) && isset($value->type)) {
					if ($value->type === 'identifier') {
						$data .= $this->name($key) . ' = ' . $this->name($value->value);
					} elseif ($value->type === 'expression') {
						if (is_numeric($key)) {
							$data .= $value->value;
						} else {
							$data .= $this->name($key) . ' = ' . $value->value;
						}
					}
				} elseif (is_array($value) && !empty($value) && !$valueInsert) {
					$keys = array_keys($value);
					if ($keys === array_values($keys)) {
						$count = count($value);
						if ($count === 1) {
							$data = $this->__quoteFields($key) . ' = (';
						} else {
							$data = $this->__quoteFields($key) . ' IN (';
						}
						if ($quoteValues) {
							if (is_object($model)) {
								$columnType = $model->getColumnType($key);
							}
							$data .= implode(', ', $this->value($value, $columnType));
						}
						$data .= ')';
					} else {
						$ret = $this->conditionKeysToString($value, $quoteValues, $model);
						if (count($ret) > 1) {
							$data = '(' . implode(') AND (', $ret) . ')';
						} elseif (isset($ret[0])) {
							$data = $ret[0];
						}
					}
				} elseif (is_numeric($key) && !empty($value)) {
					$data = $this->__quoteFields($value);
				} else {
					$data = $this->__parseKey($model, trim($key), $value);
				}

				if ($data != null) {
					$out[] = $data;
					$data = null;
				}
			}
		}
		return $out;
	}

/**
 * Extracts a Model.field identifier and an SQL condition operator from a string, formats
 * and inserts values, and composes them into an SQL snippet.
 *
 * @param Model $model Model object initiating the query
 * @param string $key An SQL key snippet containing a field and optional SQL operator
 * @param mixed $value The value(s) to be inserted in the string
 * @return string
 * @access private
 */
	function __parseKey($model, $key, $value) {
		$operatorMatch = '/^((' . implode(')|(', $this->__sqlOps);
		$operatorMatch .= '\\x20)|<[>=]?(?![^>]+>)\\x20?|[>=!]{1,3}(?!<)\\x20?)/is';
		$bound = (strpos($key, '?') !== false || (is_array($value) && strpos($key, ':') !== false));

		if (strpos($key, ' ') === false) {
			$operator = '=';
		} else {
			list($key, $operator) = explode(' ', trim($key), 2);

			if (!preg_match($operatorMatch, trim($operator)) && strpos($operator, ' ') !== false) {
				$key = $key . ' ' . $operator;
				$split = strrpos($key, ' ');
				$operator = substr($key, $split);
				$key = substr($key, 0, $split);
			}
		}

		$virtual = false;
		if (is_object($model) && $model->isVirtualField($key)) {
			$key = $this->__quoteFields($model->getVirtualField($key));
			$virtual = true;
		}

		$type = is_object($model) ? $model->getColumnType($key) : null;
		$null = $value === null || (is_array($value) && empty($value));

		if (strtolower($operator) === 'not') {
			$data = $this->conditionKeysToString(
				array($operator => array($key => $value)), true, $model
			);
			return $data[0];
		}

		$value = $this->value($value, $type);

		if (!$virtual && $key !== '?') {
			$isKey = (strpos($key, '(') !== false || strpos($key, ')') !== false);
			$key = $isKey ? $this->__quoteFields($key) : $this->name($key);
		}

		if ($bound) {
			return String::insert($key . ' ' . trim($operator), $value);
		}

		if (!preg_match($operatorMatch, trim($operator))) {
			$operator .= ' =';
		}
		$operator = trim($operator);

		if (is_array($value)) {
			$value = implode(', ', $value);

			switch ($operator) {
				case '=':
					$operator = 'IN';
				break;
				case '!=':
				case '<>':
					$operator = 'NOT IN';
				break;
			}
			$value = "({$value})";
		} elseif ($null) {
			switch ($operator) {
				case '=':
					$operator = 'IS';
				break;
				case '!=':
				case '<>':
					$operator = 'IS NOT';
				break;
			}
		}
		if ($virtual) {
			return "({$key}) {$operator} {$value}";
		}
		return "{$key} {$operator} {$value}";
	}

/**
 * Quotes Model.fields
 *
 * @param string $conditions
 * @return string or false if no match
 * @access private
 */
	function __quoteFields($conditions) {
		$start = $end = null;
		$original = $conditions;

		if (!empty($this->startQuote)) {
			$start = preg_quote($this->startQuote);
		}
		if (!empty($this->endQuote)) {
			$end = preg_quote($this->endQuote);
		}
		$conditions = str_replace(array($start, $end), '', $conditions);
		$conditions = preg_replace_callback('/(?:[\'\"][^\'\"\\\]*(?:\\\.[^\'\"\\\]*)*[\'\"])|([a-z0-9_' . $start . $end . ']*\\.[a-z0-9_' . $start . $end . ']*)/i', array(&$this, '__quoteMatchedField'), $conditions);

		if ($conditions !== null) {
			return $conditions;
		}
		return $original;
	}

/**
 * Auxiliary function to quote matches `Model.fields` from a preg_replace_callback call
 *
 * @param string matched string
 * @return string quoted strig
 * @access private
 */
	function __quoteMatchedField($match) {
		if (is_numeric($match[0])) {
			return $match[0];
		}
		return $this->name($match[0]);
	}

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param integer $limit Limit of results returned
 * @param integer $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	public function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'limit')) {
				$rt = ' LIMIT';
			}

			if ($offset) {
				$rt .= ' ' . $offset . ',';
			}

			$rt .= ' ' . $limit;
			return $rt;
		}
		return null;
	}

/**
 * Returns an ORDER BY clause as a string.
 *
 * @param string $key Field reference, as a key (i.e. Post.title)
 * @param string $direction Direction (ASC or DESC)
 * @param object $model model reference (used to look for virtual field)
 * @return string ORDER BY clause
 */
	public function order($keys, $direction = 'ASC', $model = null) {
		if (!is_array($keys)) {
			$keys = array($keys);
		}
		$keys = array_filter($keys);
		$result = array();
		while (!empty($keys)) {
			list($key, $dir) = each($keys);
			array_shift($keys);

			if (is_numeric($key)) {
				$key = $dir;
				$dir = $direction;
			}

			if (is_string($key) && strpos($key, ',') !== false && !preg_match('/\(.+\,.+\)/', $key)) {
				$key = array_map('trim', explode(',', $key));
			}
			if (is_array($key)) {
				//Flatten the array
				$key = array_reverse($key, true);
				foreach ($key as $k => $v) {
					if (is_numeric($k)) {
						array_unshift($keys, $v);
					} else {
						$keys = array($k => $v) + $keys;
					}
				}
				continue;
			} elseif (is_object($key) && isset($key->type) && $key->type === 'expression') {
				$result[] = $key->value;
				continue;
			}

			if (preg_match('/\\x20(ASC|DESC).*/i', $key, $_dir)) {
				$dir = $_dir[0];
				$key = preg_replace('/\\x20(ASC|DESC).*/i', '', $key);
			}

			$key = trim($key);

			if (is_object($model) && $model->isVirtualField($key)) {
				$key =  '(' . $this->__quoteFields($model->getVirtualField($key)) . ')';
			}

			if (strpos($key, '.')) {
				$key = preg_replace_callback('/([a-zA-Z0-9_]{1,})\\.([a-zA-Z0-9_]{1,})/', array(&$this, '__quoteMatchedField'), $key);
			}
			if (!preg_match('/\s/', $key) && strpos($key, '.') === false) {
				$key = $this->name($key);
			}
			$key .= ' ' . trim($dir);
			$result[] = $key;
		}
		if (!empty($result)) {
			return ' ORDER BY ' . implode(', ', $result);
		}
		return '';
	}

/**
 * Create a GROUP BY SQL clause
 *
 * @param string $group Group By Condition
 * @return mixed string condition or null
 */
	public function group($group, $model = null) {
		if ($group) {
			if (!is_array($group)) {
				$group = array($group);
			}
			foreach($group as $index => $key) {
				if ($model->isVirtualField($key)) {
					$group[$index] = '(' . $model->getVirtualField($key) . ')';
				}
			}
			$group = implode(', ', $group);
			return ' GROUP BY ' . $this->__quoteFields($group);
		}
		return null;
	}

/**
 * Disconnects database, kills the connection and says the connection is closed.
 *
 * @return void
 */
	public function close() {
		$this->disconnect();
	}

/**
 * Checks if the specified table contains any record matching specified SQL
 *
 * @param Model $model Model to search
 * @param string $sql SQL WHERE clause (condition only, not the "WHERE" part)
 * @return boolean True if the table has a matching record, else false
 */
	public function hasAny($Model, $sql) {
		$sql = $this->conditions($sql);
		$table = $this->fullTableName($Model);
		$alias = $this->alias . $this->name($Model->alias);
		$where = $sql ? "{$sql}" : ' WHERE 1 = 1';
		$id = $Model->escapeField();

		$out = $this->fetchRow("SELECT COUNT({$id}) {$this->alias}count FROM {$table} {$alias}{$where}");

		if (is_array($out)) {
			return $out[0]['count'];
		}
		return false;
	}

/**
 * Gets the length of a database-native column description, or null if no length
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return mixed An integer or string representing the length of the column
 */
	public function length($real) {
		if (!preg_match_all('/([\w\s]+)(?:\((\d+)(?:,(\d+))?\))?(\sunsigned)?(\szerofill)?/', $real, $result)) {
			trigger_error(__d('cake_dev', "FIXME: Can't parse field: " . $real), E_USER_WARNING);
			$col = str_replace(array(')', 'unsigned'), '', $real);
			$limit = null;

			if (strpos($col, '(') !== false) {
				list($col, $limit) = explode('(', $col);
			}
			if ($limit !== null) {
				return intval($limit);
			}
			return null;
		}

		$types = array(
			'int' => 1, 'tinyint' => 1, 'smallint' => 1, 'mediumint' => 1, 'integer' => 1, 'bigint' => 1
		);

		list($real, $type, $length, $offset, $sign, $zerofill) = $result;
		$typeArr = $type;
		$type = $type[0];
		$length = $length[0];
		$offset = $offset[0];

		$isFloat = in_array($type, array('dec', 'decimal', 'float', 'numeric', 'double'));
		if ($isFloat && $offset) {
			return $length . ',' . $offset;
		}

		if (($real[0] == $type) && (count($real) === 1)) {
			return null;
		}

		if (isset($types[$type])) {
			$length += $types[$type];
			if (!empty($sign)) {
				$length--;
			}
		} elseif (in_array($type, array('enum', 'set'))) {
			$length = 0;
			foreach ($typeArr as $key => $enumValue) {
				if ($key === 0) {
					continue;
				}
				$tmpLength = strlen($enumValue);
				if ($tmpLength > $length) {
					$length = $tmpLength;
				}
			}
		}
		return intval($length);
	}

/**
 * Translates between PHP boolean values and Database (faked) boolean values
 *
 * @param mixed $data Value to be translated
 * @return int Converted boolean value
 */
	public function boolean($data, $quote = false) {
		if ($quote) {
			return !empty($data) ? '1' : '0';
		}
		return !empty($data);
	}

/**
 * Inserts multiple values into a table
 *
 * @param string $table
 * @param string $fields
 * @param array $values
 */
	public function insertMulti($table, $fields, $values) {
		$table = $this->fullTableName($table);
		$holder = implode(',', array_fill(0, count($fields), '?'));
		$fields = implode(', ', array_map(array(&$this, 'name'), $fields));

		$count = count($values);
		$sql = "INSERT INTO {$table} ({$fields}) VALUES ({$holder})";
		$statement = $this->_connection->prepare($sql);
		$this->begin();
		for ($x = 0; $x < $count; $x++) {
			$statement->execute($values[$x]);
			$statement->closeCursor();
		}
		return $this->commit();
	}

/**
 * Returns an array of the indexes in given datasource name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	public function index($model) {
		return false;
	}

/**
 * Generate a database-native schema for the given Schema object
 *
 * @param object $schema An instance of a subclass of CakeSchema
 * @param string $tableName Optional.  If specified only the table name given will be generated.
 *   Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	public function createSchema($schema, $tableName = null) {
		if (!is_a($schema, 'CakeSchema')) {
			trigger_error(__d('cake_dev', 'Invalid schema object'), E_USER_WARNING);
			return null;
		}
		$out = '';

		foreach ($schema->tables as $curTable => $columns) {
			if (!$tableName || $tableName == $curTable) {
				$cols = $colList = $indexes = $tableParameters = array();
				$primary = null;
				$table = $this->fullTableName($curTable);

				foreach ($columns as $name => $col) {
					if (is_string($col)) {
						$col = array('type' => $col);
					}
					if (isset($col['key']) && $col['key'] === 'primary') {
						$primary = $name;
					}
					if ($name !== 'indexes' && $name !== 'tableParameters') {
						$col['name'] = $name;
						if (!isset($col['type'])) {
							$col['type'] = 'string';
						}
						$cols[] = $this->buildColumn($col);
					} elseif ($name === 'indexes') {
						$indexes = array_merge($indexes, $this->buildIndex($col, $table));
					} elseif ($name === 'tableParameters') {
						$tableParameters = array_merge($tableParameters, $this->buildTableParameters($col, $table));
					}
				}
				if (empty($indexes) && !empty($primary)) {
					$col = array('PRIMARY' => array('column' => $primary, 'unique' => 1));
					$indexes = array_merge($indexes, $this->buildIndex($col, $table));
				}
				$columns = $cols;
				$out .= $this->renderStatement('schema', compact('table', 'columns', 'indexes', 'tableParameters')) . "\n\n";
			}
		}
		return $out;
	}

/**
 * Generate a alter syntax from	 CakeSchema::compare()
 *
 * @param unknown_type $schema
 * @return boolean
 */
	function alterSchema($compare, $table = null) {
		return false;
	}

/**
 * Generate a "drop table" statement for the given Schema object
 *
 * @param CakeSchema $schema An instance of a subclass of CakeSchema
 * @param string $table Optional.  If specified only the table name given will be generated.
 *   Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	public function dropSchema(CakeSchema $schema, $table = null) {
		$out = '';

		foreach ($schema->tables as $curTable => $columns) {
			if (!$table || $table == $curTable) {
				$out .= 'DROP TABLE ' . $this->fullTableName($curTable) . ";\n";
			}
		}
		return $out;
	}

/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following: array('name'=>'value', 'type'=>'value'[, options]),
 *   where options can be 'default', 'length', or 'key'.
 * @return string
 */
	public function buildColumn($column) {
		$name = $type = null;
		extract(array_merge(array('null' => true), $column));

		if (empty($name) || empty($type)) {
			trigger_error(__d('cake_dev', 'Column name or type not defined in schema'), E_USER_WARNING);
			return null;
		}

		if (!isset($this->columns[$type])) {
			trigger_error(__d('cake_dev', 'Column type %s does not exist', $type), E_USER_WARNING);
			return null;
		}

		$real = $this->columns[$type];
		$out = $this->name($name) . ' ' . $real['name'];

		if (isset($column['length'])) {
			$length = $column['length'];
		} elseif (isset($column['limit'])) {
			$length = $column['limit'];
		} elseif (isset($real['length'])) {
			$length = $real['length'];
		} elseif (isset($real['limit'])) {
			$length = $real['limit'];
		}
		if (isset($length)) {
			$out .= '(' . $length . ')';
		}

		if (($column['type'] === 'integer' || $column['type'] === 'float' ) && isset($column['default']) && $column['default'] === '') {
			$column['default'] = null;
		}
		$out = $this->_buildFieldParameters($out, $column, 'beforeDefault');

		if (isset($column['key']) && $column['key'] === 'primary' && $type === 'integer') {
			$out .= ' ' . $this->columns['primary_key']['name'];
		} elseif (isset($column['key']) && $column['key'] === 'primary') {
			$out .= ' NOT NULL';
		} elseif (isset($column['default']) && isset($column['null']) && $column['null'] === false) {
			$out .= ' DEFAULT ' . $this->value($column['default'], $type) . ' NOT NULL';
		} elseif (isset($column['default'])) {
			$out .= ' DEFAULT ' . $this->value($column['default'], $type);
		} elseif ($type !== 'timestamp' && !empty($column['null'])) {
			$out .= ' DEFAULT NULL';
		} elseif ($type === 'timestamp' && !empty($column['null'])) {
			$out .= ' NULL';
		} elseif (isset($column['null']) && $column['null'] === false) {
			$out .= ' NOT NULL';
		}
		if ($type === 'timestamp' && isset($column['default']) && strtolower($column['default']) === 'current_timestamp') {
			$out = str_replace(array("'CURRENT_TIMESTAMP'", "'current_timestamp'"), 'CURRENT_TIMESTAMP', $out);
		}
		return $this->_buildFieldParameters($out, $column, 'afterDefault');
	}

/**
 * Build the field parameters, in a position
 *
 * @param string $columnString The partially built column string
 * @param array $columnData The array of column data.
 * @param string $position The position type to use. 'beforeDefault' or 'afterDefault' are common
 * @return string a built column with the field parameters added.
 */
	public function _buildFieldParameters($columnString, $columnData, $position) {
		foreach ($this->fieldParameters as $paramName => $value) {
			if (isset($columnData[$paramName]) && $value['position'] == $position) {
				if (isset($value['options']) && !in_array($columnData[$paramName], $value['options'])) {
					continue;
				}
				$val = $columnData[$paramName];
				if ($value['quote']) {
					$val = $this->value($val);
				}
				$columnString .= ' ' . $value['value'] . $value['join'] . $val;
			}
		}
		return $columnString;
	}

/**
 * Format indexes for create table
 *
 * @param array $indexes
 * @param string $table
 * @return array
 */
	public function buildIndex($indexes, $table = null) {
		$join = array();
		foreach ($indexes as $name => $value) {
			$out = '';
			if ($name === 'PRIMARY') {
				$out .= 'PRIMARY ';
				$name = null;
			} else {
				if (!empty($value['unique'])) {
					$out .= 'UNIQUE ';
				}
				$name = $this->startQuote . $name . $this->endQuote;
			}
			if (is_array($value['column'])) {
				$out .= 'KEY ' . $name . ' (' . implode(', ', array_map(array(&$this, 'name'), $value['column'])) . ')';
			} else {
				$out .= 'KEY ' . $name . ' (' . $this->name($value['column']) . ')';
			}
			$join[] = $out;
		}
		return $join;
	}

/**
 * Read additional table parameters
 *
 * @param array $parameters
 * @param string $table
 * @return array
 */
	public function readTableParameters($name) {
		$parameters = array();
		if (method_exists($this, 'listDetailedSources')) {
			$currentTableDetails = $this->listDetailedSources($name);
			foreach ($this->tableParameters as $paramName => $parameter) {
				if (!empty($parameter['column']) && !empty($currentTableDetails[$parameter['column']])) {
					$parameters[$paramName] = $currentTableDetails[$parameter['column']];
				}
			}
		}
		return $parameters;
	}

/**
 * Format parameters for create table
 *
 * @param array $parameters
 * @param string $table
 * @return array
 */
	public function buildTableParameters($parameters, $table = null) {
		$result = array();
		foreach ($parameters as $name => $value) {
			if (isset($this->tableParameters[$name])) {
				if ($this->tableParameters[$name]['quote']) {
					$value = $this->value($value);
				}
				$result[] = $this->tableParameters[$name]['value'] . $this->tableParameters[$name]['join'] . $value;
			}
		}
		return $result;
	}

/**
 * Guesses the data type of an array
 *
 * @param string $value
 * @return void
 */
	public function introspectType($value) {
		if (!is_array($value)) {
			if (is_bool($value)) {
				return 'boolean';
			}
			if (is_float($value) && floatval($value) === $value) {
				return 'float';
			}
			if (is_int($value) && intval($value) === $value) {
				return 'integer';
			}
			if (is_string($value) && strlen($value) > 255) {
				return 'text';
			}
			return 'string';
		}

		$isAllFloat = $isAllInt = true;
		$containsFloat = $containsInt = $containsString = false;
		foreach ($value as $key => $valElement) {
			$valElement = trim($valElement);
			if (!is_float($valElement) && !preg_match('/^[\d]+\.[\d]+$/', $valElement)) {
				$isAllFloat = false;
			} else {
				$containsFloat = true;
				continue;
			}
			if (!is_int($valElement) && !preg_match('/^[\d]+$/', $valElement)) {
				$isAllInt = false;
			} else {
				$containsInt = true;
				continue;
			}
			$containsString = true;
		}

		if ($isAllFloat) {
			return 'float';
		}
		if ($isAllInt) {
			return 'integer';
		}

		if ($containsInt && !$containsString) {
			return 'integer';
		}
		return 'string';
	}

/**
 * Writes a new key for the in memory sql query cache
 *
 * @param string $sql SQL query
 * @param mixed $data result of $sql query
 * @param array $params query params bound as values
 * @return void
 */
	protected function _writeQueryCache($sql, $data, $params = array()) {
		if (preg_match('/^\s*select/i', $sql)) {
			$this->_queryCache[$sql][serialize($params)] = $data;
		}
	}

/**
 * Returns the result for a sql query if it is already cached
 *
 * @param string $sql SQL query
 * @param array $params query params bound as values
 * @return mixed results for query if it is cached, false otherwise
 */
	public function getQueryCache($sql, $params = array()) {
		if (isset($this->_queryCache[$sql]) && preg_match('/^\s*select/i', $sql)) {
			$serialized = serialize($params);
			if (isset($this->_queryCache[$sql][$serialized])) {
				return $this->_queryCache[$sql][$serialized];
			}
		}
		return false;
	}

}