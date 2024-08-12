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
use Cake\Database\Connection;
use Cake\Database\Schema\SqlGeneratorInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;
use function Cake\Core\namespaceSplit;

/**
 * Cake TestFixture is responsible for building and destroying tables to be used
 * during testing.
 */
class TestFixture implements FixtureInterface
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
    protected TableSchemaInterface&SqlGeneratorInterface $_schema;

    /**
     * Instantiate the fixture.
     *
     * @throws \Cake\Core\Exception\CakeException on invalid datasource usage.
     */
    public function __construct()
    {
        if ($this->connection) {
            $connection = $this->connection;
            if (!str_starts_with($connection, 'test')) {
                $message = sprintf(
                    'Invalid datasource name `%s` for `%s` fixture. Fixture datasource names must begin with `test`.',
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
        if (!$this->table) {
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
        $db = ConnectionManager::get($this->connection());
        assert($db instanceof Connection);
        try {
            $name = Inflector::camelize($this->table);
            $ormTable = $this->fetchTable($name, ['connection' => $db]);

            // Remove the fetched table from the locator to avoid conflicts
            // with test cases that need to (re)configure the alias.
            $this->getTableLocator()->remove($name);

            $schema = $ormTable->getSchema();
            assert($schema instanceof TableSchema);
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
    public function insert(ConnectionInterface $connection): bool
    {
        assert($connection instanceof Connection);
        if ($this->records) {
            [$fields, $values, $types] = $this->_getRecords();
            $query = $connection->insertQuery()
                ->insert($fields, $types)
                ->into($this->sourceName());

            foreach ($values as $row) {
                $query->values($row);
            }
            $query->execute();
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
        $fields = [];
        $values = [];
        $types = [];
        $columns = $this->_schema->columns();
        foreach ($this->records as $record) {
            $fields = array_merge($fields, array_intersect(array_keys($record), $columns));
        }
        /** @var list<string> $fields */
        $fields = array_values(array_unique($fields));
        foreach ($fields as $field) {
            $column = $this->_schema->getColumn($field);
            assert($column !== null);
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
        assert($connection instanceof Connection);
        $sql = $this->_schema->truncateSql($connection);
        foreach ($sql as $stmt) {
            $connection->execute($stmt);
        }

        return true;
    }

    /**
     * Returns the table schema for this fixture.
     *
     * @return \Cake\Database\Schema\TableSchemaInterface&\Cake\Database\Schema\SqlGeneratorInterface
     */
    public function getTableSchema(): TableSchemaInterface&SqlGeneratorInterface
    {
        return $this->_schema;
    }
}
