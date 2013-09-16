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
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Console.Command
 * @since         CakePHP v 2.5
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CommandListShell', 'Console/Command');

/**
 * Provide command completion shells such as bash.
 * 
 * @package       Cake.Console.Command
 */
class CompletionShell extends CommandListShell {

/**
 * Echo no header by overriding the startup method
 *
 * @return void
 */
	public function startup() {
	}

/**
 * Not called by the autocomplete shell - this is for curious users
 *
 * @return void
 */
	public function main() {
		return $this->out($this->OptionParser->help());
	}

/**
 * list commands
 *
 * @return void
 */
	public function commands() {
		$options = $this->_commands();
		return $this->_output($options);
	}

/**
 * list options for the named command
 *
 * @return void
 */
	public function options() {
		if (!$this->args) {
			$parser = new ConsoleOptionParser();
		} else {
			$Shell = $this->_getShell($this->args[0]);
			if (!$Shell) {
				$parser = new ConsoleOptionParser();
			} else {
				$parser = $Shell->getOptionParser();
			}
		}

		$options = array();
		$array = $parser->options();
		foreach ($array as $name => $obj) {
			$options[] = "--$name";
			$short = $obj->short();
			if ($short) {
				$options[] = "-$short";
			}
		}
		return $this->_output($options);
	}

/**
 * list subcommands for the named command
 *
 * @return void
 */
	public function subCommands() {
		if (!$this->args) {
			return $this->_output();
		}

		$options = $this->_subCommands($this->args[0]);
		return $this->_output($options);
	}

/**
 * Guess autocomplete from the whole argument string
 * 
 * @return void
 */
	public function fuzzy() {
		return $this->_output();
	}

/**
 * getOptionParser for _this_ shell
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$translationDomain = 'bash_completion';

		$parser = AppShell::getOptionParser();

		$parser->description(__d($translationDomain, 'Used by bash to autocomplete command name, options and arguments'))
			->addSubcommand('commands', array(
				'help' => __d($translationDomain, 'Output a list of available commands'),
				'parser' => array(
					'description' => __d($translationDomain, 'List all availables'),
					'arguments' => array(
					)
				)
			))->addSubcommand('subcommands', array(
				'help' => __d($translationDomain, 'Output a list of available subcommands'),
				'parser' => array(
					'description' => __d($translationDomain, 'List subcommands for a command'),
					'arguments' => array(
						'command' => array(
							'help' => __d($translationDomain, 'The command name'),
							'required' => true,
						)
					)
				)
			))->addSubcommand('options', array(
				'help' => __d($translationDomain, 'Output a list of available options'),
				'parser' => array(
					'description' => __d($translationDomain, 'List options'),
					'arguments' => array(
						'command' => array(
							'help' => __d($translationDomain, 'The command name'),
							'required' => false,
						)
					)
				)
			))->epilog(
				array(
					'This command is not intended to be called manually',
				)
			);
		return $parser;
	}

/**
 * Return a list of all commands
 *
 * @return array
 */
	protected function _commands() {
		$shellList = $this->_getShellList();
		unset($shellList['Completion']);

		$options = array();
		foreach ($shellList as $type => $commands) {
			$prefix = '';
			if (!in_array($type, array('app', 'core', 'APP', 'CORE'))) {
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
	protected function _subCommands($commandName) {
		$Shell = $this->_getShell($commandName);

		if (!$Shell) {
			return array();
		}

		$return = array();
		$taskMap = TaskCollection::normalizeObjectArray((array)$Shell->tasks);
		foreach ($taskMap as $task => $properties) {
			$return[] = $task;
		}

		$return = array_map('Inflector::underscore', $return);

		$ShellReflection = new ReflectionClass('AppShell');
		$shellMethods = $ShellReflection->getMethods(ReflectionMethod::IS_PUBLIC);
		$shellMethodNames = array('main', 'help');
		foreach ($shellMethods as $method) {
			$shellMethodNames[] = $method->getName();
		}

		$Reflection = new ReflectionClass($Shell);
		$methods = $Reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		$methodNames = array();
		foreach ($methods as $method) {
			$methodNames[] = $method->getName();
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
	protected function _getShell($commandName) {
		list($plugin, $name) = pluginSplit($commandName, true);

		if ($plugin === 'CORE.' || $plugin === 'APP.' || $plugin === 'core.' || $plugin === 'app.') {
			$commandName = $name;
			$plugin = '';
		}

		if (!in_array($commandName, $this->_commands())) {
			return false;
		}

		$name = Inflector::camelize($name);
		$plugin = Inflector::camelize($plugin);
		$class = $name . 'Shell';
		APP::uses($class, $plugin . 'Console/Command');

		$Shell = new $class();
		$Shell->plugin = trim($plugin, '.');
		$Shell->initialize();
		$Shell->loadTasks();

		return $Shell;
	}

/**
 * Emit results as a string, space delimited
 *
 * @param array $options
 * @return void
 */
	protected function _output($options = array()) {
		if ($options) {
			return $this->out(implode($options, ' '));
		}
	}
}
