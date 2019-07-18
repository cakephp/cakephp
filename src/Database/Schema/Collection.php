<?php
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
use Cake\Database\Exception;
use PDOException;

/**
 * Represents a database schema collection
 *
 * Used to access information about the tables,
 * and other data in a database.
 */
class Collection
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
     * @var \Cake\Database\Schema\BaseSchema
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
     * Get the list of tables available in the current connection.
     *
     * @return array The list of tables in the connected database/schema.
     */
    public function listTables()
    {
        list($sql, $params) = $this->_dialect->listTablesSql($this->_connection->config());
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
     * @param array $options The options to use, see above.
     * @return \Cake\Database\Schema\TableSchema Object with column metadata.
     * @throws \Cake\Database\Exception when table cannot be described.
     */
    public function describe($name, array $options = [])
    {
        $config = $this->_connection->config();
        if (strpos($name, '.')) {
            list($config['schema'], $name) = explode('.', $name);
        }
        $table = new TableSchema($name);

        $this->_reflect('Column', $name, $config, $table);
        if (count($table->columns()) === 0) {
            throw new Exception(sprintf('Cannot describe %s. It has 0 columns.', $name));
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
     * @param array $config The config data.
     * @param \Cake\Database\Schema\TableSchema $schema The table instance
     * @return void
     * @throws \Cake\Database\Exception on query failure.
     */
    protected function _reflect($stage, $name, $config, $schema)
    {
        $describeMethod = "describe{$stage}Sql";
        $convertMethod = "convert{$stage}Description";

        list($sql, $params) = $this->_dialect->{$describeMethod}($name, $config);
        if (empty($sql)) {
            return;
        }
        try {
            $statement = $this->_connection->execute($sql, $params);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500, $e);
        }
        foreach ($statement->fetchAll('assoc') as $row) {
            $this->_dialect->{$convertMethod}($schema, $row);
        }
        $statement->closeCursor();
    }
}
