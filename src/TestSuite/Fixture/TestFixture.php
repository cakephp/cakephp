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

use Cake\Core\Exception\Exception as CakeException;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\Datasource\TableSchemaInterface;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Exception;

/**
 * Cake TestFixture is responsible for building and destroying tables to be used
 * during testing.
 */
class TestFixture implements FixtureInterface, TableSchemaInterface
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
     * This array should be compatible with Cake\Database\Schema\Schema.
     * The `_constraints`, `_options` and `_indexes` keys are reserved for defining
     * constraints, options and indexes respectively.
     *
     * @var array
     */
    public $fields = [];

    /**
     * Configuration for importing fixture schema
     *
     * Accepts a `connection` and `model` or `table` key, to define
     * which table and which connection contain the schema to be
     * imported.
     *
     * @var array|null
     */
    public $import = null;

    /**
     * Fixture records to be inserted.
     *
     * @var array
     */
    public $records = [];

    /**
     * The schema for this fixture.
     *
     * @var \Cake\Database\Schema\TableSchema
     */
    protected $_schema;

    /**
     * Fixture constraints to be created.
     *
     * @var array
     */
    protected $_constraints = [];

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
                    $this->table
                );
                throw new CakeException($message);
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
            $this->table = $this->_tableFromClass();
        }

        if (empty($this->import) && !empty($this->fields)) {
            $this->_schemaFromFields();
        }

        if (!empty($this->import)) {
            $this->_schemaFromImport();
        }

        if (empty($this->import) && empty($this->fields)) {
            $this->_schemaFromReflection();
        }
    }

    /**
     * Returns the table name using the fixture class
     *
     * @return string
     */
    protected function _tableFromClass()
    {
        list(, $class) = namespaceSplit(get_class($this));
        preg_match('/^(.*)Fixture$/', $class, $matches);
        $table = $class;

        if (isset($matches[1])) {
            $table = $matches[1];
        }

        return Inflector::tableize($table);
    }

    /**
     * Build the fixtures table schema from the fields property.
     *
     * @return void
     */
    protected function _schemaFromFields()
    {
        $connection = ConnectionManager::get($this->connection());
        $this->_schema = new TableSchema($this->table);
        foreach ($this->fields as $field => $data) {
            if ($field === '_constraints' || $field === '_indexes' || $field === '_options') {
                continue;
            }
            $this->_schema->addColumn($field, $data);
        }
        if (!empty($this->fields['_constraints'])) {
            foreach ($this->fields['_constraints'] as $name => $data) {
                if (!$connection->supportsDynamicConstraints() || $data['type'] !== TableSchema::CONSTRAINT_FOREIGN) {
                    $this->_schema->addConstraint($name, $data);
                } else {
                    $this->_constraints[$name] = $data;
                }
            }
        }
        if (!empty($this->fields['_indexes'])) {
            foreach ($this->fields['_indexes'] as $name => $data) {
                $this->_schema->addIndex($name, $data);
            }
        }
        if (!empty($this->fields['_options'])) {
            $this->_schema->setOptions($this->fields['_options']);
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
        $import = $this->import + ['connection' => 'default', 'table' => null, 'model' => null];

        if (!empty($import['model'])) {
            if (!empty($import['table'])) {
                throw new CakeException('You cannot define both table and model.');
            }
            $import['table'] = TableRegistry::get($import['model'])->getTable();
        }

        if (empty($import['table'])) {
            throw new CakeException('Cannot import from undefined table.');
        }

        $this->table = $import['table'];

        $db = ConnectionManager::get($import['connection'], false);
        $schemaCollection = $db->schemaCollection();
        $table = $schemaCollection->describe($import['table']);
        $this->_schema = $table;
    }

    /**
     * Build fixture schema directly from the datasource
     *
     * @return void
     * @throws \Cake\Core\Exception\Exception when trying to reflect a table that does not exist
     */
    protected function _schemaFromReflection()
    {
        $db = ConnectionManager::get($this->connection());
        $schemaCollection = $db->schemaCollection();
        $tables = $schemaCollection->listTables();

        if (!in_array($this->table, $tables)) {
            throw new CakeException(
                sprintf(
                    'Cannot describe schema for table `%s` for fixture `%s` : the table does not exist.',
                    $this->table,
                    get_class($this)
                )
            );
        }

        $this->_schema = $schemaCollection->describe($this->table);
    }

    /**
     * Gets/Sets the TableSchema instance used by this fixture.
     *
     * @param \Cake\Database\Schema\TableSchema|null $schema The table to set.
     * @return \Cake\Database\Schema\TableSchema|null
     */
    public function schema(TableSchema $schema = null)
    {
        if ($schema) {
            $this->_schema = $schema;

            return null;
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

        if (empty($this->import) && empty($this->fields)) {
            return true;
        }

        try {
            $queries = $this->_schema->createSql($db);
            foreach ($queries as $query) {
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stmt->closeCursor();
            }
        } catch (Exception $e) {
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

        if (empty($this->import) && empty($this->fields)) {
            return true;
        }

        try {
            $sql = $this->_schema->dropSql($db);
            foreach ($sql as $stmt) {
                $db->execute($stmt)->closeCursor();
            }
        } catch (Exception $e) {
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
     * {@inheritDoc}
     */
    public function createConstraints(ConnectionInterface $db)
    {
        if (empty($this->_constraints)) {
            return true;
        }

        foreach ($this->_constraints as $name => $data) {
            $this->_schema->addConstraint($name, $data);
        }

        $sql = $this->_schema->addConstraintSql($db);

        if (empty($sql)) {
            return true;
        }

        foreach ($sql as $stmt) {
            $db->execute($stmt)->closeCursor();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function dropConstraints(ConnectionInterface $db)
    {
        if (empty($this->_constraints)) {
            return true;
        }

        $sql = $this->_schema->dropConstraintSql($db);

        if (empty($sql)) {
            return true;
        }

        foreach ($sql as $stmt) {
            $db->execute($stmt)->closeCursor();
        }

        foreach ($this->_constraints as $name => $data) {
            $this->_schema->dropConstraint($name);
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
