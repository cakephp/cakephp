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
namespace Cake\TestSuite\Fixture;

use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\ConnectionHelper;
use InvalidArgumentException;

/**
 * Create test database schema from one or more SQL dump files.
 *
 * This class can be useful to create test database schema when
 * your schema is managed by tools external to your CakePHP
 * application.
 *
 * It is not well suited for applications/plugins that need to
 * support multiple database platforms. You should use migrations
 * for that instead.
 */
class SchemaLoader
{
    /**
     * Load and apply schema sql file, or an array of files.
     *
     * @param array<string>|string $paths Schema files to load
     * @param string $connectionName Connection name
     * @param bool $dropTables Drop all tables prior to loading schema files
     * @param bool $truncateTables Truncate all tables after loading schema files
     * @return void
     */
    public function loadSqlFiles(
        array|string $paths,
        string $connectionName = 'test',
        bool $dropTables = true,
        bool $truncateTables = false,
    ): void {
        $files = (array)$paths;

        // Don't create schema if we are in a phpunit separate process test method.
        if (isset($GLOBALS['__PHPUNIT_BOOTSTRAP'])) {
            return;
        }

        if ($dropTables) {
            ConnectionHelper::dropTables($connectionName);
        }

        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get($connectionName);
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new InvalidArgumentException(sprintf('Unable to load SQL file `%s`.', $file));
            }
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new CakeException(sprintf('Cannot read file content of `%s`', $file));
            }

            // Use the underlying PDO connection so we can avoid prepared statements
            // which don't support multiple queries in postgres.
            $driver = $connection->getWriteDriver();
            $driver->exec($sql);
        }

        if ($truncateTables) {
            ConnectionHelper::truncateTables($connectionName);
        }
    }

    /**
     * Load and apply CakePHP schema file.
     *
     * This method will process the array returned by `$file` and treat
     * the contents as a list of table schema.
     *
     * An example table is:
     *
     * ```
     * return [
     *   'articles' => [
     *      'columns' => [
     *          'id' => [
     *              'type' => 'integer',
     *          ],
     *          'author_id' => [
     *              'type' => 'integer',
     *              'null' => true,
     *          ],
     *          'title' => [
     *              'type' => 'string',
     *              'null' => true,
     *          ],
     *          'body' => 'text',
     *          'published' => [
     *              'type' => 'string',
     *              'length' => 1,
     *              'default' => 'N',
     *          ],
     *      ],
     *      'constraints' => [
     *          'primary' => [
     *              'type' => 'primary',
     *              'columns' => [
     *                  'id',
     *              ],
     *          ],
     *      ],
     *   ],
     * ];
     * ```
     *
     * This schema format can be useful for plugins that want to include
     * tables to test against but don't need to include production
     * ready schema via migrations. Applications should favor using migrations
     * or SQL dump files over this format for ease of maintenance.
     *
     * A more complete example can be found in `tests/schema.php`.
     *
     * @param string $file Schema file
     * @param string $connectionName Connection name
     * @throws \InvalidArgumentException For missing table name(s).
     * @return void
     */
    public function loadInternalFile(string $file, string $connectionName = 'test'): void
    {
        // Don't reload schema when we are in a separate process state.
        if (isset($GLOBALS['__PHPUNIT_BOOTSTRAP'])) {
            return;
        }

        ConnectionHelper::dropTables($connectionName);

        $tables = include $file;

        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get($connectionName);
        $connection->disableConstraints(function (Connection $connection) use ($tables): void {
            foreach ($tables as $tableName => $table) {
                $name = $table['table'] ?? $tableName;
                if (!is_string($name)) {
                    throw new InvalidArgumentException(
                        sprintf('`%s` is not a valid table name. Either use a string key for the table definition'
                            . "(`'articles' => [...]`) or define the `table` key in the table definition.", $name),
                    );
                }
                $schema = new TableSchema($name, $table['columns']);
                if (isset($table['indexes'])) {
                    foreach ($table['indexes'] as $key => $index) {
                        $schema->addIndex($key, $index);
                    }
                }
                if (isset($table['constraints'])) {
                    foreach ($table['constraints'] as $key => $index) {
                        $schema->addConstraint($key, $index);
                    }
                }

                // Generate SQL for each table.
                foreach ($schema->createSql($connection) as $sql) {
                    $connection->execute($sql);
                }
            }
        });
    }
}
