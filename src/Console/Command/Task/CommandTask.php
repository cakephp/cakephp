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
 * @since         2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use \ReflectionClass;
use \ReflectionMethod;

/**
 * Base class for Shell Command reflection.
 *
 */
class CommandTask extends Shell {

/**
 * Gets the shell command listing.
 *
 * @return array
 */
	public function getShellList() {
		$skipFiles = ['AppShell'];
		$hiddenCommands = ['CommandListShell', 'CompletionShell'];

		$plugins = Plugin::loaded();
		$shellList = array_fill_keys($plugins, null) + ['CORE' => null, 'app' => null];

		$corePath = App::core('Console/Command');
		$shells = App::objects('file', $corePath[0]);
		$shells = array_diff($shells, $skipFiles, $hiddenCommands);
		$this->_appendShells('CORE', $shells, $shellList);

		$appShells = App::objects('Console/Command', null, false);
		$appShells = array_diff($appShells, $shells, $skipFiles);
		$this->_appendShells('app', $appShells, $shellList);

		foreach ($plugins as $plugin) {
			$pluginShells = App::objects($plugin . '.Console/Command');
			$this->_appendShells($plugin, $pluginShells, $shellList);
		}

		return array_filter($shellList);
	}

/**
 * Scan the provided paths for shells, and append them into $shellList
 *
 * @param string $type
 * @param array $shells
 * @param array $shellList
 * @return void
 */
	protected function _appendShells($type, $shells, &$shellList) {
		foreach ($shells as $shell) {
			$shellList[$type][] = Inflector::underscore(str_replace('Shell', '', $shell));
		}
	}

/**
 * Return a list of all commands
 *
 * @return array
 */
	public function commands() {
		$shellList = $this->getShellList();

		$options = [];
		foreach ($shellList as $type => $commands) {
			$prefix = '';
			if (!in_array(strtolower($type), ['app', 'core'])) {
				$prefix = $type . '.';
			}

			foreach ($commands as $shell) {
				$options[] = $prefix . $shell;
			}
		}

		return $options;
	}

/**
 * Return a list of subcommands for a given command
 *
 * @param string $commandName
 * @return array
 */
	public function subCommands($commandName) {
		$Shell = $this->getShell($commandName);

		if (!$Shell) {
			return [];
		}

		$taskMap = $this->Tasks->normalizeArray((array)$Shell->tasks);
		$return = array_keys($taskMap);
		$return = array_map('Cake\Utility\Inflector::underscore', $return);

		$shellMethodNames = ['main', 'help'];

		$baseClasses = ['Object', 'Shell', 'AppShell'];

		$Reflection = new ReflectionClass($Shell);
		$methods = $Reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		$methodNames = [];
		foreach ($methods as $method) {
			$declaringClass = $method->getDeclaringClass()->getShortName();
			if (!in_array($declaringClass, $baseClasses)) {
				$methodNames[] = $method->getName();
			}
		}

		$return += array_diff($methodNames, $shellMethodNames);
		sort($return);

		return $return;
	}

/**
 * Get Shell instance for the given command
 *
 * @param mixed $commandName
 * @return mixed
 */
	public function getShell($commandName) {
		list($pluginDot, $name) = pluginSplit($commandName, true);

		if (in_array(strtolower($pluginDot), ['app.', 'core.'])) {
			$commandName = $name;
			$pluginDot = '';
		}

		if (!in_array($commandName, $this->commands())) {
			return false;
		}

		$name = Inflector::camelize($name);
		$pluginDot = Inflector::camelize($pluginDot);
		$class = App::classname($pluginDot . $name, 'Console/Command', 'Shell');
		if (!$class) {
			return false;
		}

		$Shell = new $class();
		$Shell->plugin = trim($pluginDot, '.');
		$Shell->initialize();

		return $Shell;
	}

/**
 * Get Shell instance for the given command
 *
 * @param mixed $commandName
 * @return array
 */
	public function options($commandName) {
		$Shell = $this->getShell($commandName);
		if (!$Shell) {
			$parser = new ConsoleOptionParser();
		} else {
			$parser = $Shell->getOptionParser();
		}

		$options = [];
		$array = $parser->options();
		foreach ($array as $name => $obj) {
			$options[] = "--$name";
			$short = $obj->short();
			if ($short) {
				$options[] = "-$short";
			}
		}
		return $options;
	}

}
