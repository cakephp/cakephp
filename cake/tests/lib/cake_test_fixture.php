<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

/**
 * Short description for class.
 *
 * @package       cake.tests.lib
 */
class CakeTestFixture {

/**
 * Name of the object
 *
 * @var string
 */
	public $name = null;

/**
 * Cake's DBO driver (e.g: DboMysql).
 *
 */
	public $db = null;

/**
 * Full Table Name
 *
 */
	public $table = null;

/**
 * Instantiate the fixture.
 *
 */
	public function __construct() {
		App::import('Model', 'CakeSchema');
		$this->Schema = new CakeSchema(array('name' => 'TestSuite', 'connection' => 'test'));
		$this->init();
	}

/**
 * Initialize the fixture.
 *
 */
	public function init() {
		if (isset($this->import) && (is_string($this->import) || is_array($this->import))) {
			$import = array_merge(
				array('connection' => 'default', 'records' => false), 
				is_array($this->import) ? $this->import : array('model' => $this->import)
			);

			if (isset($import['model']) && App::import('Model', $import['model'])) {
				App::import('Model', $import['model']);
				list(, $modelClass) = pluginSplit($import['model']);
				$model = new $modelClass(null, null, $import['connection']);
				$db = $model->getDataSource();
				if (empty($model->tablePrefix)) {
					$model->tablePrefix = $db->config['prefix'];
				}
				$this->fields = $model->schema(true);
				$this->fields[$model->primaryKey]['key'] = 'primary';
				$this->table = $db->fullTableName($model, false);
				ClassRegistry::config(array('ds' => 'test'));
				ClassRegistry::flush();
			} elseif (isset($import['table'])) {
				$model = new Model(null, $import['table'], $import['connection']);
				$db = ConnectionManager::getDataSource($import['connection']);
				$db->cacheSources = false;
				$model->useDbConfig = $import['connection'];
				$model->name = Inflector::camelize(Inflector::singularize($import['table']));
				$model->table = $import['table'];
				$model->tablePrefix = $db->config['prefix'];
				$this->fields = $model->schema(true);
				ClassRegistry::flush();
			}

			if (!empty($db->config['prefix']) && strpos($this->table, $db->config['prefix']) === 0) {
				$this->table = str_replace($db->config['prefix'], '', $this->table);
			}

			if (isset($import['records']) && $import['records'] !== false && isset($model) && isset($db)) {
				$this->records = array();
				$query = array(
					'fields' => $db->fields($model, null, array_keys($this->fields)),
					'table' => $db->fullTableName($model),
					'alias' => $model->alias,
					'conditions' => array(),
					'order' => null,
					'limit' => null,
					'group' => null
				);
				$records = $db->fetchAll($db->buildStatement($query, $model), false, $model->alias);

				if ($records !== false && !empty($records)) {
					$this->records = Set::extract($records, '{n}.' . $model->alias);
				}
			}
		}

		if (!isset($this->table)) {
			$this->table = Inflector::underscore(Inflector::pluralize($this->name));
		}

		if (!isset($this->primaryKey) && isset($this->fields['id'])) {
			$this->primaryKey = 'id';
		}
	}

/**
 * Run before all tests execute, should return SQL statement to create table for this fixture could be executed successfully.
 *
 * @param object	$db	An instance of the database object used to create the fixture table
 * @return boolean True on success, false on failure
 */
	public function create(&$db) {
		if (!isset($this->fields) || empty($this->fields)) {
			return false;
		}

		$this->Schema->build(array($this->table => $this->fields));
		return (
			$db->execute($db->createSchema($this->Schema), array('log' => false)) !== false
		);
	}

/**
 * Run after all tests executed, should return SQL statement to drop table for this fixture.
 *
 * @param object	$db	An instance of the database object used to create the fixture table
 * @return boolean True on success, false on failure
 */
	public function drop(&$db) {
		$this->Schema->build(array($this->table => $this->fields));
		return (
			$db->execute($db->dropSchema($this->Schema), array('log' => false)) !== false
		);
	}

/**
 * Run before each tests is executed, should return a set of SQL statements to insert records for the table
 * of this fixture could be executed successfully.
 *
 * @param object $db An instance of the database into which the records will be inserted
 * @return boolean on success or if there are no records to insert, or false on failure
 */
	public function insert(&$db) {
		if (!isset($this->_insert)) {
			$values = array();
			if (isset($this->records) && !empty($this->records)) {
				foreach ($this->records as $record) {
					$fields = array_keys($record);
					$values[] = array_values($record);
				}
				return $db->insertMulti($this->table, $fields, $values);
			}
			return true;
		}
	}

/**
 * Truncates the current fixture. Can be overwritten by classes extending CakeFixture to trigger other events before / after
 * truncate.
 *
 * @param object $db A reference to a db instance
 * @return boolean
 */
	public function truncate(&$db) {
		$fullDebug = $db->fullDebug;
		$db->fullDebug = false;
		$return = $db->truncate($this->table);
		$db->fullDebug = $fullDebug;
		return $return;
	}
}
