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

/**
 * Represents a database schema collection
 *
 * Gives a simple high-level schema reflection API that can be
 * decorated or extended with additional behavior like caching.
 *
 * @see \Cake\Database\Schema\SchemaDialect For lower level schema reflection API
 */
class Collection implements CollectionInterface
{
    /**
     * Connection object
     *
     * @var \Cake\Database\Connection
     */
    protected Connection $_connection;

    /**
     * Schema dialect instance.
     *
     * @var \Cake\Database\Schema\SchemaDialect|null
     */
    protected ?SchemaDialect $_dialect = null;

    /**
     * Constructor.
     *
     * @param \Cake\Database\Connection $connection The connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Get the list of tables, excluding any views, available in the current connection.
     *
     * @return array<string> The list of tables in the connected database/schema.
     */
    public function listTablesWithoutViews(): array
    {
        return $this->getDialect()->listTablesWithoutViews();
    }

    /**
     * Get the list of tables and views available in the current connection.
     *
     * @return array<string> The list of tables and views in the connected database/schema.
     */
    public function listTables(): array
    {
        return $this->getDialect()->listTables();
    }

    /**
     * Get the column metadata for a table.
     *
     * The name can include a database schema name in the form 'schema.table'.
     *
     * @param string $name The name of the table to describe.
     * @param array<string, mixed> $options Unused
     * @return \Cake\Database\Schema\TableSchemaInterface Object with column metadata.
     * @throws \Cake\Database\Exception\DatabaseException when table cannot be described.
     */
    public function describe(string $name, array $options = []): TableSchemaInterface
    {
        return $this->getDialect()->describe($name);
    }

    /**
     * Setups the schema dialect to be used for this collection.
     *
     * @return \Cake\Database\Schema\SchemaDialect
     */
    protected function getDialect(): SchemaDialect
    {
        return $this->_dialect ??= $this->_connection->getWriteDriver()->schemaDialect();
    }
}
