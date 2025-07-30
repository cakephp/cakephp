<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

use Cake\Database\Driver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Exception\QueryException;
use Cake\Database\Type\ColumnSchemaAwareInterface;
use Cake\Database\TypeFactory;
use InvalidArgumentException;
use PDOException;
use function Cake\Core\deprecationWarning;

/**
 * Base class for schema implementations.
 *
 * This class contains methods that are common across
 * the various SQL dialects.
 *
 * Provides methods for performing schema reflection. Results
 * will be in the form of structured arrays. The structure
 * of each result will be documented in this class. Subclasses
 * are free to include *additional* data that is not documented.
 *
 * @method array<mixed> listTablesWithoutViewsSql(array $config) Generate the SQL to list the tables, excluding all views.
 */
abstract class SchemaDialect
{
    /**
     * The driver instance being used.
     *
     * @var \Cake\Database\Driver
     */
    protected Driver $_driver;

    /**
     * Constructor
     *
     * This constructor will connect the driver so that methods like columnSql() and others
     * will fail when the driver has not been connected.
     *
     * @param \Cake\Database\Driver $driver The driver to use.
     */
    public function __construct(Driver $driver)
    {
        $driver->connect();
        $this->_driver = $driver;
    }

    /**
     * Generate an ON clause for a foreign key.
     *
     * @param string $on The on clause
     * @return string
     */
    protected function _foreignOnClause(string $on): string
    {
        if ($on === TableSchema::ACTION_SET_NULL) {
            return 'SET NULL';
        }
        if ($on === TableSchema::ACTION_SET_DEFAULT) {
            return 'SET DEFAULT';
        }
        if ($on === TableSchema::ACTION_CASCADE) {
            return 'CASCADE';
        }
        if ($on === TableSchema::ACTION_RESTRICT) {
            return 'RESTRICT';
        }
        if ($on === TableSchema::ACTION_NO_ACTION) {
            return 'NO ACTION';
        }

        throw new InvalidArgumentException('Invalid value for "on": ' . $on);
    }

    /**
     * Convert string on clauses to the abstract ones.
     *
     * @param string $clause The on clause to convert.
     * @return string
     */
    protected function _convertOnClause(string $clause): string
    {
        if ($clause === 'CASCADE' || $clause === 'RESTRICT') {
            return strtolower($clause);
        }
        if ($clause === 'NO ACTION') {
            return TableSchema::ACTION_NO_ACTION;
        }

        return TableSchema::ACTION_SET_NULL;
    }

    /**
     * Convert foreign key constraints references to a valid
     * stringified list
     *
     * @param array<string>|string $references The referenced columns of a foreign key constraint statement
     * @return string
     */
    protected function _convertConstraintColumns(array|string $references): string
    {
        if (is_string($references)) {
            return $this->_driver->quoteIdentifier($references);
        }

        return implode(', ', array_map(
            $this->_driver->quoteIdentifier(...),
            $references,
        ));
    }

    /**
     * Tries to use a matching database type to generate the SQL
     * fragment for a single column in a table.
     *
     * @param string $columnType The column type.
     * @param \Cake\Database\Schema\TableSchemaInterface $schema The table schema instance the column is in.
     * @param string $column The name of the column.
     * @return string|null An SQL fragment, or `null` in case no corresponding type was found or the type didn't provide
     *  custom column SQL.
     */
    protected function _getTypeSpecificColumnSql(
        string $columnType,
        TableSchemaInterface $schema,
        string $column,
    ): ?string {
        if (!TypeFactory::getMap($columnType)) {
            return null;
        }

        $type = TypeFactory::build($columnType);
        if (!($type instanceof ColumnSchemaAwareInterface)) {
            return null;
        }

        return $type->getColumnSql($schema, $column, $this->_driver);
    }

    /**
     * Tries to use a matching database type to convert a SQL column
     * definition to an abstract type definition.
     *
     * @param string $columnType The column type.
     * @param array $definition The column definition.
     * @return array|null Array of column information, or `null` in case no corresponding type was found or the type
     *  didn't provide custom column information.
     */
    protected function _applyTypeSpecificColumnConversion(string $columnType, array $definition): ?array
    {
        if (!TypeFactory::getMap($columnType)) {
            return null;
        }

        $type = TypeFactory::build($columnType);
        if (!($type instanceof ColumnSchemaAwareInterface)) {
            return null;
        }

        return $type->convertColumnDefinition($definition, $this->_driver);
    }

    /**
     * Generate the SQL to drop a table.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Schema instance
     * @return array SQL statements to drop a table.
     */
    public function dropTableSql(TableSchema $schema): array
    {
        $sql = sprintf(
            'DROP TABLE %s',
            $this->_driver->quoteIdentifier($schema->name()),
        );

        return [$sql];
    }

    /**
     * Generate the SQL to list the tables.
     *
     * @param array<string, mixed> $config The connection configuration to use for
     *    getting tables from.
     * @return array An array of (sql, params) to execute.
     * @deprecated 5.2.0 Use `listTables()` instead.
     */
    abstract public function listTablesSql(array $config): array;

    /**
     * Generate the SQL to describe a table.
     *
     * @param string $tableName The table name to get information on.
     * @param array<string, mixed> $config The connection configuration.
     * @return array An array of (sql, params) to execute.
     * @deprecated 5.2.0 Use `describeColumns()` instead.
     */
    abstract public function describeColumnSql(string $tableName, array $config): array;

    /**
     * Generate the SQL to describe the indexes in a table.
     *
     * @param string $tableName The table name to get information on.
     * @param array<string, mixed> $config The connection configuration.
     * @return array An array of (sql, params) to execute.
     * @deprecated 5.2.0 Use `describeIndexes()` instead.
     */
    abstract public function describeIndexSql(string $tableName, array $config): array;

    /**
     * Generate the SQL to describe the foreign keys in a table.
     *
     * @param string $tableName The table name to get information on.
     * @param array<string, mixed> $config The connection configuration.
     * @return array An array of (sql, params) to execute.
     * @deprecated 5.2.0 Use `describeForeignKeys()` instead.
     */
    abstract public function describeForeignKeySql(string $tableName, array $config): array;

    /**
     * Generate the SQL to describe table options
     *
     * @param string $tableName Table name.
     * @param array<string, mixed> $config The connection configuration.
     * @return array SQL statements to get options for a table.
     * @deprecated 5.2.0 Use `describeOptions()` instead.
     */
    public function describeOptionsSql(string $tableName, array $config): array
    {
        return ['', ''];
    }

    /**
     * Convert field description results into abstract schema fields.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table object to append fields to.
     * @param array $row The row data from `describeColumnSql`.
     * @return void
     * @deprecated 5.2.0 Use `describeColumns()` instead.
     */
    abstract public function convertColumnDescription(TableSchema $schema, array $row): void;

    /**
     * Convert an index description results into abstract schema indexes or constraints.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table object to append
     *    an index or constraint to.
     * @param array $row The row data from `describeIndexSql`.
     * @return void
     * @deprecated 5.2.0 Use `describeIndexes()` instead.
     */
    abstract public function convertIndexDescription(TableSchema $schema, array $row): void;

    /**
     * Convert a foreign key description into constraints on the Table object.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table object to append
     *    a constraint to.
     * @param array $row The row data from `describeForeignKeySql`.
     * @return void
     * @deprecated 5.2.0 Use `describeForeignKeys()` instead.
     */
    abstract public function convertForeignKeyDescription(TableSchema $schema, array $row): void;

    /**
     * Convert options data into table options.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Table instance.
     * @param array $row The row of data.
     * @return void
     * @deprecated 5.2.0 Use `describeOptions()` instead.
     */
    public function convertOptionsDescription(TableSchema $schema, array $row): void
    {
    }

    /**
     * Generate the SQL to create a table.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Table instance.
     * @param array<string> $columns The columns to go inside the table.
     * @param array<string> $constraints The constraints for the table.
     * @param array<string> $indexes The indexes for the table.
     * @return array<string> SQL statements to create a table.
     */
    abstract public function createTableSql(
        TableSchema $schema,
        array $columns,
        array $constraints,
        array $indexes,
    ): array;

    /**
     * Generate the SQL fragment for a single column in a table.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the column is in.
     * @param string $name The name of the column.
     * @return string SQL fragment.
     */
    abstract public function columnSql(TableSchema $schema, string $name): string;

    /**
     * Generate the SQL queries needed to add foreign key constraints to the table
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the foreign key constraints are.
     * @return array SQL fragment.
     */
    abstract public function addConstraintSql(TableSchema $schema): array;

    /**
     * Generate the SQL queries needed to drop foreign key constraints from the table
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the foreign key constraints are.
     * @return array SQL fragment.
     */
    abstract public function dropConstraintSql(TableSchema $schema): array;

    /**
     * Generate the SQL fragments for defining table constraints.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table instance the column is in.
     * @param string $name The name of the column.
     * @return string SQL fragment.
     */
    abstract public function constraintSql(TableSchema $schema, string $name): string;

    /**
     * Generate the SQL fragment for a single index in a table.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table object the column is in.
     * @param string $name The name of the column.
     * @return string SQL fragment.
     */
    abstract public function indexSql(TableSchema $schema, string $name): string;

    /**
     * Generate the SQL to truncate a table.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Table instance.
     * @return array SQL statements to truncate a table.
     */
    abstract public function truncateTableSql(TableSchema $schema): array;

    /**
     * Create a SQL snippet for a column based on the array shape
     * that `describeColumns()` creates.
     *
     * @param array $column The column metadata
     * @return string Generated SQL fragment for a column
     */
    public function columnDefinitionSql(array $column): string
    {
        deprecationWarning(
            '5.2.0',
            'SchemaDialect subclasses need to implement `columnDefinitionSql` before 6.0.0',
        );
        $table = new TableSchema('placeholder');
        $table->addColumn($column['name'], $column);

        return $this->columnSql($table, $column['name']);
    }

    /**
     * Get the list of tables, excluding any views, available in the current connection.
     *
     * @return array<string> The list of tables in the connected database/schema.
     */
    public function listTablesWithoutViews(): array
    {
        [$sql, $params] = $this->listTablesWithoutViewsSql($this->_driver->config());
        $result = [];
        $statement = $this->_driver->execute($sql, $params);
        while ($row = $statement->fetch()) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * Get the list of tables and views available in the current connection.
     *
     * @return array<string> The list of tables and views in the connected database/schema.
     */
    public function listTables(): array
    {
        [$sql, $params] = $this->listTablesSql($this->_driver->config());
        $result = [];
        $statement = $this->_driver->execute($sql, $params);
        while ($row = $statement->fetch()) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * Get the column metadata for a table.
     *
     * The name can include a database schema name in the form 'schema.table'.
     *
     * @param string $name The name of the table to describe.
     * @return \Cake\Database\Schema\TableSchemaInterface Object with column metadata.
     * @throws \Cake\Database\Exception\DatabaseException when table cannot be described.
     */
    public function describe(string $name): TableSchemaInterface
    {
        $tableName = $name;
        if (str_contains($name, '.')) {
            $tableName = explode('.', $name)[1];
        }
        $table = $this->_driver->newTableSchema($tableName);
        foreach ($this->describeColumns($name) as $column) {
            $table->addColumn($column['name'], $column);
        }
        foreach ($this->describeIndexes($name) as $index) {
            if (in_array($index['type'], [TableSchema::CONSTRAINT_UNIQUE, TableSchema::CONSTRAINT_PRIMARY])) {
                $table->addConstraint($index['name'], $index);
            } else {
                $table->addIndex($index['name'], $index);
            }
        }
        foreach ($this->describeForeignKeys($name) as $key) {
            $table->addConstraint($key['name'], $key);
        }
        $options = $this->describeOptions($name);
        if ($options) {
            $table->setOptions($options);
        }
        if ($table->columns() === []) {
            throw new DatabaseException(sprintf('Cannot describe %s. It has 0 columns.', $name));
        }

        return $table;
    }

    /**
     * Get a list of column metadata as a array
     *
     * Each item in the array will contain the following:
     *
     * - name : the name of the column.
     * - type : the abstract type of the column.
     * - length : the length of the column.
     * - default : the default value of the column or null.
     * - null : boolean indicating whether the column can be null.
     * - comment : the column comment or null.
     *
     * Additionaly the `autoIncrement` key will be set for columns that are a primary key.
     *
     * @param string $tableName The name of the table to describe columns on.
     * @return array
     */
    public function describeColumns(string $tableName): array
    {
        deprecationWarning(
            '5.2.0',
            'SchemaDialect subclasses need to implement `describeColumns` before 6.0.0',
        );
        $config = $this->_driver->config();
        if (str_contains($tableName, '.')) {
            [$config['schema'], $tableName] = explode('.', $tableName);
        }
        /** @var \Cake\Database\Schema\TableSchema $table */
        $table = $this->_driver->newTableSchema($tableName);

        [$sql, $params] = $this->describeColumnSql($tableName, $config);
        $statement = $this->_driver->execute($sql, $params);
        foreach ($statement->fetchAll('assoc') as $row) {
            $this->convertColumnDescription($table, $row);
        }
        $columns = [];
        foreach ($table->columns() as $columnName) {
            $column = $table->getColumn($columnName);
            $column['name'] = $columnName;
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Get a list of constraint metadata as a array
     *
     * Each item in the array will contain the following:
     *
     * - name : The name of the constraint
     * - type : the type of the constraint. Generally `foreign`.
     * - columns : the columns in the constraint on the.
     * - references : A list of the table + all columns in the referenced table
     * - update : The update action or null
     * - delete : The delete action or null
     *
     * @param string $tableName The name of the table to describe foreign keys on.
     * @return array
     */
    public function describeForeignKeys(string $tableName): array
    {
        deprecationWarning(
            '5.2.0',
            'SchemaDialect subclasses need to implement `describeForeignKeys` before 6.0.0',
        );
        $config = $this->_driver->config();
        if (str_contains($tableName, '.')) {
            [$config['schema'], $tableName] = explode('.', $tableName);
        }
        /** @var \Cake\Database\Schema\TableSchema $table */
        $table = $this->_driver->newTableSchema($tableName);
        // Add the columns because TableSchema needs them.
        foreach ($this->describeColumns($tableName) as $column) {
            $table->addColumn($column['name'], $column);
        }

        [$sql, $params] = $this->describeForeignKeySql($tableName, $config);
        $statement = $this->_driver->execute($sql, $params);
        foreach ($statement->fetchAll('assoc') as $row) {
            $this->convertForeignKeyDescription($table, $row);
        }
        $keys = [];
        foreach ($table->constraints() as $name) {
            $key = $table->getConstraint($name);
            $key['name'] = $name;
            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * Get a list of index metadata as a array
     *
     * Each item in the array will contain the following:
     *
     * - name : the name of the index.
     * - type : the type of the index. One of `unique`, `index`, `primary`.
     * - columns : the columns in the index.
     * - length : the length of the index if applicable.
     *
     * @param string $tableName The name of the table to describe indexes on.
     * @return array
     */
    public function describeIndexes(string $tableName): array
    {
        deprecationWarning(
            '5.2.0',
            'SchemaDialect subclasses need to implement `describeIndexes` before 6.0.0',
        );
        $config = $this->_driver->config();
        if (str_contains($tableName, '.')) {
            [$config['schema'], $tableName] = explode('.', $tableName);
        }
        /** @var \Cake\Database\Schema\TableSchema $table */
        $table = $this->_driver->newTableSchema($tableName);
        // Add the columns because TableSchema needs them.
        foreach ($this->describeColumns($tableName) as $column) {
            $table->addColumn($column['name'], $column);
        }

        [$sql, $params] = $this->describeIndexSql($tableName, $config);
        $statement = $this->_driver->execute($sql, $params);
        foreach ($statement->fetchAll('assoc') as $row) {
            $this->convertIndexDescription($table, $row);
        }
        $indexes = [];
        foreach ($table->indexes() as $name) {
            $index = $table->getIndex($name);
            $index['name'] = $name;
            $indexes[] = $index;
        }

        return $indexes;
    }

    /**
     * Get platform specific options
     *
     * No keys are guaranteed to be present as they are database driver dependent.
     *
     * @param string $tableName The name of the table to describe options on.
     * @return array
     */
    public function describeOptions(string $tableName): array
    {
        deprecationWarning(
            '5.2.0',
            'SchemaDialect subclasses need to implement `describeOptions` before 6.0.0',
        );
        $config = $this->_driver->config();
        if (str_contains($tableName, '.')) {
            [$config['schema'], $tableName] = explode('.', $tableName);
        }
        /** @var \Cake\Database\Schema\TableSchema $table */
        $table = $this->_driver->newTableSchema($tableName);

        [$sql, $params] = $this->describeOptionsSql($tableName, $config);
        if ($sql) {
            $statement = $this->_driver->execute($sql, $params);
            foreach ($statement->fetchAll('assoc') as $row) {
                $this->convertOptionsDescription($table, $row);
            }
        }

        return $table->getOptions();
    }

    /**
     * Check if a table has a column with a given name.
     *
     * @param string $tableName The name of the table
     * @param string $columnName The name of the column
     * @return bool
     */
    public function hasColumn(string $tableName, string $columnName): bool
    {
        try {
            $columns = $this->describeColumns($tableName);
        } catch (PDOException | DatabaseException) {
            return false;
        }
        foreach ($columns as $column) {
            if ($column['name'] === $columnName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a table exists
     *
     * @param string $tableName The name of the table
     * @return bool
     */
    public function hasTable(string $tableName): bool
    {
        $tables = $this->listTables();

        return in_array($tableName, $tables, true);
    }

    /**
     * Check if a table has an index with a given name.
     *
     * @param string $tableName The name of the table
     * @param array<string> $columns The columns in the index. Specific
     *   ordering matters.
     * @param string $name The name of the index to match on. Can be used alone,
     *   or with $columns to match indexes more precisely.
     * @return bool
     */
    public function hasIndex(string $tableName, array $columns = [], ?string $name = null): bool
    {
        try {
            $indexes = $this->describeIndexes($tableName);
        } catch (QueryException) {
            return false;
        }
        $found = null;
        foreach ($indexes as $index) {
            if ($columns && $index['columns'] === $columns) {
                $found = $index;
                break;
            }
            if ($columns === [] && $name !== null && $index['name'] === $name) {
                $found = $index;
                break;
            }
        }
        // Both columns and name provided, both must match;
        if ($found !== null && $name !== null && $found['name'] !== $name) {
            return false;
        }

        return $found !== null;
    }

    /**
     * Check if a table has a foreign key with a given name.
     *
     * @param string $tableName The name of the table
     * @param array<string> $columns The columns in the foriegn key. Specific
     *   ordering matters.
     * @param string $name The name of the foreign key to match on. Can be used alone,
     *   or with $columns to match keys more precisely.
     * @return bool
     */
    public function hasForeignKey(string $tableName, array $columns = [], ?string $name = null): bool
    {
        try {
            $keys = $this->describeForeignKeys($tableName);
        } catch (QueryException) {
            return false;
        }
        $found = null;
        foreach ($keys as $key) {
            if ($columns && $key['columns'] === $columns) {
                $found = $key;
                break;
            }
            if (!$columns && $name !== null && $key['name'] === $name) {
                $found = $key;
                break;
            }
        }
        // Both columns and name provided, both must match;
        if ($found !== null && $name !== null && $found['name'] !== $name) {
            return false;
        }

        return $found !== null;
    }
}
