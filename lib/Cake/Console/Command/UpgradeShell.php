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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Folder;
use Cake\Utility\Inflector;

/**
 * A shell class to help developers upgrade applications to CakePHP 3.0
 *
 * @package       Cake.Console.Command
 */
class UpgradeShell extends Shell {

/**
 * Files
 *
 * @var array
 */
	protected $_files = [];

/**
 * Paths
 *
 * @var array
 */
	protected $_paths = [];

/**
 * Shell startup, prints info message about dry run.
 *
 * @return void
 */
	public function startup() {
		parent::startup();
		if ($this->params['dryRun']) {
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
 * Move files and folders to their new homes
 *
 * @return void
 */
	public function locations() {
		$this->_getPath();

		$moves = array(
			'Test' . DS . 'Case' => 'Test' . DS . 'TestCase'
		);
		$dry = $this->params['dryRun'];

		foreach ($moves as $old => $new) {
			$old = $path . DS . $old;
			$new = $path . DS . $new;
			if (!is_dir($old)) {
				continue;
			}
			$this->out(__d('cake_console', '<info>Moving %s to %s</info>', $old, $new));
			if ($dry) {
				continue;
			}
			if ($this->params['git']) {
				exec('git mv -f ' . escapeshellarg($old) . ' ' . escapeshellarg($old . '__'));
				exec('git mv -f ' . escapeshellarg($old . '__') . ' ' . escapeshellarg($new));
			} else {
				$Folder = new Folder($old);
				$Folder->move($new);
			}
		}
	}

/**
 * Convert App::uses() to normal use statements.
 *
 * @return void
 */
	public function app_uses() {
		$path = $this->_getPath();
		$Folder = new Folder($path);
		$this->_paths = $Folder->tree(null, false, 'dir');
		$this->_findFiles('php');
		foreach ($this->_files as $filePath) {
			$this->_replaceUses($filePath, $this->params['dryRun']);
		}
		$this->out(__d('cake_console', '<success>App::uses() replaced successfully</success>'));
	}

/**
 * Replace all the App::uses() calls with `use`
 *
 * @param string $file The file to search and replace.
 * @param boolean $dryRun Whether or not to do the thing.
 */
	protected function _replaceUses($file) {
		$pattern = '#App::uses\([\'"]([a-z0-9_]+)[\'"],\s*[\'"]([a-z0-9/_]+)(?:\.([a-z0-9/_]+))?[\'"]\)#i';
		$contents = file_get_contents($file);

		$self = $this;

		$replacement = function ($matches) use ($file) {
			$matches = $this->_mapClassName($matches);
			if (count($matches) === 4) {
				$use = $matches[3] . '\\' . $matches[2] . '\\' . $matches[1];
			} else if ($matches[2] == 'Vendor') {
				$this->out(
					__d('cake_console', '<info>Skip %s as it is a vendor library.</info>', $matches[1]),
					1,
					Shell::VERBOSE
				);
				return $matches[0];
			} else {
				$use = 'Cake\\' . str_replace('/', '\\', $matches[2]) . '\\' . $matches[1];
			}

			if (!class_exists($use)) {
				$use = 'App\\' . substr($use, 5);
			}

			return 'use ' . $use;
		};

		$contents = preg_replace_callback($pattern, $replacement, $contents, -1, $count);

		if (!$count) {
			$this->out(
				__d('cake_console', '<info>Skip %s as there are no App::uses()</info>', $file),
				1,
				Shell::VERBOSE
			);
			return;
		}

		$this->out(__d('cake_console', '<info> * Updating App::uses()</info>'), 1, Shell::VERBOSE);

		$result = true;
		if (!$this->params['dryRun']) {
			$result = file_put_contents($file, $contents);
		}

		if ($result) {
			$this->out(__d('cake_console', '<success>Done updating %s</success>', $file), 1);
			return;
		}
		$this->err(__d(
			'cake_console',
			'<error>Error</error> Was unable to update %s',
			$filePath
		));
	}

/**
 * Convert old classnames to new ones.
 * Strips the Cake prefix off of classes that no longer have it.
 *
 * @param array $matches
 */
	protected function _mapClassName($matches) {
		$rename = [
			'CakePlugin',
			'CakeEvent',
			'CakeEventListener',
			'CakeEventManager',
			'CakeValidationRule',
			'CakeSocket',
			'CakeRoute',
			'CakeRequest',
			'CakeResponse',
			'CakeSession',
			'CakeLog',
			'CakeNumber',
			'CakeTime',
			'CakeEmail',
			'CakeLogInterface',
			'CakeSessionHandlerInterface',
		];

		if (empty($matches[3])) {
			unset($matches[3]);
		}
		if (in_array($matches[1], $rename)) {
			$matches[1] = substr($matches[1], 4);
		}
		return $matches;
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
			$this->_addNamespace($path, $filePath, $ns, $this->params['dryRun']);
		}
		$this->out(__d('cake_console', '<success>Namespaces added successfully</success>'));
	}

/**
 * Update log configs.
 *
 * @return void
 */
	public function log_config() {
		$path = $this->_getPath();

		$Folder = new Folder($path);
		$this->_paths = $Folder->tree(null, false, 'dir');
		$this->_findFiles('php');
		foreach ($this->_files as $filePath) {
			$patterns = [
				[
					' Log::config to Configure::write',
					'#(Log\:\:config\()(\s*[\'"])([^\'"]+)([\'"])#ms',
					"Configure::write('Log.\\3'",
				]
			];
			$this->_updateFile($filePath, $patterns);
		}
		$this->out(__d('cake_console', '<success>Log::config() replaced successfully</success>'));
	}

/**
 * Update cache configs.
 *
 * @return void
 */
	public function cache_config() {
		$path = $this->_getPath();

		$Folder = new Folder($path);
		$this->_paths = $Folder->tree(null, false, 'dir');
		$this->_findFiles('php');
		foreach ($this->_files as $filePath) {
			$patterns = [
				[
					' Cache::config to Configure::write',
					'#(Cache\:\:config\()(\s*[\'"])([^\'"]+)([\'"])#ms',
					"Configure::write('Cache.\\3'",
				]
			];
			$this->_updateFile($filePath, $patterns);
		}
		$this->out(__d('cake_console', '<success>Cache::config() replaced successfully</success>'));
	}

/**
 * Update fixtures
 *
 * @return void
 */
	public function fixtures() {
		$path = $this->_getPath();

		$app = rtrim(APP, DS);
		if ($path == $app || !empty($this->params['plugin'])) {
			$path .= DS . 'Test' . DS . 'Fixture' . DS;
		}
		$this->out(__d('cake_console', 'Processing fixtures on %s', $path));
		$this->_paths[] = realpath($path);
		$this->_findFiles('php');
		foreach ($this->_files as $file) {
			$this->out(__d('cake_console', 'Updating %s...', $file), 1, Shell::VERBOSE);
			$content = $this->_processFixture(file_get_contents($file));
			if (empty($this->params['dryRun'])) {
				file_put_contents($file, $content);
			}
		}
	}

/**
 * Process fixture content and update it for 3.x
 *
 * @param string $content Fixture content.
 * @return string
 */
	protected function _processFixture($content) {
		// Serializes data from PHP data into PHP code.
		// Basically a code style conformant version of var_export()
		$export = function ($values) use (&$export) {
			$vals = [];
			if (!is_array($values)) {
				return $vals;
			}
			foreach ($values as $key => $val) {
				if (is_array($val)) {
					$vals[] = "'{$key}' => [" . implode(", ", $export($val)) . "]";
				} else {
					$val = var_export($val, true);
					if ($val === 'NULL') {
						$val = 'null';
					}
					if (!is_numeric($key)) {
						$vals[] = "'{$key}' => {$val}";
					} else {
						$vals[] = "{$val}";
					}
				}
			}
			return $vals;
		};

		// Process field property.
		$processor = function ($matches) use ($export) {
			eval('$data = [' . $matches[2] . '];');
			$constraints = [];
			$out = [];
			foreach ($data as $field => $properties) {
				// Move primary key into a constraint
				if (isset($properties['key']) && $properties['key'] === 'primary') {
					$constraints['primary'] = [
						'type' => 'primary',
						'columns' => [$field]
					];
				}
				if (isset($properties['key'])) {
					unset($properties['key']);
				}
				if ($field !== 'indexes' && $field !== 'tableParameters') {
					$out[$field] = $properties;
				}
			}

			// Process indexes. Unique keys work differently now.
			if (isset($data['indexes'])) {
				foreach ($data['indexes'] as $index => $indexProps) {
					if (isset($indexProps['column'])) {
						$indexProps['columns'] = $indexProps['column'];
						unset($indexProps['column']);
					}
					// Move unique indexes over
					if (!empty($indexProps['unique'])) {
						unset($indexProps['unique']);
						$constraints[$index] = ['type' => 'unique'] + $indexProps;
						continue;
					}
					$out['_indexes'][$index] = $indexProps;
				}
			}
			if (count($constraints)) {
				$out['_constraints'] = $constraints;
			}

			// Process table parameters
			if (isset($data['tableParameters'])) {
				$out['_options'] = $data['tableParameters'];
			}
			return $matches[1] . "\n\t\t" . implode(",\n\t\t", $export($out)) . "\n\t" . $matches[3];
		};
		$content = preg_replace_callback(
			'/(public \$fields\s+=\s+(?:array\(|\[))(.*?)(\);|\];)/ms',
			$processor,
			$content,
			-1,
			$count
		);
		if ($count) {
			$this->out(__d('cake_console', 'Updated $fields property'), 1, Shell::VERBOSE);
		}
		return $content;
	}

/**
 * Filter paths to remove webroot, Plugin, tmp directories
 *
 * @return array
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
		$patterns = [
			[
				' namespace to ' . $namespace,
				'#^(<\?(?:php)?\s+(?:\/\*.*?\*\/\s{0,1})?)#s',
				"\\1namespace " . $namespace . ";\n",
			]
		];
		$this->_updateFile($filePath, $patterns);
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
			$this->out(__d('cake_console', '<info> * Updating %s</info>', $pattern[0]), 1, Shell::VERBOSE);
			$contents = preg_replace($pattern[1], $pattern[2], $contents);
		}

		$result = true;

		if (!$this->params['dryRun']) {
			$result = file_put_contents($file, $contents);
		}
		if ($result) {
			$this->out(__d('cake_console', '<success>Done updating %s</success>', $file), 1);
			return;
		}
		$this->err(__d(
			'cake_console',
			'<error>Error</error> Was unable to update %s',
			$filePath
		));
	}

/**
 * Get the path to operate on. Uses either the first argument,
 * or the plugin parameter if its set.
 *
 * @return string
 */
	protected function _getPath() {
		$path = isset($this->args[0]) ? $this->args[0] : APP;
		if (isset($this->params['plugin'])) {
			$path = Plugin::path($this->params['plugin']);
		}
		return rtrim($path, DS);
	}

/**
 * get the option parser
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$plugin = [
			'short' => 'p',
			'help' => __d('cake_console', 'The plugin to update. Only the specified plugin will be updated.')
		];
		$dryRun = [
			'short' => 'd',
			'help' => __d('cake_console', 'Dry run the update, no files will actually be modified.'),
			'boolean' => true
		];
		$git = [
			'help' => __d('cake_console', 'Perform git operations. eg. git mv instead of just moving files.'),
			'boolean' => true
		];
		$namespace = [
			'help' => __d('cake_console', 'Set the base namespace you want to use. Defaults to App or the plugin name.'),
			'default' => 'App',
		];
		$exclude = [
			'help' => __d('cake_console', 'Comma separated list of top level diretories to exclude.'),
			'default' => '',
		];
		$path = [
			'help' => __d('cake_console', 'The path to operate on. Will default to APP or the plugin option.'),
			'required' => false,
		];

		return parent::getOptionParser()
			->description(__d('cake_console', "A shell to help automate upgrading from CakePHP 3.0 to 2.x. \n" .
				"Be sure to have a backup of your application before running these commands."))
			->addSubcommand('all', [
				'help' => __d('cake_console', 'Run all upgrade commands.'),
				'parser' => ['options' => compact('plugin', 'dryRun')]
			])
			->addSubcommand('locations', [
				'help' => __d('cake_console', 'Move files/directories around. Run this *before* adding namespaces with the namespaces command.'),
				'parser' => ['options' => compact('plugin', 'dryRun', 'git'), 'arguments' => compact('path')]
			])
			->addSubcommand('namespaces', [
				'help' => __d('cake_console', 'Add namespaces to files based on their file path. Only run this *after* you have moved files.'),
				'parser' => ['options' => compact('plugin', 'dryRun', 'namespace', 'exclude')]
			])
			->addSubcommand('app_uses', [
				'help' => __d('cake_console', 'Replace App::uses() with use statements'),
				'parser' => ['options' => compact('plugin', 'dryRun')]
			])
			->addSubcommand('fixtures', [
				'help' => __d('cake_console', 'Update fixtures to use new index/constraint features. This is necessary before running tests.'),
				'parser' => ['options' => compact('plugin', 'dryRun'), 'arguments' => compact('path')],
			])
			->addSubcommand('cache_config', [
				'help' => __d('cake_console', "Replace Cache::config() with Configure."),
				'parser' => ['options' => compact('plugin', 'dryRun'), 'arguments' => compact('path')]
			])
			->addSubcommand('log_config', [
				'help' => __d('cake_console', "Replace CakeLog::config() with Configure."),
				'parser' => ['options' => compact('plugin', 'dryRun'), 'arguments' => compact('path')]
			]);
	}

}
