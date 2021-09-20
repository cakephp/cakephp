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
use Cake\Database\Schema\TableSchemaAwareInterface;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;

/**
 * Cake TestFixture is responsible for building and destroying tables to be used
 * during testing.
 */
class TestFixture implements FixtureInterface, TableSchemaAwareInterface
{
    use LocatorAwareTrait;

    /**
     * Fixture Datasource
     *
     * @var string
     */
    public string $connection = 'test';

    /**
     * Full Table Name
     *
     * @var string
     */
    public string $table = '';

    /**
     * Fixture records to be inserted.
     *
     * @var array
     */
    public array $records = [];

    /**
     * The schema for this fixture.
     *
     * @var \Cake\Database\Schema\TableSchemaInterface&\Cake\Database\Schema\SqlGeneratorInterface
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $_schema;

    /**
     * Instantiate the fixture.
     *
     * @throws \Cake\Core\Exception\CakeException on invalid datasource usage.
     */
    public function __construct()
    {
        if (!empty($this->connection)) {
            $connection = $this->connection;
            if (!str_starts_with($connection, 'test')) {
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
        if (empty($this->table)) {
            $this->table = $this->_tableFromClass();
        }

        $this->_schemaFromReflection();
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
     * Build fixture schema directly from the datasource
     *
     * @return void
     * @throws \Cake\Core\Exception\CakeException when trying to reflect a table that does not exist
     */
    protected function _schemaFromReflection(): void
    {
        /** @var \Cake\Database\Connection $db */
        $db = ConnectionManager::get($this->connection());
        $schemaCollection = $db->getSchemaCollection();
        $tables = $schemaCollection->listTables();

        if (!in_array($this->table, $tables, true)) {
            throw new CakeException(
                sprintf(
                    'Cannot describe schema for table `%s` for fixture `%s`: the table does not exist.',
                    $this->table,
                    static::class
                )
            );
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->_schema = $schemaCollection->describe($this->table);
    }

    /**
     * @inheritDoc
     */
    public function insert(ConnectionInterface $connection): StatementInterface|bool
    {
        if (!empty($this->records)) {
            [$fields, $values, $types] = $this->_getRecords();
            /** @var \Cake\Database\Connection $connection */
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
        /** @var \Cake\Database\Connection $connection */
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
