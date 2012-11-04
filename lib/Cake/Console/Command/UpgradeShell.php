<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Console\Command;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Folder;
use Cake\Utility\Inflector;

/**
 * A shell class to help developers upgrade applications to CakePHP 2.0
 *
 * @package       Cake.Console.Command
 */
class UpgradeShell extends Shell {

/**
 * Files
 *
 * @var array
 */
	protected $_files = array();

/**
 * Paths
 *
 * @var array
 */
	protected $_paths = array();

/**
 * Shell startup, prints info message about dry run.
 *
 * @return void
 */
	public function startup() {
		parent::startup();
		if ($this->params['dry-run']) {
			$this->out(__d('cake_console', '<warning>Dry-run mode enabled!</warning>'), 1, Shell::QUIET);
		}
	}

/**
 * Run all upgrade steps one at a time
 *
 * @return void
 */
	public function all() {
		foreach ($this->OptionParser->subcommands() as $command) {
			$name = $command->name();
			if ($name === 'all') {
				continue;
			}
			$this->out(__d('cake_console', 'Running %s', $name));
			$this->$name();
		}
	}

/**
 * Convert App::uses() to normal use statements.
 *
 * @return void
 */
	public function app_uses() {
		$path = isset($this->args[0]) ? $this->args[0] : APP;

		$Folder = new Folder($path);
		$this->_paths = $Folder->tree(null, false, 'dir');
		$this->_findFiles('php');
		debug($this->_files);
	}

/**
 * Add namespaces to files.
 *
 * @return void
 */
	public function namespaces() {
		$path = isset($this->args[0]) ? $this->args[0] : APP;
		$ns = $this->params['namespace'];

		if ($ns === 'App' && isset($this->params['plugin'])) {
			$ns = Inflector::camelize($this->params['plugin']);
		}

		$Folder = new Folder($path);
		$exclude = ['vendor', 'Vendor', 'webroot', 'Plugin', 'tmp'];
		if (!empty($this->params['exclude'])) {
			$exclude = array_merge($exclude, explode(',', $this->params['exclude']));
		}
		list($dirs, $files) = $Folder->read(true, true, true);

		$this->_paths = $this->_filterPaths($dirs, $exclude);
		$this->_findFiles('php', ['index.php', 'test.php', 'cake.php']);

		foreach ($this->_files as $filePath) {
			$this->_addNamespace($path, $filePath, $ns, $this->params['dry-run']);
		}
		$this->out(__d('cake_console', '<success>Namespaces added successfully</success>'));
	}

/**
 * Filter paths to remove webroot, Plugin, tmp directories
 */
	protected function _filterPaths($paths, $directories) {
		return array_filter($paths, function ($path) use ($directories) {
			foreach ($directories as $dir) {
				if (strpos($path, DS . $dir) !== false) {
					return false;
				}
			}
			return true;
		});
	}

/**
 * Adds the namespace to a given file.
 *
 * @param string $filePath The file to add a namespace to.
 * @param string $ns The base namespace to use.
 * @param bool $dry Whether or not to operate in dry-run mode.
 * @return void
 */
	protected function _addNamespace($path, $filePath, $ns, $dry) {
		$result = true;
		$shortPath = str_replace($path, '', $filePath);
		$contents = file_get_contents($filePath);
		if (preg_match('/namespace\s+[a-z0-9\\\]+;/', $contents)) {
			$this->out(__d(
				'cake_console',
				"<warning>Skipping %s as it already has a namespace.</warning>",
				$shortPath
			));
			return;
		}
		$namespace = trim($ns . str_replace(DS, '\\', dirname($shortPath)), '\\');
		$this->out(
			__d('cake_console', "<info>Adding namespace %s to %s </info>", $namespace, $shortPath),
			1,
			Shell::VERBOSE
		);

		if (!$dry) {
			$contents = preg_replace(
				'#^(<\?(?:php)?\s+(?:\/\*.*?\*\/\s{0,1})?)#s',
				"\\1namespace " . $namespace . ";\n",
				$contents
			);
			$result = file_put_contents($filePath, $contents);
		}

		if (!$result) {
			$this->err(__d(
				'cake_console',
				'<error>Error</error> Was unable to update %s',
				$filePath
			));
		}
	}

/**
 * Updates files based on regular expressions.
 *
 * @param array $patterns Array of search and replacement patterns.
 * @return void
 */
	protected function _filesRegexpUpdate($patterns) {
		$this->_findFiles($this->params['ext']);
		foreach ($this->_files as $file) {
			$this->out(__d('cake_console', 'Updating %s...', $file), 1, Shell::VERBOSE);
			$this->_updateFile($file, $patterns);
		}
	}

/**
 * Searches the paths and finds files based on extension.
 *
 * @param string $extensions
 * @return void
 */
	protected function _findFiles($extensions = '', $exclude = []) {
		$this->_files = array();
		foreach ($this->_paths as $path) {
			if (!is_dir($path)) {
				continue;
			}
			$Iterator = new \RegexIterator(
				new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)),
				'/^.+\.(' . $extensions . ')$/i',
				\RegexIterator::MATCH
			);
			foreach ($Iterator as $file) {
				if ($file->isFile() && !in_array($file->getFilename(), $exclude)) {
					$this->_files[] = $file->getPathname();
				}
			}
		}
	}

/**
 * Update a single file.
 *
 * @param string $file The file to update
 * @param array $patterns The replacement patterns to run.
 * @return void
 */
	protected function _updateFile($file, $patterns) {
		$contents = file_get_contents($file);

		foreach ($patterns as $pattern) {
			$this->out(__d('cake_console', ' * Updating %s', $pattern[0]), 1, Shell::VERBOSE);
			$contents = preg_replace($pattern[1], $pattern[2], $contents);
		}

		$this->out(__d('cake_console', 'Done updating %s', $file), 1);
		if (!$this->params['dry-run']) {
			file_put_contents($file, $contents);
		}
	}

/**
 * get the option parser
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'plugin' => array(
					'short' => 'p',
					'help' => __d('cake_console', 'The plugin to update. Only the specified plugin will be updated.')
				),
				'ext' => array(
					'short' => 'e',
					'help' => __d('cake_console', 'The extension(s) to search. A pipe delimited list, or a preg_match compatible subpattern'),
					'default' => 'php|ctp'
				),
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the update, no files will actually be modified.'),
					'boolean' => true
				)
			)
		);

		$namespaceParser = $subcommandParser;
		$namespaceParser['options']['namespace'] = [
			'help' => __d('cake_console', 'Set the base namespace you want to use. Defaults to App or the plugin name.'),
			'default' => 'App',
		];
		$namespaceParser['options']['exclude'] = [
			'help' => __d('cake_console', 'Comma separated list of top level diretories to exclude.'),
			'default' => '',
		];

		return parent::getOptionParser()
			->description(__d('cake_console', "A shell to help automate upgrading from CakePHP 1.3 to 2.0. \n" .
				"Be sure to have a backup of your application before running these commands."))
			->addSubcommand('all', array(
				'help' => __d('cake_console', 'Run all upgrade commands.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('app_uses', array(
				'help' => __d('cake_console', 'Replace App::uses() with use statements'),
				'parser' => $subcommandParser
			))
			->addSubcommand('namespaces', array(
				'help' => __d('cake_console', 'Add namespaces to files based on their file path.'),
				'parser' => $namespaceParser
			))
			->addSubcommand('cache', array(
				'help' => __d('cake_console', "Replace Cache::config() with Configure."),
				'parser' => $subcommandParser
			))
			->addSubcommand('log', array(
				'help' => __d('cake_console', "Replace CakeLog::config() with Configure."),
				'parser' => $subcommandParser
			));
	}

}
