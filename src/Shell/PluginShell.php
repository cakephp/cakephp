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

use Cake\Console\Shell;

/**
 * Shell for Plugin management.
 *
 */
class PluginShell extends Shell {

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 */
	public $tasks = ['Assets'];

/**
 * Override main() for help message hook
 *
 * @return void
 */
	public function main() {
		$this->out('<info>Plugin Shell</info>');
		$this->hr();
		$this->out('[A]ssets symlink / copy to app\'s webroot');
		$this->out('[H]elp');
		$this->out('[Q]uit');

		$choice = strtolower($this->in('What would you like to do?', ['A', 'H', 'Q']));
		switch ($choice) {
			case 'a':
				$this->Assets->main();
				break;
			case 'h':
				$this->out($this->OptionParser->help());
				break;
			case 'q':
				return $this->_stop();
			default:
				$this->out('You have made an invalid selection. Please choose a command to execute by entering A, H, or Q.');
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
			'Plugin Shell symlinks your plugin assets to app\'s webroot.'
		)->addSubcommand('assets', [
			'help' => 'Symlink / copy assets to app\'s webroot',
			'parser' => $this->Assets->getOptionParser()
		]);

		return $parser;
	}

}
