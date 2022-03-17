<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

use Cake\Core\Exception\CakeException;
use Cake\Database\ConstraintsInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaAwareInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;
use Exception;

/**
 * Cake TestFixture is responsible for building and destroying tables to be used
 * during testing.
 */
class TestFixture implements ConstraintsInterface, FixtureInterface, TableSchemaAwareInterface
{
    use LocatorAwareTrait;

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
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public $table;

    /**
     * Fields / Schema for the fixture.
     *
     * This array should be compatible with {@link \Cake\Database\Schema\Schema}.
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
    public $import;

    /**
     * Fixture records to be inserted.
     *
     * @var array
     */
    public $records = [];

    /**
     * The schema for this fixture.
     *
     * @var \Cake\Database\Schema\TableSchemaInterface&\Cake\Database\Schema\SqlGeneratorInterface
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $_schema;

    /**
     * Fixture constraints to be created.
     *
     * @var array<string, mixed>
     */
    protected $_constraints = [];

    /**
     * Instantiate the fixture.
     *
     * @throws \Cake\Core\Exception\CakeException on invalid datasource usage.
     */
    public function __construct()
    {
        if (!empty($this->connection)) {
            $connection = $this->connection;
            if (strpos($connection, 'test') !== 0) {
                $message = sprintf(
                    'Invalid datasource name "%s" for "%s" fixture. Fixture datasource names must begin with "test".',
                    $connection,
                    static::class
                );
                throw new CakeException($message);
            }
        }
        $this->init();
    }

    /**
     * @inheritDoc
     */
    public function connection(): string
    {
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function sourceName(): string
    {
        return $this->table;
    }

    /**
     * Initialize the fixture.
     *
     * @return void
     * @throws \Cake\ORM\Exception\MissingTableClassException When importing from a table that does not exist.
     */
    public function init(): void
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
    protected function _tableFromClass(): string
    {
        [, $class] = namespaceSplit(static::class);
        preg_match('/^(.*)Fixture$/', $class, $matches);
        $table = $matches[1] ?? $class;

        return Inflector::tableize($table);
    }

    /**
     * Build the fixtures table schema from the fields property.
     *
     * @return void
     */
    protected function _schemaFromFields(): void
    {
        $connection = ConnectionManager::get($this->connection());
        $this->_schema = $connection->getDriver()->newTableSchema($this->table);
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
     * @throws \Cake\Core\Exception\CakeException when trying to import from an empty table.
     */
    protected function _schemaFromImport(): void
    {
        if (!is_array($this->import)) {
            return;
        }
        $import = $this->import + ['connection' => 'default', 'table' => null, 'model' => null];

        if (!empty($import['model'])) {
            if (!empty($import['table'])) {
                throw new CakeException('You cannot define both table and model.');
            }
            $import['table'] = $this->getTableLocator()->get($import['model'])->getTable();
        }

        if (empty($import['table'])) {
            throw new CakeException('Cannot import from undefined table.');
        }

        $this->table = $import['table'];

        $db = ConnectionManager::get($import['connection'], false);
        $schemaCollection = $db->getSchemaCollection();
        $table = $schemaCollection->describe($import['table']);
        $this->_schema = $table;
    }

    /**
     * Build fixture schema directly from the datasource
     *
     * @return void
     * @throws \Cake\Core\Exception\CakeException when trying to reflect a table that does not exist
     */
    protected function _schemaFromReflection(): void
    {
        $db = ConnectionManager::get($this->connection());
        try {
            $name = Inflector::camelize($this->table);
            $ormTable = $this->fetchTable($name, ['connection' => $db]);

            /** @var \Cake\Database\Schema\TableSchema $schema */
            $schema = $ormTable->getSchema();
            $this->_schema = $schema;

            $this->getTableLocator()->clear();
        } catch (CakeException $e) {
            $message = sprintf(
                'Cannot describe schema for table `%s` for fixture `%s`. The table does not exist.',
                $this->table,
                static::class
            );
            throw new CakeException($message, null, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function create(ConnectionInterface $connection): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->_schema)) {
            return false;
        }

        if (empty($this->import) && empty($this->fields)) {
            return true;
        }

        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $queries = $this->_schema->createSql($connection);
            foreach ($queries as $query) {
                $stmt = $connection->prepare($query);
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
     * @inheritDoc
     */
    public function drop(ConnectionInterface $connection): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->_schema)) {
            return false;
        }

        if (empty($this->import) && empty($this->fields)) {
            return $this->truncate($connection);
        }

        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $sql = $this->_schema->dropSql($connection);
            foreach ($sql as $stmt) {
                $connection->execute($stmt)->closeCursor();
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function insert(ConnectionInterface $connection)
    {
        if (!empty($this->records)) {
            [$fields, $values, $types] = $this->_getRecords();
            $query = $connection->newQuery()
                ->insert($fields, $types)
                ->into($this->sourceName());

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
     * @inheritDoc
     */
    public function createConstraints(ConnectionInterface $connection): bool
    {
        if (empty($this->_constraints)) {
            return true;
        }

        foreach ($this->_constraints as $name => $data) {
            $this->_schema->addConstraint($name, $data);
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $sql = $this->_schema->addConstraintSql($connection);

        if (empty($sql)) {
            return true;
        }

        foreach ($sql as $stmt) {
            $connection->execute($stmt)->closeCursor();
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function dropConstraints(ConnectionInterface $connection): bool
    {
        if (empty($this->_constraints)) {
            return true;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $sql = $this->_schema->dropConstraintSql($connection);

        if (empty($sql)) {
            return true;
        }

        foreach ($sql as $stmt) {
            $connection->execute($stmt)->closeCursor();
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
    protected function _getRecords(): array
    {
        $fields = $values = $types = [];
        $columns = $this->_schema->columns();
        foreach ($this->records as $record) {
            $fields = array_merge($fields, array_intersect(array_keys($record), $columns));
        }
        $fields = array_values(array_unique($fields));
        foreach ($fields as $field) {
            /** @var array $column */
            $column = $this->_schema->getColumn($field);
            $types[$field] = $column['type'];
        }
        $default = array_fill_keys($fields, null);
        foreach ($this->records as $record) {
            $values[] = array_merge($default, $record);
        }

        return [$fields, $values, $types];
    }

    /**
     * @inheritDoc
     */
    public function truncate(ConnectionInterface $connection): bool
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $sql = $this->_schema->truncateSql($connection);
        foreach ($sql as $stmt) {
            $connection->execute($stmt)->closeCursor();
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getTableSchema()
    {
        return $this->_schema;
    }

    /**
     * @inheritDoc
     */
    public function setTableSchema($schema)
    {
        $this->_schema = $schema;

        return $this;
    }
}
