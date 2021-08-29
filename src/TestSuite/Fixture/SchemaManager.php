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
use Cake\Datasource\ConnectionManager;

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
class SchemaManager
{
    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $io;

    /**
     * SchemaManager constructor.
     *
     * @param bool|null $verbose Output CLI messages.
     */
    final public function __construct(?bool $verbose = false)
    {
        $this->io = new ConsoleIo();
        $this->io->level($verbose ? ConsoleIo::NORMAL : ConsoleIo::QUIET);
    }

    /**
     * Import the schema from a file, or an array of files.
     *
     * This function will drop all tables in the database and then
     * load the provided schema file(s).
     *
     * @param string $connectionName Connection
     * @param array<string>|string $file File to dump
     * @param bool|null $verbose Set to true to display messages
     * @param bool|null $enableDropping Will drop all tables prior to creating the schema (true by default)
     * @return void
     * @throws \Exception if the truncation failed
     * @throws \RuntimeException if the file could not be processed
     */
    public static function create(
        string $connectionName,
        $file,
        ?bool $verbose = false,
        ?bool $enableDropping = true
    ): void {
        $files = (array)$file;

        // Don't create schema if we are in a phpunit separate process test method.
        if (empty($files) || isset($GLOBALS['__PHPUNIT_BOOTSTRAP'])) {
            return;
        }

        $stmts = [];
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new \RuntimeException('The file ' . $file . ' could not found.');
            }

            $stmts[] = $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException('The file ' . $file . ' could not read.');
            }
        }

        $migrator = new static($verbose);
        $schemaCleaner = new SchemaCleaner($migrator->io);

        if ($enableDropping) {
            $schemaCleaner->dropTables($connectionName);
        }

        foreach ($stmts as $stmt) {
            ConnectionManager::get($connectionName)->execute($stmt);
        }
        $migrator->io->success(
            'Dump of schema in file ' . $file . ' for connection ' . $connectionName . ' successful.'
        );

        $schemaCleaner->truncateTables($connectionName);
    }
}
