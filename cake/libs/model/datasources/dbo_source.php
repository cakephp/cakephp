<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.datasources
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('set');
/**
 * DboSource
 *
 * Creates DBO-descendant objects from a given db connection configuration
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.datasources
 */
class DboSource extends DataSource {
/**
 * Description string for this Database Data Source.
 *
 * @var unknown_type
 */
	var $description = "Database Data Source";
/**
 * index definition, standard cake, primary, index, unique
 *
 * @var array
 */
	var $index = array('PRI'=> 'primary', 'MUL'=> 'index', 'UNI'=>'unique');
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $startQuote = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $endQuote = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $alias = 'AS ';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $goofyLimit = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $__bypass = false;
/**
 * The set of valid SQL operations usable in a WHERE statement
 *
 * @var array
 */
	var $__sqlOps = array('like', 'ilike', 'or', 'not', 'in', 'between', 'regexp', 'similar to');
/**
 * Constructor
 */
	function __construct($config = null, $autoConnect = true) {
		$this->debug = Configure::read() > 0;
		$this->fullDebug = Configure::read() > 1;
		parent::__construct($config);

		if ($autoConnect) {
			return $this->connect();
		} else {
			return true;
		}
	}
/**
 * Reconnects to database server with optional new settings
 *
 * @param array $config An array defining the new configuration settings
 * @return boolean True on success, false on failure
 */
	function reconnect($config = null) {
		$this->disconnect();
		if ($config != null) {
			$this->config = am($this->_baseConfig, $config);
		}
		return $this->connect();
	}
/**
 * Prepares a value, or an array of values for database queries by quoting and escaping them.
 *
 * @param mixed $data A value or an array of values to prepare.
 * @return mixed Prepared value or array of values.
 */
	function value($data, $column = null) {
		if (is_array($data)) {
			$out = array();
			$keys = array_keys($data);
			$count = count($data);
			for ($i = 0; $i < $count; $i++) {
				$out[$keys[$i]] = $this->value($data[$keys[$i]]);
			}
			return $out;
		} elseif (in_array($data, array('{$__cakeID__$}', '{$__cakeForeignKey__$}'), true)) {
			return $data;
		} else {
			return null;
		}
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return unknown
 */
	function rawQuery($sql) {
		$this->took = $this->error = $this->numRows = false;
		return $this->execute($sql);
	}
/**
 * Queries the database with given SQL statement, and obtains some metadata about the result
 * (rows affected, timing, any errors, number of rows in resultset). The query is also logged.
 * If DEBUG is set, the log is shown all the time, else it is only shown on errors.
 *
 * @param string $sql
 * @return unknown
 */
	function execute($sql) {
		$t = getMicrotime();
		$this->_result = $this->_execute($sql);
		$this->affected = $this->lastAffected();
		$this->took = round((getMicrotime() - $t) * 1000, 0);
		$this->error = $this->lastError();
		$this->numRows = $this->lastNumRows($this->_result);

		if ($this->fullDebug && Configure::read() > 1) {
			$this->logQuery($sql);
		}

		if ($this->error) {
			$this->showQuery($sql);
			return false;
		} else {
			return $this->_result;
		}
	}
/**
 * DataSource Query abstraction
 *
 * @return resource Result resource identifier
 */
	function query() {
		$args     = func_get_args();
		$fields   = null;
		$order    = null;
		$limit    = null;
		$page     = null;
		$recursive = null;

		if (count($args) == 1) {
			return $this->fetchAll($args[0]);

		} elseif (count($args) > 1 && (strpos(low($args[0]), 'findby') === 0 || strpos(low($args[0]), 'findallby') === 0)) {
			$params = $args[1];

			if (strpos(strtolower($args[0]), 'findby') === 0) {
				$all  = false;
				$field = Inflector::underscore(preg_replace('/findBy/i', '', $args[0]));
			} else {
				$all  = true;
				$field = Inflector::underscore(preg_replace('/findAllBy/i', '', $args[0]));
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
			$query = array();
			foreach ($field as $f) {
				if (!is_array($params[$c]) && !empty($params[$c]) && $params[$c] !== true && $params[$c] !== false) {
					$query[$args[2]->name . '.' . $f] = '= ' . $params[$c];
				} else {
					$query[$args[2]->name . '.' . $f] = $params[$c];
				}
				$c++;
			}

			if ($or) {
				$query = array('OR' => $query);
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
				return $args[2]->findAll($query, $fields, $order, $limit, $page, $recursive);
			} else {
				if (isset($params[3 + $off])) {
					$recursive = $params[3 + $off];
				}
				return $args[2]->find($query, $fields, $order, $recursive);
			}
		} else {
			if (isset($args[1]) && $args[1] === true) {
				return $this->fetchAll($args[0], true);
			}
			return $this->fetchAll($args[0], false);
		}
	}
/**
 * Returns a row from current resultset as an array .
 *
 * @return array The fetched row as an array
 */
	function fetchRow($sql = null) {

		if (!empty($sql) && is_string($sql) && strlen($sql) > 5) {
			if (!$this->execute($sql)) {
				return null;
			}
		}

		if (is_resource($this->_result) || is_object($this->_result)) {
			$this->resultSet($this->_result);
			$resultRow = $this->fetchResult();
			return $resultRow;
		} else {
			return null;
		}
	}
/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 * @param string $sql SQL statement
 * @param bool $cache Enables returning/storing cached query results
 * @return array Array of resultset rows, or false if no rows matched
 */
	function fetchAll($sql, $cache = true, $modelName = null) {
		if ($cache && isset($this->_queryCache[$sql])) {
			if (preg_match('/^\s*select/i', $sql)) {
				return $this->_queryCache[$sql];
			}
		}

		if ($this->execute($sql)) {
			$out = array();

			while ($item = $this->fetchRow()) {
				$out[] = $item;
			}

			if ($cache) {
				if (strpos(trim(strtolower($sql)), 'select') !== false) {
					$this->_queryCache[$sql] = $out;
				}
			}
			return $out;

		} else {
			return false;
		}
	}
/**
 * Returns a single field of the first of query results for a given SQL query, or false if empty.
 *
 * @param string $name Name of the field
 * @param string $sql SQL query
 * @return unknown
 */
	function field($name, $sql) {
		$data = $this->fetchRow($sql);

		if (!isset($data[$name]) || empty($data[$name])) {
			return false;
		} else {
			return $data[$name];
		}
	}
/**
 * Returns a quoted name of $data for use in an SQL statement.
 * Strips fields out of SQL functions before quoting.
 *
 * @param string $data
 * @return string SQL field
 */
	function name($data) {
		if (preg_match_all('/([^(]*)\((.*)\)(.*)/', $data, $fields)) {
			$fields = Set::extract($fields, '{n}.0');
			if (!empty($fields[1])) {
				if (!empty($fields[2])) {
					return $fields[1] . '(' . $this->name($fields[2]) . ')' . $fields[3];
				} else {
					return $fields[1] . '()' . $fields[3];
				}
			}
		}
		if ($data == '*') {
			return '*';
		}
		$data = $this->startQuote . str_replace('.', $this->endQuote . '.' . $this->startQuote, $data) . $this->endQuote;
		$data = str_replace($this->startQuote . $this->startQuote, $this->startQuote, $data);

		if (!empty($this->endQuote) && $this->endQuote == $this->startQuote) {
			$oddMatches = substr_count($data, $this->endQuote);
			if ($oddMatches % 2 == 1) {
				$data = trim($data, $this->endQuote);
			}
		}
		return str_replace($this->endQuote . $this->endQuote, $this->endQuote, $data);
	}
/**
 * Checks if it's connected to the database
 *
 * @return boolean True if the database is connected, else false
 */
	function isConnected() {
		return $this->connected;
	}
/**
 * Outputs the contents of the queries log.
 *
 * @param bool $sorted
 */
	function showLog($sorted = false) {
		if ($sorted) {
			$log = sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_queriesLog;
		}

		if ($this->_queriesCnt > 1) {
			$text = 'queries';
		} else {
			$text = 'query';
		}

		if (php_sapi_name() != 'cli') {
			print ("<table class=\"cake-sql-log\" id=\"cakeSqlLog_" . preg_replace('/[^A-Za-z0-9_]/', '_', uniqid(time(), true)) . "\" summary=\"Cake SQL Log\" cellspacing=\"0\" border = \"0\">\n<caption>{$this->_queriesCnt} {$text} took {$this->_queriesTime} ms</caption>\n");
			print ("<thead>\n<tr><th>Nr</th><th>Query</th><th>Error</th><th>Affected</th><th>Num. rows</th><th>Took (ms)</th></tr>\n</thead>\n<tbody>\n");

			foreach ($log as $k => $i) {
				print ("<tr><td>" . ($k + 1) . "</td><td>" . h($i['query']) . "</td><td>{$i['error']}</td><td style = \"text-align: right\">{$i['affected']}</td><td style = \"text-align: right\">{$i['numRows']}</td><td style = \"text-align: right\">{$i['took']}</td></tr>\n");
			}
			print ("</tbody></table>\n");
		} else {
			foreach ($log as $k => $i) {
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
	function logQuery($sql) {
		$this->_queriesCnt++;
		$this->_queriesTime += $this->took;
		$this->_queriesLog[] = array('query' => $sql,
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
	function showQuery($sql) {
		$error = $this->error;
		if (strlen($sql) > 200 && !$this->fullDebug && Configure::read() > 1) {
			$sql = substr($sql, 0, 200) . '[...]';
		}

		if (($this->debug || $error) && Configure::read() > 0) {
			e("<p style = \"text-align:left\"><b>Query:</b> {$sql} ");
			if ($error) {
				trigger_error("<span style = \"color:Red;text-align:left\"><b>SQL Error:</b> {$this->error}</span>", E_USER_WARNING);
			} else {
				e("<small>[Aff:{$this->affected} Num:{$this->numRows} Took:{$this->took}ms]</small>");
			}
			print ('</p>');
		}
	}
/**
 * Gets full table name including prefix
 *
 * @param mixed $model
 * @param bool $quote
 * @return string Full quoted table name
 */
	function fullTableName($model, $quote = true) {
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
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @return boolean Success
 */
	function create(&$model, $fields = null, $values = null) {
		$fieldInsert = array();
		$valueInsert = array();
		$id = null;

		if ($fields == null) {
			unset($fields, $values);
			$fields = array_keys($model->data);
			$values = array_values($model->data);
		}

		$count = count($fields);
		for ($i = 0; $i < $count; $i++) {
			$fieldInsert[] = $this->name($fields[$i]);
			if ($fields[$i] == $model->primaryKey) {
				$id = $values[$i];
			}
		}

		$count = count($values);
		for ($i = 0; $i < $count; $i++) {
			$set = $this->value($values[$i], $model->getColumnType($fields[$i]));

			if ($set === "''") {
				unset ($fieldInsert[$i]);
			} else {
				$valueInsert[] = $set;
			}
		}

		if ($this->execute('INSERT INTO ' . $this->fullTableName($model) . ' (' . join(',', $fieldInsert). ') VALUES (' . join(',', $valueInsert) . ')')) {
			if (empty($id)) {
				$id = $this->lastInsertId($this->fullTableName($model, false), $model->primaryKey);
			}
			$model->setInsertID($id);
			$model->id = $id;
			return true;
		} else {
			$model->onError();
			return false;
		}
	}
/**
 * The "R" in CRUD
 *
 * @param Model $model
 * @param array $queryData
 * @param int $recursive Number of levels of association
 * @return unknown
 */
	function read(&$model, $queryData = array(), $recursive = null) {

		$this->__scrubQueryData($queryData);
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

		foreach ($model->__associations as $type) {
			foreach ($model->{$type} as $assoc => $assocData) {
				if ($model->recursive > -1) {
					$linkModel =& $model->{$assoc};

					$external = isset($assocData['external']);
					if ($model->name == $linkModel->name && $type != 'hasAndBelongsToMany' && $type != 'hasMany') {
						if (true === $this->generateSelfAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null)) {
							$linkedModels[] = $type . '/' . $assoc;
						}
					} else {
						if ($model->useDbConfig == $linkModel->useDbConfig) {
							if (true === $this->generateAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null)) {
								$linkedModels[] = $type . '/' . $assoc;
							}
						}
					}
				}
			}
		}
		// Build final query SQL
		$query = $this->generateAssociationQuery($model, $null, null, null, null, $queryData, false, $null);
		$resultSet = $this->fetchAll($query, $model->cacheQueries, $model->name);

		if ($resultSet === false) {
			$model->onError();
			return false;
		}

		$filtered = $this->__filterResults($resultSet, $model);

		if ($model->recursive > 0) {
			foreach ($model->__associations as $type) {
				foreach ($model->{$type} as $assoc => $assocData) {
					$db = null;
					$linkModel =& $model->{$assoc};

					if (!in_array($type . '/' . $assoc, $linkedModels)) {
						if ($model->useDbConfig == $linkModel->useDbConfig) {
							$db =& $this;
						} else {
							$db =& ConnectionManager::getDataSource($linkModel->useDbConfig);
						}
					} elseif ($model->recursive > 1 && ($type == 'belongsTo' || $type == 'hasOne')) {
						// Do recursive joins on belongsTo and hasOne relationships
						$db =& $this;
					} else {
						unset ($db);
					}

					if (isset($db) && $db != null) {
						$stack = array($assoc);
						$db->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $array, true, $resultSet, $model->recursive - 1, $stack);
						unset($db);
					}
				}
			}
			$this->__filterResults($resultSet, $model, $filtered);
		}

		if (!is_null($recursive)) {
			$model->recursive = $_recursive;
		}
		return $resultSet;
	}
/**
 * Private method.  Passes association results thru afterFind filter of corresponding model
 *
 * @param unknown_type $results
 * @param unknown_type $model
 * @param unknown_type $filtered
 * @return unknown
 */
	function __filterResults(&$results, &$model, $filtered = array()) {

		$filtering = array();
		$associations = am($model->belongsTo, $model->hasOne, $model->hasMany, $model->hasAndBelongsToMany);
		$count = count($results);

		for ($i = 0; $i < $count; $i++) {
			if (is_array($results[$i])) {
				$keys = array_keys($results[$i]);
				$count2 = count($keys);

				for ($j = 0; $j < $count2; $j++) {
					$className = $key = $keys[$j];

					if ($model->name != $className && !in_array($key, $filtered)) {
						if (!in_array($key, $filtering)) {
							$filtering[] = $key;
						}

						if (isset($model->{$className}) && is_object($model->{$className})) {
							$data = $model->{$className}->afterFind(array(array($key => $results[$i][$key])), false);
						}
						if (isset($data[0][$key])) {
							$results[$i][$key] = $data[0][$key];
						}
					}
				}
			}
		}
		return $filtering;
	}
/**
 * Enter description here...
 *
 * @param Model $model
 * @param unknown_type $linkModel
 * @param string $type Association type
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param unknown_type $queryData
 * @param unknown_type $external
 * @param unknown_type $resultSet
 * @param int $recursive Number of levels of association
 * @param array $stack
 */
	function queryAssociation(&$model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive, $stack) {

		if ($query = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet)) {
			if (!isset($resultSet) || !is_array($resultSet)) {
				if (Configure::read() > 0) {
					e('<div style = "font: Verdana bold 12px; color: #FF0000">' . sprintf(__('SQL Error in model %s:', true), $model->name) . ' ');
					if (isset($this->error) && $this->error != null) {
						e($this->error);
					}
					e('</div>');
				}
				return null;
			}
			$count = count($resultSet);

			if ($type === 'hasMany' && (!isset($assocData['limit']) || empty($assocData['limit']))) {
				$ins = $fetch = array();
				for ($i = 0; $i < $count; $i++) {
					if ($in = $this->insertQueryData('{$__cakeID__$}', $resultSet[$i], $association, $assocData, $model, $linkModel, $stack)) {
						$ins[] = $in;
					}
				}

				if (!empty($ins)) {
					$query = r('{$__cakeID__$}', join(', ', $ins), $query);
					$fetch = $this->fetchAll($query, $model->cacheQueries, $model->name);
				}

				if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {

						foreach ($linkModel->__associations as $type1) {
							foreach ($linkModel->{$type1} as $assoc1 => $assocData1) {

								$deepModel =& $linkModel->{$assoc1};
								if ($deepModel->alias != $model->name) {
									$tmpStack = $stack;
									$tmpStack[] = $assoc1;
									if ($linkModel->useDbConfig == $deepModel->useDbConfig) {
										$db =& $this;
									} else {
										$db =& ConnectionManager::getDataSource($deepModel->useDbConfig);
									}
									$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1, $tmpStack);
								}
							}
						}
					}
				}
				return $this->__mergeHasMany($resultSet, $fetch, $association, $model, $linkModel, $recursive);
			} elseif ($type === 'hasAndBelongsToMany') {
				$ins = $fetch = array();
				for ($i = 0; $i < $count; $i++) {
					if ($in = $this->insertQueryData('{$__cakeID__$}', $resultSet[$i], $association, $assocData, $model, $linkModel, $stack)) {
						$ins[] = $in;
					}
				}
				if (!empty($ins)) {
					$query = r('{$__cakeID__$}', '(' .join(', ', $ins) .')', $query);
					$query = r('=  (', 'IN (', $query);
					$query = r('  WHERE 1 = 1', '', $query);
				}

				$with = $model->hasAndBelongsToMany[$association]['with'];
				$foreignKey = $model->hasAndBelongsToMany[$association]['foreignKey'];
				$habtmFields = $model->{$with}->loadInfo();
				$habtmFields = $habtmFields->extract('{n}.name');
				$habtmFieldsCount = count($habtmFields);

				$q = $this->insertQueryData($query, null, $association, $assocData, $model, $linkModel, $stack);
				if ($q != false) {
					$fetch = $this->fetchAll($q, $model->cacheQueries, $model->name);
				} else {
					$fetch = null;
				}
			}

			for ($i = 0; $i < $count; $i++) {
				$row =& $resultSet[$i];

				if ($type !== 'hasAndBelongsToMany') {
					$q = $this->insertQueryData($query, $resultSet[$i], $association, $assocData, $model, $linkModel, $stack);
					if ($q != false) {
						$fetch = $this->fetchAll($q, $model->cacheQueries, $model->name);
					} else {
						$fetch = null;
					}
				}

				if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {

						foreach ($linkModel->__associations as $type1) {
							foreach ($linkModel->{$type1} as $assoc1 => $assocData1) {

								$deepModel =& $linkModel->{$assoc1};
								if (($type1 === 'belongsTo') || ($deepModel->name === $model->name && $type === 'belongsTo') || ($deepModel->name != $model->name)) {
									$tmpStack = $stack;
									$tmpStack[] = $assoc1;
									if ($linkModel->useDbConfig == $deepModel->useDbConfig) {
										$db =& $this;
									} else {
										$db =& ConnectionManager::getDataSource($deepModel->useDbConfig);
									}
									$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1, $tmpStack);
								}
							}
						}
					}
					if ($type == 'hasAndBelongsToMany') {
						$merge = array();
						foreach($fetch as $j => $data) {
							if(isset($data[$with]) && $data[$with][$foreignKey] === $row[$model->name][$model->primaryKey]) {
								if ($habtmFieldsCount > 2) {
									$merge[] = $data;
								} else {
									$merge[] = Set::diff($data, array($with => $data[$with]));
								}
							}
						}
						if (empty($merge) && !isset($row[$association])) {
							$row[$association] = $merge;
						} else {
							$this->__mergeAssociation($resultSet[$i], $merge, $association, $type);
						}
					} else {
						$this->__mergeAssociation($resultSet[$i], $fetch, $association, $type);
					}
					$resultSet[$i][$association] = $linkModel->afterfind($resultSet[$i][$association]);

				} else {
					$tempArray[0][$association] = false;
					$this->__mergeAssociation($resultSet[$i], $tempArray, $association, $type);
				}
			}
		}
	}

	function __mergeHasMany(&$resultSet, $merge, $association, &$model, &$linkModel) {
		foreach ($resultSet as $i => $value) {
			$count = 0;
			$merged[$association] = array();
			foreach ($merge as $j => $data) {
				if (isset($value[$model->name]) && $value[$model->name][$model->primaryKey] === $data[$association][$model->hasMany[$association]['foreignKey']]) {
					if (count($data) > 1) {
						$data = am($data[$association], $data);
						unset($data[$association]);
						$merged[$association][] = $data;
					} else {
						$merged[$association][] = $data[$association];
					}
				}
				$count++;
			}
			if (isset($value[$model->name])) {
				$resultSet[$i] = Set::pushDiff($resultSet[$i], $merged);
				unset($temp);
			}
		}
	}
/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @param unknown_type $merge
 * @param unknown_type $association
 * @param unknown_type $type
 */
	function __mergeAssociation(&$data, $merge, $association, $type) {

		if (isset($merge[0]) && !isset($merge[0][$association])) {
			$association = Inflector::pluralize($association);
		}

		if ($type == 'belongsTo' || $type == 'hasOne') {
			if (isset($merge[$association])) {
				$data[$association] = $merge[$association][0];
			} else {
				if (count($merge[0][$association]) > 1) {
					foreach ($merge[0] as $assoc => $data2) {
						if ($assoc != $association) {
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

						if (array_keys($merge[0]) === array_keys($data)) {
							$data[$association][$association] = $merge[0][$association];
						} else {
							$diff = Set::diff($dataAssocTmp, $mergeAssocTmp);
							$data[$association] = array_merge($merge[0][$association], $diff);
						}
					}
				}
			}
		} else {
			if ($merge[0][$association] === false) {
				if (!isset($data[$association])) {
					$data[$association] = array();
				}
			} else {
				foreach ($merge as $i => $row) {
					if (count($row) == 1) {
						$data[$association][] = $row[$association];
					} else {
						$tmp = array_merge($row[$association], $row);
						unset($tmp[$association]);
						$data[$association][] = $tmp;
					}
				}
			}
		}
	}
/**
 * Enter description here...
 *
 * @param unknown_type $model
 * @param unknown_type $linkModel
 * @param unknown_type $type
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param unknown_type $queryData
 * @param unknown_type $external
 * @param unknown_type $resultSet
 * @return unknown
 */
	function generateSelfAssociationQuery(&$model, &$linkModel, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet) {
		$alias = $association;
		if (empty($alias) && !empty($linkModel)) {
			$alias = $linkModel->name;
		}

		if (!isset($queryData['selfJoin'])) {
			$queryData['selfJoin'] = array();

			$self = array(
				'fields'	=> $this->fields($model, null, $queryData['fields']),
				'joins' => array(array(
					'table' => $this->fullTableName($linkModel),
					'alias' => $alias,
					'type' => 'LEFT',
					'conditions' => array(
						$model->escapeField($assocData['foreignKey']) => '{$__cakeIdentifier[' . "{$alias}.{$linkModel->primaryKey}" . ']__$}'))
					),
				'table' => $this->fullTableName($model),
				'alias' => $model->name,
				'limit' => $queryData['limit'],
				'offset'	=> $queryData['offset'],
				'conditions'=> $queryData['conditions'],
				'order' => $queryData['order']
			);

			if (!empty($assocData['conditions'])) {
				$self['joins'][0]['conditions'] = trim($this->conditions(am($self['joins'][0]['conditions'], $assocData['conditions']), true, false));
			}

			if (!empty($queryData['joins'])) {
				foreach ($queryData['joins'] as $join) {
					$self['joins'][] = $join;
				}
			}

			if ($this->__bypass === false) {
				$self['fields'] = am($self['fields'], $this->fields($linkModel, $alias, (isset($assocData['fields']) ? $assocData['fields'] : '')));
			}

			if (!in_array($self, $queryData['selfJoin'])) {
				$queryData['selfJoin'][] = $self;
				return true;
			}

		} elseif (isset($linkModel)) {
			return $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);

		} else {
			$result = $queryData['selfJoin'][0];
			if (!empty($queryData['joins'])) {
				foreach ($queryData['joins'] as $join) {
					if (!in_array($join, $result['joins'])) {
						$result['joins'][] = $join;
					}
				}
			}
			if (!empty($queryData['conditions'])) {
				$result['conditions'] = trim($this->conditions(am($result['conditions'], $assocData['conditions']), true, false));
			}
			if (!empty($queryData['fields'])) {
				$result['fields'] = array_unique(am($result['fields'], $queryData['fields']));
			}
			$sql = $this->buildStatement($result, $model);
			return $sql;
		}
	}
/**
 * Enter description here...
 *
 * @param Model $model
 * @param unknown_type $linkModel
 * @param unknown_type $type
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param unknown_type $queryData
 * @param unknown_type $external
 * @param unknown_type $resultSet
 * @return unknown
 */
	function generateAssociationQuery(&$model, &$linkModel, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet) {
		$this->__scrubQueryData($queryData);
		$this->__scrubQueryData($assocData);
		$joinedOnSelf = false;

		if (empty($queryData['fields'])) {
			$queryData['fields'] = $this->fields($model, $model->name);
		} elseif (!empty($model->hasMany) && $model->recursive > -1) {
			$assocFields = $this->fields($model, $model->name, array("{$model->name}.{$model->primaryKey}"));
			$passedFields = $this->fields($model, $model->name, $queryData['fields']);

			if (count($passedFields) === 1) {
				$match = strpos($passedFields[0], $assocFields[0]);
				$match1 = strpos($passedFields[0], 'COUNT(');
				if ($match === false && $match1 === false) {
					$queryData['fields'] = array_unique(array_merge($passedFields, $assocFields));
				} else {
					$queryData['fields'] = $passedFields;
				}
			} else {
				$queryData['fields'] = array_unique(array_merge($passedFields, $assocFields));
			}
			unset($assocFields, $passedFields);
		}

		if ($linkModel == null) {
			if (array_key_exists('selfJoin', $queryData)) {
				return $this->generateSelfAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
			} else {
				return $this->buildStatement(array(
					'fields' => array_unique($queryData['fields']),
					'table' => $this->fullTableName($model),
					'alias' => $model->name,
					'limit' => $queryData['limit'],
					'offset' => $queryData['offset'],
					'joins' => $queryData['joins'],
					'conditions' => $queryData['conditions'],
					'order' => $queryData['order']), $model
				);
			}
		}
		$alias = $association;

		if ($model->name == $linkModel->name) {
			$joinedOnSelf = true;
		}

		if ($external && isset($assocData['finderQuery'])) {
			if (!empty($assocData['finderQuery'])) {
				return $assocData['finderQuery'];
			}
		}

		if ((!$external && in_array($type, array('hasOne', 'belongsTo')) && $this->__bypass === false) || $external) {
			$fields = $this->fields($linkModel, $alias, $assocData['fields']);
		} else {
			$fields = array();
		}
		$limit = '';

		if (isset($assocData['limit'])) {
			if ((!isset($assocData['offset']) || (empty($assocData['offset']))) && isset($assocData['page'])) {
				$assocData['offset'] = ($assocData['page'] - 1) * $assocData['limit'];
			} elseif (!isset($assocData['offset'])) {
				$assocData['offset'] = null;
			}
			$limit = $this->limit($assocData['limit'], $assocData['offset']);
		}

		switch($type) {
			case 'hasOne':
			case 'belongsTo':
				if ($external) {
					if ($type == 'hasOne') {
						$conditions = $this->__mergeConditions($assocData['conditions'], array("{$alias}.{$assocData['foreignKey']}" => '{$__cakeID__$}'));
					} elseif ($type == 'belongsTo') {
						$conditions = $this->__mergeConditions($assocData['conditions'], array("{$alias}.{$linkModel->primaryKey}" => '{$__cakeForeignKey__$}'));
					}
					$query = am($assocData, array(
						'conditions' => $conditions,
						'table' => $this->fullTableName($linkModel),
						'fields' => $fields,
						'alias' => $alias
					));

					if ($type == 'belongsTo') {
						// Dunno if we should be doing this for hasOne also...?
						// Or maybe not doing it at all...?
						$query = am($query, array('order' => $assocData['order'], 'limit' => $limit));
					}
				} else {
					if ($type == 'hasOne') {
						$conditions = $this->__mergeConditions($assocData['conditions'], array("{$alias}.{$assocData['foreignKey']}" => '{$__cakeIdentifier[' . "{$model->name}.{$model->primaryKey}" . ']__$}'));
					} elseif ($type == 'belongsTo') {
						$conditions = $this->__mergeConditions($assocData['conditions'], array("{$model->name}.{$assocData['foreignKey']}" => '{$__cakeIdentifier[' . "{$alias}.{$linkModel->primaryKey}" . ']__$}'));
					}

					$join = array(
						'table' => $this->fullTableName($linkModel),
						'alias' => $alias,
						'type' => 'LEFT',
						'conditions' => trim($this->conditions($conditions, true, false))
					);

					$queryData['fields'] = am($queryData['fields'], $fields);

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
				$assocData['fields'] = array_unique(array_merge(
					$this->fields($linkModel, $alias, $assocData['fields']),
					$this->fields($linkModel, $alias, array("{$alias}.{$assocData['foreignKey']}"))
				));

				$query = array(
					'conditions' => $this->__mergeConditions(array("{$alias}.{$assocData['foreignKey']}" => array('{$__cakeID__$}')), $assocData['conditions']),
					'fields' => $assocData['fields'],
					'table' => $this->fullTableName($linkModel),
					'alias' => $alias,
					'order' => $assocData['order'],
					'limit' => $limit
				);
			break;
			case 'hasAndBelongsToMany':
				$joinTbl = $this->fullTableName($assocData['joinTable']);
				$joinFields = array();
				$joinAssoc = null;
				$joinAlias = $joinTbl;

				if (isset($assocData['with']) && !empty($assocData['with'])) {
					$joinFields = $model->{$assocData['with']}->loadInfo();
					$joinFields = $joinFields->extract('{n}.name');

					if (is_array($joinFields) && !empty($joinFields)) {
						$joinFields = $this->fields($model->{$assocData['with']}, $model->{$assocData['with']}->name, $joinFields);
						$joinAssoc = $joinAlias = $model->{$assocData['with']}->name;

					} else {
						$joinFields = array();
					}
				}

				$query = array(
					'conditions' => $assocData['conditions'],
					'limit' => $limit,
					'table' => $this->fullTableName($linkModel),
					'alias' => $alias,
					'fields' => am($this->fields($linkModel, $alias, $assocData['fields']), $joinFields),
					'order' => $assocData['order'],
					'joins' => array(array(
						'table' => $joinTbl,
						'alias' => $joinAssoc,
						'conditions' => array(
							array("{$joinAlias}.{$assocData['foreignKey']}" => '{$__cakeID__$}'),
							array("{$joinAlias}.{$assocData['associationForeignKey']}" => '{$__cakeIdentifier['."{$alias}.{$linkModel->primaryKey}".']__$}')
						))
					)
				);
			break;
		}
		if (isset($query)) {
			return $this->buildStatement($query, $model);
		}
		return null;
	}

	function buildJoinStatement($join) {
		$data = am(array(
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

	function buildStatement($query, $model) {
		$query = am(array('offset' => null, 'joins' => array()), $query);
		if (!empty($query['joins'])) {
			for ($i = 0; $i < count($query['joins']); $i++) {
				if (is_array($query['joins'][$i])) {
					$query['joins'][$i] = $this->buildJoinStatement($query['joins'][$i]);
				}
			}
		}
		return $this->renderStatement(array(
			'conditions' => $this->conditions($query['conditions']),
			'fields' => join(', ', $query['fields']),
			'table' => $query['table'],
			'alias' => $this->alias . $this->name($query['alias']),
			'order' => $this->order($query['order']),
			'limit' => $this->limit($query['limit'], $query['offset']),
			'joins' => join(' ', $query['joins'])
		));
	}

	function renderJoinStatement($data) {
		extract($data);
		return trim("{$type} JOIN {$table} {$alias} ON ({$conditions})");
	}

	function renderStatement($data) {
		extract($data);
		return "SELECT {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$order} {$limit}";
	}
/**
 * Private method
 *
 * @return array
 */
	function __mergeConditions($query, $assoc) {
		if (!empty($assoc)) {
			if (is_array($query)) {
				return array_merge((array)$assoc, $query);
			} else {
				if (!empty($query)) {
					$query = array($query);
					if (is_array($assoc)) {
						$query = array_merge($query, $assoc);
					} else {
						$query[] = $assoc;
					}
					return $query;
				} else {
					return $assoc;
				}
			}
		}
		return $query;
	}
/**
 * Generates and executes an SQL UPDATE statement for given model, fields, and values.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return array
 */
	function update(&$model, $fields = array(), $values = null, $conditions = null) {
		$updates = array();

		if ($values == null) {
			$combined = $fields;
		} else {
			$combined = array_combine($fields, $values);
		}

		foreach ($combined as $field => $value) {
			if ($value === null) {
				$updates[] = $this->name($field) . ' = NULL';
			} else {
				$update = $this->name($field) . ' = ';
				if ($conditions == null) {
					$update .= $this->value($value, $model->getColumnType($field));
				} else {
					$update .= $value;
				}
				$updates[] =  $update;
			}
		}
		$conditions = $this->defaultConditions($model, $conditions);

		if ($conditions === false) {
			return false;
		}
		$fields = join(',', $updates);
		$table = $this->fullTableName($model);
		$conditions = $this->conditions($conditions);

		if (!$this->execute("UPDATE {$table} SET {$fields} {$conditions}")) {
			$model->onError();
			return false;
		}
		return true;
	}
/**
 * Generates and executes an SQL DELETE statement for given id on given model.
 *
 * @param Model $model
 * @param mixed $conditions
 * @return boolean Success
 */
	function delete(&$model, $conditions = null) {
		$query = $this->defaultConditions($model, $conditions);

		if ($query === false) {
			return false;
		}

		$table = $this->fullTableName($model);
		$conditions = $this->conditions($query);

		if ($this->execute("DELETE FROM {$table} {$conditions}") === false) {
			$model->onError();
			return false;
		}
		return true;
	}
/**
 * Creates a default set of conditions from the model if $conditions is null/empty.
 *
 * @param object $model
 * @param mixed  $conditions
 * @return mixed
 */
	function defaultConditions(&$model, $conditions) {
		if (!empty($conditions)) {
			return $conditions;
		}
		if (!$model->exists()) {
			return false;
		}
		return array($model->primaryKey => (array)$model->getID());
	}
/**
 * Returns a key formatted like a string Model.fieldname(i.e. Post.title, or Country.name)
 *
 * @param unknown_type $model
 * @param unknown_type $key
 * @param unknown_type $assoc
 * @return string
 */
	function resolveKey($model, $key, $assoc = null) {
		if (empty($assoc)) {
			$assoc = $model->name;
		}
		if (!strpos('.', $key)) {
			return $this->name($model->name) . '.' . $this->name($key);
		}
		return $key;
	}
/**
 * Returns the column type of a given
 *
 * @param Model $model
 * @param string $field
 */
	function getColumnType(&$model, $field) {
		return $model->getColumnType($field);
	}
/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data
 */
	function __scrubQueryData(&$data) {
		foreach (array('conditions', 'fields', 'joins', 'order', 'limit', 'offset') as $key) {
			if (!isset($data[$key]) || empty($data[$key])) {
				$data[$key] = array();
			}
		}
	}
/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields
 * @param bool $quote If false, returns fields array unquoted
 * @return array
 */
	function fields(&$model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->name;
		}

		if (!is_array($fields)) {
			if (!empty($fields)) {
				$depth = 0;
				$offset = 0;
				$buffer = '';
				$results = array();
				$length = strlen($fields);

				while ($offset <= $length) {
					$tmpOffset = -1;
					$offsets = array(strpos($fields, ',', $offset), strpos($fields, '(', $offset), strpos($fields, ')', $offset));
					for ($i = 0; $i < 3; $i++) {
						if ($offsets[$i] !== false && ($offsets[$i] < $tmpOffset || $tmpOffset == -1)) {
							$tmpOffset = $offsets[$i];
						}
					}
					if ($tmpOffset !== -1) {
						$buffer .= substr($fields, $offset, ($tmpOffset - $offset));
						if ($fields{$tmpOffset} == ',' && $depth == 0) {
							$results[] = $buffer;
							$buffer = '';
						} else {
							$buffer .= $fields{$tmpOffset};
						}
						if ($fields{$tmpOffset} == '(') {
							$depth++;
						}
						if ($fields{$tmpOffset} == ')') {
							$depth--;
						}
						$offset = ++$tmpOffset;
					} else {
						$results[] = $buffer . substr($fields, $offset);
						$offset = $length + 1;
					}
				}
				if (empty($results) && !empty($buffer)) {
					$results[] = $buffer;
				}

				if (!empty($results)) {
					$fields = array_map('trim', $results);
				} else {
					$fields = array();
				}
			}
		}
		if (empty($fields)) {
			$fieldData = $model->loadInfo();
			$fields = $fieldData->extract('{n}.name');
		} else {
			$fields = array_filter($fields);
		}
		if (!$quote) {
			return $fields;
		}
		$count = count($fields);

		if ($count >= 1 && !in_array($fields[0], array('*', 'COUNT(*)'))) {
			for ($i = 0; $i < $count; $i++) {
				if (!preg_match('/^.+\\(.*\\)/', $fields[$i])) {
					$prepend = '';

					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend   = 'DISTINCT ';
						$fields[$i] = trim(r('DISTINCT', '', $fields[$i]));
					}
					$dot = strpos($fields[$i], '.');

					if ($dot === false) {
						$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]);
					} else {
						$comma = strpos($fields[$i], ',');
						if ($comma === false) {
							$build = explode('.', $fields[$i]);
							if (!Set::numeric($build)) {
								$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]);
							}
						} else {
							$comma = explode(',', $fields[$i]);
							foreach ($comma as $string) {
								$build = explode('.', $string);
								if (!Set::numeric($build)) {
									$value[] = $prepend . $this->name(trim($build[0])) . '.' . $this->name(trim($build[1]));
								}
							}
							$fields[$i] = implode(', ', $value);
						}
					}
				} elseif (preg_match('/\(([\.\w]+)\)/', $fields[$i], $field)) {
					if (isset($field[1])) {
						if (strpos($field[1], '.') === false) {
							$field[1] = $this->name($alias) . '.' . $this->name($field[1]);
						} else {
							$field[0] = explode('.', $field[1]);
							if (!Set::numeric($field[0])) {
								$field[0] = join('.', array_map(array($this, 'name'), $field[0]));
								$fields[$i] = preg_replace('/\(' . $field[1] . '\)/', '(' . $field[0] . ')', $fields[$i], 1);
							}
						}
					}
				}
			}
		}
		return $fields;
	}
/**
 * Creates a WHERE clause by parsing given conditions data.
 *
 * @param mixed $conditions Array or string of conditions
 * @return string SQL fragment
 */
	function conditions($conditions, $quoteValues = true, $where = true) {
		$clause = $out = '';
		if (is_string($conditions) || empty($conditions) || $conditions === true) {
			if (empty($conditions) || trim($conditions) == '' || $conditions === true) {
				return ' WHERE 1 = 1';
			}
			if (!preg_match('/^WHERE\\x20|^GROUP\\x20BY\\x20|^HAVING\\x20|^ORDER\\x20BY\\x20/i', $conditions, $match)) {
				if ($where) {
					$clause = ' WHERE ';
				}
			}
			if (trim($conditions) == '') {
				$conditions = ' 1 = 1';
			} else {
				$conditions = $this->__quoteFields($conditions);
			}
			return $clause . $conditions;
		} else {
			if ($where) {
				$clause = ' WHERE ';
			}
			if (!empty($conditions)) {
				$out = $this->conditionKeysToString($conditions, $quoteValues);
			}
			if (empty($out) || empty($conditions)) {
				return $clause . ' 1 = 1';
			}
			return $clause . join(' AND ', $out);
		}
	}
/**
 * Creates a WHERE clause by parsing given conditions array.  Used by DboSource::conditions().
 *
 * @param array $conditions Array or string of conditions
 * @return string SQL fragment
 */
	function conditionKeysToString($conditions, $quoteValues = true) {
		$c = 0;
		$data = $not = null;
		$out = array();
		$bool = array('and', 'or', 'not', 'and not', 'or not', 'xor', '||', '&&');
		$join = ' AND ';

		foreach ($conditions as $key => $value) {
			if (is_numeric($key) && empty($value)) {
				continue;
			} elseif (is_numeric($key) && is_string($value)) {
				$out[] = $not . $this->__quoteFields($value);
			} elseif (in_array(strtolower(trim($key)), $bool)) {
				$join = ' ' . strtoupper($key) . ' ';
				$value = $this->conditionKeysToString($value, $quoteValues);
				if (strpos($join, 'NOT') !== false) {
					if (up(trim($key)) == 'NOT') {
						$key = 'AND ' . $key;
					}
					$not = 'NOT ';
				} else {
					$not = null;
				}
				$out[] = $not . '((' . join(') ' . strtoupper($key) . ' (', $value) . '))';
			} else {
				if (is_string($value) && preg_match('/^\{\$__cakeIdentifier\[(.*)\]__\$}$/', $value, $identifier) && isset($identifier[1])) {
					$data .= $this->name($key) . ' = ' . $this->name($identifier[1]);
				} elseif (is_array($value) && !empty($value)) {
					$keys = array_keys($value);
					if ($keys[0] === 0) {
						$data = $this->name($key) . ' IN (';
						if	(strpos($value[0], '-!') === 0) {
							$value[0] = str_replace('-!', '', $value[0]);
							$data .= $value[0];
							$data .= ')';
						} else {
							if ($quoteValues) {
								foreach ($value as $valElement) {
									$data .= $this->value($valElement) . ', ';
								}
							}
							$data[strlen($data) - 2] = ')';
						}
					} else {
						$ret = $this->conditionKeysToString($value, $quoteValues);
						if (count($ret) > 1) {
							$out[] = '(' . join(') AND (', $ret) . ')';
						} elseif (isset($ret[0])) {
							$out[] = $ret[0];
						}
					}
				} elseif (is_numeric($key) && !empty($value)) {
					$data = $this->__quoteFields($value);
				} elseif ($value === null || (is_array($value) && empty($value))) {
					$data = $this->name($key) . ' IS NULL';
				} elseif ($value === false || $value === true) {
					$data = $this->name($key) . " = " . $this->value($value, 'boolean');
				} elseif ($value === '') {
					$data = $this->name($key) . " = ''";
				} elseif (preg_match('/^([a-z]+\\([a-z0-9]*\\)\\x20+|(?:' . join('\\x20)|(?:', $this->__sqlOps) . '\\x20)|<[>=]?(?![^>]+>)\\x20?|[>=!]{1,3}(?!<)\\x20?)?(.*)/is', $value, $match)) {
					if (preg_match('/(\\x20[\\w]*\\x20)/', $key, $regs)) {
						$clause = $regs['1'];
						$key = preg_replace('/' . $regs['1'] . '/', '', $key);
					}

					$not = false;
					$mValue = trim($match['1']);
					if (empty($match['1'])) {
						$match['1'] = ' = ';
					} elseif (empty($mValue)) {
						$match['1'] = ' = ';
						$match['2'] = $match['0'];
					} elseif (!isset($match['2'])) {
						$match['1'] = ' = ';
						$match['2'] = $match['0'];
					} elseif (low($mValue) == 'not') {
						$not = $this->conditionKeysToString(array($mValue => array($key => $match[2])), $quoteValues);
					}

					if ($not) {
						$data = $not[0];
					} elseif (strpos($match['2'], '-!') === 0) {
						$match['2'] = str_replace('-!', '', $match['2']);
						$data = $this->name($key) . ' ' . $match['1'] . ' ' . $match['2'];
					} else {
						if (!empty($match['2']) && $quoteValues) {
							if (!preg_match('/[A-Za-z]+\\([a-z0-9]*\\),?\\x20+/', $match['2'])) {
								$match['2'] = $this->value($match['2']);
							}
							$match['2'] = str_replace(' AND ', "' AND '", $match['2']);
						}
						$data = $this->__quoteFields($key);
						if ($data === $key) {
							$data = $this->name($key) . ' ' . $match['1'] . ' ' . $match['2'];
						} else {
							$data = $data . ' ' . $match['1'] . ' ' . $match['2'];
						}
					}
				}

				if ($data != null) {
					$out[] = $data;
					$data = null;
				}
			}
			$c++;
		}
		return $out;
	}
/**
 * Quotes Model.fields
 *
 * @param string $conditions
 * @return string or false if no match
 * @access private
 */
	function __quoteFields($conditions) {
		$start = null;
		$end  = null;
		$original = $conditions;

		if (!empty($this->startQuote)) {
			$start = preg_quote($this->startQuote);
		}

		if (!empty($this->endQuote)) {
			$end = preg_quote($this->endQuote);
		}
		$conditions = str_replace(array($start, $end), '', $conditions);
		preg_match_all('/(?:[\'\"][^\'\"\\\]*(?:\\\.[^\'\"\\\]*)*[\'\"])|([a-z0-9_' . $start . $end . ']*\\.[a-z0-9_' . $start . $end . ']*)/i', $conditions, $replace, PREG_PATTERN_ORDER);

		if (isset($replace['1']['0'])) {
			$pregCount = count($replace['1']);

			for ($i = 0; $i < $pregCount; $i++) {
				if (!empty($replace['1'][$i]) && !is_numeric($replace['1'][$i])) {
					$conditions = preg_replace('/\b' . preg_quote($replace['1'][$i]) . '\b/', $this->name($replace['1'][$i]), $conditions);
				}
			}
			return $conditions;
		}
		return $original;
	}
/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'limit') || strpos(strtolower($limit), 'limit') === 0) {
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
 * @return string ORDER BY clause
 */
	function order($keys, $direction = 'ASC') {
		if (is_string($keys) && strpos($keys, ',') && !preg_match('/\(.+\,.+\)/', $keys)) {
			$keys = explode(',', $keys);
			array_map('trim', $keys);
		}

		if (is_array($keys)) {
			foreach ($keys as $key => $val) {
				if (is_numeric($key) && empty($val)) {
					unset ($keys[$key]);
				}
			}
		}

		if (empty($keys) || (is_array($keys) && count($keys) && isset($keys[0]) && empty($keys[0]))) {
			return '';
		}

		if (is_array($keys)) {
			if (Set::countDim($keys) > 1) {
				$new = array();

				foreach ($keys as $val) {
					$val = $this->order($val);
					$new[] = $val;
				}

				$keys = $new;
			}

			foreach ($keys as $key => $value) {
				if (is_numeric($key)) {
					$value = ltrim(r('ORDER BY ', '', $this->order($value)));
					$key  = $value;

					if (!preg_match('/\\x20ASC|\\x20DESC/i', $key)) {
						$value = ' ' . $direction;
					} else {
						$value = '';
					}
				} else {
					$value = ' ' . $value;
				}

				if (!preg_match('/^.+\\(.*\\)/', $key) && !strpos($key, ',')) {
					$dir   = '';
					$hasDir = preg_match('/\\x20ASC|\\x20DESC/i', $key, $dir);

					if ($hasDir) {
						$dir = $dir[0];
						$key = preg_replace('/\\x20ASC|\\x20DESC/i', '', $key);
					} else {
						$dir = '';
					}
					$key = trim($this->name(trim($key)) . ' ' . trim($dir));
				}
				$order[] = $this->order($key . $value);
			}

			return ' ORDER BY ' . trim(r('ORDER BY', '', join(',', $order)));
		} else {
			$keys = preg_replace('/ORDER\\x20BY/i', '', $keys);

			if (strpos($keys, '.')) {
				preg_match_all('/([a-zA-Z0-9_]{1,})\\.([a-zA-Z0-9_]{1,})/', $keys, $result,
									PREG_PATTERN_ORDER);
				$pregCount = count($result['0']);

				for ($i = 0; $i < $pregCount; $i++) {
					$keys = preg_replace('/' . $result['0'][$i] . '/', $this->name($result['0'][$i]), $keys);
				}

				if (preg_match('/\\x20ASC|\\x20DESC/i', $keys)) {
					return ' ORDER BY ' . $keys;
				} else {
					return ' ORDER BY ' . $keys . ' ' . $direction;
				}
			} elseif (preg_match('/(\\x20ASC|\\x20DESC)/i', $keys, $match)) {
				$direction = $match['1'];
				$keys     = preg_replace('/' . $match['1'] . '/', '', $keys);
				return ' ORDER BY ' . $keys . $direction;
			} else {
				$direction = ' ' . $direction;
			}
			return ' ORDER BY ' . $keys . $direction;
		}
	}
/**
 * Disconnects database, kills the connection and says the connection is closed,
 * and if DEBUG is turned on, the log for this object is shown.
 *
 */
	function close() {
		if ($this->fullDebug && Configure::read() > 1) {
			$this->showLog();
		}
		$this->disconnect();
	}
/**
 * Checks if the specified table contains any record matching specified SQL
 *
 * @param Model $model Model to search
 * @param string $sql SQL WHERE clause (condition only, not the "WHERE" part)
 * @return boolean True if the table has a matching record, else false
 */
	function hasAny($model, $sql) {
		$sql = $this->conditions($sql);
		$out = $this->fetchRow("SELECT COUNT(" . $model->primaryKey . ") " . $this->alias . "count FROM " . $this->fullTableName($model) . ' ' . ($sql ? ' ' . $sql : 'WHERE 1 = 1'));

		if (is_array($out)) {
			return $out[0]['count'];
		} else {
			return false;
		}
	}
/**
 * Translates between PHP boolean values and Database (faked) boolean values
 *
 * @param mixed $data Value to be translated
 * @return mixed Converted boolean value
 */
	function boolean($data) {
		if ($data === true || $data === false) {
			if ($data === true) {
				return 1;
			}
			return 0;

		} else {
			if (!empty($data)) {
				return true;
			}
			return false;
		}
	}
/**
 * Destructor. Closes connection to the database.
 *
 */
	function __destruct() {
		if ($this->_transactionStarted) {
			$null = null;
			$this->rollback($null);
		}
		parent::__destruct();
	}
/**
 * Inserts multiple values into a join table
 *
 * @param string $table
 * @param string $fields
 * @param array $values
 */
	function insertMulti($table, $fields, $values) {
		$values = implode(', ', $values);
		$this->query("INSERT INTO {$table} ({$fields}) VALUES {$values}");
	}
/**
 * Returns an array of the indexes in given datasource name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	function index($model) {
		return false;
	}
/**
 * Generate a create syntax from CakeSchema
 *
 * @param object $schema An instance of a subclass of CakeSchema
 * @param string $table Optional.  If specified only the table name given will be generated.
 *                      Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	function createSchema($schema, $table = null) {
		return false;
	}
/**
 * Generate a alter syntax from  CakeSchema::compare()
 *
 * @param unknown_type $schema
 * @return unknown
 */
	function alterSchema($compare, $table = null) {
		return false;
	}
/**
 * Generate a drop syntax from CakeSchema
 *
 * @param object $schema An instance of a subclass of CakeSchema
 * @param string $table Optional.  If specified only the table name given will be generated.
 *                      Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	function dropSchema($schema, $table = null) {
		return false;
	}
/**
 * Generate a column schema string
 *
 * @param array $column An array structured like the following: array('name'=>'value', 'type'=>'value'[, options]),
 *                      where options can be 'default', 'length', or 'key'.
 * @return string
 */
	function buildColumn($column) {
		return false;
	}
/**
 * Format indexes for create table
 *
 * @param array $indexes
 * @return string
 */
	function buildIndex($indexes) {
		return false;
	}
}
?>
