<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\Exception\Exception;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\Log\Log;
use Cake\Utility\Inflector;

/**
 * Cake TestFixture is responsible for building and destroying tables to be used
 * during testing.
 */
class TestFixture implements FixtureInterface
{

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
     * Fields / Schema for the fixture.
     *
     * This array should be compatible with Cake\Database\Schema\Table.
     * The `_constraints`, `_options` and `_indexes` keys are reserved for defining
     * constraints, options and indexes respectively.
     *
     * @var array
     */
    public $fields = [];

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
    public $records = [];

    /**
     * The Cake\Database\Schema\Table for this fixture.
     *
     * @var \Cake\Database\Schema\Table
     */
    protected $_schema;

    /**
     * Instantiate the fixture.
     *
     * @throws \Cake\Core\Exception\Exception on invalid datasource usage.
     */
    public function __construct()
    {
        if (!empty($this->connection)) {
            $connection = $this->connection;
            if (strpos($connection, 'test') !== 0) {
                $message = sprintf(
                    'Invalid datasource name "%s" for "%s" fixture. Fixture datasource names must begin with "test".',
                    $connection,
                    $this->name
                );
                throw new Exception($message);
            }
        }
        $this->init();
    }

    /**
     * {@inheritDoc}
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * {@inheritDoc}
     */
    public function sourceName()
    {
        return $this->table;
    }

    /**
     * Initialize the fixture.
     *
     * @return void
     * @throws \Cake\ORM\Exception\MissingTableClassException When importing from a table that does not exist.
     */
    public function init()
    {
        if ($this->table === null) {
            list(, $class) = namespaceSplit(get_class($this));
            preg_match('/^(.*)Fixture$/', $class, $matches);
            $table = $class;
            if (isset($matches[1])) {
                $table = $matches[1];
            }
            $this->table = Inflector::tableize($table);
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
    protected function _schemaFromFields()
    {
        $this->_schema = new Table($this->table);
        foreach ($this->fields as $field => $data) {
            if ($field === '_constraints' || $field === '_indexes' || $field === '_options') {
                continue;
            }
            $this->_schema->addColumn($field, $data);
        }
        if (!empty($this->fields['_constraints'])) {
            foreach ($this->fields['_constraints'] as $name => $data) {
                $this->_schema->addConstraint($name, $data);
            }
        }
        if (!empty($this->fields['_indexes'])) {
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
     * @throws \Cake\Core\Exception\Exception when trying to import from an empty table.
     */
    protected function _schemaFromImport()
    {
        if (!is_array($this->import)) {
            return;
        }
        $import = array_merge(
            ['connection' => 'default', 'table' => null],
            $this->import
        );

        if (empty($import['table'])) {
            throw new Exception('Cannot import from undefined table.');
        } else {
            $this->table = $import['table'];
        }

        $db = ConnectionManager::get($import['connection'], false);
        $schemaCollection = $db->schemaCollection();
        $table = $schemaCollection->describe($import['table']);
        $this->_schema = $table;
    }

    /**
     * Get/Set the Cake\Database\Schema\Table instance used by this fixture.
     *
     * @param \Cake\Database\Schema\Table $schema The table to set.
     * @return void|\Cake\Database\Schema\Table
     */
    public function schema(Table $schema = null)
    {
        if ($schema) {
            $this->_schema = $schema;
            return;
        }
        return $this->_schema;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ConnectionInterface $db)
    {
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
     * {@inheritDoc}
     */
    public function drop(ConnectionInterface $db)
    {
        if (empty($this->_schema)) {
            return false;
        }
        try {
            $sql = $this->_schema->dropSql($db);
            foreach ($sql as $stmt) {
                $db->execute($stmt)->closeCursor();
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function insert(ConnectionInterface $db)
    {
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
    protected function _getRecords()
    {
        $fields = $values = $types = [];
        $columns = $this->_schema->columns();
        foreach ($this->records as $record) {
            $fields = array_merge($fields, array_intersect(array_keys($record), $columns));
        }
        $fields = array_values(array_unique($fields));
        foreach ($fields as $field) {
            $types[$field] = $this->_schema->column($field)['type'];
        }
        $default = array_fill_keys($fields, null);
        foreach ($this->records as $record) {
            $values[] = array_merge($default, $record);
        }
        return [$fields, $values, $types];
    }

    /**
     * {@inheritDoc}
     */
    public function truncate(ConnectionInterface $db)
    {
        $sql = $this->_schema->truncateSql($db);
        foreach ($sql as $stmt) {
            $db->execute($stmt)->closeCursor();
        }
        return true;
    }
}
