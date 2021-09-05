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

use Cake\Console\ConsoleIo;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\ConnectionManager;
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
    use InstanceConfigTrait;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $io;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'dropTables' => true,
        'outputLevel' => ConsoleIo::NORMAL,
    ];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $config Config settings
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        $this->io = new ConsoleIo();
        $this->io->level($this->getConfig('outputLevel'));
    }

    /**
     * Import schema from a file, or an array of files.
     *
     * @param array<string>|string $files Schema files to load
     * @param string $connectionName Connection name
     * @param bool $dropTables Drop all tables prior to loading schema files
     * @param bool $truncateTables Truncate all tables after loading schema files
     * @return void
     */
    public function loadFiles(
        $files,
        string $connectionName,
        bool $dropTables = true,
        bool $truncateTables = true
    ): void {
        $files = (array)$files;

        // Don't create schema if we are in a phpunit separate process test method.
        if (isset($GLOBALS['__PHPUNIT_BOOTSTRAP'])) {
            return;
        }

        $cleaner = new SchemaCleaner($this->io);
        if ($dropTables) {
            $cleaner->dropTables($connectionName);
        }

        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get($connectionName);
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new InvalidArgumentException("Unable to load schema file `$file`.");
            }
            $sql = file_get_contents($file);

            // Use the underlying PDO connection so we can avoid prepared statements
            // which don't support multiple queries in postgres.
            $driver = $connection->getDriver();
            $driver->getConnection()->exec($sql);
        }

        if ($truncateTables) {
            $cleaner->truncateTables($connectionName);
        }
    }
}
