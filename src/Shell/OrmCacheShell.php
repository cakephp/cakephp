<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;

/**
 * ORM Cache Shell.
 *
 * Provides a CLI interface to the ORM metadata caching features.
 * This tool is intended to be used by deployment scripts so that you
 * can prevent thundering herd effects on the metadata cache when new
 * versions of your application are deployed, or when migrations
 * requiring updated metadata are required.
 */
class OrmCacheShell extends Shell
{

    /**
     * Build metadata.
     *
     * @param string|null $name The name of the table to build cache data for.
     * @return bool
     */
    public function build($name = null)
    {
        $schema = $this->_getSchema();
        if (!$schema) {
            return false;
        }
        $tables = [$name];
        if (empty($name)) {
            $tables = $schema->listTables();
        }
        foreach ($tables as $table) {
            $this->_io->verbose('Building metadata cache for ' . $table);
            $schema->describe($table, ['forceRefresh' => true]);
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
        $schema = $this->_getSchema();
        if (!$schema) {
            return false;
        }
        $tables = [$name];
        if (empty($name)) {
            $tables = $schema->listTables();
        }
        $configName = $schema->cacheMetadata();

        foreach ($tables as $table) {
            $this->_io->verbose(sprintf(
                'Clearing metadata cache from "%s" for %s',
                $configName,
                $table
            ));
            $key = $schema->cacheKey($table);
            Cache::delete($key, $configName);
        }
        $this->out('<success>Cache clear complete</success>');

        return true;
    }

    /**
     * Helper method to get the schema collection.
     *
     * @return false|\Cake\Database\Schema\Collection
     */
    protected function _getSchema()
    {
        $source = ConnectionManager::get($this->params['connection']);
        if (!method_exists($source, 'schemaCollection')) {
            $msg = sprintf(
                'The "%s" connection is not compatible with orm caching, ' .
                'as it does not implement a "schemaCollection()" method.',
                $this->params['connection']
            );
            $this->error($msg);

            return false;
        }
        $config = $source->config();
        if (empty($config['cacheMetadata'])) {
            $this->_io->verbose('Metadata cache was disabled in config. Enabling to clear cache.');
            $source->cacheMetadata(true);
        }

        return $source->schemaCollection();
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
