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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\App;
use Cake\Database\Connection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
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
 * The `_constraints`, `_options` and `_indexes` keys are reserved for defining
 * constraints, options and indexes respectively.
 *
 * @var array
 */
	public $fields = array();

/**
 * Configuration for importing fixture schema
 *
 * Accepts a `connection` and `table` key, to define
 * which table and which connection contain the schema to be
 * imported.
 *
 * @var array
 */
	public $import = null;

/**
 * Fixture records to be inserted.
 *
 * @var array
 */
	public $records = array();

/**
 * The Cake\Database\Schema\Table for this fixture.
 *
 * @var \Cake\Database\Schema\Table
 */
	protected $_schema;

/**
 * Instantiate the fixture.
 *
 * @throws \Cake\Error\Exception on invalid datasource usage.
 */
	public function __construct() {
		$connection = 'test';
		if (!empty($this->connection)) {
			$connection = $this->connection;
			if (strpos($connection, 'test') !== 0) {
				$message = sprintf(
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
 * @throws \Cake\ORM\Error\MissingTableException When importing from a table that does not exist.
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

		if (!empty($this->import)) {
			$this->_schemaFromImport();
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
			if ($field === '_constraints' || $field === '_indexes' || $field === '_options') {
				continue;
			}
			// Trigger errors on deprecated usage.
			if (is_array($data) && isset($data['key'])) {
				$msg = 'Usage of the `key` options in columns is not supported. Try using the upgrade shell to migrate your fixtures.';
				$msg .= ' You can download the upgrade shell from https://github.com/cakephp/upgrade.';
				trigger_error($msg, E_USER_NOTICE);
			}
			$this->_schema->addColumn($field, $data);
		}
		if (!empty($this->fields['_constraints'])) {
			foreach ($this->fields['_constraints'] as $name => $data) {
				$this->_schema->addConstraint($name, $data);
			}
		}
		if (!empty($this->fields['_indexes'])) {
			// Trigger errors on deprecated usage.
			if (empty($data['type'])) {
				$msg = 'Indexes must define a type. Try using the upgrade shell to migrate your fixtures.';
				$msg .= ' You can download the upgrade shell from https://github.com/cakephp/upgrade.';
				trigger_error($msg, E_USER_NOTICE);
			}
			foreach ($this->fields['_indexes'] as $name => $data) {
				$this->_schema->addIndex($name, $data);
			}
		}
		if (!empty($this->fields['_options'])) {
			$this->_schema->options($this->fields['_options']);
		}
	}

/**
 * Build fixture schema from a table in another datasource.
 *
 * @return void
 * @throws \Cake\Error\Exception when trying to import from an empty table.
 */
	protected function _schemaFromImport() {
		if (!is_array($this->import)) {
			return;
		}
		$import = array_merge(
			array('connection' => 'default', 'table' => null),
			$this->import
		);

		if (empty($import['table'])) {
			throw new Error\Exception('Cannot import from undefined table.');
		}

		$db = ConnectionManager::get($import['connection']);
		$schemaCollection = $db->schemaCollection();
		$table = $schemaCollection->describe($import['table']);
		$this->_schema = $table;
	}

/**
 * Get/Set the Cake\Database\Schema\Table instance used by this fixture.
 *
 * @param \Cake\Database\Schema\Table $schema The table to set.
 * @return \Cake\Database\Schema\Table|null
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
 * @return bool True on success, false on failure
 */
	public function create(Connection $db) {
		if (empty($this->_schema)) {
			return false;
		}

		try {
			$queries = $this->_schema->createSql($db);
			foreach ($queries as $query) {
				$db->execute($query)->closeCursor();
			}
			$this->created[] = $db->configName();
		} catch (\Exception $e) {
			$msg = sprintf(
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
 * @return bool True on success, false on failure
 */
	public function drop(Connection $db) {
		if (empty($this->_schema)) {
			return false;
		}
		try {
			$sql = $this->_schema->dropSql($db);
			foreach ($sql as $stmt) {
				$db->execute($stmt)->closeCursor();
			}
			$this->created = array_diff($this->created, [$db->configName()]);
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
 * @return bool on success or if there are no records to insert, or false on failure
 */
	public function insert(Connection $db) {
		if (isset($this->records) && !empty($this->records)) {
			list($fields, $values, $types) = $this->_getRecords();
			$query = $db->newQuery()
				->insert($fields, $types)
				->into($this->table);

			foreach ($values as $row) {
				$query->values($row);
			}
			$statement = $query->execute();
			$statement->closeCursor();
			return $statement;
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
 * @param Connection $db A reference to a db instance
 * @return bool
 */
	public function truncate(Connection $db) {
		$sql = $this->_schema->truncateSql($db);
		foreach ($sql as $stmt) {
			$db->execute($stmt)->closeCursor();
		}
		return true;
	}

}
