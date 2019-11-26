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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\SchemaCache;
use Cake\Datasource\ConnectionManager;
use RuntimeException;

/**
 * Provides CLI tool for updating schema cache.
 */
class SchemacacheBuildCommand extends Command
{
    /**
     * Get the command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'schema_cache build';
    }

    /**
     * Display all routes in an application
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        try {
            /** @var \Cake\Database\Connection $connection */
            $connection = ConnectionManager::get((string)$args->getOption('connection'));

            $cache = new SchemaCache($connection);
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());

            return static::CODE_ERROR;
        }
        $tables = $cache->build($args->getArgument('name'));

        foreach ($tables as $table) {
            $io->verbose(sprintf('Cached "%s"', $table));
        }

        $io->out('<success>Cache build complete</success>');

        return static::CODE_SUCCESS;
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Build all metadata caches for the connection. If a ' .
            'table name is provided, only that table will be cached.'
        )->addOption('connection', [
            'help' => 'The connection to build/clear metadata cache data for.',
            'short' => 'c',
            'default' => 'default',
        ])->addArgument('name', [
            'help' => 'A specific table you want to refresh cached data for.',
            'optional' => true,
        ]);

        return $parser;
    }
}
