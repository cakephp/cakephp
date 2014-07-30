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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Utility\ConventionsTrait;

/**
 * Base class for Bake Tasks.
 *
 */
class BakeTask extends Shell {

	use ConventionsTrait;

/**
 * The pathFragment appended to the plugin/app path.
 *
 * @var string
 */
	public $pathFragment;

/**
 * Name of plugin
 *
 * @var string
 */
	public $plugin = null;

/**
 * The db connection being used for baking
 *
 * @var string
 */
	public $connection = null;

/**
 * Disable caching and enable debug for baking.
 * This forces the most current database schema to be used.
 *
 * @return void
 */
	public function startup() {
		Configure::write('debug', true);
		Cache::disable();
	}

/**
 * Initialize hook.
 *
 * Populates the connection property, which is useful for tasks of tasks.
 *
 * @return void
 */
	public function initialize() {
		if (empty($this->connection) && !empty($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}
	}

/**
 * Gets the path for output. Checks the plugin property
 * and returns the correct path.
 *
 * @return string Path to output.
 */
	public function getPath() {
		$path = APP . $this->pathFragment;
		if (isset($this->plugin)) {
			$path = $this->_pluginPath($this->plugin) . 'src/' . $this->pathFragment;
		}
		return str_replace('/', DS, $path);
	}

/**
 * Base execute method parses some parameters and sets some properties on the bake tasks.
 * call when overriding execute()
 *
 * @return void
 */
	public function main() {
		if (isset($this->params['plugin'])) {
			$this->plugin = $this->params['plugin'];
		}
		if (isset($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}
	}

/**
 * Executes an external shell command and pipes its output to the stdout
 *
 * @param string $command the command to execute
 * @return void
 * @throws \RuntimeException if any errors occurred during the execution
 */
	public function callProcess($command) {
		$descriptorSpec = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];
		$this->_io->verbose('Running ' . $command);
		$process = proc_open(
			$command,
			$descriptorSpec,
			$pipes
		);
		if (!is_resource($process)) {
			$this->error('Could not start subprocess.');
			return false;
		}
		$output = $error = '';
		fclose($pipes[0]);

		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$error = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		proc_close($process);

		if ($error) {
			throw new \RuntimeException($error);
		}

		$this->out($output);
	}

/**
 * Handles splitting up the plugin prefix and classname.
 *
 * Sets the plugin parameter and plugin property.
 *
 * @param string $name The name to possibly split.
 * @return string The name without the plugin prefix.
 */
	protected function _getName($name) {
		if (strpos($name, '.')) {
			list($plugin, $name) = pluginSplit($name);
			$this->plugin = $this->params['plugin'] = $plugin;
		}
		return $name;
	}

/**
 * Get the option parser for this task.
 *
 * This base class method sets up some commonly used options.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addOption('plugin', [
			'short' => 'p',
			'help' => 'Plugin to bake into.'
		])->addOption('force', [
			'short' => 'f',
			'boolean' => true,
			'help' => 'Force overwriting existing files without prompting.'
		])->addOption('connection', [
			'short' => 'c',
			'default' => 'default',
			'help' => 'The datasource connection to get data from.'
		])->addOption('theme', [
			'short' => 't',
			'default' => 'default',
			'help' => 'Theme to use when baking code.'
		]);
		return $parser;
	}

}
