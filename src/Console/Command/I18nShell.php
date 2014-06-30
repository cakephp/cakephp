<?php
/**
 * Internationalization Management Shell
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command;

use Cake\Console\Shell;

/**
 * Shell for I18N management.
 *
 */
class I18nShell extends Shell {

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 */
	public $tasks = ['Extract'];

/**
 * Override main() for help message hook
 *
 * @return void
 */
	public function main() {
		$this->out(__d('cake_console', '<info>I18n Shell</info>'));
		$this->hr();
		$this->out(__d('cake_console', '[E]xtract POT file from sources'));
		$this->out(__d('cake_console', '[H]elp'));
		$this->out(__d('cake_console', '[Q]uit'));

		$choice = strtolower($this->in(__d('cake_console', 'What would you like to do?'), ['E', 'H', 'Q']));
		switch ($choice) {
			case 'e':
				$this->Extract->execute();
				break;
			case 'h':
				$this->out($this->OptionParser->help());
				break;
			case 'q':
				return $this->_stop();
			default:
				$this->out(__d('cake_console', 'You have made an invalid selection. Please choose a command to execute by entering E, I, H, or Q.'));
		}
		$this->hr();
		$this->main();
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'I18n Shell generates .pot files(s) with translations.')
		)->addSubcommand('extract', [
			'help' => __d('cake_console', 'Extract the po translations from your application'),
			'parser' => $this->Extract->getOptionParser()
		]);

		return $parser;
	}

}
