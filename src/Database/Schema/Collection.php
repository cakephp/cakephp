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

use Cake\Database\Connection;
use Cake\Database\Exception\DatabaseException;
use PDOException;

/**
 * Represents a database schema collection
 *
 * Used to access information about the tables,
 * and other data in a database.
 */
class Collection implements CollectionInterface
{
    /**
     * Connection object
     *
     * @var \Cake\Database\Connection
     */
    protected $_connection;

    /**
     * Schema dialect instance.
     *
     * @var \Cake\Database\Schema\SchemaDialect
     */
    protected $_dialect;

    /**
     * Constructor.
     *
     * @param \Cake\Database\Connection $connection The connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
        $this->_dialect = $connection->getDriver()->schemaDialect();
    }

    /**
     * Get the list of tables, excluding any views, available in the current connection.
     *
     * @return array<string> The list of tables in the connected database/schema.
     */
    public function listTablesWithoutViews(): array
    {
        [$sql, $params] = $this->_dialect->listTablesWithoutViewsSql($this->_connection->getDriver()->config());
        $result = [];
        $statement = $this->_connection->execute($sql, $params);
        while ($row = $statement->fetch()) {
            $result[] = $row[0];
        }
        $statement->closeCursor();

        return $result;
    }

    /**
     * Get the list of tables and views available in the current connection.
     *
     * @return array<string> The list of tables and views in the connected database/schema.
     */
    public function listTables(): array
    {
        [$sql, $params] = $this->_dialect->listTablesSql($this->_connection->getDriver()->config());
        $result = [];
        $statement = $this->_connection->execute($sql, $params);
        while ($row = $statement->fetch()) {
            $result[] = $row[0];
        }
        $statement->closeCursor();

        return $result;
    }

    /**
     * Get the column metadata for a table.
     *
     * The name can include a database schema name in the form 'schema.table'.
     *
     * Caching will be applied if `cacheMetadata` key is present in the Connection
     * configuration options. Defaults to _cake_model_ when true.
     *
     * ### Options
     *
     * - `forceRefresh` - Set to true to force rebuilding the cached metadata.
     *   Defaults to false.
     *
     * @param string $name The name of the table to describe.
     * @param array<string, mixed> $options The options to use, see above.
     * @return \Cake\Database\Schema\TableSchema Object with column metadata.
     * @throws \Cake\Database\Exception\DatabaseException when table cannot be described.
     */
    public function describe(string $name, array $options = []): TableSchemaInterface
    {
        $config = $this->_connection->getDriver()->config();
        if (strpos($name, '.')) {
            [$config['schema'], $name] = explode('.', $name);
        }
        $table = $this->_connection->getDriver()->newTableSchema($name);

        $this->_reflect('Column', $name, $config, $table);
        if (count($table->columns()) === 0) {
            throw new DatabaseException(sprintf('Cannot describe %s. It has 0 columns.', $name));
        }

        $this->_reflect('Index', $name, $config, $table);
        $this->_reflect('ForeignKey', $name, $config, $table);
        $this->_reflect('Options', $name, $config, $table);

        return $table;
    }

    /**
     * Helper method for running each step of the reflection process.
     *
     * @param string $stage The stage name.
     * @param string $name The table name.
     * @param array<string, mixed> $config The config data.
     * @param \Cake\Database\Schema\TableSchema $schema The table schema instance.
     * @return void
     * @throws \Cake\Database\Exception\DatabaseException on query failure.
     * @uses \Cake\Database\Schema\SchemaDialect::describeColumnSql
     * @uses \Cake\Database\Schema\SchemaDialect::describeIndexSql
     * @uses \Cake\Database\Schema\SchemaDialect::describeForeignKeySql
     * @uses \Cake\Database\Schema\SchemaDialect::describeOptionsSql
     * @uses \Cake\Database\Schema\SchemaDialect::convertColumnDescription
     * @uses \Cake\Database\Schema\SchemaDialect::convertIndexDescription
     * @uses \Cake\Database\Schema\SchemaDialect::convertForeignKeyDescription
     * @uses \Cake\Database\Schema\SchemaDialect::convertOptionsDescription
     */
    protected function _reflect(string $stage, string $name, array $config, TableSchema $schema): void
    {
        $describeMethod = "describe{$stage}Sql";
        $convertMethod = "convert{$stage}Description";

        [$sql, $params] = $this->_dialect->{$describeMethod}($name, $config);
        if (empty($sql)) {
            return;
        }
        try {
            $statement = $this->_connection->execute($sql, $params);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, $e);
        }
        /** @psalm-suppress PossiblyFalseIterator */
        foreach ($statement->fetchAll('assoc') as $row) {
            $this->_dialect->{$convertMethod}($schema, $row);
        }
        $statement->closeCursor();
    }
}
