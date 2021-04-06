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

use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;

/**
 * This class will create the schema of the test DB
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
     * @param string $connectionName Connection
     * @param string|string[] $file File to dump
     * @param bool|null $verbose Set to true to display messages
     * @return void
     * @throws \Exception if the truncation failed
     * @throws \RuntimeException if the file could not be processed
     */
    public static function create(string $connectionName, $file, ?bool $verbose = false)
    {
        $files = (array)$file;

        $migrator = new static($verbose);

        SchemaCleaner::drop($connectionName, $migrator->io);

        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new \RuntimeException(__('cake', 'The file {0} was not found.', $file));
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException(__('cake', 'The file {0} could not be read.', $file));
            }

            ConnectionManager::get($connectionName)->execute($sql);

            $migrator->io->success(__d(
                'cake',
                'Dump of schema in file {0} for connection {1} successful.',
                [$file, $connectionName]
            ));
        }

        SchemaCleaner::truncate($connectionName, $migrator->io);
    }
}
