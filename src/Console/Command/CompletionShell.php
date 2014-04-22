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
 * @since         2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command;

use Cake\Console\Shell;

/**
 * Provide command completion shells such as bash.
 */
class CompletionShell extends Shell {

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 */
	public $tasks = ['Command'];

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
		return $this->out($this->getOptionParser()->help());
	}

/**
 * list commands
 *
 * @return void
 */
	public function commands() {
		$options = $this->Command->commands();
		return $this->_output($options);
	}

/**
 * list options for the named command
 *
 * @return void
 */
	public function options() {
		$commandName = '';
		if (!empty($this->args[0])) {
			$commandName = $this->args[0];
		}
		$options = $this->Command->options($commandName);

		return $this->_output($options);
	}

/**
 * list subcommands for the named command
 *
 * @return void
 */
	public function subcommands() {
		if (!$this->args) {
			return $this->_output();
		}

		$options = $this->Command->subCommands($this->args[0]);
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
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Used by shells like bash to autocomplete command name, options and arguments')
		)->addSubcommand('commands', [
			'help' => __d('cake_console', 'Output a list of available commands'),
			'parser' => [
				'description' => __d('cake_console', 'List all availables'),
			]
		])->addSubcommand('subcommands', [
			'help' => __d('cake_console', 'Output a list of available subcommands'),
			'parser' => [
				'description' => __d('cake_console', 'List subcommands for a command'),
				'arguments' => [
					'command' => [
						'help' => __d('cake_console', 'The command name'),
						'required' => false,
					]
				]
			]
		])->addSubcommand('options', [
			'help' => __d('cake_console', 'Output a list of available options'),
			'parser' => [
				'description' => __d('cake_console', 'List options'),
				'arguments' => [
					'command' => [
						'help' => __d('cake_console', 'The command name'),
						'required' => false,
					]
				]
			]
		])->addSubcommand('fuzzy', [
			'help' => __d('cake_console', 'Guess autocomplete')
		])->epilog([
			__d('cake_console', 'This command is not intended to be called manually'),
		]);

		return $parser;
	}

/**
 * Emit results as a string, space delimited
 *
 * @param array $options
 * @return void
 */
	protected function _output($options = []) {
		if ($options) {
			return $this->out(implode($options, ' '));
		}
	}
}
