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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell;

use Cake\Console\Shell;
use Cake\Database\SchemaCache;
use Cake\Datasource\ConnectionManager;
use RuntimeException;

/**
 * Schema Cache Shell.
 *
 * Provides a CLI interface to the schema metadata caching features.
 * This tool is intended to be used by deployment scripts so that you
 * can prevent "thundering herd" effect on the metadata cache when new
 * versions of your application is deployed, or when migrations
 * requiring updated metadata are required.
 */
class SchemaCacheShell extends Shell
{
    /**
     * Build metadata.
     *
     * @param string|null $name The name of the table to build cache data for.
     * @return bool
     */
    public function build($name = null)
    {
        $cache = $this->_getSchemaCache();
        $tables = $cache->build($name);

        foreach ($tables as $table) {
            $this->verbose(sprintf('Cached "%s"', $table));
        }

        $this->out('<success>Cache build complete</success>');

        return true;
    }

    /**
     * Clear metadata.
     *
     * @param string|null $name The name of the table to clear cache data for.
     * @return bool
     */
    public function clear($name = null)
    {
        $cache = $this->_getSchemaCache();
        $tables = $cache->clear($name);

        foreach ($tables as $table) {
            $this->verbose(sprintf('Cleared "%s"', $table));
        }

        $this->out('<success>Cache clear complete</success>');

        return true;
    }

    /**
     * Gets the Schema Cache instance
     *
     * @return \Cake\Database\SchemaCache
     */
    protected function _getSchemaCache()
    {
        try {
            $connection = ConnectionManager::get($this->params['connection']);

            return new SchemaCache($connection);
        } catch (RuntimeException $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Get the option parser for this shell.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addSubcommand('clear', [
            'help' => 'Clear all metadata caches for the connection. If a ' .
                'table name is provided, only that table will be removed.',
        ])->addSubcommand('build', [
            'help' => 'Build all metadata caches for the connection. If a ' .
            'table name is provided, only that table will be cached.',
        ])->addOption('connection', [
            'help' => 'The connection to build/clear metadata cache data for.',
            'short' => 'c',
            'default' => 'default',
        ])->addArgument('name', [
            'help' => 'A specific table you want to clear/refresh cached data for.',
            'optional' => true,
        ]);

        return $parser;
    }
}
