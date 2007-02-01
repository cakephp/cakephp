<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.datasources
 * @since			CakePHP v 0.10.0.1076
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
 * Enter description here...
 *
 * @var unknown_type
 */
	var $__bypass = false;
/**
 * Enter description here...
 *
 * @var array
 */
	var $__assocJoins = null;
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
 * The set of valid SQL operations usable in a WHERE statement
 *
 * @var array
 */
	var $__sqlOps = array('like', 'ilike', 'or', 'not', 'in', 'between', 'regexp', 'similar to');
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $goofyLimit = false;
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
			for($i = 0; $i < $count; $i++) {
				$out[$keys[$i]] = $this->value($data[$keys[$i]]);
			}
			return $out;
		} elseif (in_array($data, array('{$__cakeID__$}', '{$__cakeForeignKey__$}'))) {
			return $data;
		} else {
			return null;
		}
	}
/**
 * Caches/returns cached results for child instances
 *
 * @return array
 */
	function listSources($data = null) {
		if ($this->__sources != null) {
			return $this->__sources;
		}

		if (Configure::read() > 0) {
			$expires = "+30 seconds";
		} else {
			$expires = "+999 days";
		}

		if ($data != null) {
			$data = serialize($data);
		}
		$filename = ConnectionManager::getSourceName($this) . '_' . $this->config['database'] . '_list';
		$new = cache('models' . DS . $filename, $data, $expires);

		if ($new != null) {
			$new = unserialize($new);
			$this->__sources = $new;
		}
		return $new;
	}
/**
 * Convenience method for DboSource::listSources().
 *
 * @return array
 */
	function sources() {
		$return = array_map('strtolower', $this->listSources());
		return $return;
	}
/**
 * SQL Query abstraction
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

			$c = 0;
			$query = array();
			foreach ($field as $f) {
				if (!is_array($params[$c])) {
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
			return $this->fetchAll($args[0], false);
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

		if($this->fullDebug && Configure::read() > 1) {
			$this->logQuery($sql);
		}

		if ($this->error) {
			return false;
		} else {
			return $this->_result;
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
 * @deprecated
 * @see DboSource::fetchRow
 */
	function fetchArray() {
		trigger_error(__('Deprecated: Use DboSource::fetchRow() instead'), E_USER_WARNING);
		return $this->fetchRow();
	}
/**
 * @deprecated
 * @see DboSource::fetchRow
 */
	function one($sql) {
		trigger_error(__('Deprecated: Use DboSource::fetchRow($sql) instead'), E_USER_WARNING);
		return $this->fetchRow($sql);
	}
/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 * @param string $sql SQL statement
 * @param boolean $cache Enables returning/storing cached query results
 * @return array Array of resultset rows, or false if no rows matched
 */
	function fetchAll($sql, $cache = true, $modelName = null) {
		if ($cache && isset($this->_queryCache[$sql])) {
			if (strpos(trim(strtolower($sql)), 'select') !== false) {
				return $this->_queryCache[$sql];
			}
		}

		if ($this->execute($sql)) {
			$out = array();

			while($item = $this->fetchRow()) {
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

		if (empty($data[$name])) {
			return false;
		} else {
			return $data[$name];
		}
	}
/**
 * Strips fields out of SQL functions before quoting.
 *
 * @param string $data
 * @return string SQL field
 */
	function name($data) {
		if (preg_match_all('/(.*)\((.*)\)/', $data, $fields)) {
			$fields = Set::extract($fields, '{n}.0');
			if (isset($fields[1]) && isset($fields[2])) {
				return $fields[1] . '(' . $this->name($fields[2]) . ')';
			}
		}
		return null;
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
 * @param boolean $sorted
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
			print ("<table id=\"cakeSqlLog\" cellspacing=\"0\" border = \"0\">\n<caption>{$this->_queriesCnt} {$text} took {$this->_queriesTime} ms</caption>\n");
			print ("<thead>\n<tr><th>Nr</th><th>Query</th><th>Error</th><th>Affected</th><th>Num. rows</th><th>Took (ms)</th></tr>\n</thead>\n<tbody>\n");

			foreach($log as $k => $i) {
				print ("<tr><td>" . ($k + 1) . "</td><td>{$i['query']}</td><td>{$i['error']}</td><td style = \"text-align: right\">{$i['affected']}</td><td style = \"text-align: right\">{$i['numRows']}</td><td style = \"text-align: right\">{$i['took']}</td></tr>\n");
			}
			print ("</tbody></table>\n");
		} else {
			foreach($log as $k => $i) {
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

		if (($this->debug && Configure::read() > 0) || $error) {
			print ("<p style = \"text-align:left\"><b>Query:</b> {$sql} <small>[Aff:{$this->affected} Num:{$this->numRows} Took:{$this->took}ms]</small>");
			if ($error) {
				print ("<br /><span style = \"color:Red;text-align:left\"><b>ERROR:</b> {$this->error}</span>");
			}
			print ('</p>');
		}
	}
/**
 * Gets full table name including prefix
 *
 * @param mixed $model
 * @param boolean $quote
 * @return string Full quoted table name
 */
	function fullTableName($model, $quote = true) {
		if (is_object($model)) {
			$table = $model->table;
			if ($model->tablePrefix != null && !empty($model->tablePrefix)) {
				$table = $model->tablePrefix . $table;
			}
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
 * @param integer $recursive Number of levels of association
 * @return unknown
 */
	function read(&$model, $queryData = array(), $recursive = null) {

		$this->__scrubQueryData($queryData);
		$null = null;
		$array = array();
		$linkedModels = array();
		$this->__bypass = false;
		$this->__assocJoins = null;

		if (!is_null($recursive)) {
			$_recursive = $model->recursive;
			$model->recursive = $recursive;
		}

		if (!empty($queryData['fields'])) {
			$this->__bypass = true;
		}

		foreach($model->__associations as $type) {
			foreach($model->{$type} as $assoc => $assocData) {
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
			foreach($model->__associations as $type) {
				foreach($model->{$type} as $assoc => $assocData) {
					$db = null;
					$linkModel =& $model->{$assoc};

					if (!in_array($type . '/' . $assoc, $linkedModels)) {
						if ($model->useDbConfig == $linkModel->useDbConfig) {
							$db =& $this;
						} else {
							$db =& ConnectionManager::getDataSource($linkModel->useDbConfig);
						}
					} elseif($model->recursive > 1 && ($type == 'belongsTo' || $type == 'hasOne')) {
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

		for($i = 0; $i < $count; $i++) {
			if (is_array($results[$i])) {
				$keys = array_keys($results[$i]);
				$count2 = count($keys);

				for($j = 0; $j < $count2; $j++) {

					$key = $keys[$j];
					if (isset($associations[$key])) {
						$className = $associations[$key]['className'];
					} else {
						$className = $key;
					}

					if ($model->name != $className && !in_array($key, $filtered)) {
						if (!in_array($key, $filtering)) {
							$filtering[] = $key;
						}

						if (isset($model->{$className}) && is_object($model->{$className})) {
							$data = $model->{$className}->afterFind(array(array($key => $results[$i][$key])), false);
						}
						$results[$i][$key] = $data[0][$key];
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
 * @param integer $recursive Number of levels of association
 * @param array $stack
 */
	function queryAssociation(&$model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive, $stack) {
		$query = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
		if ($query) {

			if (!isset($resultSet) || !is_array($resultSet)) {
				if (Configure::read() > 0) {
					e('<div style = "font: Verdana bold 12px; color: #FF0000">SQL Error in model ' . $model->name . ': ');
					if (isset($this->error) && $this->error != null) {
						e($this->error);
					}
					e('</div>');
				}
				return null;
			}

			$count = count($resultSet);
			for($i = 0; $i < $count; $i++) {

				$row =& $resultSet[$i];
				$q = $this->insertQueryData($query, $resultSet[$i], $association, $assocData, $model, $linkModel, $stack);
				if($q != false){
					$fetch = $this->fetchAll($q, $model->cacheQueries, $model->name);
				} else {
					$fetch = null;
				}

				if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {

						foreach($linkModel->__associations as $type1) {
							foreach($linkModel->{$type1} as $assoc1 => $assocData1) {

								$deepModel =& $linkModel->{$assocData1['className']};
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
					$this->__mergeAssociation($resultSet[$i], $fetch, $association, $type);

				} else {
					$tempArray[0][$association] = false;
					$this->__mergeAssociation($resultSet[$i], $tempArray, $association, $type);
				}
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
					foreach($merge[0] as $assoc => $data2) {
						if ($assoc != $association) {
							$merge[0][$association][$assoc] = $data2;
						}
					}
				}
				if(!isset($data[$association])) {
					$data[$association] = $merge[0][$association];
				} else {
					if(is_array($merge[0][$association])){
						$data[$association] = array_merge($merge[0][$association], $data[$association]);
					}
				}
			}
		} else {
			if ($merge[0][$association] === false) {
				if(!isset($data[$association])){
					$data[$association] = array();
				}
			} else {
				foreach($merge as $i => $row) {
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
		if (!isset($queryData['selfJoin'])) {
			$queryData['selfJoin'] = array();
			$sql = 'SELECT ' . join(', ', $this->fields($model, $model->name, $queryData['fields']));
			if($this->__bypass === false){
				$sql .= ', ';
				$sql .= join(', ', $this->fields($linkModel, $alias, ''));
			}
			$sql .= ' FROM ' . $this->fullTableName($model) . ' ' . $this->alias . $this->name($model->name);
			$sql .= ' LEFT JOIN ' . $this->fullTableName($linkModel) . ' ' . $this->alias . $this->name($alias);
			$sql .= ' ON ' . $this->name($model->name) . '.' . $this->name($assocData['foreignKey']);
			$sql .= ' = ' . $this->name($alias) . '.' . $this->name($linkModel->primaryKey);

			if (!in_array($sql, $queryData['selfJoin'])) {
				$queryData['selfJoin'][] = $sql;
				return true;
			}
		} elseif (isset($linkModel)) {
			return $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
		} else {
			if (isset($this->__assocJoins)) {
				$replace = ', ';
				$replace .= join(', ', $this->__assocJoins['fields']);
				$replace .= ' FROM';
			} else {
				$replace = 'FROM';
			}
			$sql = $queryData['selfJoin'][0];
			$sql .= ' ' . join(' ', $queryData['joins']);
			$sql .= $this->conditions($queryData['conditions']) . ' ' . $this->order($queryData['order']);
			$sql .= ' ' . $this->limit($queryData['limit'], $queryData['offset']);
			$result = preg_replace('/FROM/', $replace, $sql);
			return $result;
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

		if ($linkModel == null) {
			if (array_key_exists('selfJoin', $queryData)) {
				return $this->generateSelfAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
			} else {
				if (isset($this->__assocJoins)) {
					$joinFields = ', ';
					$joinFields .= join(', ', $this->__assocJoins['fields']);
				} else {
					$joinFields = null;
				}

				$sql = 'SELECT ';
				if ($this->goofyLimit) {
					$sql .= $this->limit($queryData['limit'], $queryData['offset']);
				}
				$sql .= ' ' . join(', ', $this->fields($model, $model->name, $queryData['fields'])) . $joinFields . ' FROM ';
				$sql .= $this->fullTableName($model) . ' ' . $this->alias;
				$sql .= $this->name($model->name) . ' ' . join(' ', $queryData['joins']) . ' ';
				$sql .= $this->conditions($queryData['conditions']) . ' ' . $this->order($queryData['order']);

				if (!$this->goofyLimit) {
					$sql .= ' ' . $this->limit($queryData['limit'], $queryData['offset']);
				}
			}
			return $sql;
		}
		$alias = $association;

		if ($model->name == $linkModel->name) {
			$joinedOnSelf = true;
		}

		if ($external && isset($assocData['finderQuery'])) {
			if (!empty($assocData['finderQuery']) && $assocData['finderQuery'] != null) {
				return $assocData['finderQuery'];
			}
		}

		if (!$external && in_array($type, array('hasOne', 'belongsTo'))) {
			if ($this->__bypass === false) {
				$fields = join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
				$this->__assocJoins['fields'][] = $fields;
			} else {
				$this->__assocJoins = null;
			}
		}
		$limit = '';

		if (isset($assocData['limit'])) {
			if (!isset($assocData['offset']) && isset($assocData['page'])) {
				$assocData['offset'] = ($assocData['page'] - 1) * $assocData['limit'];
			} elseif (!isset($assocData['offset'])) {
				$assocData['offset'] = null;
			}
			$limit = $this->limit($assocData['limit'], $assocData['offset']);
		}

		switch($type) {
			case 'hasOne':
				if ($external) {
					if (isset($queryData['limit']) && !empty($queryData['limit'])) {
						$limit = $this->limit($queryData['limit'], $queryData['offset']);
					}

					$sql = 'SELECT ';
					if ($this->goofyLimit) {
						$sql .= $limit;
					}

					$sql .= ' ' . join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
					$sql .= ' FROM ' . $this->fullTableName($linkModel) . ' ' . $this->alias . $this->name($alias) . ' ';

					$conditions = $queryData['conditions'];
					$condition = $this->name($alias) . '.' . $this->name($assocData['foreignKey']);
					$condition .= ' = {$__cakeForeignKey__$}';

					if (is_array($conditions)) {
						$conditions[] = $condition;
					} else {
						$cond = $this->name($alias) . '.' . $this->name($assocData['foreignKey']);
						$cond .= ' = {$__cakeID__$}';

						if (trim($conditions) != '') {
							$conditions .= ' AND ';
						}
						$conditions .= $cond;
					}

					$sql .= $this->conditions($conditions) . $this->order($queryData['order']);
					if (!$this->goofyLimit) {
						$sql .= $limit;
					}
					return $sql;

				} else {

					$sql = ' LEFT JOIN ' . $this->fullTableName($linkModel);
					$sql .= ' ' . $this->alias . $this->name($alias) . ' ON ';
					$sql .= $this->name($alias) . '.' . $this->name($assocData['foreignKey']);
					$sql .= ' = ' . $model->escapeField($model->primaryKey);

					if ($assocData['order'] != null) {
						$queryData['order'][] = $assocData['order'];
					}

					$this->__mergeConditions($queryData, $assocData);
					if (!in_array($sql, $queryData['joins'])) {
						$queryData['joins'][] = $sql;
					}
					return true;
				}
			break;
			case 'belongsTo':
				if ($external) {

					$sql = 'SELECT ';
					if ($this->goofyLimit) {
						$sql .= $limit;
					}

					$sql .= ' ' . join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
					$sql .= ' FROM ' . $this->fullTableName($linkModel) . ' ' . $this->alias . $this->name($alias) . ' ';

					$conditions = $assocData['conditions'];
					$condition = $this->name($alias) . '.' . $this->name($linkModel->primaryKey);
					$condition .= ' = {$__cakeForeignKey__$}';

					if (is_array($conditions)) {
						$conditions[] = $condition;
					} else {
						if (trim($conditions) != '') {
							$conditions .= ' AND ';
						}
						$conditions .= $condition;
					}

					$sql .= $this->conditions($conditions) . $this->order($assocData['order']);

					if (!$this->goofyLimit) {
						$sql .= $limit;
					}
					return $sql;

				} else {

					$sql = ' LEFT JOIN ' . $this->fullTableName($linkModel);
					$sql .= ' ' . $this->alias . $this->name($alias) . ' ON ';
					$sql .= $this->name($model->name) . '.' . $this->name($assocData['foreignKey']);
					$sql .= ' = ' . $this->name($alias) . '.' . $this->name($linkModel->primaryKey);

					$this->__mergeConditions($queryData, $assocData);
					if (!in_array($sql, $queryData['joins'])) {
						$queryData['joins'][] = $sql;
					}
					return true;
				}

			break;
			case 'hasMany':

				$conditions = $assocData['conditions'];
				$sql = 'SELECT ';

				if ($this->goofyLimit) {
					$sql .= $limit;
				}

				$sql .= ' ' . join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
				$sql .= ' FROM ' . $this->fullTableName($linkModel) . ' ' . $this->alias . $this->name($alias);

				if (is_array($conditions)) {
					$conditions[$alias . '.' . $assocData['foreignKey']] = '{$__cakeID__$}';
				} else {
					$cond = $this->name($alias) . '.' . $this->name($assocData['foreignKey']);
					$cond .= ' = {$__cakeID__$}';
					if (trim($conditions) != '') {
						$conditions .= ' AND ';
					}
					$conditions .= $cond;
				}

				$sql .= $this->conditions($conditions);
				$sql .= $this->order($assocData['order']);

				if (!$this->goofyLimit) {
					$sql .= $limit;
				}
				return $sql;
			break;
			case 'hasAndBelongsToMany':
				$joinTbl = $this->fullTableName($assocData['joinTable']);
				$sql = 'SELECT ';

				if ($this->goofyLimit) {
					$sql .= $limit;
				}
				$joinFields = array();

				if (isset($assocData['with']) && !empty($assocData['with'])) {
					$joinFields = $model->{$assocData['with']}->loadInfo();
					$joinFields = $joinFields->extract('{n}.name');

					if (is_array($joinFields) && !empty($joinFields)) {
						$joinFields = $this->fields($model->{$assocData['with']}, $model->{$assocData['with']}->name, $joinFields);
					}
				}
				$sql .= ' ' . join(', ', am($this->fields($linkModel, $alias, $assocData['fields']), $joinFields));
				$sql .= ' FROM ' . $this->fullTableName($linkModel) . ' ' . $this->alias . $this->name($alias);
				$sql .= ' JOIN ' . $joinTbl;

				$joinAssoc = $joinTbl;

				if (isset($assocData['with']) && !empty($assocData['with'])) {
					$joinAssoc = $model->{$assocData['with']}->name;
					$sql .= $this->alias . $this->name($joinAssoc);
				}
				$sql .= ' ON ' . $this->name($joinAssoc);
				$sql .= '.' . $this->name($assocData['foreignKey']) . ' = {$__cakeID__$}';
				$sql .= ' AND ' . $this->name($joinAssoc) . '.' . $this->name($assocData['associationForeignKey']);
				$sql .= ' = ' . $this->name($alias) . '.' . $this->name($linkModel->primaryKey);
				$sql .= $this->conditions($assocData['conditions']);
				$sql .= $this->order($assocData['order']);

				if (!$this->goofyLimit) {
					$sql .= $limit;
				}
				return $sql;
			break;
		}
		return null;
	}
/**
 * Private method
 *
 * @return array
 */
	function __mergeConditions(&$queryData, $assocData) {
		if (isset($assocData['conditions']) && !empty($assocData['conditions'])) {
			if (is_array($queryData['conditions'])) {
				$queryData['conditions'] = array_merge((array)$assocData['conditions'], $queryData['conditions']);
			} else {
				if (!empty($queryData['conditions'])) {
					$queryData['conditions'] = array($queryData['conditions']);
					if (is_array($assocData['conditions'])) {
						$queryData['conditions'] = array_merge($queryData['conditions'], $assocData['conditions']);
					} else {
						$queryData['conditions'][] = $assocData['conditions'];
					}
				} else {
					$queryData['conditions'] = $assocData['conditions'];
				}
			}
		}
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

		foreach($combined as $field => $value) {
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
		if ($conditions == null) {
			$conditions = array($model->primaryKey => $model->getID());
		}

		if (!$this->execute('UPDATE '.$this->fullTableName($model).' SET '.join(',', $updates).$this->conditions($conditions))) {
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
		if (empty($model->id) && empty($conditions)) {
			return false;
		} elseif (empty($conditions)) {
			$conditions = array($model->primaryKey => (array)$model->id);
		}
		if ($this->execute('DELETE FROM ' . $this->fullTableName($model) . $this->conditions($conditions)) === false) {
			$model->onError();
			return false;
		}
		return true;
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
		if ($assoc == null) {
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
		if (!isset($data['conditions'])) {
			$data['conditions'] = ' 1 = 1 ';
		}

		if (!isset($data['fields'])) {
			$data['fields'] = '';
		}

		if (!isset($data['joins'])) {
			$data['joins'] = array();
		}

		if (!isset($data['order'])) {
			$data['order'] = '';
		}

		if (!isset($data['limit'])) {
			$data['limit'] = '';
		}

		if (!isset($data['offset'])) {
			$data['offset'] = null;
		}
	}
/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields
 * @return array
 */
	function fields(&$model, $alias, $fields) {
		$resultMatch = null;
		$build = true;
		if (is_array($fields)) {
			$fields = $fields;
		} else {
			if ($fields != null) {
				preg_match_all('/(\\w*\\([\\s\\S]*?\\)[\.,\\s\\w]*?\\))([\\s\\S]*)/', $fields, $result, PREG_PATTERN_ORDER);

				if(isset($result[1][0])){
					$resultMatch = $result[1][0];

					if(isset($result[2][0])){
						$fields = $result[2][0];

						if (preg_match('/AS/i', $fields)) {
							$build = false;
						}
					}
				}
				if($build === true){
					if (strpos($fields, ',')) {
						$fields = explode(',', $fields);
					} else {
						$fields = array($fields);
					}
					$fields = array_map('trim', $fields);
				}
			} else {
				foreach($model->_tableInfo->value as $field) {
					$fields[] = $field['name'];
				}
			}
		}
		if($build === true){
			$count = count($fields);

			if ($count >= 1 && $fields[0] != '*') {
				for($i = 0; $i < $count; $i++) {
					if (!preg_match('/^.+\\(.*\\)/', $fields[$i])) {
						$prepend = '';

						if (strpos($fields[$i], 'DISTINCT') !== false) {
							$prepend   = 'DISTINCT ';
							$fields[$i] = trim(r('DISTINCT', '', $fields[$i]));
						}

						$dot = strrpos($fields[$i], '.');

						if ($dot === false) {
							$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]);
						} else {
							$build = explode('.', $fields[$i]);
							$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]);
						}
					}
				}
			}
		}

		if($resultMatch != null){
			if(is_string($fields)) {
				$fields = array($resultMatch . $fields);
			} else {
				$fields = array_merge(array($resultMatch), $fields);
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
	function conditions($conditions) {
		$clause = '';
		if (!is_array($conditions)) {
			if (!preg_match('/^WHERE\\x20|^GROUP\\x20BY\\x20|^HAVING\\x20|^ORDER\\x20BY\\x20/i', $conditions, $match)) {
				$clause = ' WHERE ';
			}
		}

		if (is_string($conditions)) {
			if (trim($conditions) == '') {
				$conditions = ' 1 = 1';
			} else {
				$start = null;
				$end  = null;

				if (!empty($this->startQuote)) {
					$start = '\\\\' . $this->startQuote . '\\\\';
				}
				$end = $this->endQuote;

				if (!empty($this->endQuote)) {
					$end = '\\\\' . $this->endQuote . '\\\\';
				}
				preg_match_all('/(?:\'[^\'\\\]*(?:\\\.[^\'\\\]*)*\')|([a-z0-9_' . $start . $end . ']*\\.[a-z0-9_' . $start . $end . ']*)/i', $conditions, $match, PREG_PATTERN_ORDER);

				if (isset($match['1']['0'])) {
					$pregCount = count($match['1']);

					for($i = 0; $i < $pregCount; $i++) {
						if (!empty($match['1'][$i]) && !is_numeric($match['1'][$i])) {
							$conditions = $conditions . ' ';
							$conditions = preg_replace('/^' . $match['0'][$i] . '(?=[^\\w])/', ' '.$this->name($match['1'][$i]), $conditions);
							if (strpos($conditions, '(' . $match['0'][$i]) === false) {
								$conditions = preg_replace('/[^\w]' . $match['0'][$i] . '(?=[^\\w])/', ' '.$this->name($match['1'][$i]), $conditions);
							} else {
								$conditions = preg_replace('/' . $match['0'][$i] . '(?=[^\\w])/', ' '.$this->name($match['1'][$i]), $conditions);
							}
						}
					}
					$conditions = rtrim($conditions);
				}
			}
			return $clause . $conditions;
		} else {
			$clause = ' WHERE ';
			$out   = $this->conditionKeysToString($conditions);
			if (empty($out)) {
				return $clause . ' (1 = 1)';
			}
			return $clause . ' (' . join(') AND (', $out) . ')';
		}
	}
/**
 * Creates a WHERE clause by parsing given conditions array.  Used by DboSource::conditions().
 *
 * @param array $conditions Array or string of conditions
 * @return string SQL fragment
 */
	function conditionKeysToString($conditions) {
		$c = 0;
		$data = null;
		$out = array();
		$bool = array('and', 'or', 'not', 'and not', 'or not', 'xor', '||', '&&');
		$join = ' AND ';

		foreach($conditions as $key => $value) {
			if (in_array(strtolower(trim($key)), $bool)) {
				$join = ' ' . strtoupper($key) . ' ';
				$value = $this->conditionKeysToString($value);
				if (strpos($join, 'NOT') !== false) {
					$out[] = 'NOT (' . join(') ' . strtoupper($key) . ' (', $value) . ')';
				} else {
					$out[] = '(' . join(') ' . strtoupper($key) . ' (', $value) . ')';
				}
			} else {
				if (is_array($value) && !empty($value)) {
					$keys = array_keys($value);
					if ($keys[0] === 0) {
						$data = $this->name($key) . ' IN (';
						if	(strpos($value[0], '-!') === 0){
							$value[0] = str_replace('-!', '', $value[0]);
							$data .= $value[0];
							$data .= ')';
						} else {
							foreach($value as $valElement) {
								$data .= $this->value($valElement) . ', ';
							}
							$data[strlen($data) - 2] = ')';
						}
					} else {
						$out[] = '(' . join(') AND (', $this->conditionKeysToString($value)) . ')';
					}
				} elseif(is_numeric($key)) {
					$data = ' ' . $value;
				} elseif($value === null || (is_array($value) && empty($value))) {
					$data = $this->name($key) . ' IS NULL';
				} elseif($value === '') {
					$data = $this->name($key) . " = ''";
				} elseif (preg_match('/^([a-z]*\\([a-z0-9]*\\)\\x20?|(?:' . join('\\x20)|(?:', $this->__sqlOps) . '\\x20)|<=?(?![^>]+>)\\x20?|[>=!]{1,3}(?!<)\\x20?)?(.*)/i', $value, $match)) {
					if (preg_match('/(\\x20[\\w]*\\x20)/', $key, $regs)) {
						$clause = $regs['1'];
						$key = preg_replace('/' . $regs['1'] . '/', '', $key);
					}

					$mValue = trim($match['1']);
					if (empty($match['1'])) {
						$match['1'] = ' = ';
					} elseif (empty($mValue)) {
						$match['1'] = ' = ';
						$match['2'] = $match['0'];
					}

					if (strpos($match['2'], '-!') === 0) {
						$match['2'] = str_replace('-!', '', $match['2']);
						$data = $this->name($key) . ' ' . $match['1'] . ' ' . $match['2'];
					} else {
						if ($match['2'] != '' && !is_numeric($match['2'])) {
							$match['2'] = $this->value($match['2']);
							$match['2'] = str_replace(' AND ', "' AND '", $match['2']);
						}
						$data = $this->name($key) . ' ' . $match['1'] . ' ' . $match['2'];
					}
				}

				if ($data != null) {
					$out[] = $data;
				}
			}
			$c++;
		}
		return $out;
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
			foreach($keys as $key => $val) {
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

				foreach($keys as $val) {
					$val = $this->order($val);
					$new[] = $val;
				}

				$keys = $new;
			}

			foreach($keys as $key => $value) {
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

					$key = trim($this->name($key) . ' ' . $dir);
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

				for($i = 0; $i < $pregCount; $i++) {
					$keys = preg_replace('/' . $result['0'][$i] . '/', $this->name($result['0'][$i]), $keys);
				}

				if (preg_match('/\\x20ASC|\\x20DESC/i', $keys)) {
					return ' ORDER BY ' . $keys;
				} else {
					return ' ORDER BY ' . $keys . ' ' . $direction;
				}
			} elseif(preg_match('/(\\x20ASC|\\x20DESC)/i', $keys, $match)) {
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
 * To-be-overridden in subclasses.
 *
 */
	function buildSchemaQuery($schema) {
		die (__("Implement in DBO"));
	}
/**
 * Destructor. Closes connection to the database.
 *
 */
	function __destruct() {
		if ($this->__transactionStarted) {
			$this->rollback();
		}
		$this->close();
		parent::__destruct();
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
		$out = $this->fetchRow("SELECT COUNT(*) " . $this->alias . "count FROM " . $this->fullTableName($model) . ' ' . ($sql ? ' ' . $sql : 'WHERE 1 = 1'));

		if (is_array($out)) {
			return $out[0]['count'];
		} else {
			return false;
		}
	}
/**
 * Gets the 'meta' definition of the given database table, where $model is either
 * a model object, or the full table name.
 *
 * @param mixed $model
 * @return array
 */
	function getDefinition($model) {
		if (is_string($model)) {
			$table = $model;
		} else {
			$table = $this->fullTableName($model, false);
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
}
?>
