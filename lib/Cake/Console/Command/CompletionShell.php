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

App::uses('AppShell', 'Console/Command');

/**
 * Provide command completion shells such as bash.
 * 
 * @package       Cake.Console.Command
 */
class CompletionShell extends AppShell {

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 */
	public $tasks = array('Command');

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
	public function subCommands() {
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
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Used by shells like bash to autocomplete command name, options and arguments')
		)->addSubcommand('commands', array(
			'help' => __d('cake_console', 'Output a list of available commands'),
			'parser' => array(
				'description' => __d('cake_console', 'List all availables'),
				'arguments' => array(
				)
			)
		))->addSubcommand('subcommands', array(
			'help' => __d('cake_console', 'Output a list of available subcommands'),
			'parser' => array(
				'description' => __d('cake_console', 'List subcommands for a command'),
				'arguments' => array(
					'command' => array(
						'help' => __d('cake_console', 'The command name'),
						'required' => true,
					)
				)
			)
		))->addSubcommand('options', array(
			'help' => __d('cake_console', 'Output a list of available options'),
			'parser' => array(
				'description' => __d('cake_console', 'List options'),
				'arguments' => array(
					'command' => array(
						'help' => __d('cake_console', 'The command name'),
						'required' => false,
					)
				)
			)
		))->epilog(
			__d('cake_console', 'This command is not intended to be called manually')
		);

		return $parser;
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
