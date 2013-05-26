<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\App;
use Cake\Database\Connection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\Table;
use Cake\Error;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Cake TestFixture is responsible for building and destroying tables to be used
 * during testing.
 */
class TestFixture {

/**
 * Fixture Datasource
 *
 * @var string
 */
	public $connection = 'test';

/**
 * Full Table Name
 *
 * @var string
 */
	public $table = null;

/**
 * List of datasources where this fixture has been created
 *
 * @var array
 */
	public $created = array();

/**
 * Fields / Schema for the fixture.
 *
 * This array should be compatible with Cake\Database\Schema\Table.
 * The `constraints` and `indexes` keys are reserved for defining 
 * constraints and indexes respectively.
 *
 * @var array
 */
	public $fields = array();

/**
 * Fixture records to be inserted.
 *
 * @var array
 */
	public $records = array();

/**
 * The Cake\Database\Schema\Table for this fixture.
 *
 * @var Cake\Database\Schema\Table;
 */
	protected $_schema;

/**
 * Instantiate the fixture.
 *
 * @throws Cake\Error\Exception on invalid datasource usage.
 */
	public function __construct() {
		$connection = 'test';
		if (!empty($this->connection)) {
			$connection = $this->connection;
			if (strpos($connection, 'test') !== 0) {
				$message = __d(
					'cake_dev',
					'Invalid datasource name "%s" for "%s" fixture. Fixture datasource names must begin with "test".',
					$connection,
					$this->name
				);
				throw new Error\Exception($message);
			}
		}
		$this->init();
	}

/**
 * Initialize the fixture.
 *
 * @return void
 * @throws Cake\Error\MissingModelException Whe importing from a model that does not exist.
 */
	public function init() {
		if ($this->table === null) {
			list($namespace, $class) = namespaceSplit(get_class($this));
			preg_match('/^(.*)Fixture$/', $class, $matches);
			$table = $class;
			if (isset($matches[1])) {
				$table = $matches[1];
			}
			$this->table = Inflector::tableize(Inflector::pluralize($table));
		}

		if (empty($this->import) && !empty($this->fields)) {
			$this->_schemaFromFields();
		}

		if (isset($this->import) && (is_string($this->import) || is_array($this->import))) {
			$import = array_merge(
				array('connection' => 'default', 'records' => false),
				is_array($this->import) ? $this->import : array('model' => $this->import)
			);

			$this->Schema->connection = $import['connection'];
			if (isset($import['table'])) {
				$model = new Model(null, $import['table'], $import['connection']);
				$db = ConnectionManager::getDataSource($import['connection']);
				$db->cacheSources = false;
				$model->useDbConfig = $import['connection'];
				$model->name = Inflector::camelize(Inflector::singularize($import['table']));
				$model->table = $import['table'];
				$model->tablePrefix = $db->config['prefix'];
				$this->fields = $model->schema(true);
				$this->primaryKey = $model->primaryKey;
				ClassRegistry::flush();
			}

			if (!empty($db->config['prefix']) && strpos($this->table, $db->config['prefix']) === 0) {
				$this->table = str_replace($db->config['prefix'], '', $this->table);
			}
		}
	}

/**
 * Build the fixtures table schema from the fields property.
 *
 * @return void
 */
	protected function _schemaFromFields() {
		$this->_schema = new Table($this->table);
		foreach ($this->fields as $field => $data) {
			if ($field === 'constraints' || $field === 'indexes') {
				continue;
			}
			// TODO issue E_USER_NOTICE if a column defines 'key'?
			// Or handle the case correctly?
			$this->_schema->addColumn($field, $data);
		}
		if (!empty($this->fields['constraints'])) {
			foreach ($this->fields['constraints'] as $name => $data) {
				$this->_schema->addConstraint($name, $data);
			}
		}
		if (!empty($this->fields['indexes'])) {
			// TODO 2.x indexes contains indexes + constraints.
			// Should we issue an error or handle the case?
			foreach ($this->fields['indexes'] as $name => $data) {
				$this->_schema->addIndex($name, $data);
			}
		}
	}

/**
 * Get/Set the Cake\Database\Schema\Table instance used by this fixture.
 *
 * @param Cake\Database\Schema\Table $schema The table to set.
 * @return Cake\Database\Schema\Table|null
 */
	public function schema(Table $schema = null) {
		if ($schema) {
			$this->_schema = $schema;
			return;
		}
		return $this->_schema;
	}

/**
 * Run before all tests execute, should return SQL statement to create table for this fixture could be executed successfully.
 *
 * @param Connection $db An instance of the database object used to create the fixture table
 * @return boolean True on success, false on failure
 */
	public function create(Connection $db) {
		if (empty($this->_schema)) {
			return false;
		}

		// TODO figure this out as Table does not have tableOptions completed.
		if (empty($this->fields['tableParameters']['engine'])) {
			$canUseMemory = true;
			foreach ($this->fields as $args) {

				if (is_string($args)) {
					$type = $args;
				} elseif (!empty($args['type'])) {
					$type = $args['type'];
				} else {
					continue;
				}

				if (in_array($type, array('blob', 'text', 'binary'))) {
					$canUseMemory = false;
					break;
				}
			}

			if ($canUseMemory) {
				$this->fields['tableParameters']['engine'] = 'MEMORY';
			}
		}

		try {
			$queries = $this->_schema->createSql($db);
			foreach ($queries as $query) {
				$db->execute($query);
			}
			$this->created[] = $db->configKeyName;
		} catch (\Exception $e) {
			$msg = __d(
				'cake_dev',
				'Fixture creation for "%s" failed "%s"',
				$this->table,
				$e->getMessage()
			);
			Log::error($msg);
			trigger_error($msg, E_USER_WARNING);
			return false;
		}
		return true;
	}

/**
 * Run after all tests executed, should return SQL statement to drop table for this fixture.
 *
 * @param Connection $db An instance of the database object used to create the fixture table
 * @return boolean True on success, false on failure
 */
	public function drop(Connection $db) {
		if (empty($this->_schema)) {
			return false;
		}
		try {
			$sql = $this->_schema->dropSql($db);
			foreach ($sql as $stmt) {
				$db->execute($stmt);
			}
			$this->created = array_diff($this->created, [$db->configKeyName]);
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

/**
 * Run before each tests is executed, should return a set of SQL statements to insert records for the table
 * of this fixture could be executed successfully.
 *
 * @param Connection $db An instance of the database into which the records will be inserted
 * @return boolean on success or if there are no records to insert, or false on failure
 */
	public function insert(Connection $db) {
		if (isset($this->records) && !empty($this->records)) {
			list($fields, $values, $types) = $this->_getRecords();
			$query = $db->newQuery()
				->insert($this->table, $fields, $types);

			foreach ($values as $row) {
				$query->values($row);
			}

			$result = $query->execute();

			$primary = $this->_schema->primaryKey();
			if (
				count($primary) == 1 &&
				in_array($this->_schema->column($primary[0])['type'], ['integer', 'biginteger'])
			) {
				$db->resetSequence($this->table, $primary[0]);
			}
			return $result;
		}
		return true;
	}

/**
 * Converts the internal records into data used to generate a query.
 *
 * @return array
 */
	protected function _getRecords() {
		$fields = $values = $types = [];
		foreach ($this->records as $record) {
			$fields = array_merge($fields, array_keys(array_intersect_key($record, $this->fields)));
		}
		$fields = array_values(array_unique($fields));
		foreach ($fields as $field) {
			$types[] = $this->_schema->column($field)['type'];
		}
		$default = array_fill_keys($fields, null);
		foreach ($this->records as $record) {
			$values[] = array_merge($default, $record);
		}
		return [$fields, $values, $types];
	}

/**
 * Truncates the current fixture. Can be overwritten by classes extending
 * CakeFixture to trigger other events before / after truncate.
 *
 * @param Connection DboSource $db A reference to a db instance
 * @return boolean
 */
	public function truncate(Connection $db) {
		$sql = $this->_schema->truncateSql($db);
		foreach ($sql as $stmt) {
			$db->execute($stmt);
		}
		return true;
	}

}
