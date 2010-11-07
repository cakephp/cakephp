<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class CakeTestFixture extends Object {

/**
 * Name of the object
 *
 * @var string
 */
	var $name = null;

/**
 * Cake's DBO driver (e.g: DboMysql).
 *
 * @access public
 */
	var $db = null;

/**
 * Full Table Name
 *
 * @access public
 */
	var $table = null;

/**
 * Instantiate the fixture.
 *
 * @access public
 */
	function __construct() {
		App::import('Model', 'CakeSchema');
		$this->Schema = new CakeSchema(array('name' => 'TestSuite', 'connection' => 'test_suite'));

		$this->init();
	}

/**
 * Initialize the fixture.
 *
 * @param object	Cake's DBO driver (e.g: DboMysql).
 * @access public
 *
 */
	function init() {
		if (isset($this->import) && (is_string($this->import) || is_array($this->import))) {
			$import = array_merge(
				array('connection' => 'default', 'records' => false), 
				is_array($this->import) ? $this->import : array('model' => $this->import)
			);

			if (isset($import['model']) && App::import('Model', $import['model'])) {
				ClassRegistry::config(array('ds' => $import['connection']));
				$model =& ClassRegistry::init($import['model']);
				$db =& ConnectionManager::getDataSource($model->useDbConfig);
				$db->cacheSources = false;
				$this->fields = $model->schema(true);
				$this->fields[$model->primaryKey]['key'] = 'primary';
				$this->table = $db->fullTableName($model, false);
				ClassRegistry::config(array('ds' => 'test_suite'));
				ClassRegistry::flush();
			} elseif (isset($import['table'])) {
				$model =& new Model(null, $import['table'], $import['connection']);
				$db =& ConnectionManager::getDataSource($import['connection']);
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
 * @access public
 */
	function create(&$db) {
		if (!isset($this->fields) || empty($this->fields)) {
			return false;
		}

		$this->Schema->_build(array($this->table => $this->fields));
		return (
			$db->execute($db->createSchema($this->Schema), array('log' => false)) !== false
		);
	}

/**
 * Run after all tests executed, should return SQL statement to drop table for this fixture.
 *
 * @param object	$db	An instance of the database object used to create the fixture table
 * @return boolean True on success, false on failure
 * @access public
 */
	function drop(&$db) {
		$this->Schema->_build(array($this->table => $this->fields));
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
 * @access public
 */
	function insert(&$db) {
		if (!isset($this->_insert)) {
			$values = array();

			if (isset($this->records) && !empty($this->records)) {
				foreach ($this->records as $record) {
					$fields = array_keys($record);
					$values[] = '(' . implode(', ', array_map(array(&$db, 'value'), array_values($record))) . ')';
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
 * @access public
 */
	function truncate(&$db) {
		$fullDebug = $db->fullDebug;
		$db->fullDebug = false;
		$return = $db->truncate($this->table);
		$db->fullDebug = $fullDebug;
		return $return;
	}
}
