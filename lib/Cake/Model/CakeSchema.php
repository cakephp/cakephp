<?php
/**
 * Schema database management for CakePHP.
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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('ConnectionManager', 'Model');
App::uses('File', 'Utility');

/**
 * Base Class for Schema management.
 *
 * @package       Cake.Model
 */
class CakeSchema extends CakeObject {

/**
 * Name of the schema.
 *
 * @var string
 */
	public $name = null;

/**
 * Path to write location.
 *
 * @var string
 */
	public $path = null;

/**
 * File to write.
 *
 * @var string
 */
	public $file = 'schema.php';

/**
 * Connection used for read.
 *
 * @var string
 */
	public $connection = 'default';

/**
 * Plugin name.
 *
 * @var string
 */
	public $plugin = null;

/**
 * Set of tables.
 *
 * @var array
 */
	public $tables = array();

/**
 * Constructor
 *
 * @param array $options Optional load object properties.
 */
	public function __construct($options = array()) {
		parent::__construct();

		if (empty($options['name'])) {
			$this->name = preg_replace('/schema$/i', '', get_class($this));
		}
		if (!empty($options['plugin'])) {
			$this->plugin = $options['plugin'];
		}

		if (strtolower($this->name) === 'cake') {
			$this->name = 'App';
		}

		if (empty($options['path'])) {
			$this->path = APP . 'Config' . DS . 'Schema';
		}

		$options = array_merge(get_object_vars($this), $options);
		$this->build($options);
	}

/**
 * Builds schema object properties.
 *
 * @param array $data Loaded object properties.
 * @return void
 */
	public function build($data) {
		$file = null;
		foreach ($data as $key => $val) {
			if (!empty($val)) {
				if (!in_array($key, array('plugin', 'name', 'path', 'file', 'connection', 'tables', '_log'))) {
					if ($key[0] === '_') {
						continue;
					}
					$this->tables[$key] = $val;
					unset($this->{$key});
				} elseif ($key !== 'tables') {
					if ($key === 'name' && $val !== $this->name && !isset($data['file'])) {
						$file = Inflector::underscore($val) . '.php';
					}
					$this->{$key} = $val;
				}
			}
		}
		if (file_exists($this->path . DS . $file) && is_file($this->path . DS . $file)) {
			$this->file = $file;
		} elseif (!empty($this->plugin)) {
			$this->path = CakePlugin::path($this->plugin) . 'Config' . DS . 'Schema';
		}
	}

/**
 * Before callback to be implemented in subclasses.
 *
 * @param array $event Schema object properties.
 * @return bool Should process continue.
 */
	public function before($event = array()) {
		return true;
	}

/**
 * After callback to be implemented in subclasses.
 *
 * @param array $event Schema object properties.
 * @return void
 */
	public function after($event = array()) {
	}

/**
 * Reads database and creates schema tables.
 *
 * @param array $options Schema object properties.
 * @return array Set of name and tables.
 */
	public function load($options = array()) {
		if (is_string($options)) {
			$options = array('path' => $options);
		}

		$this->build($options);
		extract(get_object_vars($this));

		$class = $name . 'Schema';

		if (!class_exists($class) && !$this->_requireFile($path, $file)) {
			$class = Inflector::camelize(Inflector::slug(Configure::read('App.dir'))) . 'Schema';
			if (!class_exists($class)) {
				$this->_requireFile($path, $file);
			}
		}

		if (class_exists($class)) {
			$Schema = new $class($options);
			return $Schema;
		}
		return false;
	}

/**
 * Reads database and creates schema tables.
 *
 * Options
 *
 * - 'connection' - the db connection to use
 * - 'name' - name of the schema
 * - 'models' - a list of models to use, or false to ignore models
 *
 * @param array $options Schema object properties.
 * @return array Array indexed by name and tables.
 */
	public function read($options = array()) {
		extract(array_merge(
			array(
				'connection' => $this->connection,
				'name' => $this->name,
				'models' => true,
			),
			$options
		));
		$db = ConnectionManager::getDataSource($connection);

		if (isset($this->plugin)) {
			App::uses($this->plugin . 'AppModel', $this->plugin . '.Model');
		}

		$tables = array();
		$currentTables = (array)$db->listSources();

		$prefix = null;
		if (isset($db->config['prefix'])) {
			$prefix = $db->config['prefix'];
		}

		if (!is_array($models) && $models !== false) {
			if (isset($this->plugin)) {
				$models = App::objects($this->plugin . '.Model', null, false);
			} else {
				$models = App::objects('Model');
			}
		}

		if (is_array($models)) {
			foreach ($models as $model) {
				$importModel = $model;
				$plugin = null;
				if ($model === 'AppModel') {
					continue;
				}

				if (isset($this->plugin)) {
					if ($model === $this->plugin . 'AppModel') {
						continue;
					}
					$importModel = $model;
					$plugin = $this->plugin . '.';
				}

				App::uses($importModel, $plugin . 'Model');
				if (!class_exists($importModel)) {
					continue;
				}

				$vars = get_class_vars($model);
				if (empty($vars['useDbConfig']) || $vars['useDbConfig'] != $connection) {
					continue;
				}

				try {
					$Object = ClassRegistry::init(array('class' => $model, 'ds' => $connection));
				} catch (CakeException $e) {
					continue;
				}

				if (!is_object($Object) || $Object->useTable === false) {
					continue;
				}
				$db = $Object->getDataSource();

				$fulltable = $table = $db->fullTableName($Object, false, false);
				if ($prefix && strpos($table, $prefix) !== 0) {
					continue;
				}
				if (!in_array($fulltable, $currentTables)) {
					continue;
				}

				$table = $this->_noPrefixTable($prefix, $table);

				$key = array_search($fulltable, $currentTables);
				if (empty($tables[$table])) {
					$tables[$table] = $this->_columns($Object);
					$tables[$table]['indexes'] = $db->index($Object);
					$tables[$table]['tableParameters'] = $db->readTableParameters($fulltable);
					unset($currentTables[$key]);
				}
				if (empty($Object->hasAndBelongsToMany)) {
					continue;
				}
				foreach ($Object->hasAndBelongsToMany as $assocData) {
					if (isset($assocData['with'])) {
						$class = $assocData['with'];
					}
					if (!is_object($Object->$class)) {
						continue;
					}
					$withTable = $db->fullTableName($Object->$class, false, false);
					if ($prefix && strpos($withTable, $prefix) !== 0) {
						continue;
					}
					if (in_array($withTable, $currentTables)) {
						$key = array_search($withTable, $currentTables);
						$noPrefixWith = $this->_noPrefixTable($prefix, $withTable);

						$tables[$noPrefixWith] = $this->_columns($Object->$class);
						$tables[$noPrefixWith]['indexes'] = $db->index($Object->$class);
						$tables[$noPrefixWith]['tableParameters'] = $db->readTableParameters($withTable);
						unset($currentTables[$key]);
					}
				}
			}
		}

		if (!empty($currentTables)) {
			foreach ($currentTables as $table) {
				if ($prefix) {
					if (strpos($table, $prefix) !== 0) {
						continue;
					}
					$table = $this->_noPrefixTable($prefix, $table);
				}
				$Object = new AppModel(array(
					'name' => Inflector::classify($table), 'table' => $table, 'ds' => $connection
				));

				$systemTables = array(
					'aros', 'acos', 'aros_acos', Configure::read('Session.table'), 'i18n'
				);

				$fulltable = $db->fullTableName($Object, false, false);

				if (in_array($table, $systemTables)) {
					$tables[$Object->table] = $this->_columns($Object);
					$tables[$Object->table]['indexes'] = $db->index($Object);
					$tables[$Object->table]['tableParameters'] = $db->readTableParameters($fulltable);
				} elseif ($models === false) {
					$tables[$table] = $this->_columns($Object);
					$tables[$table]['indexes'] = $db->index($Object);
					$tables[$table]['tableParameters'] = $db->readTableParameters($fulltable);
				} else {
					$tables['missing'][$table] = $this->_columns($Object);
					$tables['missing'][$table]['indexes'] = $db->index($Object);
					$tables['missing'][$table]['tableParameters'] = $db->readTableParameters($fulltable);
				}
			}
		}

		ksort($tables);
		return compact('name', 'tables');
	}

/**
 * Writes schema file from object or options.
 *
 * @param array|object $object Schema object or options array.
 * @param array $options Schema object properties to override object.
 * @return mixed False or string written to file.
 */
	public function write($object, $options = array()) {
		if (is_object($object)) {
			$object = get_object_vars($object);
			$this->build($object);
		}

		if (is_array($object)) {
			$options = $object;
			unset($object);
		}

		extract(array_merge(
			get_object_vars($this), $options
		));

		$out = "class {$name}Schema extends CakeSchema {\n\n";

		if ($path !== $this->path) {
			$out .= "\tpublic \$path = '{$path}';\n\n";
		}

		if ($file !== $this->file) {
			$out .= "\tpublic \$file = '{$file}';\n\n";
		}

		if ($connection !== 'default') {
			$out .= "\tpublic \$connection = '{$connection}';\n\n";
		}

		$out .= "\tpublic function before(\$event = array()) {\n\t\treturn true;\n\t}\n\n\tpublic function after(\$event = array()) {\n\t}\n\n";

		if (empty($tables)) {
			$this->read();
		}

		foreach ($tables as $table => $fields) {
			if (!is_numeric($table) && $table !== 'missing') {
				$out .= $this->generateTable($table, $fields);
			}
		}
		$out .= "}\n";

		$file = new File($path . DS . $file, true);
		$content = "<?php \n{$out}";
		if ($file->write($content)) {
			return $content;
		}
		return false;
	}

/**
 * Generate the schema code for a table.
 *
 * Takes a table name and $fields array and returns a completed,
 * escaped variable declaration to be used in schema classes.
 *
 * @param string $table Table name you want returned.
 * @param array $fields Array of field information to generate the table with.
 * @return string Variable declaration for a schema class.
 */
	public function generateTable($table, $fields) {
		$out = "\tpublic \${$table} = array(\n";
		if (is_array($fields)) {
			$cols = array();
			foreach ($fields as $field => $value) {
				if ($field !== 'indexes' && $field !== 'tableParameters') {
					if (is_string($value)) {
						$type = $value;
						$value = array('type' => $type);
					}
					$col = "\t\t'{$field}' => array('type' => '" . $value['type'] . "', ";
					unset($value['type']);
					$col .= implode(', ', $this->_values($value));
				} elseif ($field === 'indexes') {
					$col = "\t\t'indexes' => array(\n\t\t\t";
					$props = array();
					foreach ((array)$value as $key => $index) {
						$props[] = "'{$key}' => array(" . implode(', ', $this->_values($index)) . ")";
					}
					$col .= implode(",\n\t\t\t", $props) . "\n\t\t";
				} elseif ($field === 'tableParameters') {
					$col = "\t\t'tableParameters' => array(";
					$props = $this->_values($value);
					$col .= implode(', ', $props);
				}
				$col .= ")";
				$cols[] = $col;
			}
			$out .= implode(",\n", $cols);
		}
		$out .= "\n\t);\n\n";
		return $out;
	}

/**
 * Compares two sets of schemas.
 *
 * @param array|object $old Schema object or array.
 * @param array|object $new Schema object or array.
 * @return array Tables (that are added, dropped, or changed.)
 */
	public function compare($old, $new = null) {
		if (empty($new)) {
			$new = $this;
		}
		if (is_array($new)) {
			if (isset($new['tables'])) {
				$new = $new['tables'];
			}
		} else {
			$new = $new->tables;
		}

		if (is_array($old)) {
			if (isset($old['tables'])) {
				$old = $old['tables'];
			}
		} else {
			$old = $old->tables;
		}
		$tables = array();
		foreach ($new as $table => $fields) {
			if ($table === 'missing') {
				continue;
			}
			if (!array_key_exists($table, $old)) {
				$tables[$table]['create'] = $fields;
			} else {
				$diff = $this->_arrayDiffAssoc($fields, $old[$table]);
				if (!empty($diff)) {
					$tables[$table]['add'] = $diff;
				}
				$diff = $this->_arrayDiffAssoc($old[$table], $fields);
				if (!empty($diff)) {
					$tables[$table]['drop'] = $diff;
				}
			}

			foreach ($fields as $field => $value) {
				if (!empty($old[$table][$field])) {
					$diff = $this->_arrayDiffAssoc($value, $old[$table][$field]);
					if (empty($diff)) {
						$diff = $this->_arrayDiffAssoc($old[$table][$field], $value);
					}
					if (!empty($diff) && $field !== 'indexes' && $field !== 'tableParameters') {
						$tables[$table]['change'][$field] = $value;
					}
				}

				if (isset($tables[$table]['add'][$field]) && $field !== 'indexes' && $field !== 'tableParameters') {
					$wrapper = array_keys($fields);
					if ($column = array_search($field, $wrapper)) {
						if (isset($wrapper[$column - 1])) {
							$tables[$table]['add'][$field]['after'] = $wrapper[$column - 1];
						}
					}
				}
			}

			if (isset($old[$table]['indexes']) && isset($new[$table]['indexes'])) {
				$diff = $this->_compareIndexes($new[$table]['indexes'], $old[$table]['indexes']);
				if ($diff) {
					if (!isset($tables[$table])) {
						$tables[$table] = array();
					}
					if (isset($diff['drop'])) {
						$tables[$table]['drop']['indexes'] = $diff['drop'];
					}
					if ($diff && isset($diff['add'])) {
						$tables[$table]['add']['indexes'] = $diff['add'];
					}
				}
			}
			if (isset($old[$table]['tableParameters']) && isset($new[$table]['tableParameters'])) {
				$diff = $this->_compareTableParameters($new[$table]['tableParameters'], $old[$table]['tableParameters']);
				if ($diff) {
					$tables[$table]['change']['tableParameters'] = $diff;
				}
			}
		}
		return $tables;
	}

/**
 * Extended array_diff_assoc noticing change from/to NULL values.
 *
 * It behaves almost the same way as array_diff_assoc except for NULL values: if
 * one of the values is not NULL - change is detected. It is useful in situation
 * where one value is strval('') ant other is strval(null) - in string comparing
 * methods this results as EQUAL, while it is not.
 *
 * @param array $array1 Base array.
 * @param array $array2 Corresponding array checked for equality.
 * @return array Difference as array with array(keys => values) from input array
 *     where match was not found.
 */
	protected function _arrayDiffAssoc($array1, $array2) {
		$difference = array();
		foreach ($array1 as $key => $value) {
			if (!array_key_exists($key, $array2)) {
				$difference[$key] = $value;
				continue;
			}
			$correspondingValue = $array2[$key];
			if (($value === null) !== ($correspondingValue === null)) {
				$difference[$key] = $value;
				continue;
			}
			if (is_bool($value) !== is_bool($correspondingValue)) {
				$difference[$key] = $value;
				continue;
			}
			if (is_array($value) && is_array($correspondingValue)) {
				continue;
			}
			if ($value === $correspondingValue) {
				continue;
			}
			$difference[$key] = $value;
		}
		return $difference;
	}

/**
 * Formats Schema columns from Model Object.
 *
 * @param array $values Options keys(type, null, default, key, length, extra).
 * @return array Formatted values.
 */
	protected function _values($values) {
		$vals = array();
		if (is_array($values)) {
			foreach ($values as $key => $val) {
				if (is_array($val)) {
					$vals[] = "'{$key}' => array(" . implode(", ", $this->_values($val)) . ")";
				} else {
					$val = var_export($val, true);
					if ($val === 'NULL') {
						$val = 'null';
					}
					if (!is_numeric($key)) {
						$vals[] = "'{$key}' => {$val}";
					} else {
						$vals[] = "{$val}";
					}
				}
			}
		}
		return $vals;
	}

/**
 * Formats Schema columns from Model Object.
 *
 * @param array &$Obj model object.
 * @return array Formatted columns.
 */
	protected function _columns(&$Obj) {
		$db = $Obj->getDataSource();
		$fields = $Obj->schema(true);

		$columns = array();
		foreach ($fields as $name => $value) {
			if ($Obj->primaryKey === $name) {
				$value['key'] = 'primary';
			}
			if (!isset($db->columns[$value['type']])) {
				trigger_error(__d('cake_dev', 'Schema generation error: invalid column type %s for %s.%s does not exist in DBO', $value['type'], $Obj->name, $name), E_USER_NOTICE);
				continue;
			} else {
				$defaultCol = $db->columns[$value['type']];
				if (isset($defaultCol['limit']) && $defaultCol['limit'] == $value['length']) {
					unset($value['length']);
				} elseif (isset($defaultCol['length']) && $defaultCol['length'] == $value['length']) {
					unset($value['length']);
				}
				unset($value['limit']);
			}

			if (isset($value['default']) && ($value['default'] === '' || ($value['default'] === false && $value['type'] !== 'boolean'))) {
				unset($value['default']);
			}
			if (empty($value['length'])) {
				unset($value['length']);
			}
			if (empty($value['key'])) {
				unset($value['key']);
			}
			$columns[$name] = $value;
		}

		return $columns;
	}

/**
 * Compare two schema files table Parameters.
 *
 * @param array $new New indexes.
 * @param array $old Old indexes.
 * @return mixed False on failure, or an array of parameters to add & drop.
 */
	protected function _compareTableParameters($new, $old) {
		if (!is_array($new) || !is_array($old)) {
			return false;
		}
		$change = $this->_arrayDiffAssoc($new, $old);
		return $change;
	}

/**
 * Compare two schema indexes.
 *
 * @param array $new New indexes.
 * @param array $old Old indexes.
 * @return mixed False on failure or array of indexes to add and drop.
 */
	protected function _compareIndexes($new, $old) {
		if (!is_array($new) || !is_array($old)) {
			return false;
		}

		$add = $drop = array();

		$diff = $this->_arrayDiffAssoc($new, $old);
		if (!empty($diff)) {
			$add = $diff;
		}

		$diff = $this->_arrayDiffAssoc($old, $new);
		if (!empty($diff)) {
			$drop = $diff;
		}

		foreach ($new as $name => $value) {
			if (isset($old[$name])) {
				$newUnique = isset($value['unique']) ? $value['unique'] : 0;
				$oldUnique = isset($old[$name]['unique']) ? $old[$name]['unique'] : 0;
				$newColumn = $value['column'];
				$oldColumn = $old[$name]['column'];

				$diff = false;

				if ($newUnique != $oldUnique) {
					$diff = true;
				} elseif (is_array($newColumn) && is_array($oldColumn)) {
					$diff = ($newColumn !== $oldColumn);
				} elseif (is_string($newColumn) && is_string($oldColumn)) {
					$diff = ($newColumn != $oldColumn);
				} else {
					$diff = true;
				}
				if ($diff) {
					$drop[$name] = null;
					$add[$name] = $value;
				}
			}
		}
		return array_filter(compact('add', 'drop'));
	}

/**
 * Trim the table prefix from the full table name, and return the prefix-less
 * table.
 *
 * @param string $prefix Table prefix.
 * @param string $table Full table name.
 * @return string Prefix-less table name.
 */
	protected function _noPrefixTable($prefix, $table) {
		return preg_replace('/^' . preg_quote($prefix) . '/', '', $table);
	}

/**
 * Attempts to require the schema file specified.
 *
 * @param string $path Filesystem path to the file.
 * @param string $file Filesystem basename of the file.
 * @return bool True when a file was successfully included, false on failure.
 */
	protected function _requireFile($path, $file) {
		if (file_exists($path . DS . $file) && is_file($path . DS . $file)) {
			require_once $path . DS . $file;
			return true;
		} elseif (file_exists($path . DS . 'schema.php') && is_file($path . DS . 'schema.php')) {
			require_once $path . DS . 'schema.php';
			return true;
		}
		return false;
	}

}
