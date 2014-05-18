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
namespace Cake\Console\Command;

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
class OrmCacheShell extends Shell {

/**
 * Build metadata.
 *
 * @param $name string
 * @return boolean
 */
	public function build($name = null) {
		$source = ConnectionManager::get($this->params['connection']);
		if (!method_exists($source, 'schemaCollection')) {
			$msg = sprintf('The "%s" connection is not compatible with orm caching, ' .
				'as it does not implement a "schemaCollection()" method.',
				$this->params['connection']);
			$this->error($msg);
			return false;
		}
		$schema = $source->schemaCollection();
		if (!$schema->cacheMetadata()) {
			$this->_io->verbose('Metadata cache was disabled in config. Enabling to write cache.');
			$schema->cacheMetadata(true);
		}
		$tables = [$name];
		if (empty($name)) {
			$tables = $schema->listTables();
		}
		foreach ($tables as $table) {
			$this->_io->verbose('Building metadata cache for ' . $table);
			$schema->describe($table);
		}
	}

/**
 * Clear metadata.
 *
 * @param $name string
 * @return void
 */
	public function clear($name = null) {
		$source = ConnectionManager::get($this->params['connection']);
	}

/**
 * Get the option parser for this shell.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
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
