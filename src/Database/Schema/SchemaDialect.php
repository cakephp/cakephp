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
use Cake\Database\Type\ColumnSchemaAwareInterface;
use Cake\Database\TypeFactory;
use InvalidArgumentException;

/**
 * Base class for schema implementations.
 *
 * This class contains methods that are common across
 * the various SQL dialects.
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
     * @param list<string>|string $references The referenced columns of a foreign key constraint statement
     * @return string
     */
    protected function _convertConstraintColumns(array|string $references): string
    {
        if (is_string($references)) {
            return $this->_driver->quoteIdentifier($references);
        }

        return implode(', ', array_map(
            $this->_driver->quoteIdentifier(...),
            $references
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
        string $column
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
            $this->_driver->quoteIdentifier($schema->name())
        );

        return [$sql];
    }

    /**
     * Generate the SQL to list the tables.
     *
     * @param array<string, mixed> $config The connection configuration to use for
     *    getting tables from.
     * @return array An array of (sql, params) to execute.
     */
    abstract public function listTablesSql(array $config): array;

    /**
     * Generate the SQL to describe a table.
     *
     * @param string $tableName The table name to get information on.
     * @param array<string, mixed> $config The connection configuration.
     * @return array An array of (sql, params) to execute.
     */
    abstract public function describeColumnSql(string $tableName, array $config): array;

    /**
     * Generate the SQL to describe the indexes in a table.
     *
     * @param string $tableName The table name to get information on.
     * @param array<string, mixed> $config The connection configuration.
     * @return array An array of (sql, params) to execute.
     */
    abstract public function describeIndexSql(string $tableName, array $config): array;

    /**
     * Generate the SQL to describe the foreign keys in a table.
     *
     * @param string $tableName The table name to get information on.
     * @param array<string, mixed> $config The connection configuration.
     * @return array An array of (sql, params) to execute.
     */
    abstract public function describeForeignKeySql(string $tableName, array $config): array;

    /**
     * Generate the SQL to describe table options
     *
     * @param string $tableName Table name.
     * @param array<string, mixed> $config The connection configuration.
     * @return array SQL statements to get options for a table.
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
     */
    abstract public function convertColumnDescription(TableSchema $schema, array $row): void;

    /**
     * Convert an index description results into abstract schema indexes or constraints.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table object to append
     *    an index or constraint to.
     * @param array $row The row data from `describeIndexSql`.
     * @return void
     */
    abstract public function convertIndexDescription(TableSchema $schema, array $row): void;

    /**
     * Convert a foreign key description into constraints on the Table object.
     *
     * @param \Cake\Database\Schema\TableSchema $schema The table object to append
     *    a constraint to.
     * @param array $row The row data from `describeForeignKeySql`.
     * @return void
     */
    abstract public function convertForeignKeyDescription(TableSchema $schema, array $row): void;

    /**
     * Convert options data into table options.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Table instance.
     * @param array $row The row of data.
     * @return void
     */
    public function convertOptionsDescription(TableSchema $schema, array $row): void
    {
    }

    /**
     * Generate the SQL to create a table.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Table instance.
     * @param list<string> $columns The columns to go inside the table.
     * @param list<string> $constraints The constraints for the table.
     * @param list<string> $indexes The indexes for the table.
     * @return list<string> SQL statements to create a table.
     */
    abstract public function createTableSql(
        TableSchema $schema,
        array $columns,
        array $constraints,
        array $indexes
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
}
