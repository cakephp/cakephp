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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Schema;

use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use RuntimeException;

/**
 * Create database schema from the provided metadata file.
 *
 * @internal
 */
class SchemaGenerator
{
    /**
     * The metadata file to load.
     *
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $connection;

    /**
     * Constructor
     *
     * @param string $file The file to load
     * @param string $connection The connection to use.
     * @return void
     */
    public function __construct(string $file, string $connection)
    {
        $this->file = $file;
        $this->connection = $connection;
    }

    /**
     * Reload the schema.
     *
     * Will drop all tables and re-create them from the metadata file.
     *
     * @param ?string[] $tables The list of tables to reset. Primarily for testing.
     * @return void
     */
    public function reload(?array $tables = null): void
    {
        if (!file_exists($this->file)) {
            throw new RuntimeException("Cannot load `{$this->file}`");
        }

        $cleaner = new SchemaCleaner();
        $cleaner->dropTables($this->connection, $tables);

        $config = include $this->file;
        $connection = ConnectionManager::get($this->connection);
        if (!($connection instanceof Connection)) {
            throw new RuntimeException("The `{$this->connection}` connection is not a Cake\Database\Connection");
        }

        if (!count($config)) {
            return;
        }

        $connection->disableConstraints(function ($connection) use ($config) {
            foreach ($config as $metadata) {
                $table = new TableSchema($metadata['table'], $metadata['columns']);
                if (isset($metadata['indexes'])) {
                    foreach ($metadata['indexes'] as $key => $index) {
                        $table->addIndex($key, $index);
                    }
                }
                if (isset($metadata['constraints'])) {
                    foreach ($metadata['constraints'] as $key => $index) {
                        $table->addConstraint($key, $index);
                    }
                }
                // Generate SQL for each table.
                $stmts = $table->createSql($connection);
                foreach ($stmts as $stmt) {
                    $connection->execute($stmt);
                }
            }
        });
    }
}
