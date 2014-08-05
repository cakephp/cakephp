<?php
/**
 * Dbo Source
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DataSource', 'Model/Datasource');
App::uses('String', 'Utility');
App::uses('View', 'View');

/**
 * DboSource
 *
 * Creates DBO-descendant objects from a given db connection configuration
 *
 * @package       Cake.Model.Datasource
 */
class DboSource extends DataSource {

/**
 * Description string for this Database Data Source.
 *
 * @var string
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
 */
	public $alias = 'AS ';

/**
 * Caches result from query parsing operations. Cached results for both DboSource::name() and
 * DboSource::conditions() will be stored here. Method caching uses `md5()`. If you have
 * problems with collisions, set DboSource::$cacheMethods to false.
 *
 * @var array
 */
	public static $methodCache = array();

/**
 * Whether or not to cache the results of DboSource::name() and DboSource::conditions()
 * into the memory cache. Set to false to disable the use of the memory cache.
 *
 * @var bool
 */
	public $cacheMethods = true;

/**
 * Flag to support nested transactions. If it is set to false, you will be able to use
 * the transaction methods (begin/commit/rollback), but just the global transaction will
 * be executed.
 *
 * @var bool
 */
	public $useNestedTransactions = false;

/**
 * Print full query debug info?
 *
 * @var bool
 */
	public $fullDebug = false;

/**
 * String to hold how many rows were affected by the last SQL operation.
 *
 * @var string
 */
	public $affected = null;

/**
 * Number of rows in current resultset
 *
 * @var int
 */
	public $numRows = null;

/**
 * Time the last query took
 *
 * @var int
 */
	public $took = null;

/**
 * Result
 *
 * @var array
 */
	protected $_result = null;

/**
 * Queries count.
 *
 * @var int
 */
	protected $_queriesCnt = 0;

/**
 * Total duration of all queries.
 *
 * @var int
 */
	protected $_queriesTime = null;

/**
 * Log of queries executed by this DataSource
 *
 * @var array
 */
	protected $_queriesLog = array();

/**
 * Maximum number of items in query log
 *
 * This is to prevent query log taking over too much memory.
 *
 * @var int
 */
	protected $_queriesLogMax = 200;

/**
 * Caches serialized results of executed queries
 *
 * @var array
 */
	protected $_queryCache = array();

/**
 * A reference to the physical connection of this DataSource
 *
 * @var array
 */
	protected $_connection = null;

/**
 * The DataSource configuration key name
 *
 * @var string
 */
	public $configKeyName = null;

/**
 * The starting character that this DataSource uses for quoted identifiers.
 *
 * @var string
 */
	public $startQuote = null;

/**
 * The ending character that this DataSource uses for quoted identifiers.
 *
 * @var string
 */
	public $endQuote = null;

/**
 * The set of valid SQL operations usable in a WHERE statement
 *
 * @var array
 */
	protected $_sqlOps = array('like', 'ilike', 'or', 'not', 'in', 'between', 'regexp', 'similar to');

/**
 * Indicates the level of nested transactions
 *
 * @var int
 */
	protected $_transactionNesting = 0;

/**
 * Default fields that are used by the DBO
 *
 * @var array
 */
	protected $_queryDefaults = array(
		'conditions' => array(),
		'fields' => null,
		'table' => null,
		'alias' => null,
		'order' => null,
		'limit' => null,
		'joins' => array(),
		'group' => null,
		'offset' => null
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
 */
	public $tableParameters = array();

/**
 * List of engine specific additional field parameters used on table creating
 *
 * @var array
 */
	public $fieldParameters = array();

/**
 * Indicates whether there was a change on the cached results on the methods of this class
 * This will be used for storing in a more persistent cache
 *
 * @var bool
 */
	protected $_methodCacheChange = false;

/**
 * Constructor
 *
 * @param array $config Array of configuration information for the Datasource.
 * @param bool $autoConnect Whether or not the datasource should automatically connect.
 * @throws MissingConnectionException when a connection cannot be made.
 */
	public function __construct($config = null, $autoConnect = true) {
		if (!isset($config['prefix'])) {
			$config['prefix'] = '';
		}
		parent::__construct($config);
		$this->fullDebug = Configure::read('debug') > 1;
		if (!$this->enabled()) {
			throw new MissingConnectionException(array(
				'class' => get_class($this),
				'message' => __d('cake_dev', 'Selected driver is not enabled'),
				'enabled' => false
			));
		}
		if ($autoConnect) {
			$this->connect();
		}
	}

/**
 * Reconnects to database server with optional new settings
 *
 * @param array $config An array defining the new configuration settings
 * @return bool True on success, false on failure
 */
	public function reconnect($config = array()) {
		$this->disconnect();
		$this->setConfig($config);
		$this->_sources = null;

		return $this->connect();
	}

/**
 * Disconnects from database.
 *
 * @return bool Always true
 */
	public function disconnect() {
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
 * @return PDO
 */
	public function getConnection() {
		return $this->_connection;
	}

/**
 * Gets the version string of the database server
 *
 * @return string The database version
 */
	public function getVersion() {
		return $this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column datatype into which this data will be inserted.
 * @return string Quoted and escaped data
 */
	public function value($data, $column = null) {
		if (is_array($data) && !empty($data)) {
			return array_map(
				array(&$this, 'value'),
				$data, array_fill(0, count($data), $column)
			);
		} elseif (is_object($data) && isset($data->type, $data->value)) {
			if ($data->type === 'identifier') {
				return $this->name($data->value);
			} elseif ($data->type === 'expression') {
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
			case 'boolean':
				return $this->_connection->quote($this->boolean($data, true), PDO::PARAM_BOOL);
			case 'string':
			case 'text':
				return $this->_connection->quote($data, PDO::PARAM_STR);
			default:
				if ($data === '') {
					return 'NULL';
				}
				if (is_float($data)) {
					return str_replace(',', '.', strval($data));
				}
				if ((is_int($data) || $data === '0') || (
					is_numeric($data) && strpos($data, ',') === false &&
					$data[0] != '0' && strpos($data, 'e') === false)
				) {
					return $data;
				}
				return $this->_connection->quote($data);
		}
	}

/**
 * Returns an object to represent a database identifier in a query. Expression objects
 * are not sanitized or escaped.
 *
 * @param string $identifier A SQL expression to be used as an identifier
 * @return stdClass An object representing a database identifier to be used in a query
 */
	public function identifier($identifier) {
		$obj = new stdClass();
		$obj->type = 'identifier';
		$obj->value = $identifier;
		return $obj;
	}

/**
 * Returns an object to represent a database expression in a query. Expression objects
 * are not sanitized or escaped.
 *
 * @param string $expression An arbitrary SQL expression to be inserted into a query.
 * @return stdClass An object representing a database expression to be used in a query
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
 * @return bool
 */
	public function rawQuery($sql, $params = array()) {
		$this->took = $this->numRows = false;
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
 * @param string $sql SQL statement
 * @param array $options The options for executing the query.
 * @param array $params values to be bound to the query.
 * @return mixed Resource or object representing the result set, or false on failure
 */
	public function execute($sql, $options = array(), $params = array()) {
		$options += array('log' => $this->fullDebug);

		$t = microtime(true);
		$this->_result = $this->_execute($sql, $params);

		if ($options['log']) {
			$this->took = round((microtime(true) - $t) * 1000, 0);
			$this->numRows = $this->affected = $this->lastAffected();
			$this->logQuery($sql, $params);
		}

		return $this->_result;
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @param array $params list of params to be bound to query
 * @param array $prepareOptions Options to be used in the prepare statement
 * @return mixed PDOStatement if query executes with no problem, true as the result of a successful, false on error
 * query returning no rows, such as a CREATE statement, false otherwise
 * @throws PDOException
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$sql = trim($sql);
		if (preg_match('/^(?:CREATE|ALTER|DROP)\s+(?:TABLE|INDEX)/i', $sql)) {
			$statements = array_filter(explode(';', $sql));
			if (count($statements) > 1) {
				$result = array_map(array($this, '_execute'), $statements);
				return array_search(false, $result) === false;
			}
		}

		try {
			$query = $this->_connection->prepare($sql, $prepareOptions);
			$query->setFetchMode(PDO::FETCH_LAZY);
			if (!$query->execute($params)) {
				$this->_results = $query;
				$query->closeCursor();
				return false;
			}
			if (!$query->columnCount()) {
				$query->closeCursor();
				if (!$query->rowCount()) {
					return true;
				}
			}
			return $query;
		} catch (PDOException $e) {
			if (isset($query->queryString)) {
				$e->queryString = $query->queryString;
			} else {
				$e->queryString = $sql;
			}
			throw $e;
		}
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @param PDOStatement $query the query to extract the error from if any
 * @return string Error message with error number
 */
	public function lastError(PDOStatement $query = null) {
		if ($query) {
			$error = $query->errorInfo();
		} else {
			$error = $this->_connection->errorInfo();
		}
		if (empty($error[2])) {
			return null;
		}
		return $error[1] . ': ' . $error[2];
	}

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @param mixed $source The source to check.
 * @return int Number of affected rows
 */
	public function lastAffected($source = null) {
		if ($this->hasResult()) {
			return $this->_result->rowCount();
		}
		return 0;
	}

/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @param mixed $source Not used
 * @return int Number of rows in resultset
 */
	public function lastNumRows($source = null) {
		return $this->lastAffected();
	}

/**
 * DataSource Query abstraction
 *
 * @return resource Result resource identifier.
 */
	public function query() {
		$args = func_get_args();
		$fields = null;
		$order = null;
		$limit = null;
		$page = null;
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
			}
			if (isset($params[3 + $off])) {
				$recursive = $params[3 + $off];
			}
			return $args[2]->find('first', compact('conditions', 'fields', 'order', 'recursive'));
		}
		if (isset($args[1]) && $args[1] === true) {
			return $this->fetchAll($args[0], true);
		} elseif (isset($args[1]) && !is_array($args[1])) {
			return $this->fetchAll($args[0], false);
		} elseif (isset($args[1]) && is_array($args[1])) {
			if (isset($args[2])) {
				$cache = $args[2];
			} else {
				$cache = true;
			}
			return $this->fetchAll($args[0], $args[1], array('cache' => $cache));
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
		}
		return null;
	}

/**
 * Returns an array of all result rows for a given SQL query.
 *
 * Returns false if no rows matched.
 *
 * ### Options
 *
 * - `cache` - Returns the cached version of the query, if exists and stores the result in cache.
 *   This is a non-persistent cache, and only lasts for a single request. This option
 *   defaults to true. If you are directly calling this method, you can disable caching
 *   by setting $options to `false`
 *
 * @param string $sql SQL statement
 * @param array|bool $params Either parameters to be bound as values for the SQL statement,
 *  or a boolean to control query caching.
 * @param array $options additional options for the query.
 * @return bool|array Array of resultset rows, or false if no rows matched
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
		$result = $this->execute($sql, array(), $params);
		if ($result) {
			$out = array();

			if ($this->hasResult()) {
				$first = $this->fetchRow();
				if ($first) {
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
 * @return bool
 */
	public function fetchResult() {
		return false;
	}

/**
 * Modifies $result array to place virtual fields in model entry where they belongs to
 *
 * @param array &$result Reference to the fetched row
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

				$Model = ClassRegistry::getObject($alias);

				if ($Model->isVirtualField($virtual)) {
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
		$this->_methodCacheChange = true;
		self::$methodCache = array();
	}

/**
 * Cache a value into the methodCaches. Will respect the value of DboSource::$cacheMethods.
 * Will retrieve a value from the cache if $value is null.
 *
 * If caching is disabled and a write is attempted, the $value will be returned.
 * A read will either return the value or null.
 *
 * @param string $method Name of the method being cached.
 * @param string $key The key name for the cache operation.
 * @param mixed $value The value to cache into memory.
 * @return mixed Either null on failure, or the value if its set.
 */
	public function cacheMethod($method, $key, $value = null) {
		if ($this->cacheMethods === false) {
			return $value;
		}
		if (!$this->_methodCacheChange && empty(self::$methodCache)) {
			self::$methodCache = Cache::read('method_cache', '_cake_core_');
		}
		if ($value === null) {
			return (isset(self::$methodCache[$method][$key])) ? self::$methodCache[$method][$key] : null;
		}
		$this->_methodCacheChange = true;
		return self::$methodCache[$method][$key] = $value;
	}

/**
 * Returns a quoted name of $data for use in an SQL statement.
 * Strips fields out of SQL functions before quoting.
 *
 * Results of this method are stored in a memory cache. This improves performance, but
 * because the method uses a hashing algorithm it can have collisions.
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
		$cacheKey = md5($this->startQuote . $data . $this->endQuote);
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
 * @return bool True if the database is connected, else false
 */
	public function isConnected() {
		return $this->connected;
	}

/**
 * Checks if the result is valid
 *
 * @return bool True if the result is valid else false
 */
	public function hasResult() {
		return $this->_result instanceof PDOStatement;
	}

/**
 * Get the query log as an array.
 *
 * @param bool $sorted Get the queries sorted by time taken, defaults to false.
 * @param bool $clear If True the existing log will cleared.
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
 * will be rendered and output. If in a CLI environment, a plain text log is generated.
 *
 * @param bool $sorted Get the queries sorted by time taken, defaults to false.
 * @return void
 */
	public function showLog($sorted = false) {
		$log = $this->getLog($sorted, false);
		if (empty($log['log'])) {
			return;
		}
		if (PHP_SAPI !== 'cli') {
			$controller = null;
			$View = new View($controller, false);
			$View->set('sqlLogs', array($this->configKeyName => $log));
			echo $View->element('sql_dump', array('_forced_from_dbo_' => true));
		} else {
			foreach ($log['log'] as $k => $i) {
				print (($k + 1) . ". {$i['query']}\n");
			}
		}
	}

/**
 * Log given SQL query.
 *
 * @param string $sql SQL statement
 * @param array $params Values binded to the query (prepared statements)
 * @return void
 */
	public function logQuery($sql, $params = array()) {
		$this->_queriesCnt++;
		$this->_queriesTime += $this->took;
		$this->_queriesLog[] = array(
			'query' => $sql,
			'params' => $params,
			'affected' => $this->affected,
			'numRows' => $this->numRows,
			'took' => $this->took
		);
		if (count($this->_queriesLog) > $this->_queriesLogMax) {
			array_shift($this->_queriesLog);
		}
	}

/**
 * Gets full table name including prefix
 *
 * @param Model|string $model Either a Model object or a string table name.
 * @param bool $quote Whether you want the table name quoted.
 * @param bool $schema Whether you want the schema name included.
 * @return string Full quoted table name
 */
	public function fullTableName($model, $quote = true, $schema = true) {
		if (is_object($model)) {
			$schemaName = $model->schemaName;
			$table = $model->tablePrefix . $model->table;
		} elseif (!empty($this->config['prefix']) && strpos($model, $this->config['prefix']) !== 0) {
			$table = $this->config['prefix'] . strval($model);
		} else {
			$table = strval($model);
		}

		if ($schema && !isset($schemaName)) {
			$schemaName = $this->getSchemaName();
		}

		if ($quote) {
			if ($schema && !empty($schemaName)) {
				if (strstr($table, '.') === false) {
					return $this->name($schemaName) . '.' . $this->name($table);
				}
			}
			return $this->name($table);
		}

		if ($schema && !empty($schemaName)) {
			if (strstr($table, '.') === false) {
				return $schemaName . '.' . $table;
			}
		}

		return $table;
	}

/**
 * The "C" in CRUD
 *
 * Creates new records in the database.
 *
 * @param Model $Model Model object that the record is for.
 * @param array $fields An array of field names to insert. If null, $Model->data will be
 *   used to generate field names.
 * @param array $values An array of values with keys matching the fields. If null, $Model->data will
 *   be used to generate values.
 * @return bool Success
 */
	public function create(Model $Model, $fields = null, $values = null) {
		$id = null;

		if (!$fields) {
			unset($fields, $values);
			$fields = array_keys($Model->data);
			$values = array_values($Model->data);
		}
		$count = count($fields);

		for ($i = 0; $i < $count; $i++) {
			$valueInsert[] = $this->value($values[$i], $Model->getColumnType($fields[$i]));
			$fieldInsert[] = $this->name($fields[$i]);
			if ($fields[$i] === $Model->primaryKey) {
				$id = $values[$i];
			}
		}

		$query = array(
			'table' => $this->fullTableName($Model),
			'fields' => implode(', ', $fieldInsert),
			'values' => implode(', ', $valueInsert)
		);

		if ($this->execute($this->renderStatement('create', $query))) {
			if (empty($id)) {
				$id = $this->lastInsertId($this->fullTableName($Model, false, false), $Model->primaryKey);
			}
			$Model->setInsertID($id);
			$Model->id = $id;
			return true;
		}

		$Model->onError();
		return false;
	}

/**
 * The "R" in CRUD
 *
 * Reads record(s) from the database.
 *
 * @param Model $Model A Model object that the query is for.
 * @param array $queryData An array of queryData information containing keys similar to Model::find().
 * @param int $recursive Number of levels of association
 * @return mixed boolean false on error/failure. An array of results on success.
 */
	public function read(Model $Model, $queryData = array(), $recursive = null) {
		$queryData = $this->_scrubQueryData($queryData);

		$array = array('callbacks' => $queryData['callbacks']);

		if ($recursive === null && isset($queryData['recursive'])) {
			$recursive = $queryData['recursive'];
		}

		if ($recursive !== null) {
			$modelRecursive = $Model->recursive;
			$Model->recursive = $recursive;
		}

		if (!empty($queryData['fields'])) {
			$noAssocFields = true;
			$queryData['fields'] = $this->fields($Model, null, $queryData['fields']);
		} else {
			$noAssocFields = false;
			$queryData['fields'] = $this->fields($Model);
		}

		if ($Model->recursive === -1) {
			// Primary model data only, no joins.
			$associations = array();

		} else {
			$associations = $Model->associations();

			if ($Model->recursive === 0) {
				// Primary model data and its domain.
				unset($associations[2], $associations[3]);
			}
		}

		$originalJoins = $queryData['joins'];
		$queryData['joins'] = array();

		// Generate hasOne and belongsTo associations inside $queryData
		$linkedModels = array();
		foreach ($associations as $type) {
			if ($type !== 'hasOne' && $type !== 'belongsTo') {
				continue;
			}

			foreach ($Model->{$type} as $assoc => $assocData) {
				$LinkModel = $Model->{$assoc};

				if ($Model->useDbConfig !== $LinkModel->useDbConfig) {
					continue;
				}

				if ($noAssocFields) {
					$assocData['fields'] = false;
				}

				$external = isset($assocData['external']);

				if ($this->generateAssociationQuery($Model, $LinkModel, $type, $assoc, $assocData, $queryData, $external) === true) {
					$linkedModels[$type . '/' . $assoc] = true;
				}
			}
		}

		if (!empty($originalJoins)) {
			$queryData['joins'] = array_merge($queryData['joins'], $originalJoins);
		}

		// Build SQL statement with the primary model, plus hasOne and belongsTo associations
		$query = $this->buildAssociationQuery($Model, $queryData);

		$resultSet = $this->fetchAll($query, $Model->cacheQueries);
		unset($query);

		if ($resultSet === false) {
			$Model->onError();
			return false;
		}

		$filtered = array();

		// Filter hasOne and belongsTo associations
		if ($queryData['callbacks'] === true || $queryData['callbacks'] === 'after') {
			$filtered = $this->_filterResults($resultSet, $Model);
		}

		// Deep associations
		if ($Model->recursive > -1) {
			$joined = array();
			if (isset($queryData['joins'][0]['alias'])) {
				$joined[$Model->alias] = (array)Hash::extract($queryData['joins'], '{n}.alias');
			}

			foreach ($associations as $type) {
				foreach ($Model->{$type} as $assoc => $assocData) {
					$LinkModel = $Model->{$assoc};

					if (!isset($linkedModels[$type . '/' . $assoc])) {
						$db = $Model->useDbConfig === $LinkModel->useDbConfig ? $this : $LinkModel->getDataSource();
					} elseif ($Model->recursive > 1) {
						$db = $this;
					}

					if (isset($db) && method_exists($db, 'queryAssociation')) {
						$stack = array($assoc);
						$stack['_joined'] = $joined;

						$db->queryAssociation($Model, $LinkModel, $type, $assoc, $assocData, $array, true, $resultSet, $Model->recursive - 1, $stack);
						unset($db);

						if ($type === 'hasMany' || $type === 'hasAndBelongsToMany') {
							$filtered[] = $assoc;
						}
					}
				}
			}

			if ($queryData['callbacks'] === true || $queryData['callbacks'] === 'after') {
				$this->_filterResults($resultSet, $Model, $filtered);
			}
		}

		if ($recursive !== null) {
			$Model->recursive = $modelRecursive;
		}

		return $resultSet;
	}

/**
 * Passes association results through afterFind filters of the corresponding model.
 *
 * The primary model is always excluded, because the filtering is later done by Model::_filterResults().
 *
 * @param array &$resultSet Reference of resultset to be filtered.
 * @param Model $Model Instance of model to operate against.
 * @param array $filtered List of classes already filtered, to be skipped.
 * @return array Array of results that have been filtered through $Model->afterFind.
 */
	protected function _filterResults(&$resultSet, Model $Model, $filtered = array()) {
		if (!is_array($resultSet)) {
			return array();
		}

		$current = reset($resultSet);
		if (!is_array($current)) {
			return array();
		}

		$keys = array_diff(array_keys($current), $filtered, array($Model->alias));
		$filtering = array();

		foreach ($keys as $className) {
			if (!isset($Model->{$className}) || !is_object($Model->{$className})) {
				continue;
			}

			$LinkedModel = $Model->{$className};
			$filtering[] = $className;

			foreach ($resultSet as $key => &$result) {
				$data = $LinkedModel->afterFind(array(array($className => $result[$className])), false);
				if (isset($data[0][$className])) {
					$result[$className] = $data[0][$className];
				} else {
					unset($resultSet[$key]);
				}
			}
		}

		return $filtering;
	}

/**
 * Queries associations.
 *
 * Used to fetch results on recursive models.
 *
 * - 'hasMany' associations with no limit set:
 *    Fetch, filter and merge is done recursively for every level.
 *
 * - 'hasAndBelongsToMany' associations:
 *    Fetch and filter is done unaffected by the (recursive) level set.
 *
 * @param Model $Model Primary Model object.
 * @param Model $LinkModel Linked model object.
 * @param string $type Association type, one of the model association types ie. hasMany.
 * @param string $association Association name.
 * @param array $assocData Association data.
 * @param array &$queryData An array of queryData information containing keys similar to Model::find().
 * @param bool $external Whether or not the association query is on an external datasource.
 * @param array &$resultSet Existing results.
 * @param int $recursive Number of levels of association.
 * @param array $stack A list with joined models.
 * @return mixed
 * @throws CakeException when results cannot be created.
 */
	public function queryAssociation(Model $Model, Model $LinkModel, $type, $association, $assocData, &$queryData, $external, &$resultSet, $recursive, $stack) {
		if (isset($stack['_joined'])) {
			$joined = $stack['_joined'];
			unset($stack['_joined']);
		}

		$queryTemplate = $this->generateAssociationQuery($Model, $LinkModel, $type, $association, $assocData, $queryData, $external);
		if (empty($queryTemplate)) {
			return;
		}

		if (!is_array($resultSet)) {
			throw new CakeException(__d('cake_dev', 'Error in Model %s', get_class($Model)));
		}

		if ($type === 'hasMany' && empty($assocData['limit']) && !empty($assocData['foreignKey'])) {
			// 'hasMany' associations with no limit set.

			$assocIds = array();
			foreach ($resultSet as $result) {
				$assocIds[] = $this->insertQueryData('{$__cakeID__$}', $result, $association, $Model, $stack);
			}
			$assocIds = array_filter($assocIds);

			// Fetch
			$assocResultSet = array();
			if (!empty($assocIds)) {
				$assocResultSet = $this->_fetchHasMany($Model, $queryTemplate, $assocIds);
			}

			// Recursively query associations
			if ($recursive > 0 && !empty($assocResultSet) && is_array($assocResultSet)) {
				foreach ($LinkModel->associations() as $type1) {
					foreach ($LinkModel->{$type1} as $assoc1 => $assocData1) {
						$DeepModel = $LinkModel->{$assoc1};
						$tmpStack = $stack;
						$tmpStack[] = $assoc1;

						$db = $LinkModel->useDbConfig === $DeepModel->useDbConfig ? $this : $DeepModel->getDataSource();

						$db->queryAssociation($LinkModel, $DeepModel, $type1, $assoc1, $assocData1, $queryData, true, $assocResultSet, $recursive - 1, $tmpStack);
					}
				}
			}

			// Filter
			if ($queryData['callbacks'] === true || $queryData['callbacks'] === 'after') {
				$this->_filterResults($assocResultSet, $Model);
			}

			// Merge
			return $this->_mergeHasMany($resultSet, $assocResultSet, $association, $Model);

		} elseif ($type === 'hasAndBelongsToMany') {
			// 'hasAndBelongsToMany' associations.

			$assocIds = array();
			foreach ($resultSet as $result) {
				$assocIds[] = $this->insertQueryData('{$__cakeID__$}', $result, $association, $Model, $stack);
			}
			$assocIds = array_filter($assocIds);

			// Fetch
			$assocResultSet = array();
			if (!empty($assocIds)) {
				$assocResultSet = $this->_fetchHasAndBelongsToMany($Model, $queryTemplate, $assocIds, $association);
			}

			$habtmAssocData = $Model->hasAndBelongsToMany[$association];
			$foreignKey = $habtmAssocData['foreignKey'];
			$joinKeys = array($foreignKey, $habtmAssocData['associationForeignKey']);
			list($with, $habtmFields) = $Model->joinModel($habtmAssocData['with'], $joinKeys);
			$habtmFieldsCount = count($habtmFields);

			// Filter
			if ($queryData['callbacks'] === true || $queryData['callbacks'] === 'after') {
				$this->_filterResults($assocResultSet, $Model);
			}
		}

		$modelAlias = $Model->alias;
		$primaryKey = $Model->primaryKey;
		$selfJoin = ($Model->name === $LinkModel->name);

		foreach ($resultSet as &$row) {
			if ($type === 'hasOne' || $type === 'belongsTo' || $type === 'hasMany') {
				$assocResultSet = array();
				$prefetched = false;

				if (
					($type === 'hasOne' || $type === 'belongsTo') &&
					isset($row[$LinkModel->alias], $joined[$Model->alias]) &&
					in_array($LinkModel->alias, $joined[$Model->alias])
				) {
					$joinedData = Hash::filter($row[$LinkModel->alias]);
					if (!empty($joinedData)) {
						$assocResultSet[0] = array($LinkModel->alias => $row[$LinkModel->alias]);
					}
					$prefetched = true;
				} else {
					$query = $this->insertQueryData($queryTemplate, $row, $association, $Model, $stack);
					if ($query !== false) {
						$assocResultSet = $this->fetchAll($query, $Model->cacheQueries);
					}
				}
			}

			if (!empty($assocResultSet) && is_array($assocResultSet)) {
				if ($recursive > 0) {
					foreach ($LinkModel->associations() as $type1) {
						foreach ($LinkModel->{$type1} as $assoc1 => $assocData1) {
							$DeepModel = $LinkModel->{$assoc1};

							if (
								$type1 === 'belongsTo' ||
								($type === 'belongsTo' && $DeepModel->alias === $modelAlias) ||
								($DeepModel->alias !== $modelAlias)
							) {
								$tmpStack = $stack;
								$tmpStack[] = $assoc1;

								$db = $LinkModel->useDbConfig === $DeepModel->useDbConfig ? $this : $DeepModel->getDataSource();

								$db->queryAssociation($LinkModel, $DeepModel, $type1, $assoc1, $assocData1, $queryData, true, $assocResultSet, $recursive - 1, $tmpStack);
							}
						}
					}
				}

				if ($type === 'hasAndBelongsToMany') {
					$merge = array();
					foreach ($assocResultSet as $data) {
						if (isset($data[$with]) && $data[$with][$foreignKey] === $row[$modelAlias][$primaryKey]) {
							if ($habtmFieldsCount <= 2) {
								unset($data[$with]);
							}
							$merge[] = $data;
						}
					}

					if (empty($merge) && !isset($row[$association])) {
						$row[$association] = $merge;
					} else {
						$this->_mergeAssociation($row, $merge, $association, $type);
					}
				} else {
					$this->_mergeAssociation($row, $assocResultSet, $association, $type, $selfJoin);
				}

				if ($type !== 'hasAndBelongsToMany' && isset($row[$association]) && !$prefetched) {
					$row[$association] = $LinkModel->afterFind($row[$association], false);
				}

			} else {
				$tempArray[0][$association] = false;
				$this->_mergeAssociation($row, $tempArray, $association, $type, $selfJoin);
			}
		}
	}

/**
 * Fetch 'hasMany' associations.
 *
 * This is just a proxy to maintain BC.
 *
 * @param Model $Model Primary model object.
 * @param string $query Association query template.
 * @param array $ids Array of IDs of associated records.
 * @return array Association results.
 * @see DboSource::_fetchHasMany()
 */
	public function fetchAssociated(Model $Model, $query, $ids) {
		return $this->_fetchHasMany($Model, $query, $ids);
	}

/**
 * Fetch 'hasMany' associations.
 *
 * @param Model $Model Primary model object.
 * @param string $query Association query template.
 * @param array $ids Array of IDs of associated records.
 * @return array Association results.
 */
	protected function _fetchHasMany(Model $Model, $query, $ids) {
		$ids = array_unique($ids);

		$query = str_replace('{$__cakeID__$}', implode(', ', $ids), $query);
		if (count($ids) > 1) {
			$query = str_replace('= (', 'IN (', $query);
		}

		return $this->fetchAll($query, $Model->cacheQueries);
	}

/**
 * Fetch 'hasAndBelongsToMany' associations.
 *
 * @param Model $Model Primary model object.
 * @param string $query Association query.
 * @param array $ids Array of IDs of associated records.
 * @param string $association Association name.
 * @return array Association results.
 */
	protected function _fetchHasAndBelongsToMany(Model $Model, $query, $ids, $association) {
		$ids = array_unique($ids);

		if (count($ids) > 1) {
			$query = str_replace('{$__cakeID__$}', '(' . implode(', ', $ids) . ')', $query);
			$query = str_replace('= (', 'IN (', $query);
		} else {
			$query = str_replace('{$__cakeID__$}', $ids[0], $query);
		}
		$query = str_replace(' WHERE 1 = 1', '', $query);

		return $this->fetchAll($query, $Model->cacheQueries);
	}

/**
 * Merge the results of 'hasMany' associations.
 *
 * Note: this function also deals with the formatting of the data.
 *
 * @param array &$resultSet Data to merge into.
 * @param array $assocResultSet Data to merge.
 * @param string $association Name of Model being merged.
 * @param Model $Model Model being merged onto.
 * @return void
 */
	protected function _mergeHasMany(&$resultSet, $assocResultSet, $association, Model $Model) {
		$modelAlias = $Model->alias;
		$primaryKey = $Model->primaryKey;
		$foreignKey = $Model->hasMany[$association]['foreignKey'];

		foreach ($resultSet as &$result) {
			if (!isset($result[$modelAlias])) {
				continue;
			}

			$resultPrimaryKey = $result[$modelAlias][$primaryKey];

			$merged = array();
			foreach ($assocResultSet as $data) {
				if ($resultPrimaryKey !== $data[$association][$foreignKey]) {
					continue;
				}

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

			$result = Hash::mergeDiff($result, array($association => $merged));
		}
	}

/**
 * Merge association of merge into data
 *
 * @param array &$data The data to merge.
 * @param array &$merge The data to merge.
 * @param string $association The association name to merge.
 * @param string $type The type of association
 * @param bool $selfJoin Whether or not this is a self join.
 * @return void
 */
	protected function _mergeAssociation(&$data, &$merge, $association, $type, $selfJoin = false) {
		if (isset($merge[0]) && !isset($merge[0][$association])) {
			$association = Inflector::pluralize($association);
		}

		$dataAssociation =& $data[$association];

		if ($type === 'belongsTo' || $type === 'hasOne') {
			if (isset($merge[$association])) {
				$dataAssociation = $merge[$association][0];
			} else {
				if (!empty($merge[0][$association])) {
					foreach ($merge[0] as $assoc => $data2) {
						if ($assoc !== $association) {
							$merge[0][$association][$assoc] = $data2;
						}
					}
				}
				if (!isset($dataAssociation)) {
					$dataAssociation = array();
					if ($merge[0][$association]) {
						$dataAssociation = $merge[0][$association];
					}
				} else {
					if (is_array($merge[0][$association])) {
						foreach ($dataAssociation as $k => $v) {
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
							$dataAssociation[$association] = $merge[0][$association];
						} else {
							$diff = Hash::diff($dataAssocTmp, $mergeAssocTmp);
							$dataAssociation = array_merge($merge[0][$association], $diff);
						}
					} elseif ($selfJoin && array_key_exists($association, $merge[0])) {
						$dataAssociation = array_merge($dataAssociation, array($association => array()));
					}
				}
			}
		} else {
			if (isset($merge[0][$association]) && $merge[0][$association] === false) {
				if (!isset($dataAssociation)) {
					$dataAssociation = array();
				}
			} else {
				foreach ($merge as $row) {
					$insert = array();
					if (count($row) === 1) {
						$insert = $row[$association];
					} elseif (isset($row[$association])) {
						$insert = array_merge($row[$association], $row);
						unset($insert[$association]);
					}

					if (empty($dataAssociation) || (isset($dataAssociation) && !in_array($insert, $dataAssociation, true))) {
						$dataAssociation[] = $insert;
					}
				}
			}
		}
	}

/**
 * Prepares fields required by an SQL statement.
 *
 * When no fields are set, all the $Model fields are returned.
 *
 * @param Model $Model The model to prepare.
 * @param array $queryData An array of queryData information containing keys similar to Model::find().
 * @return array Array containing SQL fields.
 */
	public function prepareFields(Model $Model, $queryData) {
		if (empty($queryData['fields'])) {
			$queryData['fields'] = $this->fields($Model);

		} elseif (!empty($Model->hasMany) && $Model->recursive > -1) {
			// hasMany relationships need the $Model primary key.
			$assocFields = $this->fields($Model, null, "{$Model->alias}.{$Model->primaryKey}");
			$passedFields = $queryData['fields'];

			if (
				count($passedFields) > 1 ||
				(strpos($passedFields[0], $assocFields[0]) === false && !preg_match('/^[a-z]+\(/i', $passedFields[0]))
			) {
				$queryData['fields'] = array_merge($passedFields, $assocFields);
			}
		}

		return array_unique($queryData['fields']);
	}

/**
 * Builds an SQL statement.
 *
 * This is merely a convenient wrapper to DboSource::buildStatement().
 *
 * @param Model $Model The model to build an association query for.
 * @param array $queryData An array of queryData information containing keys similar to Model::find().
 * @return string String containing an SQL statement.
 * @see DboSource::buildStatement()
 */
	public function buildAssociationQuery(Model $Model, $queryData) {
		$queryData = $this->_scrubQueryData($queryData);

		return $this->buildStatement(
			array(
				'fields' => $this->prepareFields($Model, $queryData),
				'table' => $this->fullTableName($Model),
				'alias' => $Model->alias,
				'limit' => $queryData['limit'],
				'offset' => $queryData['offset'],
				'joins' => $queryData['joins'],
				'conditions' => $queryData['conditions'],
				'order' => $queryData['order'],
				'group' => $queryData['group']
			),
			$Model
		);
	}

/**
 * Generates a query or part of a query from a single model or two associated models.
 *
 * Builds a string containing an SQL statement template.
 *
 * @param Model $Model Primary Model object.
 * @param Model|null $LinkModel Linked model object.
 * @param string $type Association type, one of the model association types ie. hasMany.
 * @param string $association Association name.
 * @param array $assocData Association data.
 * @param array &$queryData An array of queryData information containing keys similar to Model::find().
 * @param bool $external Whether or not the association query is on an external datasource.
 * @return mixed
 *   String representing a query.
 *   True, when $external is false and association $type is 'hasOne' or 'belongsTo'.
 */
	public function generateAssociationQuery(Model $Model, $LinkModel, $type, $association, $assocData, &$queryData, $external) {
		$assocData = $this->_scrubQueryData($assocData);
		$queryData = $this->_scrubQueryData($queryData);

		if ($LinkModel === null) {
			return $this->buildStatement(
				array(
					'fields' => array_unique($queryData['fields']),
					'table' => $this->fullTableName($Model),
					'alias' => $Model->alias,
					'limit' => $queryData['limit'],
					'offset' => $queryData['offset'],
					'joins' => $queryData['joins'],
					'conditions' => $queryData['conditions'],
					'order' => $queryData['order'],
					'group' => $queryData['group']
				),
				$Model
			);
		}

		if ($external && !empty($assocData['finderQuery'])) {
			return $assocData['finderQuery'];
		}

		if ($type === 'hasMany' || $type === 'hasAndBelongsToMany') {
			if (empty($assocData['offset']) && !empty($assocData['page'])) {
				$assocData['offset'] = ($assocData['page'] - 1) * $assocData['limit'];
			}
		}

		switch ($type) {
			case 'hasOne':
			case 'belongsTo':
				$conditions = $this->_mergeConditions(
					$assocData['conditions'],
					$this->getConstraint($type, $Model, $LinkModel, $association, array_merge($assocData, compact('external')))
				);

				if ($external) {
					// Not self join
					if ($Model->name !== $LinkModel->name) {
						$modelAlias = $Model->alias;
						foreach ($conditions as $key => $condition) {
							if (is_numeric($key) && strpos($condition, $modelAlias . '.') !== false) {
								unset($conditions[$key]);
							}
						}
					}

					$query = array_merge($assocData, array(
						'conditions' => $conditions,
						'table' => $this->fullTableName($LinkModel),
						'fields' => $this->fields($LinkModel, $association, $assocData['fields']),
						'alias' => $association,
						'group' => null
					));
				} else {
					$join = array(
						'table' => $LinkModel,
						'alias' => $association,
						'type' => isset($assocData['type']) ? $assocData['type'] : 'LEFT',
						'conditions' => trim($this->conditions($conditions, true, false, $Model))
					);

					$fields = array();
					if ($assocData['fields'] !== false) {
						$fields = $this->fields($LinkModel, $association, $assocData['fields']);
					}

					$queryData['fields'] = array_merge($this->prepareFields($Model, $queryData), $fields);

					if (!empty($assocData['order'])) {
						$queryData['order'][] = $assocData['order'];
					}
					if (!in_array($join, $queryData['joins'], true)) {
						$queryData['joins'][] = $join;
					}

					return true;
				}
				break;
			case 'hasMany':
				$assocData['fields'] = $this->fields($LinkModel, $association, $assocData['fields']);
				if (!empty($assocData['foreignKey'])) {
					$assocData['fields'] = array_merge($assocData['fields'], $this->fields($LinkModel, $association, array("{$association}.{$assocData['foreignKey']}")));
				}

				$query = array(
					'conditions' => $this->_mergeConditions($this->getConstraint('hasMany', $Model, $LinkModel, $association, $assocData), $assocData['conditions']),
					'fields' => array_unique($assocData['fields']),
					'table' => $this->fullTableName($LinkModel),
					'alias' => $association,
					'order' => $assocData['order'],
					'limit' => $assocData['limit'],
					'offset' => $assocData['offset'],
					'group' => null
				);
				break;
			case 'hasAndBelongsToMany':
				$joinFields = array();
				$joinAssoc = null;

				if (isset($assocData['with']) && !empty($assocData['with'])) {
					$joinKeys = array($assocData['foreignKey'], $assocData['associationForeignKey']);
					list($with, $joinFields) = $Model->joinModel($assocData['with'], $joinKeys);

					$joinTbl = $Model->{$with};
					$joinAlias = $joinTbl;

					if (is_array($joinFields) && !empty($joinFields)) {
						$joinAssoc = $joinAlias = $joinTbl->alias;
						$joinFields = $this->fields($joinTbl, $joinAlias, $joinFields);
					} else {
						$joinFields = array();
					}
				} else {
					$joinTbl = $assocData['joinTable'];
					$joinAlias = $this->fullTableName($assocData['joinTable']);
				}

				$query = array(
					'conditions' => $assocData['conditions'],
					'limit' => $assocData['limit'],
					'offset' => $assocData['offset'],
					'table' => $this->fullTableName($LinkModel),
					'alias' => $association,
					'fields' => array_merge($this->fields($LinkModel, $association, $assocData['fields']), $joinFields),
					'order' => $assocData['order'],
					'group' => null,
					'joins' => array(array(
						'table' => $joinTbl,
						'alias' => $joinAssoc,
						'conditions' => $this->getConstraint('hasAndBelongsToMany', $Model, $LinkModel, $joinAlias, $assocData, $association)
					))
				);
				break;
		}

		if (isset($query)) {
			return $this->buildStatement($query, $Model);
		}

		return null;
	}

/**
 * Returns a conditions array for the constraint between two models.
 *
 * @param string $type Association type.
 * @param Model $Model Primary Model object.
 * @param Model $LinkModel Linked model object.
 * @param string $association Association name.
 * @param array $assocData Association data.
 * @param string $association2 HABTM association name.
 * @return array Conditions array defining the constraint between $Model and $LinkModel.
 */
	public function getConstraint($type, Model $Model, Model $LinkModel, $association, $assocData, $association2 = null) {
		$assocData += array('external' => false);

		if (empty($assocData['foreignKey'])) {
			return array();
		}

		switch ($type) {
			case 'hasOne':
				if ($assocData['external']) {
					return array(
						"{$association}.{$assocData['foreignKey']}" => '{$__cakeID__$}'
					);
				} else {
					return array(
						"{$association}.{$assocData['foreignKey']}" => $this->identifier("{$Model->alias}.{$Model->primaryKey}")
					);
				}
			case 'belongsTo':
				if ($assocData['external']) {
					return array(
						"{$association}.{$LinkModel->primaryKey}" => '{$__cakeForeignKey__$}'
					);
				} else {
					return array(
						"{$Model->alias}.{$assocData['foreignKey']}" => $this->identifier("{$association}.{$LinkModel->primaryKey}")
					);
				}
			case 'hasMany':
				return array("{$association}.{$assocData['foreignKey']}" => array('{$__cakeID__$}'));
			case 'hasAndBelongsToMany':
				return array(
					array(
						"{$association}.{$assocData['foreignKey']}" => '{$__cakeID__$}'
					),
					array(
						"{$association}.{$assocData['associationForeignKey']}" => $this->identifier("{$association2}.{$LinkModel->primaryKey}")
					)
				);
		}

		return array();
	}

/**
 * Builds and generates a JOIN condition from an array. Handles final clean-up before conversion.
 *
 * @param array $join An array defining a JOIN condition in a query.
 * @return string An SQL JOIN condition to be used in a query.
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
		if (!empty($data['table']) && (!is_string($data['table']) || strpos($data['table'], '(') !== 0)) {
			$data['table'] = $this->fullTableName($data['table']);
		}
		return $this->renderJoinStatement($data);
	}

/**
 * Builds and generates an SQL statement from an array. Handles final clean-up before conversion.
 *
 * @param array $query An array defining an SQL query.
 * @param Model $Model The model object which initiated the query.
 * @return string An executable SQL statement.
 * @see DboSource::renderStatement()
 */
	public function buildStatement($query, Model $Model) {
		$query = array_merge($this->_queryDefaults, $query);

		if (!empty($query['joins'])) {
			$count = count($query['joins']);
			for ($i = 0; $i < $count; $i++) {
				if (is_array($query['joins'][$i])) {
					$query['joins'][$i] = $this->buildJoinStatement($query['joins'][$i]);
				}
			}
		}

		return $this->renderStatement('select', array(
			'conditions' => $this->conditions($query['conditions'], true, true, $Model),
			'fields' => implode(', ', $query['fields']),
			'table' => $query['table'],
			'alias' => $this->alias . $this->name($query['alias']),
			'order' => $this->order($query['order'], 'ASC', $Model),
			'limit' => $this->limit($query['limit'], $query['offset']),
			'joins' => implode(' ', $query['joins']),
			'group' => $this->group($query['group'], $Model)
		));
	}

/**
 * Renders a final SQL JOIN statement
 *
 * @param array $data The data to generate a join statement for.
 * @return string
 */
	public function renderJoinStatement($data) {
		if (strtoupper($data['type']) === 'CROSS') {
			return "{$data['type']} JOIN {$data['table']} {$data['alias']}";
		}
		return trim("{$data['type']} JOIN {$data['table']} {$data['alias']} ON ({$data['conditions']})");
	}

/**
 * Renders a final SQL statement by putting together the component parts in the correct order
 *
 * @param string $type type of query being run. e.g select, create, update, delete, schema, alter.
 * @param array $data Array of data to insert into the query.
 * @return string Rendered SQL expression to be run.
 */
	public function renderStatement($type, $data) {
		extract($data);
		$aliases = null;

		switch (strtolower($type)) {
			case 'select':
				return trim("SELECT {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order} {$limit}");
			case 'create':
				return "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
			case 'update':
				if (!empty($alias)) {
					$aliases = "{$this->alias}{$alias} {$joins} ";
				}
				return trim("UPDATE {$table} {$aliases}SET {$fields} {$conditions}");
			case 'delete':
				if (!empty($alias)) {
					$aliases = "{$this->alias}{$alias} {$joins} ";
				}
				return trim("DELETE {$alias} FROM {$table} {$aliases}{$conditions}");
			case 'schema':
				foreach (array('columns', 'indexes', 'tableParameters') as $var) {
					if (is_array(${$var})) {
						${$var} = "\t" . implode(",\n\t", array_filter(${$var}));
					} else {
						${$var} = '';
					}
				}
				if (trim($indexes) !== '') {
					$columns .= ',';
				}
				return "CREATE TABLE {$table} (\n{$columns}{$indexes}) {$tableParameters};";
			case 'alter':
				return;
		}
	}

/**
 * Merges a mixed set of string/array conditions.
 *
 * @param mixed $query The query to merge conditions for.
 * @param mixed $assoc The association names.
 * @return array
 */
	protected function _mergeConditions($query, $assoc) {
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
 * @param Model $Model The model to update.
 * @param array $fields The fields to update
 * @param array $values The values fo the fields.
 * @param mixed $conditions The conditions for the update. When non-empty $values will not be quoted.
 * @return bool Success
 */
	public function update(Model $Model, $fields = array(), $values = null, $conditions = null) {
		if (!$values) {
			$combined = $fields;
		} else {
			$combined = array_combine($fields, $values);
		}

		$fields = implode(', ', $this->_prepareUpdateFields($Model, $combined, empty($conditions)));

		$alias = $joins = null;
		$table = $this->fullTableName($Model);
		$conditions = $this->_matchRecords($Model, $conditions);

		if ($conditions === false) {
			return false;
		}
		$query = compact('table', 'alias', 'joins', 'fields', 'conditions');

		if (!$this->execute($this->renderStatement('update', $query))) {
			$Model->onError();
			return false;
		}
		return true;
	}

/**
 * Quotes and prepares fields and values for an SQL UPDATE statement
 *
 * @param Model $Model The model to prepare fields for.
 * @param array $fields The fields to update.
 * @param bool $quoteValues If values should be quoted, or treated as SQL snippets
 * @param bool $alias Include the model alias in the field name
 * @return array Fields and values, quoted and prepared
 */
	protected function _prepareUpdateFields(Model $Model, $fields, $quoteValues = true, $alias = false) {
		$quotedAlias = $this->startQuote . $Model->alias . $this->endQuote;

		$updates = array();
		foreach ($fields as $field => $value) {
			if ($alias && strpos($field, '.') === false) {
				$quoted = $Model->escapeField($field);
			} elseif (!$alias && strpos($field, '.') !== false) {
				$quoted = $this->name(str_replace($quotedAlias . '.', '', str_replace(
					$Model->alias . '.', '', $field
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
				$update .= $this->value($value, $Model->getColumnType($field));
			} elseif ($Model->getColumnType($field) === 'boolean' && (is_int($value) || is_bool($value))) {
				$update .= $this->boolean($value, true);
			} elseif (!$alias) {
				$update .= str_replace($quotedAlias . '.', '', str_replace(
					$Model->alias . '.', '', $value
				));
			} else {
				$update .= $value;
			}
			$updates[] = $update;
		}
		return $updates;
	}

/**
 * Generates and executes an SQL DELETE statement.
 * For databases that do not support aliases in UPDATE queries.
 *
 * @param Model $Model The model to delete from
 * @param mixed $conditions The conditions to use. If empty the model's primary key will be used.
 * @return bool Success
 */
	public function delete(Model $Model, $conditions = null) {
		$alias = $joins = null;
		$table = $this->fullTableName($Model);
		$conditions = $this->_matchRecords($Model, $conditions);

		if ($conditions === false) {
			return false;
		}

		if ($this->execute($this->renderStatement('delete', compact('alias', 'table', 'joins', 'conditions'))) === false) {
			$Model->onError();
			return false;
		}
		return true;
	}

/**
 * Gets a list of record IDs for the given conditions. Used for multi-record updates and deletes
 * in databases that do not support aliases in UPDATE/DELETE queries.
 *
 * @param Model $Model The model to find matching records for.
 * @param mixed $conditions The conditions to match against.
 * @return array List of record IDs
 */
	protected function _matchRecords(Model $Model, $conditions = null) {
		if ($conditions === true) {
			$conditions = $this->conditions(true);
		} elseif ($conditions === null) {
			$conditions = $this->conditions($this->defaultConditions($Model, $conditions, false), true, true, $Model);
		} else {
			$noJoin = true;
			foreach ($conditions as $field => $value) {
				$originalField = $field;
				if (strpos($field, '.') !== false) {
					list(, $field) = explode('.', $field);
					$field = ltrim($field, $this->startQuote);
					$field = rtrim($field, $this->endQuote);
				}
				if (!$Model->hasField($field)) {
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
			$idList = $Model->find('all', array(
				'fields' => "{$Model->alias}.{$Model->primaryKey}",
				'conditions' => $conditions
			));

			if (empty($idList)) {
				return false;
			}

			$conditions = $this->conditions(array(
				$Model->primaryKey => Hash::extract($idList, "{n}.{$Model->alias}.{$Model->primaryKey}")
			));
		}

		return $conditions;
	}

/**
 * Returns an array of SQL JOIN conditions from a model's associations.
 *
 * @param Model $Model The model to get joins for.2
 * @return array
 */
	protected function _getJoins(Model $Model) {
		$join = array();
		$joins = array_merge($Model->getAssociated('hasOne'), $Model->getAssociated('belongsTo'));

		foreach ($joins as $assoc) {
			if (!isset($Model->{$assoc})) {
				continue;
			}

			$LinkModel = $Model->{$assoc};

			if ($Model->useDbConfig !== $LinkModel->useDbConfig) {
				continue;
			}

			$assocData = $Model->getAssociated($assoc);

			$join[] = $this->buildJoinStatement(array(
				'table' => $LinkModel,
				'alias' => $assoc,
				'type' => isset($assocData['type']) ? $assocData['type'] : 'LEFT',
				'conditions' => trim($this->conditions(
					$this->_mergeConditions($assocData['conditions'], $this->getConstraint($assocData['association'], $Model, $LinkModel, $assoc, $assocData)),
					true,
					false,
					$Model
				))
			));
		}

		return $join;
	}

/**
 * Returns an SQL calculation, i.e. COUNT() or MAX()
 *
 * @param Model $Model The model to get a calculated field for.
 * @param string $func Lowercase name of SQL function, i.e. 'count' or 'max'
 * @param array $params Function parameters (any values must be quoted manually)
 * @return string An SQL calculation function
 */
	public function calculate(Model $Model, $func, $params = array()) {
		$params = (array)$params;

		switch (strtolower($func)) {
			case 'count':
				if (!isset($params[0])) {
					$params[0] = '*';
				}
				if (!isset($params[1])) {
					$params[1] = 'count';
				}
				if ($Model->isVirtualField($params[0])) {
					$arg = $this->_quoteFields($Model->getVirtualField($params[0]));
				} else {
					$arg = $this->name($params[0]);
				}
				return 'COUNT(' . $arg . ') AS ' . $this->name($params[1]);
			case 'max':
			case 'min':
				if (!isset($params[1])) {
					$params[1] = $params[0];
				}
				if ($Model->isVirtualField($params[0])) {
					$arg = $this->_quoteFields($Model->getVirtualField($params[0]));
				} else {
					$arg = $this->name($params[0]);
				}
				return strtoupper($func) . '(' . $arg . ') AS ' . $this->name($params[1]);
		}
	}

/**
 * Deletes all the records in a table and resets the count of the auto-incrementing
 * primary key, where applicable.
 *
 * @param Model|string $table A string or model class representing the table to be truncated
 * @return bool SQL TRUNCATE TABLE statement, false if not applicable.
 */
	public function truncate($table) {
		return $this->execute('TRUNCATE TABLE ' . $this->fullTableName($table));
	}

/**
 * Check if the server support nested transactions
 *
 * @return bool
 */
	public function nestedTransactionSupported() {
		return false;
	}

/**
 * Begin a transaction
 *
 * @return bool True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function begin() {
		if ($this->_transactionStarted) {
			if ($this->nestedTransactionSupported()) {
				return $this->_beginNested();
			}
			$this->_transactionNesting++;
			return $this->_transactionStarted;
		}

		$this->_transactionNesting = 0;
		if ($this->fullDebug) {
			$this->logQuery('BEGIN');
		}
		return $this->_transactionStarted = $this->_connection->beginTransaction();
	}

/**
 * Begin a nested transaction
 *
 * @return bool
 */
	protected function _beginNested() {
		$query = 'SAVEPOINT LEVEL' . ++$this->_transactionNesting;
		if ($this->fullDebug) {
			$this->logQuery($query);
		}
		$this->_connection->exec($query);
		return true;
	}

/**
 * Commit a transaction
 *
 * @return bool True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function commit() {
		if (!$this->_transactionStarted) {
			return false;
		}

		if ($this->_transactionNesting === 0) {
			if ($this->fullDebug) {
				$this->logQuery('COMMIT');
			}
			$this->_transactionStarted = false;
			return $this->_connection->commit();
		}

		if ($this->nestedTransactionSupported()) {
			return $this->_commitNested();
		}

		$this->_transactionNesting--;
		return true;
	}

/**
 * Commit a nested transaction
 *
 * @return bool
 */
	protected function _commitNested() {
		$query = 'RELEASE SAVEPOINT LEVEL' . $this->_transactionNesting--;
		if ($this->fullDebug) {
			$this->logQuery($query);
		}
		$this->_connection->exec($query);
		return true;
	}

/**
 * Rollback a transaction
 *
 * @return bool True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	public function rollback() {
		if (!$this->_transactionStarted) {
			return false;
		}

		if ($this->_transactionNesting === 0) {
			if ($this->fullDebug) {
				$this->logQuery('ROLLBACK');
			}
			$this->_transactionStarted = false;
			return $this->_connection->rollBack();
		}

		if ($this->nestedTransactionSupported()) {
			return $this->_rollbackNested();
		}

		$this->_transactionNesting--;
		return true;
	}

/**
 * Rollback a nested transaction
 *
 * @return bool
 */
	protected function _rollbackNested() {
		$query = 'ROLLBACK TO SAVEPOINT LEVEL' . $this->_transactionNesting--;
		if ($this->fullDebug) {
			$this->logQuery($query);
		}
		$this->_connection->exec($query);
		return true;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param mixed $source The source to get an id for.
 * @return mixed
 */
	public function lastInsertId($source = null) {
		return $this->_connection->lastInsertId();
	}

/**
 * Creates a default set of conditions from the model if $conditions is null/empty.
 * If conditions are supplied then they will be returned. If a model doesn't exist and no conditions
 * were provided either null or false will be returned based on what was input.
 *
 * @param Model $Model The model to get conditions for.
 * @param string|array|bool $conditions Array of conditions, conditions string, null or false. If an array of conditions,
 *   or string conditions those conditions will be returned. With other values the model's existence will be checked.
 *   If the model doesn't exist a null or false will be returned depending on the input value.
 * @param bool $useAlias Use model aliases rather than table names when generating conditions
 * @return mixed Either null, false, $conditions or an array of default conditions to use.
 * @see DboSource::update()
 * @see DboSource::conditions()
 */
	public function defaultConditions(Model $Model, $conditions, $useAlias = true) {
		if (!empty($conditions)) {
			return $conditions;
		}
		$exists = $Model->exists();
		if (!$exists && ($conditions !== null || !empty($Model->__safeUpdateMode))) {
			return false;
		} elseif (!$exists) {
			return null;
		}
		$alias = $Model->alias;

		if (!$useAlias) {
			$alias = $this->fullTableName($Model, false);
		}
		return array("{$alias}.{$Model->primaryKey}" => $Model->getID());
	}

/**
 * Returns a key formatted like a string Model.fieldname(i.e. Post.title, or Country.name)
 *
 * @param Model $Model The model to get a key for.
 * @param string $key The key field.
 * @param string $assoc The association name.
 * @return string
 */
	public function resolveKey(Model $Model, $key, $assoc = null) {
		if (strpos('.', $key) !== false) {
			return $this->name($Model->alias) . '.' . $this->name($key);
		}
		return $key;
	}

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data The data to scrub.
 * @return array
 */
	protected function _scrubQueryData($data) {
		static $base = null;
		if ($base === null) {
			$base = array_fill_keys(array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group'), array());
			$base['callbacks'] = null;
		}
		return (array)$data + $base;
	}

/**
 * Converts model virtual fields into sql expressions to be fetched later
 *
 * @param Model $Model The model to get virtual fields for.
 * @param string $alias Alias table name
 * @param array $fields virtual fields to be used on query
 * @return array
 */
	protected function _constructVirtualFields(Model $Model, $alias, $fields) {
		$virtual = array();
		foreach ($fields as $field) {
			$virtualField = $this->name($alias . $this->virtualFieldSeparator . $field);
			$expression = $this->_quoteFields($Model->getVirtualField($field));
			$virtual[] = '(' . $expression . ") {$this->alias} {$virtualField}";
		}
		return $virtual;
	}

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $Model The model to get fields for.
 * @param string $alias Alias table name
 * @param mixed $fields The provided list of fields.
 * @param bool $quote If false, returns fields array unquoted
 * @return array
 */
	public function fields(Model $Model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $Model->alias;
		}
		$virtualFields = $Model->getVirtualField();
		$cacheKey = array(
			$alias,
			get_class($Model),
			$Model->alias,
			$virtualFields,
			$fields,
			$quote,
			ConnectionManager::getSourceName($this),
			$Model->schemaName,
			$Model->table
		);
		$cacheKey = md5(serialize($cacheKey));
		if ($return = $this->cacheMethod(__FUNCTION__, $cacheKey)) {
			return $return;
		}
		$allFields = empty($fields);
		if ($allFields) {
			$fields = array_keys($Model->schema());
		} elseif (!is_array($fields)) {
			$fields = String::tokenize($fields);
		}
		$fields = array_values(array_filter($fields));
		$allFields = $allFields || in_array('*', $fields) || in_array($Model->alias . '.*', $fields);

		$virtual = array();
		if (!empty($virtualFields)) {
			$virtualKeys = array_keys($virtualFields);
			foreach ($virtualKeys as $field) {
				$virtualKeys[] = $Model->alias . '.' . $field;
			}
			$virtual = ($allFields) ? $virtualKeys : array_intersect($virtualKeys, $fields);
			foreach ($virtual as $i => $field) {
				if (strpos($field, '.') !== false) {
					$virtual[$i] = str_replace($Model->alias . '.', '', $field);
				}
				$fields = array_diff($fields, array($field));
			}
			$fields = array_values($fields);
		}
		if (!$quote) {
			if (!empty($virtual)) {
				$fields = array_merge($fields, $this->_constructVirtualFields($Model, $alias, $virtual));
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
				} elseif (preg_match('/^\(.*\)\s' . $this->alias . '.*/i', $fields[$i])) {
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
						if (strpos($fields[$i], ',') === false) {
							$build = explode('.', $fields[$i]);
							if (!Hash::numeric($build)) {
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
							if (!Hash::numeric($field[0])) {
								$field[0] = implode('.', array_map(array(&$this, 'name'), $field[0]));
								$fields[$i] = preg_replace('/\(' . $field[1] . '\)/', '(' . $field[0] . ')', $fields[$i], 1);
							}
						}
					}
				}
			}
		}
		if (!empty($virtual)) {
			$fields = array_merge($fields, $this->_constructVirtualFields($Model, $alias, $virtual));
		}
		return $this->cacheMethod(__FUNCTION__, $cacheKey, array_unique($fields));
	}

/**
 * Creates a WHERE clause by parsing given conditions data. If an array or string
 * conditions are provided those conditions will be parsed and quoted. If a boolean
 * is given it will be integer cast as condition. Null will return 1 = 1.
 *
 * Results of this method are stored in a memory cache. This improves performance, but
 * because the method uses a hashing algorithm it can have collisions.
 * Setting DboSource::$cacheMethods to false will disable the memory cache.
 *
 * @param mixed $conditions Array or string of conditions, or any value.
 * @param bool $quoteValues If true, values should be quoted
 * @param bool $where If true, "WHERE " will be prepended to the return value
 * @param Model $Model A reference to the Model instance making the query
 * @return string SQL fragment
 */
	public function conditions($conditions, $quoteValues = true, $where = true, Model $Model = null) {
		$clause = $out = '';

		if ($where) {
			$clause = ' WHERE ';
		}

		if (is_array($conditions) && !empty($conditions)) {
			$out = $this->conditionKeysToString($conditions, $quoteValues, $Model);

			if (empty($out)) {
				return $clause . ' 1 = 1';
			}
			return $clause . implode(' AND ', $out);
		}

		if (is_bool($conditions)) {
			return $clause . (int)$conditions . ' = 1';
		}

		if (empty($conditions) || trim($conditions) === '') {
			return $clause . '1 = 1';
		}

		$clauses = '/^WHERE\\x20|^GROUP\\x20BY\\x20|^HAVING\\x20|^ORDER\\x20BY\\x20/i';

		if (preg_match($clauses, $conditions)) {
			$clause = '';
		}

		$conditions = $this->_quoteFields($conditions);

		return $clause . $conditions;
	}

/**
 * Creates a WHERE clause by parsing given conditions array. Used by DboSource::conditions().
 *
 * @param array $conditions Array or string of conditions
 * @param bool $quoteValues If true, values should be quoted
 * @param Model $Model A reference to the Model instance making the query
 * @return string SQL fragment
 */
	public function conditionKeysToString($conditions, $quoteValues = true, Model $Model = null) {
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
				$out[] = $this->_quoteFields($value);
			} elseif ((is_numeric($key) && is_array($value)) || in_array(strtolower(trim($key)), $bool)) {
				if (in_array(strtolower(trim($key)), $bool)) {
					$join = ' ' . strtoupper($key) . ' ';
				} else {
					$key = $join;
				}
				$value = $this->conditionKeysToString($value, $quoteValues, $Model);

				if (strpos($join, 'NOT') !== false) {
					if (strtoupper(trim($key)) === 'NOT') {
						$key = 'AND ' . trim($key);
					}
					$not = 'NOT ';
				}

				if (empty($value)) {
					continue;
				}

				if (empty($value[1])) {
					if ($not) {
						$out[] = $not . '(' . $value[0] . ')';
					} else {
						$out[] = $value[0];
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
						if ($count === 1 && !preg_match('/\s+(?:NOT|\!=)$/', $key)) {
							$data = $this->_quoteFields($key) . ' = (';
							if ($quoteValues) {
								if ($Model !== null) {
									$columnType = $Model->getColumnType($key);
								}
								$data .= implode(', ', $this->value($value, $columnType));
							}
							$data .= ')';
						} else {
							$data = $this->_parseKey($key, $value, $Model);
						}
					} else {
						$ret = $this->conditionKeysToString($value, $quoteValues, $Model);
						if (count($ret) > 1) {
							$data = '(' . implode(') AND (', $ret) . ')';
						} elseif (isset($ret[0])) {
							$data = $ret[0];
						}
					}
				} elseif (is_numeric($key) && !empty($value)) {
					$data = $this->_quoteFields($value);
				} else {
					$data = $this->_parseKey(trim($key), $value, $Model);
				}

				if ($data) {
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
 * @param string $key An SQL key snippet containing a field and optional SQL operator
 * @param mixed $value The value(s) to be inserted in the string
 * @param Model $Model Model object initiating the query
 * @return string
 */
	protected function _parseKey($key, $value, Model $Model = null) {
		$operatorMatch = '/^(((' . implode(')|(', $this->_sqlOps);
		$operatorMatch .= ')\\x20?)|<[>=]?(?![^>]+>)\\x20?|[>=!]{1,3}(?!<)\\x20?)/is';
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
		$type = null;

		if ($Model !== null) {
			if ($Model->isVirtualField($key)) {
				$key = $this->_quoteFields($Model->getVirtualField($key));
				$virtual = true;
			}

			$type = $Model->getColumnType($key);
		}

		$null = $value === null || (is_array($value) && empty($value));

		if (strtolower($operator) === 'not') {
			$data = $this->conditionKeysToString(
				array($operator => array($key => $value)), true, $Model
			);
			return $data[0];
		}

		$value = $this->value($value, $type);

		if (!$virtual && $key !== '?') {
			$isKey = (
				strpos($key, '(') !== false ||
				strpos($key, ')') !== false ||
				strpos($key, '|') !== false
			);
			$key = $isKey ? $this->_quoteFields($key) : $this->name($key);
		}

		if ($bound) {
			return String::insert($key . ' ' . trim($operator), $value);
		}

		if (!preg_match($operatorMatch, trim($operator))) {
			$operator .= is_array($value) ? ' IN' : ' =';
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
		} elseif ($null || $value === 'NULL') {
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
 * @param string $conditions The conditions to quote.
 * @return string or false if no match
 */
	protected function _quoteFields($conditions) {
		$start = $end = null;
		$original = $conditions;

		if (!empty($this->startQuote)) {
			$start = preg_quote($this->startQuote);
		}
		if (!empty($this->endQuote)) {
			$end = preg_quote($this->endQuote);
		}
		$conditions = str_replace(array($start, $end), '', $conditions);
		$conditions = preg_replace_callback(
			'/(?:[\'\"][^\'\"\\\]*(?:\\\.[^\'\"\\\]*)*[\'\"])|([a-z0-9_][a-z0-9\\-_]*\\.[a-z0-9_][a-z0-9_\\-]*)/i',
			array(&$this, '_quoteMatchedField'),
			$conditions
		);
		if ($conditions !== null) {
			return $conditions;
		}
		return $original;
	}

/**
 * Auxiliary function to quote matches `Model.fields` from a preg_replace_callback call
 *
 * @param string $match matched string
 * @return string quoted string
 */
	protected function _quoteMatchedField($match) {
		if (is_numeric($match[0])) {
			return $match[0];
		}
		return $this->name($match[0]);
	}

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	public function limit($limit, $offset = null) {
		if ($limit) {
			$rt = ' LIMIT';

			if ($offset) {
				$rt .= sprintf(' %u,', $offset);
			}

			$rt .= sprintf(' %u', $limit);
			return $rt;
		}
		return null;
	}

/**
 * Returns an ORDER BY clause as a string.
 *
 * @param array|string $keys Field reference, as a key (i.e. Post.title)
 * @param string $direction Direction (ASC or DESC)
 * @param Model $Model Model reference (used to look for virtual field)
 * @return string ORDER BY clause
 */
	public function order($keys, $direction = 'ASC', Model $Model = null) {
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

			if ($Model !== null) {
				if ($Model->isVirtualField($key)) {
					$key = '(' . $this->_quoteFields($Model->getVirtualField($key)) . ')';
				}

				list($alias) = pluginSplit($key);

				if ($alias !== $Model->alias && is_object($Model->{$alias}) && $Model->{$alias}->isVirtualField($key)) {
					$key = '(' . $this->_quoteFields($Model->{$alias}->getVirtualField($key)) . ')';
				}
			}

			if (strpos($key, '.')) {
				$key = preg_replace_callback('/([a-zA-Z0-9_-]{1,})\\.([a-zA-Z0-9_-]{1,})/', array(&$this, '_quoteMatchedField'), $key);
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
 * Create a GROUP BY SQL clause.
 *
 * @param string|array $fields Group By fields
 * @param Model $Model The model to get group by fields for.
 * @return string Group By clause or null.
 */
	public function group($fields, Model $Model = null) {
		if (empty($fields)) {
			return null;
		}

		if (!is_array($fields)) {
			$fields = array($fields);
		}

		if ($Model !== null) {
			foreach ($fields as $index => $key) {
				if ($Model->isVirtualField($key)) {
					$fields[$index] = '(' . $Model->getVirtualField($key) . ')';
				}
			}
		}

		$fields = implode(', ', $fields);

		return ' GROUP BY ' . $this->_quoteFields($fields);
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
 * @param Model $Model Model to search
 * @param string $sql SQL WHERE clause (condition only, not the "WHERE" part)
 * @return bool True if the table has a matching record, else false
 */
	public function hasAny(Model $Model, $sql) {
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
 * @return mixed An integer or string representing the length of the column, or null for unknown length.
 */
	public function length($real) {
		if (!preg_match_all('/([\w\s]+)(?:\((\d+)(?:,(\d+))?\))?(\sunsigned)?(\szerofill)?/', $real, $result)) {
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

		list($real, $type, $length, $offset, $sign) = $result;
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
 * @param bool $quote Whether or not the field should be cast to a string.
 * @return string|bool Converted boolean value
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
 * @param string $table The table being inserted into.
 * @param array $fields The array of field/column names being inserted.
 * @param array $values The array of values to insert. The values should
 *   be an array of rows. Each row should have values keyed by the column name.
 *   Each row must have the values in the same order as $fields.
 * @return bool
 */
	public function insertMulti($table, $fields, $values) {
		$table = $this->fullTableName($table);
		$holder = implode(',', array_fill(0, count($fields), '?'));
		$fields = implode(', ', array_map(array(&$this, 'name'), $fields));

		$pdoMap = array(
			'integer' => PDO::PARAM_INT,
			'float' => PDO::PARAM_STR,
			'boolean' => PDO::PARAM_BOOL,
			'string' => PDO::PARAM_STR,
			'text' => PDO::PARAM_STR
		);
		$columnMap = array();

		$sql = "INSERT INTO {$table} ({$fields}) VALUES ({$holder})";
		$statement = $this->_connection->prepare($sql);
		$this->begin();

		foreach ($values[key($values)] as $key => $val) {
			$type = $this->introspectType($val);
			$columnMap[$key] = $pdoMap[$type];
		}

		foreach ($values as $value) {
			$i = 1;
			foreach ($value as $col => $val) {
				$statement->bindValue($i, $val, $columnMap[$col]);
				$i += 1;
			}
			$statement->execute();
			$statement->closeCursor();

			if ($this->fullDebug) {
				$this->logQuery($sql, $value);
			}
		}
		return $this->commit();
	}

/**
 * Reset a sequence based on the MAX() value of $column. Useful
 * for resetting sequences after using insertMulti().
 *
 * This method should be implemented by datasources that require sequences to be used.
 *
 * @param string $table The name of the table to update.
 * @param string $column The column to use when resetting the sequence value.
 * @return bool|void success.
 */
	public function resetSequence($table, $column) {
	}

/**
 * Returns an array of the indexes in given datasource name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	public function index($model) {
		return array();
	}

/**
 * Generate a database-native schema for the given Schema object
 *
 * @param CakeSchema $schema An instance of a subclass of CakeSchema
 * @param string $tableName Optional. If specified only the table name given will be generated.
 *   Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	public function createSchema($schema, $tableName = null) {
		if (!$schema instanceof CakeSchema) {
			trigger_error(__d('cake_dev', 'Invalid schema object'), E_USER_WARNING);
			return null;
		}
		$out = '';

		foreach ($schema->tables as $curTable => $columns) {
			if (!$tableName || $tableName === $curTable) {
				$cols = $indexes = $tableParameters = array();
				$primary = null;
				$table = $this->fullTableName($curTable);

				$primaryCount = 0;
				foreach ($columns as $col) {
					if (isset($col['key']) && $col['key'] === 'primary') {
						$primaryCount++;
					}
				}

				foreach ($columns as $name => $col) {
					if (is_string($col)) {
						$col = array('type' => $col);
					}
					$isPrimary = isset($col['key']) && $col['key'] === 'primary';
					// Multi-column primary keys are not supported.
					if ($isPrimary && $primaryCount > 1) {
						unset($col['key']);
						$isPrimary = false;
					}
					if ($isPrimary) {
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
				if (!isset($columns['indexes']['PRIMARY']) && !empty($primary)) {
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
 * Generate an alter syntax from CakeSchema::compare()
 *
 * @param mixed $compare The comparison data.
 * @param string $table The table name.
 * @return bool
 */
	public function alterSchema($compare, $table = null) {
		return false;
	}

/**
 * Generate a "drop table" statement for the given Schema object
 *
 * @param CakeSchema $schema An instance of a subclass of CakeSchema
 * @param string $table Optional. If specified only the table name given will be generated.
 *   Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	public function dropSchema(CakeSchema $schema, $table = null) {
		$out = '';

		if ($table && array_key_exists($table, $schema->tables)) {
			return $this->_dropTable($table) . "\n";
		} elseif ($table) {
			return $out;
		}

		foreach (array_keys($schema->tables) as $curTable) {
			$out .= $this->_dropTable($curTable) . "\n";
		}
		return $out;
	}

/**
 * Generate a "drop table" statement for a single table
 *
 * @param type $table Name of the table to drop
 * @return string Drop table SQL statement
 */
	protected function _dropTable($table) {
		return 'DROP TABLE ' . $this->fullTableName($table) . ";";
	}

/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following: array('name' => 'value', 'type' => 'value'[, options]),
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

		if (($column['type'] === 'integer' || $column['type'] === 'float') && isset($column['default']) && $column['default'] === '') {
			$column['default'] = null;
		}
		$out = $this->_buildFieldParameters($out, $column, 'beforeDefault');

		if (isset($column['key']) && $column['key'] === 'primary' && ($type === 'integer' || $type === 'biginteger')) {
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
	protected function _buildFieldParameters($columnString, $columnData, $position) {
		foreach ($this->fieldParameters as $paramName => $value) {
			if (isset($columnData[$paramName]) && $value['position'] == $position) {
				if (isset($value['options']) && !in_array($columnData[$paramName], $value['options'], true)) {
					continue;
				}
				if (isset($value['types']) && !in_array($columnData['type'], $value['types'], true)) {
					continue;
				}
				$val = $columnData[$paramName];
				if ($value['quote']) {
					$val = $this->value($val);
				}
				$columnString .= ' ' . $value['value'] . (empty($value['noVal']) ? $value['join'] . $val : '');
			}
		}
		return $columnString;
	}

/**
 * Format indexes for create table.
 *
 * @param array $indexes The indexes to build
 * @param string $table The table name.
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
 * @param string $name The table name to read.
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
 * @param array $parameters The parameters to create SQL for.
 * @param string $table The table name.
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
 * @param string $value The value to introspect for type data.
 * @return string
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
		$containsInt = $containsString = false;
		foreach ($value as $valElement) {
			$valElement = trim($valElement);
			if (!is_float($valElement) && !preg_match('/^[\d]+\.[\d]+$/', $valElement)) {
				$isAllFloat = false;
			} else {
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

/**
 * Used for storing in cache the results of the in-memory methodCache
 */
	public function __destruct() {
		if ($this->_methodCacheChange) {
			Cache::write('method_cache', self::$methodCache, '_cake_core_');
		}
	}

}
